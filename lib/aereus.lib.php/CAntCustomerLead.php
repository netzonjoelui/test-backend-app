<?php
/*======================================================================================
	
	Class:		CAntCustomerLead

	Purpose:	Remote API for ANT customer leads

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2009 Aereus Corporation. All rights reserved.
	
	Depends:	CAntCustomer.php

	Usage:		
				// Create a new instance of parser
				$lead = new CAntCustomerLead("https://[company].ant.aereus.com", "username", "pass", "account");
				$lead->setField('first_name', 'Joe');
				$lead->setField('last_name', 'Smith');
				$lead->saveLead();

	Fields:	
				queue_id	= // ANT system ID
				owner_id	= // ANT system ID
				source_id	= // ANT system ID
				class_id	= // ANT system ID
				status_id	= // ANT system ID
				rating_id	= // ANT system ID
				first_name	= text(256)
				last_name	= text(256)
				email		= text(256)
				company		= text(512)
				title		= text(256)
				website		= text(256)
				phone		= text(32)
				street		= text(256)
				street2		= text(256)
				city 		= text(256)
				state 		= text(256)
				zip 		= text(32)
				country 	= text
				notes 		= text
======================================================================================*/
class CAntCustomerLead
{
	var $m_url;
	var $m_id;
	var $m_values;
	
	function CAntCustomerLead($url, $username, $password, $account='') 
	{
		$this->m_url = $url . "/customer/wapi.php?auth=".base64_encode($username).":".md5($password);
		$this->m_values = array();
	}

	function __destruct() 
	{
	}

	function setAttribute($fname, $fval)
	{
		$this->m_values[$fname] = $fval;
	}

	// Set fields with an associated array
	function setAttributes($farr)
	{
	}

	function getAttributes($fname)
	{
	}

	function getAttribute($name)
	{
		return $this->m_attribs[$name];
	}

	// Open an existing lead
	function openLead($lid)
	{
	}

	// Save changes or enter a new lead
	function saveLead()
	{
		$url = $this->m_url;
		$url .= "&function=lead_save";

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
