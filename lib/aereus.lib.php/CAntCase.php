<?php
/*======================================================================================
	
	Module:		CAntCase

	Purpose:	Remote API for ANT Cases

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		// Create a new instance of parser - The last two params are optional
				$antCase = new CAntCase("ant.aereus.com", "user", "pass");
				$antCase->setAttribute('title', "Problem");
				$antCase->setAttribute('description', "Describe case problem here");
				$caseid = $antCase->save();

	Variables:	

======================================================================================*/
class CAntCase
{
	var $id; // customer id
	var $m_url; // base URL
	var $m_urlGet; // base URL
	var $m_user;
	var $m_pass;
	var $lastErrorMessage;
	var $m_values; // Store set data (for saving onlychanged)
	var $m_attribs; // For now this will store retrieved data
	
	function CAntCase($server, $username, $password, $account="aereus") 
	{
		$this->m_url = "http://".$server."/project/wapi.php?auth=".base64_encode($username).":".md5($password);
		$this->m_urlGet = "http://".$server."/objects/xml_get_object.php?auth=".base64_encode($username).":".md5($password)."&obj_type=case";
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

	/*************************************************************************************
	*	Function:	open	
	*
	*	Purpose:	Open an existing customer, right now only the id is set which enforces an 
	*				update rather than an insert
	*
	*	Arguments:	cid:number - a valid ANT customer ID
	**************************************************************************************/
	function open($cid)
	{
		$this->id = $cid;
		$this->m_url .= "&id=".$cid;

		$ch = curl_init($this->m_urlGet."&oid=$cid"); // URL of gateway for cURL to post to
		//curl_setopt($ch, CURLOPT_PROXY,"localhost:80");
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$dom = new DomDocument();
		$dom->loadXml($resp); 
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
		$url .= "&function=save_case";
		if ($onlychange)
			$url .= "&onlychange=1";

		$fields = "";
		foreach( $this->m_values as $key => $value ) 
			$fields .= "$key=" . urlencode( $value ) . "&";

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
				$this->m_id = $node->textContent;
				return rawurldecode($node->textContent);
			}
		}

		return 0;
	}
}
?>
