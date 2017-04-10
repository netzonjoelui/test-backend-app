<?php
/**
 * Aereus API Library for working with invoices
 *
 * Product class is basically just an alias for AntApi_Object but adds invoice detail to save and load
 *
 * @category  AntApi
 * @package   AntApi_SalesOrder
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
if (!defined("ANTAPI_NOHTTPS"))
	define("ANTAPI_NOHTTPS", false);

/**
 * Class representing an ANT object of type=invoice
 */
class AntApi_Invoice extends AntApi_Object
{
	/**
     * The detailed entries for this invoice
     *
     * @var array
     */
	private $invoiceDetail = array();

	/**
     * Billing address
     *
     * @var array
     */
	private $addressBilling = array();

	/**
     * Credit card info
     *
     * @var array
     */
	private $creditCard = array();

	/**
     * Last error message
     *
     * @var string
     */
	public $lastErrorMessage = "";

	/**
     * Put api in test mode
     *
     * @var bool
     */
	public $testMode = false;

	/**
     * Class constructor just calls base class constructor with appropriate object type
	 *
	 * @param string $server ANT server name
	 * @param string $username A valid ANT user name with appropriate permissions
	 * @param string $password ANT user password
     */
	function __construct($server, $username, $password) 
	{
		parent::__construct($server, $username, $password, "invoice");
	}

	/**
	 * Load invoice details
	 *
	 * This is called by the root object once the main object has finished loaded
	 */
	public function loaded()
	{
		if (!$this->id)
			return false;

		$entries = $this->sendRequest("Sales", "invoiceGetDetail", array("invoice_id"=>$this->id));

		if (is_array($entries) && count($entries))
			$this->invoiceDetail = $entries;
	}

	/**
	 * Call default object save, then post order details
	 */
	public function save()
	{
		// set total amount if not set
		$this->setValue("amount", $this->getTotalAmount());

		$id = parent::save();
        $this->id = $id;
        
		// Now save details        
		if ($this->id)
		{
			$data = array("invoice_id"=>$this->id);
			$data['entries'] = array();
			for ($i = 0; $i < count($this->invoiceDetail); $i++)
			{
				$data['entries'][] = $i;
				$data['ent_pid_'.$i] = $this->invoiceDetail[$i]->id;
				$data['ent_name_'.$i] = $this->invoiceDetail[$i]->name;
				$data['ent_amount_'.$i] = $this->invoiceDetail[$i]->amount;
				$data['ent_quantity_'.$i] = $this->invoiceDetail[$i]->quantity;
			}

			$ret = $this->sendRequest("Sales", "invoiceSaveDetail", $data);

			if (!isset($ret->error))
				return $id;
		}

		return false;
	}

	/**
	 * Process payment for this order
	 *
	 * @param int $invId Optional invoice id to apply payment to, otherwise just apply to sales_order
	 */
	public function processPayment($invId=null)
	{
		$ret = -1; // Assume fail

		if (ANTAPI_NOHTTPS)
			$url = "http://";
		else
			$url = "https://";

		$url .= $this->server."/api/php/Sales/";

		$fields = "order_id=" . $this->id;
		$fields .= "&method=credit";
		if ($this->testMode)
			$fields .= "&testmode=1";

		// Bill through invoice
		if ($invId)
		{
            $url .= "invoiceBill";
			$fields .= "&invoice_id=" . $invId;
		}
		else // no invoice, just process payment using order
		{
            $url .= "paymentProcess";
		}

		// Customer info
		if (is_array($this->addressBilling) && count($this->addressBilling))
		{
			$fields .= "&billing_address=" . urlencode($this->addressBilling['address']);
			$fields .= "&billing_city=" . urlencode($this->addressBilling['city']);
			$fields .= "&billing_state=" . urlencode($this->addressBilling['state']);
			$fields .= "&billing_zip=" . urlencode($this->addressBilling['zip']);
		}
		else if ($this->getValue("customer_id"))
		{
			$fields .= "&customer_id=" . urlencode($this->getValue("customer_id"));
		}

		// Credit card details
		if (is_array($this->creditCard) && count($this->creditCard))
		{
			$fields .= "&ccard_name=" . urlencode($this->creditCard['fullname']);
			$fields .= "&ccard_exp_year=" . urlencode($this->creditCard['exp_year']);
			$fields .= "&ccard_exp_month=" . urlencode($this->creditCard['exp_month']);
			$fields .= "&ccard_number=" . urlencode($this->creditCard['card_number']);
		}

		$url .= "?auth=" . base64_encode($this->username) . ":" . md5($this->password);            
		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$ret = json_decode($resp);

		return $ret;
	}

	/**
	 * Set credit card
	 *
	 * @param string $nameoncard Cardholder name
	 * @param string $card_number Credit card number
	 * @param string $exp_month mm - month exptires
	 * @param string $exp_year yyyy - year expires
	 * @param string $sec_code Optional security verification code
	 */
	public function setCreditCard($nameoncard, $card_number, $exp_month, $exp_year, $sec_code="")
	{
		$this->creditCard = array(
			"fullname" => $nameoncard,
			"card_number" => $card_number,
			"exp_month" => $exp_month,
			"exp_year" => $exp_year,
			"sec_code" => $sec_code
		);
	}

	/**
	 * Add an item to this cart
	 *
	 * @param string $name The name of this product
	 * @param int $quantity The number of the selected products to add to the cart
	 * @param float $amount The price/amount each of this product
	 * @param string $product_id Optional product ID
	 * @retrun bool true on success, false on failure
	 */
	public function addItem($name, $quantity, $amount, $product_id=null)
	{
		// Add item data to session array
		$item = new stdClass();
		$item->id = $product_id;
		$item->name = $name;
		$item->amount = (float) $amount;
		$item->quantity = $quantity;
		$this->invoiceDetail[] = $item;
		return true;
	}


	/**
	 * Retrieve the last error
	 */
	public function getLastError()
	{
		return $this->lastErrorMessage;
	}

	/**
	 * Clear the contents
	 */
	public function clear()
	{
		$this->invoiceDetail = array();
	}

	/**
	 * Get number of item entries
	 */
	public function getNumItems()
	{
		return count($this->invoiceDetail);
	}

	/**
	 * Get total of items without tax or shipping
	 */
	public function getSubTotal()
	{
		$total = 0;

		foreach ($this->invoiceDetail as $item)
			$total += ($item->amount * $item->quantity);

		return $total;
	}

	/**
	 * Get total - including tax and shipping
	 */
	public function getTotalAmount()
	{
		// TODO: we will work on this later, for now just return subtotal
		return $this->getSubTotal();
	}
}
