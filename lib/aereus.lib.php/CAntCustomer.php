<?php
/*======================================================================================
	
	Module:		CAntCustomer	

	Purpose:	Remote API for ANT Customers

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		// Create a new instance of parser - The last two params are optional
				$antCust = new CAntCustomer("ant.aereus.com", "user", "pass");
				$antCust->setAttribute('first_name', "API");
				$antCust->setAttribute('last_name', "Test");
				$antCust->setMvalAttribute('groups', "213");
				$custid = $antCust->save();

	Variables:	

======================================================================================*/
class CAntCustomer
{
	var $id; // customer id
	var $m_resp; // raw response cache
	var $m_url; // base URL
	var $m_urlGet; // base URL
	var $m_user;
	var $m_pass;
	var $lastErrorMessage;
	var $m_values; // Store set data (for saving onlychanged)
	var $m_attribs; // For now this will store retrieved data
	
	function CAntCustomer($server, $username, $password, $account="aereus") 
	{
		$this->m_url = "http://".$server."/customer/wapi.php?auth=".base64_encode($username).":".md5($password);
		$this->m_urlGet = "http://".$server."/objects/xml_get_object.php?auth=".base64_encode($username).":".md5($password)."&obj_type=customer";
		$this->m_values = array();
		$this->m_attribs = array();
	}

	function __destruct() 
	{
	}

	/*************************************************************************************
	*	Function:	setAttribute	
	*
	*	Purpose:	Set the value for an attributed
	*
	*	Arguments:	fname:string - name of the property to set. Non-existant properties will
	*								ignored by ANT
	*				fval:string	 - value to set attribute to
	**************************************************************************************/
	function setAttribute($fname, $fval)
	{
		$this->m_values[$fname] = $fval;
	}

	/*************************************************************************************
	*	Function:	setMvalAttribute	
	*
	*	Purpose:	MVals are multi-values or one/many to many relationships shuch as groups etc.
	*
	*	Arguments:	fname:string - name of the property to set. Non-existant properties will
	*								ignored by ANT
	*				fval:fval	 - value to set attribute to
	**************************************************************************************/
	function setMvalAttribute($fname, $fval)
	{
		$this->m_values[$fname."[]"] = $fval;
	}

