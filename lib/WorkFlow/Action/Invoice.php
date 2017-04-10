<?php
/**
 * Action to create new invoice
 *
 * @category	Ant
 * @package		WorkFlow_Action
 * @subpackage	Invoice
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/WorkFlow/Action/Abstract.php");
require_once("lib/Object/Invoice.php");
require_once("lib/Object/Customer.php");
require_once("lib/Email.php");

/**
 * Invoice action used to create new invoices
 */
class WorkFlow_Action_Invoice extends WorkFlow_Action_Abstract
{
	/**
	 * Payment gateway type override
	 *
	 * This can be set to a specific payment gateway type to override the default
	 *
	 * var int
	 */
	public $pgwType = null;

	/**
	 * Id of created invoice
	 *
	 * var int
	 */
	public $invoiceId = null;

	/**
	 * Result of payment attempt (if applicable)
	 *
	 * var bool
	 */
	public $pmtResult = false;

	/**
	 * Execute action
	 *
	 * This extends common object creation because it has additional functions/features
	 * like sending invoice to customer and automated billing
	 *
	 * @param CAntObject $obj object that we are running this workflow action against
	 * @param WorkFlow_Action $act current action object
	 */
	public function execute($obj, $act)
	{
		$ovals = $act->getObjectValues();
		$act->replaceMergeVars($ovals, $obj); // replace <%vars%> with values from object

		$invobj = new CAntObject_Invoice($this->dbh, null, $act->getWorkflowUser());
		$invobj->setValue("name", ($ovals['name']) ? $ovals['name'] : 'Automated Invoice');
		$invobj->setValue("owner_id", $ovals['owner_id']);
		$invobj->setValue("date_due", date("m/d/Y"));
		if ($obj->object_type == "customer")
			$invobj->setValue("customer_id", $obj->id);
		$invobj->addAssociation($obj->object_type, $obj->id, "associations");

		// Add details to invoice
		if (is_numeric($ovals['ent_product_0']) && $ovals['ent_quantity_0'])
		{
			$objProduct = new CAntObject($this->dbh, "product", $ovals['ent_product_0']);

			$invobj->addItem($objProduct->getValue("name"), $objProduct->getValue("price"), $ovals['ent_quantity_0'], $objProduct->id);
			$invobj->setValue("amount", $invobj->getTotal());
		}

		// TODO: carry-over past due balance

		
		// Save invoice
		$this->invoiceId = $invobj->save();

		// Optionally charge invoice
		if ($ovals['paywithdefcard'] == "1" && $invobj->getValue("customer_id"))
		{
			$cust = new CAntObject_Customer($this->dbh, $invobj->getValue("customer_id"), $act->getWorkflowUser());
			$ccard = $cust->getDefaultCreditCard();

			if ($ccard && PaymentGatewayManager::hasGateway($this->dbh) || $this->pgwType==PMTGW_TEST)
			{
				// Get gateway
				$gw = PaymentGatewayManager::getGateway($this->dbh, $this->pgwType); // Second param is defualt to null which pulls default gw

				// Set credit card info
				$cardData = array(
					"number" => $ccard['number'],
					"exp_month" => $ccard['exp_month'],
					"exp_year" => $ccard['exp_year'],
				);

				// Set customer data
				$street = $cust->getValue("billing_street");
				if ($cust->getValue("billing_street2"))
					$street .= "\n".$cust->getValue("billing_street2");

				// See if there is a name associated with this card (there should be)
				$fname = "";
				$lname = "";
				if ($ccard['nameoncard'])
				{
					$parts = explode(" ", $ccard['nameoncard']);
					if (count($parts))
					{
						$fname = $part[0];
						for ($i = 1; $i < count($parts); $i++)
						{
							if ($lname) $lname .= " ";
							$lname .= $parts[$i];
						}
					}
				}
				else if ($cust->getValue("first_name"))
				{
					$fname = $cust->getValue("first_name");
					$lname = $cust->getValue("last_name");
				}
				else
				{
					$fname = $cust->getValue("name");
				}

				// Set customer data
				$custData = array(
					"first_name" => $fname,
					"last_name" => $lname,
					"street" => $street,
					"city" => $cust->getValue("billing_city"),
					"state" => $cust->getValue("billing_state"),
					"zip" => $cust->getValue("billing_zip")
				);
				
				$billSuccessStatus = (isset($ovals['billing_success_status'])) ? $ovals['billing_success_status'] : null;
				$billFailStatus = (isset($ovals['billing_fail_status'])) ? $ovals['billing_fail_status'] : null;
				$this->pmtResult = $invobj->payWithCard($gw, $cardData, $custData, $billSuccessStatus, $billFailStatus);


				if (isset($ovals['billing_success_notify']) && $this->pmtResult)
				{
					// Create new email object
					$headers['From'] = AntConfig::getInstance()->email['noreply'];
					$headers['To'] = $ovals['billing_success_notify'];
					$headers['Subject'] = "Credit Card Processing Success";
					$body = "On " . date("l jS \of F Y h:i:s A") . " Invoice #" . $invobj->id . " was billed for ".$invobj->getTotal();
					$body .= "\r\n\r\nCustomer:\r\n";
					$body .= $act->getAccBaseUrl()."/obj/customer/".$invobj->getValue("customer_id");
					$body .= "\r\n\r\nInvoice:\r\n";
					$body .= $act->getAccBaseUrl()."/obj/invoices/".$invobj->id;

					$email = new Email();
					$status = $email->send($headers['To'], $headers, $body);
					unset($email);
				}
				else if (isset($ovals['billing_fail_notify']))
				{
                    // Create new email object
					$headers['From'] = AntConfig::getInstance()->email['noreply'];
					$headers['To'] = $ovals['billing_fail_notify'];
					$headers['Subject'] = "Credit Card Processing FAILED";
					$body = "On " . date("l jS \of F Y h:i:s A") . " Invoice #" . $invobj->id . " could not be billed for ".$invobj->getTotal();
					$body .= "\r\n\r\nCustomer:\r\n";
					$body .= $act->getAccBaseUrl()."/obj/customer/".$invobj->getValue("customer_id");
					$body .= "\r\n\r\nInvoice:\r\n";
					$body .= $act->getAccBaseUrl()."/obj/invoices/".$invobj->id;

					$email = new Email();
					$status = $email->send($headers['To'], $headers, $body);
					unset($email);
				}
			}
		}
		
		// TODO: check if invoice should be emailed to customer
	}
}
