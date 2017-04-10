<?php
/*======================================================================================
	
	Module:		AntApi_Customer	

	Purpose:	Remote API for ANT Customers

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2011 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		// Create a new instance of parser - The last two params are optional
				$antCust = new CAntCustomer("ant.aereus.com", "user", "pass");
				$antCust->setAttribute('first_name', "API");
				$antCust->setAttribute('last_name', "Test");
				$antCust->setMvalAttribute('groups', "213");
				$custid = $antCust->save();

	Variables:	

======================================================================================*/
define("ANTAPI_CUSTOMER_CONTACT", 1);
define("ANTAPI_CUSTOMER_ORG", 2);

class AntApi_Customer extends AntApi_Object
{
	var $id; // customer id
	var $m_wapiUrl;        
	var $m_url;
    var $m_cid = 0;
    
	function __construct($server, $username, $password) 
	{
		$this->m_wapiUrl = "http://".$server."/customer/wapi.php?auth=".base64_encode($username).":".md5($password);
        $this->m_controller = "http://".$server."/controller/";
		parent::__construct($server, $username, $password, "customer");
	}

	function __destruct() 
	{
	}

	/**
	 * Get customer id by email address
	 *
	 * @param string|array $email Either an array of email addresses or a single address
	 * @return integer The id of the first customer found with matching email or null if no customers found
	 */
	public function getIdByEmail($email)
	{
		$tocheck = (is_array($email)) ? $email : array($email);
		$custid = null;

		$objList = new AntApi_ObjectList($this->m_server, $this->m_user, $this->m_pass, "customer");
		$objList->setStoreSource("ant");
		for($i = 0; $i < count($tocheck); $i++)
		{
			$blogic = ($i) ? "or" : "and";
			$objList->addCondition($blogic, "email", "is_equal", $tocheck[$i]);
			$objList->addCondition("or", "email2", "is_equal", $tocheck[$i]);
		}

		$objList->getObjects();

		if ($objList->getNumObjects())
		{
			$objm = $objList->getObjectMin(0);
			$custid = $objm["id"];
		}

		return $custid;
	}

	/*************************************************************************************
	*	Function:	addRelationship	
	*
	*	Purpose:	Add a customer_id as a relationship of this customer. Future versions
	*				will allow for settings relationship type(id) as a second param.
	*
	*	Arguments:	cid:number - a valid ANT customer ID
	**************************************************************************************/
	function addRelationship($cid)
	{
		$this->m_values["relationships[]"] = $cid;
	}

	/*************************************************************************************
	*	Function:	testCreditCard
	*
	*	Purpose:	Test for a valid credit card using a merchant account
	*
	* @param string $ccard_type	'visa', 'amex', 'discover' are the options
	**************************************************************************************/
	function testCreditCard($nameoncard, $ccardnumber, $exp_month, $exp_year, $ccard_type, $ccid="")
	{
		$url = $this->m_controller . "Customer/billingTestCcard";
        $url .= "?auth=".base64_encode($this->username).":".md5($this->password);
		$ret = -1; // Assume fail

		$fields = "customer_id=".$this->id;
		$fields .= "&ccard_name=" . urlencode($nameoncard);
		$fields .= "&ccard_type=" . urlencode($ccard_type);
		$fields .= "&ccard_number=" . urlencode($ccardnumber);
		$fields .= "&ccard_exp_month=" . urlencode($exp_month);
		$fields .= "&ccard_exp_year=" . urlencode($exp_year);
		$fields .= "&ccard_ccid=" . urlencode($ccid);
        
		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);        
        
