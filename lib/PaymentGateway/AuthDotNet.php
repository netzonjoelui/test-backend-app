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
class PaymentGateway_AuthDotNet extends PaymentGateway
{
	/**
     * Authorize.net login - set in constructor
     *
     * @var string
	 */
	private $authLoginId;

	/**
     * Authorize.net private key - set in constructor
     *
     * @var string
	 */
	private $authTransKey;

	/**
     * Gateway URL
     *
     * @var string
	 */
	private $gatewayUrl = "https://secure2.authorize.net/gateway/transact.dll";

	/**
     * Test Gateway URL
     *
     * @var string
	 */
	private $testGatewayUrl = "https://test.authorize.net/gateway/transact.dll";

	/**
	 * Last Transaction Id
	 *
	 * @var string
	 */
	public $respTransId = null;

	/**
	 * Last Transaction reason
	 *
	 * @var string
	 */
	public $respReason = null;

	/**
	 * Full text from response
	 *
	 * @var string
	 */
	public $respFull = null;

	/**
	 * Class constructor
	 * 
	 * @param string $login_id the unique authorize.net login
	 * @param string $transaction_key the assigned transaction key from authorize.net
	 */
	function __construct($login_id, $transaction_key)
	{
		$this->authLoginId = $login_id;
		$this->authTransKey = $transaction_key;
	}

	/**
	 * Charge a credit or debit card - initiate a new transaction
	 * 
	 * @param float $price the amount to bill
	 * @param string $description the short description of this transaction
	 * @return true on success, false on failure
	 */
	public function charge($price, $description="")
	{
		$auth_net_url = ($this->testMode) ? $this->testGatewayUrl : $this->gatewayUrl;

		$authnet_values = array
		(
			"x_login"				=> $this->authLoginId,
			"x_tran_key"			=> $this->authTransKey,
			"x_version"				=> "3.1",
			"x_delim_char"			=> "|",
			"x_delim_data"			=> "TRUE",
			"x_url"					=> "FALSE",
			"x_type"				=> "AUTH_CAPTURE",
			"x_method"				=> "CC",
			"x_relay_response"		=> "FALSE",
			"x_card_num"			=> $this->normalizeCardNumber($this->cardNumber),
			"x_exp_date"			=> $this->cardExpiresMonth.$this->cardExpiresYear,
			"x_description"			=> $description,
			"x_amount"				=> $this->normalizePrice($price),
			"x_first_name"			=> $this->firstName,
			"x_last_name"			=> $this->lastName,
			"x_address"				=> $this->street,
			"x_city"				=> $this->city,
			"x_state"				=> $this->state,
			"x_zip"					=> $this->zip
		);

		//echo "<pre>$auth_net_url: ".var_export($authnet_values, true)."</pre>";

		$fields = "";
		foreach( $authnet_values as $key => $value ) $fields .= "$key=" . urlencode( $value ) . "&";

		$ch = curl_init($auth_net_url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$text = $resp;	
		$h = substr_count($text, "|");
		$h++;

		$restxt = $text;

		$this->respFull = $restxt;

		$resparts = explode("|", $restxt);

		// Result
		$ccard_result = $resparts[0];

		// Reason
		$this->respReason = $resparts[3];

		// Transaction id
		if (is_array($resparts))
			$this->respTransId = $resparts[6];

		// Codes
		// 1 = success
		// 2 = declined
		// 3 = failed - usually for data reasons

		if (1 == $ccard_result) // 1 = success
		{
			return true;	
		}
		else
		{
			return false;
		}
	}

	/**
	 * Refund or credit a transaction
	 * 
	 * @param float $price the amount to bill
	 * @param string $transId the id of the transaction to credit
	 * @param string $description the short description of this transaction
	 * @return true on success, false on failure
	 */
	public function credit($price, $transId, $description="")
	{
		$auth_net_url = ($this->testMode) ? $this->testGatewayUrl : $this->gatewayUrl;

		$authnet_values = array
		(
			"x_login"				=> $this->authLoginId,
			"x_tran_key"			=> $this->authTransKey,
			"x_version"				=> "3.1",
			"x_delim_char"			=> "|",
			"x_delim_data"			=> "TRUE",
			"x_url"					=> "FALSE",
			"x_type"				=> "CREDIT",
			"x_method"				=> "CC",
			"x_tran_key"			=> $transId,
			"x_relay_response"		=> "FALSE",
			"x_card_num"			=> $this->normalizeCardNumber($this->cardNumber),
			"x_exp_date"			=> $this->cardExpiresMonth.$this->cardExpiresYear,
			"x_description"			=> $description,
			"x_trans_id"			=> $transId,
			"x_amount"				=> $this->normalizePrice($price),
			"x_first_name"			=> $this->firstName,
			"x_last_name"			=> $this->lastName,
			"x_address"				=> $this->street,
			"x_city"				=> $this->city,
			"x_state"				=> $this->state,
			"x_zip"					=> $this->zip
		);

		//if ($this->debug)
			//echo "\n" . var_export($authnet_values, true) . "\n";

		$fields = "";
		foreach( $authnet_values as $key => $value ) $fields .= "$key=" . urlencode( $value ) . "&";

		$ch = curl_init($auth_net_url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);
		
		$text = $resp;	
		$h = substr_count($text, "|");
		$h++;

		$restxt = $text;

		$resparts = explode("|", $restxt);

		//echo "<pre>".var_export($resparts, true)."</pre>";
		// Result
		$ccard_result = $resparts[0];

		// Reason
		$this->respReason = $resparts[3];

		// Transaction id
		if (is_array($resparts))
			$this->respTransId = $resparts[6];

		// Codes
		// 1 = success
		// 2 = declined
		// 3 = failed - usually for data reasons

		if (1 == $ccard_result)
		{
			return true;
		}
		else
		{
			return false;
		}
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