	/*************************************************************************************
	*	Function:	getAttribute	
	*
	*	Purpose:	Get the value for an attributed
	*
	*	Arguments:	fname:string - name of the property to set. Non-existant properties will
	*								ignored by ANT
	**************************************************************************************/
	function getAttribute($name)
	{
		return $this->m_attribs[$name];
	}
	function getValue($name)
	{
		return $this->getAttribute($name);
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
	*	Function:	open	
	*
	*	Purpose:	Open an existing customer, right now only the id is set which enforces an update rather than an insert
	*				TODO: this function needs to be finished, so far no data is pulled from ANT
	*
	*	Arguments:	cid:number - a valid ANT customer ID
	**************************************************************************************/
	function open($cid)
	{
		$this->id = $cid;
		$this->m_url .= "&id=".$cid;

		/*
		$dom = new DomDocument();
		$dom->load($this->m_urlGet."&oid=$cid"); 

		foreach ($dom->documentElement->childNodes as $cust) 
		{
			//if node is an element (nodeType == 1) and the name is "item" loop further
			if ($cust->nodeType == 1)
			{
				switch ($cust->nodeName)
				{
				default:
					$this->m_attribs[$cust->nodeName] = rawurldecode($cust->textContent);
					break;
				}
			}
		}*/
		
		$ch = curl_init($this->m_urlGet."&oid=$cid"); // URL of gateway for cURL to post to
		//curl_setopt($ch, CURLOPT_PROXY,"localhost:80");
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		//curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$dom = new DomDocument();
        error_reporting(0);
        $dom->loadXml($resp);
        
        
        if(is_object($dom->documentElement))
        {
            foreach ($dom->documentElement->childNodes as $cust) 
            {
                //if node is an element (nodeType == 1) and the name is "item" loop further
                if ($cust->nodeType == 1)
                {
                    switch ($cust->nodeName)
                    {
                    default:
                        $this->m_attribs[$cust->nodeName] = rawurldecode($cust->textContent);
                        break;
                    }
                }
            }
        }
	}


	
	/*************************************************************************************
	*	Function:	saveChanges	
	*
	*	Purpose:	Save only changed attributes to ANT CRM. On success a valid customer id is returned.
	*				On failure -1 is returned with a message explaining the error.
	**************************************************************************************/
	function saveChanges()
	{
		return $this->save(true);
	}

	/*************************************************************************************
	*	Function:	save	
	*
	*	Purpose:	Saves all attributes to ANT by default. This can be dangerous if customer
	*				is already existing and has not been opened yet. onlychanged=true
	*				allows for updating only of changed attributes.
	*
	*	Arguments:	onlychange:bool - defaults to saving/overwriting all attributes even if blank
	**************************************************************************************/
	function save($onlychange=false)
	{
		$url = $this->m_url;
		$url .= "&function=customer_save";
		if ($onlychange)
			$url .= "&onlychange=1";

		$fields = "";
		foreach( $this->m_values as $key => $value ) 
			$fields .= "$key=" . urlencode( $value ) . "&";
		
		$ch = curl_init($url); // URL of gateway for cURL to post to
		//curl_setopt($ch, CURLOPT_PROXY,"localhost:80");
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		$this->m_resp = $resp;
		curl_close ($ch);

		try
		{
			$dom = new DomDocument();
			if ($dom->loadXml($resp))
			{
				foreach ($dom->documentElement->childNodes as $node) 
				{
					if ($node->nodeType == 1 && $node->nodeName == "retval") 
					{
						$this->id = $node->textContent;
						return rawurldecode($node->textContent);
					}
				}
			}
			else
			{
				echo "CAntCustomer::save: Failed to load - $resp";
			}
		}
		catch (Exception $e) 
		{
			echo "CAntCustomer::save: ".$e->getMessage()." ------ ".$resp;
		}

		return 0;
	}

	/*************************************************************************************
	*	Function:	testCreditCard
	*
	*	Purpose:	Test for a valid credit card using a merchant account
	**************************************************************************************/
	function testCreditCard($nameoncard, $ccardnumber, $exp_month, $exp_year, $ccard_type, $ccid="")
	{
		$url = $this->m_url;
		$url .= "&function=billing_test_ccard";
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

		$dom = new DomDocument();
		$dom->loadXml($resp); 
		foreach ($dom->documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 && $node->nodeName == "retval") 
				$ret = rawurldecode($node->textContent);
			if ($node->nodeType == 1 && $node->nodeName == "message") 
				$this->lastErrorMessage = rawurldecode($node->textContent);
		}

		return $ret;
	}

	/*************************************************************************************
	*	Function:	addCreditCard	
	*
	*	Purpose:	Adds a credit card for the current customer. $this->open must have been called
	**************************************************************************************/
	function addCreditCard($nameoncard, $ccardnumber, $exp_month, $exp_year, $ccard_type="", $ccid="")
	{
		if (!$this->id)
			return false;

		$url = $this->m_url;
		$url .= "&function=billing_save_ccard";
		
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

		$dom = new DomDocument();
		$dom->loadXml($resp); 
		foreach ($dom->documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 && $node->nodeName == "retval") 
			{
				//$this->id = $node->textContent;
				return rawurldecode($node->textContent);
			}
		}

		return 0;
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

		$url = $this->m_url;
		$url .= "&function=billing_get_ccards";
		
		$fields = "customer_id=".$this->id;
		
