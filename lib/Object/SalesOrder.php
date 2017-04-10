<?php
/**
 * Aereus Object Sales Order
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * function like "processPayment"
 *
 * @category  CAntObject
 * @package   CAntObject_SalesOrder
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/PaymentGatewayManager.php");

/**
 * Object extensions for managing sales orders
 */
class CAntObject_SalesOrder extends CAntObject
{
	/**
	 * Items in this invoice
	 *
	 * @var array(stdCls(id, invoice_id, quantity, name, amount, product_id))
	 */
	private $itemsDetail = array();

	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $eid 			The event id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "sales_order", $eid, $user);
	}

	/**
	 * Function used for derrived classes to hook save event
	 *
	 * This is called after CAntObject base saves all properties
	 */
	protected function saved()
	{
		// Base object must have successfully saved before the details can be saved
		if (!$this->id)
			return false;

		foreach ($this->itemsDetail as $det)
		{
			if ($det->id)
			{
				$query = "UPDATE sales_order_detail SET quantity=".$this->dbh->EscapeNumber($det->quantity).", 
						  amount=".$this->dbh->EscapeNumber($det->amount).", 
						  name='".$this->dbh->Escape($det->name)."' WHERE id='".$det->id."'";
			}
			else
			{
				$query = "INSERT INTO sales_order_detail(name, quantity, amount, product_id, order_id)
						  VALUES('".$this->dbh->Escape($det->name)."', ".$this->dbh->EscapeNumber($det->quantity).", 
								  ".$this->dbh->EscapeNumber($det->amount).", ".$this->dbh->EscapeNumber($det->productId).", 
								  '".$this->id."');";
			}

			$this->dbh->Query($query);
		}
	}

	/**
	 * Function used for derrived classes to hook onload event
	 *
	 * This is called after CAntObject base loads all properties
	 */
	protected function loaded()
	{
		if ($this->id)
		{
			$this->itemsDetail = array();

			$result = $this->dbh->Query("SELECT * from sales_order_detail WHERE order_id='".$this->id."'");
			$num = $this->dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $this->dbh->GetRow($result, $i);
				$this->addItem($row['name'], $row['amount'], $row['quantity'], $row['product_id'], $row['id']);
			}
		}
	}

	/**
	 * Process payment for an order with a debit or credit card
	 * 
	 * @param PaymentGateway $gw a PaymentGateway object to perform the transaction
	 * @param int $cardData the customer to bill invoice to
	 * @param int $custData the saved credit card to use when paying invoice
	 * @param int $paidStatusId the status to set the invoice once the invoice has been paid
	 * @param int $paidStatusId the status to set the invoice if payment fails
	 */
	public function payWithCard($gw, $cardData, $custData, $paidStatusId=null, $failedStatusId=null)
	{
		$total = $this->getTotal();

		// Skip processing if zero value
		if ($total <= 0)
		{
			if ($paidStatusId)
			{
				$this->setValue("status_id", $paidStatusId);
				$this->save();
			}

			return true;
		}

		// Set customer information
		$gw->firstName = $custData['first_name'];
		$gw->lastName = $custData['last_name'];
		$gw->street = $custData['street'];
		$gw->city = $custData['city'];
		$gw->state = $custData['state'];
		$gw->zip = $custData['zip'];

		// Set credit card information
		$gw->cardNumber = $cardData['number'];
        
        $cdExpire = null;
        if(isset($cardData['expires']))
            $cdExpire = $cardData['expires'];
        
		if(!$cdExpire)
		{
			$gw->cardExpiresMonth = $cardData['exp_month'];
			$gw->cardExpiresYear = $cardData['exp_year'];
		}
		else
			$gw->cardExpires = $cardData['expires'];

		$ret = $gw->charge($total, "Paid invoice #".$this->id);
		if ($ret)
		{
			$this->addActivity("processed", "Credit Card Transaction", 
								"Amount: $total, Transaction ID: ".$gw->respTransId.", Message:".$gw->respReason);
            
			if ($paidStatusId)
			{
				$this->setValue("status_id", $paidStatusId);
				$this->save();
			}

			return true;
		}
		else
		{
			$this->addActivity("processed", "Credit Card Transaction Failed", 
								"Amount: $total, Transaction ID: ".$gw->respTransId.", Message:".$gw->respReason);

			if ($failedStatusId)
			{
				$this->setValue("status_id", $failedStatusId);
				$this->save();
			}

			return false;
		}
	}

	/**
	 * Get the total invoice amount
	 */
	public function addItem($name, $amount, $quantity, $product_id=null, $id=null)
	{
		$item = new stdClass();
		$item->name = $name;
		$item->amount = $amount;
		$item->quantity = $quantity;
		$item->productId = $product_id;
		$item->id = $id;

		$this->itemsDetail[] = $item;
	}

	/**
	 * Get the total invoice amount
	 */
	public function getNumItems()
	{
		return count($this->itemsDetail);
	}


	/**
	 * Get the total invoice amount
	 */
	public function getTotal()
	{
		$subtotal = $this->getSubtotal();
		$shipping = $this->getShippingTotal();
		$taxes = $this->getTaxesTotal();

		$total = $subtotal + $shipping + $taxes;

		return $total;
	}

	/**
	 * Get the total invoice amount
	 */
	public function getSubtotal()
	{
		$val = 0;

		foreach ($this->itemsDetail as $det)
		{
			$val += $det->amount * $det->quantity;
		}

		return $val;
	}

	/**
	 * Get the total shipping amount
	 *
	 * For each item, check to see if manual shipping cost is set.
	 * If no manual shipping cost, then check weight and see if a system
	 * per-weight setting is set, then check to see if there is a flat shipping fee
	 */
	public function getShippingTotal()
	{
		return 0;
	}

	/**
	 * Get the total invoice amount
	 */
	public function getTaxesTotal()
	{
		return 0;
	}
}