		$ret = json_decode($resp, true);
		return $ret;
	}

	/*************************************************************************************
	*	Function:	addCreditCard	
	*
	*	Purpose:	Adds a credit card for the current customer. $this->open must have been called
	**************************************************************************************/
	function addCreditCard($nameoncard, $ccardnumber, $exp_month, $exp_year, $ccard_type, $ccid="")
	{
		if (!$this->id)
			return false;
        
        $url = $this->m_controller . "Customer/billingSaveCcard";
        $url .= "?auth=".base64_encode($this->username).":".md5($this->password);
        
		$fields = "customer_id=".$this->id;
		$fields .= "&ccard_name=" . urlencode($nameoncard);
		$fields .= "&ccard_type=" . urlencode($ccard_type);
		$fields .= "&ccard_number=" . urlencode($ccardnumber);
		$fields .= "&ccard_exp_month=" . urlencode($exp_month);
		$fields .= "&ccard_exp_year=" . urlencode($exp_year);
		$fields .= "&ccard_ccid=" . urlencode($ccid);

		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$ret = json_decode($resp, true);        
		return $ret;
	}

	/**
	 * Charge a customer using this credit card
	 *
	 * @param string $ccard_type Can be 'visa', 'amex' or 'discover'
	 */
	function chargeCreditCard($price, $nameoncard, $ccardnumber, $exp_month, $exp_year, $ccid="")
	{
		$url = $this->m_controller . "Customer/billingTestCcard";
        $url .= "?auth=".base64_encode($this->username).":".md5($this->password);
		$ret = -1; // Assume fail

		$fields = "customer_id=".$this->id;
		$fields .= "&ccard_name=" . urlencode($nameoncard);
		$fields .= "&ccard_number=" . urlencode($ccardnumber);
		$fields .= "&ccard_exp_month=" . urlencode($exp_month);
		$fields .= "&ccard_exp_year=" . urlencode($exp_year);
		$fields .= "&ccard_ccid=" . urlencode($ccid);
        
		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);        
        
		$ret = json_decode($resp, true);
		return $ret;
	}
	
	
	/*************************************************************************************
	*	Function:	getCreditCards	
	*
	*	Purpose:	Get a credit cards for the current customer. $this->open must have been called
	*
	*	Arguments:	
	**************************************************************************************/
	function getCreditCards()
	{
		if (!$this->id)
			return false;
        		
        $url = $this->m_controller . "Customer/billingGetCcards";
        $url .= "?auth=".base64_encode($this->username).":".md5($this->password);
		
		$fields = "customer_id=".$this->id;
		
		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$ret = json_decode($resp, true);        
		return $ret;
	}


	/*************************************************************************************
	*	Function:	getCases	
	*
	*	Purpose:	Get cases
	*
	*	Arguments:	
	**************************************************************************************/
	function getCases()
	{
		if (!$this->id)
			return false;
        
        $url = $this->m_controller . "Customer/getCases?customer_id=".$this->id;
        $url .= "&auth=".base64_encode($this->username).":".md5($this->password);
        
		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		//curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$ret = json_decode($resp, true);        
		//echo "<pre>".var_export($resp, true)."</pre>";
		return $ret;
	}
	
	/*************************************************************************************
	*	Function:	authUser	
	*
	*	Purpose:	Authenticate Customer.
	*
	*	Arguments:	$username:string - Customer's username
	*				$passwordl:fval	 - Customer's password
	**************************************************************************************/
	function authUser($username, $password)
	{
        $url = $this->m_controller . "Customer/authChallenge";
        $url .= "?auth=".base64_encode($this->username).":".md5($this->password);

		$fields = 'username='.urlencode($username).'&password='.md5($password);
		foreach( $this->m_values as $key => $value ) 
		{
			$fields .= "&$key=" . urlencode( $value );
		}

		$ch = curl_init($url); // URL of gateway for cURL to post to
		//curl_setopt($ch, CURLOPT_PROXY,"localhost:80");
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); ### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results	
		curl_close ($ch);
        
        $ret = json_decode($resp, true);        
		$this->lastErrorMessage = $ret['error'];
		return $ret['ret_val'];
	}
	
	// Register Customer
	function registerCustomer($username, $password, $attrib = array())
	{
        $url = $this->m_controller . "Customer/customerRegister";
        $url .= "?auth=".base64_encode($this->username).":".md5($this->password);
		
		// post values
		$fields = '';
		
		// set username and password as post values
		$fields .= 'username='.urlencode($username).'&password='.md5($password);
		
		// add attributes as post values
		if( count($attrib)>0 )
		{
			foreach( $attrib as $key => $value ){
				$fields .= '&'.urlencode($key).'='.urlencode($value);
			}
		}
		
		if($this->m_cid > 0 ){
			// set update customer flag as ON
			$url .= "&onlychange=1";
			
			// add customner id
			$fields .= '&customer_id='.$this->m_cid;
		}

		
		foreach( $this->m_values as $key => $value ) 
		{
			$fields .= "&$key=" . urlencode( $value );
		}

		$ch = curl_init($url); // URL of gateway for cURL to post to
		//curl_setopt($ch, CURLOPT_PROXY,"localhost:80");
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);
		
		$ret = json_decode($resp, true);        
        $this->lastErrorMessage = $ret['error'];
        return $ret['ret_val'];
	}

	/*************************************************************************************
	*	Function:	authGetCustId	
	*
	*	Purpose:	Get a customer id from a user name
	*
	*	Arguments:	$username:string - Customer's username
	**************************************************************************************/
	function authGetCustId($username)
	{		
        $url = $this->m_controller . "Customer/authGetCustId?username=".urlencode($username);
        $url .= "&auth=".base64_encode($this->username).":".md5($this->password);
        
        $fields = "";        
		foreach( $this->m_values as $key => $value ) 
			$fields .= "&$key=" . urlencode( $value );
		
		$ch = curl_init($url); // URL of gateway for cURL to post to
		//curl_setopt($ch, CURLOPT_PROXY,"localhost:80");
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); ### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

        $ret = json_decode($resp, true);        
        return $ret;
	}

	/*************************************************************************************
	*	Function:	authSetPassword	
	*
	*	Purpose:	Set password for a customer id
	*
	*	Arguments:	$cid:int - Customer's id
	*				$pass:string - new password to use 
	**************************************************************************************/
	function authSetPassword($cid, $password)
	{
		$url = $this->m_controller . "Customer/authSetPassword";
        $url .= "?auth=".base64_encode($this->username).":".md5($this->password);
        
		$fields = 'customer_id='.$cid.'&password='.md5($password);;
		foreach( $this->m_values as $key => $value ) 
			$fields .= "&$key=" . urlencode( $value );
		
		$ch = curl_init($url); // URL of gateway for cURL to post to
		//curl_setopt($ch, CURLOPT_PROXY,"localhost:80");
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); ### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);
		
        $ret = json_decode($resp, true);        
        return $ret;
	}

	/*************************************************************************************
	*	Function:	resetAuthPassword	
	*
	*	Purpose:	Reset password for an account
	*
	*	Arguments:	$username:string 	 - 	Customer's username
	*				$passwordl:string	 - 	Customer's password
	*				$returnpage:string	 - 	page to link in email when password reset is
	*										sent
	**************************************************************************************/
	function resetAuthPassword($username, $password, $returnpage='')
	{
		$url = $this->m_controller . "Customer/authChallenge";
        $url .= "?auth=".base64_encode($this->username).":".md5($this->password);

		$fields = 'username='.urlencode($username).'&password='.md5($password);
		foreach( $this->m_values as $key => $value ) 
		{
			$fields .= "&$key=" . urlencode( $value );
		}
		
		$ch = curl_init($url); // URL of gateway for cURL to post to
		//curl_setopt($ch, CURLOPT_PROXY,"localhost:80");
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); ### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results	
		curl_close ($ch);

		$ret = json_decode($resp, true);        
        $this->lastErrorMessage = $ret['error'];
        return $ret['ret_val'];
	}

	/**
	 * Send an email through ant
	 *
	 * @param
	 */
	public function sendEmail($subject, $body, $templateId=null, $vars=array())
	{
		if (!$this->id)
			return false;

		$params = array(
			"customer_id" => $this->id,
			"subject" => $subject,
			"body_plain" => $body,
			"template_id" => $templateId,
		);

		// Add all additional values
		foreach ($vars as $varname=>$varval)
			$params[$varname] = $varval;

		$ret = $this->sendRequest("Customer", "sendEmail", $params);
		return $ret;
	}
}