		echo $url;
		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$dom = new DomDocument();
		$dom->loadXml($resp); 
		foreach ($dom->documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 && $node->nodeName == "retval") 
			{
				//$this->id = $node->textContent;
				return rawurldecode($node->textContent);
			}
		}

		return 0;
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

		$cases = array();

		$url = $this->m_url;
		$url .= "&function=get_cases";
		
		$fields = "customer_id=".$this->id;
		
		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$dom = new DomDocument();
		$dom->loadXml($resp); 
		foreach ($dom->documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 && $node->nodeName == "case")
			{
				$case = array();
				foreach ($node->childNodes as $cnode) 
				{
					//if node is an element (nodeType == 1) and the name is "item" loop further
					if ($cnode->nodeType == 1)
					{
						switch ($cnode->nodeName)
						{
						default:
							$case[$cnode->nodeName] = rawurldecode($cnode->textContent);
							break;
						}
					}
				}
				$cases[] = $case;
			}
			else if ($node->nodeType == 1 && $node->nodeName == "retval") 
			{
				//$this->id = $node->textContent;
				return rawurldecode($node->textContent);
			}
		}
		return $cases;
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
		$url = $this->m_url;
		$url .= "&function=auth_challenge";

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

		$result_code = 0; //unknown
		$error_message = '';
		$customer_id = 0;

		$dom = new DomDocument();
		$dom->loadXml($resp); 
		
		/*echo '<pre>';
		echo htmlentities($resp);
		echo '</pre>';*/

		foreach ($dom->documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 ) 
			{

				switch( $node->nodeName )
				{
					case 'result_code';
						$result_code = rawurldecode($node->textContent);
					break;
					case 'error_message';
						$error_message = rawurldecode($node->textContent);
					break;
					case 'customer_id';
						$customer_id = rawurldecode($node->textContent);
					break;
				}
			}
		}
		
		
		switch( $result_code )
		{
			case '-1': // account not found
				$this->lastErrorMessage = $error_message;
				return 0;
			break;
			case '-10': // invalid password
				$this->lastErrorMessage = $error_message;
				return 0;
			break;
			case '1': // 
				$this->lastErrorMessage = '';
				return $customer_id;
			break;
			default: //  unknown
				$this->lastErrorMessage = 'Unknown result';
				return 0;
			break;
		}

		return 0;
		
	}
	
	// Register Customer
	function registerCustomer($username, $password, $attrib = array())
	{
		
		$url = $this->m_url;
		$url .= "&function=customer_register";
		
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
		
		if( $this->m_cid > 0 ){
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
		
		
		$result_code = 0; //unknown
		$error_message = '';
		
		$dom = new DomDocument();
		$dom->loadXml($resp); 
		foreach ($dom->documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 ) 
			{
				switch( $node->nodeName )
				{
					case 'result_code';
						$result_code = rawurldecode($node->textContent);
					break;
					case 'error_message';
						$error_message = rawurldecode($node->textContent);
					break;
				}
			}
		}
		
		
		switch( $result_code )
		{
			case '-1': // username existed
				$this->lastErrorMessage = $error_message;
				return -1;
			break;
			case '-10': // username existed
				$this->lastErrorMessage = $error_message;
				return -2;
			break;
			default: //  unknown
				if( $result_code>0 ){
					$this->lastErrorMessage = '';
					return $result_code;
				}else{
					$this->lastErrorMessage = 'Unknown result';
					return -1;
				}
			break;
		}


		return 0;
		
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
		$url = $this->m_url;
		$url .= "&function=auth_get_custid";

		$fields = 'username='.urlencode($username);
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
		$result_code = 0; //unknown
		$error_message = '';
		$customer_id = 0;

		$dom = new DomDocument();
		$dom->loadXml($resp); 
		foreach ($dom->documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 && $node->nodeName == "retval") 
				return rawurldecode($node->textContent);
		}

		return 0;
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
		$url = $this->m_url;
		$url .= "&function=auth_set_password";
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
		$result_code = 0; //unknown
		$error_message = '';
		$customer_id = 0;

		$dom = new DomDocument();
		$dom->loadXml($resp); 
		foreach ($dom->documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 && $node->nodeName == "retval") 
				return rawurldecode($node->textContent);
		}

		return 0;
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
		$url = $this->m_url;
		$url .= "&function=auth_challenge";

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

		$result_code = 0; //unknown
		$error_message = '';
		$customer_id = 0;

		$dom = new DomDocument();
		$dom->loadXml($resp); 
		
		foreach ($dom->documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 ) 
			{

				switch( $node->nodeName )
				{
					case 'result_code';
						$result_code = rawurldecode($node->textContent);
					break;
					case 'error_message';
						$error_message = rawurldecode($node->textContent);
					break;
					case 'customer_id';
						$customer_id = rawurldecode($node->textContent);
					break;
				}
			}
		}
		
		switch( $result_code )
		{
		case '-1': // account not found
			$this->lastErrorMessage = $error_message;
			return 0;
			break;
		case '1': // 
			$this->lastErrorMessage = '';
			return $customer_id;
			break;
		default: //  unknown
			$this->lastErrorMessage = 'Unknown result';
			return 0;
			break;
		}

		return 0;
		
	}
}
?>
