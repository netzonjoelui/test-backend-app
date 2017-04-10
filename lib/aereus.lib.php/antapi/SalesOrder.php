<?php
/**
 * Aereus API Library
 *
 * Product class is basically just an alias for AntApi_Object
 *
 * @category  AntApi
 * @package   AntApi_SalesOrder
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

if (!defined("ANTAPI_NOHTTPS"))
	define("ANTAPI_NOHTTPS", false);

/**
 * Class representing an ANT object of type=sales_order
 */
class AntApi_SalesOrder extends AntApi_Object
{
	/**
     * The order details for this sales order
     *
     * @var array
     */
	private $orderDetail = array();

	/**
     * Shipping address
     *
     * @var array
     */
	private $addressShipping = array();

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
		parent::__construct($server, $username, $password, "sales_order");
	}

	/**
	 * Call default object save, then post order details
	 */
	public function save()
	{
		global $settings_no_https;

		$this->setValue("amount", $this->getTotal());

		$id = parent::save();
        $this->id = $id;
        
		// Now save order details        
		if ($this->id)
		{
			if ($settings_no_https)
				$url = "http://";
			else
				$url = "https://";
			
            $url .= $this->server."/controller/Sales/orderSaveDetail";
			$url .= "?auth=".base64_encode($this->username).":".md5($this->password);            
			$ret = -1; // Assume fail

			$fields = "order_id=".$this->id;
			for ($i = 0; $i < count($this->orderDetail); $i++)
			{
				$fields .= "&entries[]=" . urlencode($i);
				$fields .= "&ent_pid_$i=" . urlencode($this->orderDetail[$i]->id);
				$fields .= "&ent_name_$i=" . urlencode($this->orderDetail[$i]->name);
				$fields .= "&ent_amount_$i=" . urlencode($this->orderDetail[$i]->price);
				$fields .= "&ent_quantity_$i=" . urlencode($this->orderDetail[$i]->quantity);
			}

			$ch = curl_init($url); // URL of gateway for cURL to post to
			curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
			curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
			$resp = curl_exec($ch); //execute post and get results
			curl_close ($ch);
            
			//$ret = json_decode($resp);
		}

		return $id;
	}

	/**
	 * Create invoice and bill if applicable
	 *
	 * @param int $setStatusTo Upon successful processing, set the status of this order to this var
	 * @param bool $pay Try to bill this order to a credit card. Defaults to true.
	 * @param bool $createInv If set to true an invoice will be generated for this order
	 */
	public function processOrder($setStatusTo=null, $pay=true, $createInv=false)
	{
		// If order has not yet been saved, save data before trying to process
		if (!$this->id)
			$this->save();

		// Make sure that the above worked, this order must be saved before processing
		if (!$this->id)
			return false;

		// Create an invoice
		$invoiceId = null;
		if ($createInv)
		{
		}

		// If selected, then try to process payment for this order
		if ($pay && count($this->creditCard))
		{
			$this->processPayment($invoiceId);
		}

		// Update status of this order if set
		if ($setStatusTo)
		{
		}

		return true;
	}

	/**
	 * Process payment for this order
	 *
	 * @param int $invId Optional invoice id to apply payment to, otherwise just apply to sales_order
	 */
	public function processPayment($invId=null)
	{
		global $settings_no_https;

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
	 * Set billing address for this order
	 *
	 * @param string $street Street or PO box number, may contain multiple lines
	 * @param string $city The city of the billing address
	 * @param string $state	The stage/region of the billing address
	 * @param string $zip The 5 or 9 digit zip code
	 */
	public function setBillingAddress($street, $city, $state, $zip)
	{
		$this->addressBilling = array(
			"address" => $street,
			"city" => $city,
			"state" => $state,
			"zip" => $zip
		);
	}

	/**
	 * Set shipping address for this order
	 *
	 * @param string $street Street or PO box number, may contain multiple lines
	 * @param string $city The city of the shipping address
	 * @param string $state The stage/region of the shipping address
	 * @param string $zip The 5 or 9 digit zip code
	 */
	public function setShippingAddress($street, $city, $state, $zip)
	{
		$this->addressShipping = array(
			"address" => $street,
			"city" => $city,
			"state" => $state,
			"zip" => $zip
		);
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
	 * @param float $price The price each of this product
	 * @param string $product_id Optional product ID
	 * @retrun bool true on success, false on failure
	 */
	public function addItem($name, $quantity, $price, $product_id=null)
	{
		// Add item data to session array
		$item = new stdClass();
		$item->id = $product_id;
		$item->name = $name;
		$item->price = (float) $price;
		$item->quantity = $quantity;
		$this->orderDetail[] = $item;
		return true;
	}

	/**
	 * Get the total amount
	 */
	public function getTotal()
	{
		$total = 0;

		foreach ($this->orderDetail as $item)
			$total += $item->price * $item->quantity;

		return $total;
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
		$this->orderDetail = array();
	}
}
