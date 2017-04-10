<?php
/**
 * Implementation of Authorize.net payment gateway
 *
 * This class requires that curl and ssl be installed and enabled
 *
 * @category  PaymentGateway
 * @package   PaymentGateway_AuthDotNet
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Handle transaction with authorize.net payment gateway
 */
class PaymentGateway_Test extends PaymentGateway
{
	/**
	 * Charge a credit or debit card - initiate a new transaction
	 * 
	 * @param float $price the amount to bill
	 * @param string $description the short description of this transaction
	 */
	public function charge($price, $description="")
	{
		$this->respReason = "Charge was successful!";
		$this->respTransId = 1001010;
		return true;
	}

	/**
	 * Refund or credit a transaction
	 * 
	 * @param float $price the amount to bill
	 * @param string $transId the id of the transaction to credit
	 * @param string $description the short description of this transaction
	 */
	public function credit($price, $transId, $description="")
	{
		return true;
	}

	/**
	 * Test if a card is workings and approved for this amount
	 * 
	 * @param float $price the amount to bill
	 * @return true on success, false on failure
	 */
	public function validate($price)
	{
		return true;
	}
}
