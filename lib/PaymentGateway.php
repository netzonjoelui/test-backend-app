<?php
/**
 * Main abstract class for payment gateways 
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * function like "payInvoice"
 *
 * @category  CAntObject
 * @package   CAntObjectInvoice
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("security/security_functions.php");
require_once("lib/PaymentGateway/LinkPoint.php");
require_once("lib/PaymentGateway/AuthDotNet.php");
require_once("lib/PaymentGateway/Test.php");

define("PMTGW_AUTHDOTNET", 1);
define("PMTGW_TEST", 2); // a dummy for testing purposes
define("PMTGW_LINKPOINT", 3);

/**
 * Object extensions for managing invoices
 */
abstract class PaymentGateway 
{
	/**
     * Store last error generated
     *
     * @var string
	 */
	public $lastError = "";

	/**
     * Put the gateway in test mode
     *
     * @var bool
	 */
	public $testMode = false;

	/**
     * Customer first name
     *
     * @var string
	 */
	public $firstName;

	/**
     * Customer last name
     *
     * @var string
	 */
	public $lastName;

	/**
     * Customer address: street
     *
     * @var string
	 */
	public $street;

	/**
     * Customer address: city
     *
     * @var string
	 */
	public $city;

	/**
     * Customer address: state
     *
     * @var string
	 */
	public $state;

	/**
     * Customer address: zip
     *
     * @var string
	 */
	public $zip;

	/**
     * Credit/Debit card number
     *
     * @var string
	 */
	public $cardNumber;

	/**
     * Credit/Debit card exp date (mm)
     *
     * @var string
	 */
	public $cardExpiresMonth;

	/**
     * Credit/Debit card exp date (yyyy)
     *
     * @var string
	 */
	public $cardExpiresYear;

	/**
     * Response message/reason. Usually used to indicate what went wrong with last transaction.
     *
     * @var string
	 */
	public $respReason;

	/**
     * ID of last transaction if it was successful
     *
     * @var string
	 */
	public $respTransId;

	/**
	 * Charge a credit or debit card - initiate a new transaction
	 * 
	 * @param float $price the amount to bill
	 * @param string $description the short description of this transaction
	 * @return true on success, false on failure
	 */
	abstract public function charge($price, $description="");

	/**
	 * Refund or credit a transaction
	 * 
	 * @param float $price the amount to bill
	 * @param string $transId the id of the transaction to credit
	 * @param string $description the short description of this transaction
	 * @return true on success, false on failure
	 */
	abstract public function credit($price, $transId, $description="");

	/**
	 * Test if a card is workings and approved for this amount
	 * 
	 * @param float $price the amount to bill
	 * @return true on success, false on failure
	 */
	abstract public function validate($price);

	/**
	 * Get the last error
	 */
	public function getLastError()
	{
		return $this->lastError;
	}

	/**
	 * Set test mode flag
	 */
	public function setTestMode($on=true)
	{
		$this->testMode = $on;
	}

	/**
	 * Make credit card number just numbers
	 */
	protected function normalizeCardNumber($num)
	{
		return str_replace(" ", '', str_replace("-", '', $num));
	}

	/**
	 * Make sure price is formatted correctly
	 */
	protected function normalizePrice($price)
	{
		// Get price
		if (strpos($price, ".") === false)
			$price .= ".00";

		return $price;
	}

	/**
	 * Set month number of digits
	 *
	 * @param int $month The number representing a month
	 * @return string A two digit representation of any number i.e '01' rather than '1'
	 */
	protected function normalizeMonth($month)
	{
		if (strlen($month) == 1)
			return "0" . $month;

		// Do nothing
		return $month;
	}

	/**
	 * Set year number of digits
	 *
	 * @param int $year The number representing a year
	 * @param int $digits The number of digits needed for this gateway
	 * @return string A num($digits) digit representation of any number i.e '01' rather than '1'
	 */
	protected function normalizeYear($year, $digits=4)
	{
		// Covert to 4 digits from 2
		if (strlen($year) == 2 && $digits == 4)
			return "20" . $year;

		// Covert to 2 digits from 4
		if (strlen($year) == 4 && $digits == 2)
			return substr($year, 2, 2);

		// Do nothing
		return $buf;
	}
}
