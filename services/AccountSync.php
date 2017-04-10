<?php
/**
 * ANT Service that handles background maintenance tasks for objects
 *
 * @category	AntService
 * @package		ObjectDynIdx
 * @copyright	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/CAntObject.php");
require_once("lib/AntService.php");		
require_once("lib/aereus.lib.php/antapi.php");
require_once("lib/Email.php");

class AccountSync extends AntRoutine
{
	/**
	 * Api references
	 *
	 * @var AntApi
	 */
	public $api = null;

	/**
	 * Last created invoice id
	 *
	 * @var int
	 */
	public $invoiceId = null;

	/**
	 * Routine will sync data with aereus Netric account
	 *
	 * @param CDatabase $dbh The account database handle
	 */
	public function main(&$dbh)
	{
		$ant = $this->ant;

		if (!$this->api)
		{
			$api = new AntApi(AntConfig::getInstance()->aereus['server'], 
							  AntConfig::getInstance()->aereus['user'],
							  AntConfig::getInstance()->aereus['password']);
		}

		// Get account from aereus DB or from parent account
		$apiAntAccount = $this->ant->getAereusAccount();

		// Bill the account
		echo "Process Invoice for " . $this->ant->accountName . "\t";
		$inv = $this->billAccount($apiAntAccount);
		echo  ($inv) ? "[created=$inv]\n" : "[skipped]\n";

		// Update data
		$apiAntAccount->setValue("edition", $ant->getEditionName());
		$apiAntAccount->setValue("num_users", $ant->getNumUsers());
		$apiAntAccount->save();
	}

	/**
	 * Bill account
	 *
	 * @param AntApi_Object $apiAntAccount The remote ANT account object
	 */
	public function billAccount(&$apiAntAccount, $dbg=false)
	{
		$nextBill = $apiAntAccount->getValue("bill_nextdate");
		$lastBill = $apiAntAccount->getValue("bill_lastdate");
		$manualInv = $apiAntAccount->getValue("bill_manualinv"); // Most accounts charge card, some choose to pay manually
		$custid = $apiAntAccount->getValue("customer");

		// Create customer if it does not exist for some reaason
		if (!$custid)
		{
			$custid = $this->ant->getAereusCustomerId();
			$apiAntAccount->setValue("customer", $custid);
		}

		if (!$nextBill || !$custid)
			return false; // TODO: lock account and alert on error, for now let slide

		//$cust = $this->api->getObject("customer", $custid);
		$lastTs = strtotime($lastBill);
		$nextTs = strtotime($nextBill);

		// Is it time to bill?
		if ($nextTs > time())
			return false;

		$numUsers = $this->ant->getNumUsers();
		$edition = $this->ant->getEdition();
		$price = $this->ant->getEditionPrice();

		// Create an invoice
		// ----------------------------------------------------------
		$inv = new AntApi_Invoice(AntConfig::getInstance()->aereus['server'], 
								  AntConfig::getInstance()->aereus['user'],
								  AntConfig::getInstance()->aereus['password']);
		$inv->setValue("name", "Netric Subscription");
		$inv->setValue("type", "r");
		$inv->setValue("customer_id", $custid);
		$inv->addItem($this->ant->getEditionName() . " User Account", $this->ant->getNumUsers(), $price);
		if ($apiAntAccount->getValue("edition_discount"))
			$this->applyBillingDiscount($inv, $apiAntAccount->getValue("edition_discount"), $price, $numUsers);
		$invid = $inv->save();

		if ($invid)
		{
			$apiAntAccount->setValue("bill_lastdate", $nextBill);

			$nextBillDate = ($lastBill) ? strtotime("+1 month", strtotime($lastBill)) : strtotime("+1 month", strtotime($nextBill));
			$nextBillDate = date("m/d/Y", $nextBillDate);
			$apiAntAccount->setValue("bill_nextdate", $nextBillDate);
            
            // Get customer to bill
            $customer = new AntApi_Customer(AntConfig::getInstance()->aereus['server'], 
                                        AntConfig::getInstance()->aereus['user'],
                                        AntConfig::getInstance()->aereus['password']);
            $customer->open($custid);
        
            // Email the invoice to the user if no credit card
            if ("t" == $manualInv && ($customer->getValue("email") || $customer->getValue("email2")))
            {
                // Create new email object
                $headers = array();
                $headers['From'] = "no-reply@netric.com";
                $headers['To'] = ($customer->getValue("email2")) ? $customer->getValue("email2") : $customer->getValue("email");
                $headers['Bcc'] = "sky.stebnicki@aereus.com";
                $headers['Subject'] = "Netric Account Invoice";
                $body = "A new invoice has been created for your account.\r\n\r\n"
                        . "https://aereus.netric.com/public/sales/invoice/$invid\r\n\r\n"
                        . "Thank you so much for your business! Please let us know if there "
                        . "is anything we can do to help.";
                
                if ($headers['To'])
                {
                    $email = new Email();
                    $status = $email->send($headers['To'], $headers, $body);
                    
                    // Update status
                    $inv->setValue("status_id", 2); // Sent
                    $inv->save();
                }
                else
                {
                    // TODO: handle with some sort of notification!
                }
            }
            else
            {
                // TODO: we need to have this bill automatically if there is a credit card on file
                
                // For now just email.
                $headers = array();
                $headers['From'] = "no-reply@netric.com";
                $headers['To'] = "sky.stebnicki@aereus.com";
                $headers['Subject'] = "Invoice Needs Billing";
                $body = "A new invoice has been created and needs to be sent or charged.\r\n\r\n"
                        . "https://aereus.netric.com/obj/invoice/$invid\r\n\r\n";
                $email = new Email();
                $status = $email->send($headers['To'], $headers, $body);
            }
		}

		return $invid;
	}

	/**
	 * Apply any discounts by adding a negative item to the invoice
	 *
	 * @param AntApi_Invoice $inv
	 * @param string $dicount Discount label
	 * @param float $price The normal price
	 * @param int $numUsers the number of billable users in this account
	 */
	public function applyBillingDiscount(&$inv, $discount, $price, $numUsers)
	{
		switch ($discount)
		{
		case 'nonprofit':
			// First 5 users are free
			$discNum = ($numUsers > 5) ? 5 : $numUsers;
			$inv->addItem("Non-Profit Discount", $discNum, ($price * -1));
			break;
		case 'entforpro':
			// Get the enterprise edition for the professional price
			$entPrice = $this->ant->getEditionPrice(EDITION_ENTERPRISE);
			$proPrice = $this->ant->getEditionPrice(EDITION_PROFESSIONAL);
			$diff = $proPrice - $entPrice;
			if ($diff < 0) // make sure its actually a discount
				$inv->addItem("Discount - Enterprise for Professional Price", $numUsers, $diff);
			break;
		case '10per':
			// 10 % discount
			$disPerUser = $price * .1;
			if ($disPerUser > 0) // make sure its actually a discount
				$inv->addItem("Discount - 10% off per user", $numUsers, $disPerUser * -1);
			break;
		case '20per':
			// 20 % discount
			$disPerUser = $price * .2;
			if ($disPerUser > 0) // make sure its actually a discount
				$inv->addItem("Discount - 20% off per user", $numUsers, $disPerUser * -1);
			break;
		case '30per':
			// 30 % discount
			$disPerUser = $price * .3;
			if ($disPerUser > 0) // make sure its actually a discount
				$inv->addItem("Discount - 30% off per user", $numUsers, $disPerUser * -1);
			break;
		case 'free':
			$inv->addItem("Complimentary Discount", $numUsers, ($price * -1));
			break;
		}
	}
}
