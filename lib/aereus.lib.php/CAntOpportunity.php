<?php
/*======================================================================================
	
	Module:		CAntOpportunity

	Purpose:	Remote API for ANT Customers

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		// Create a new instance of parser - The last two params are optional
				$antOpp = new CAntOpportunity("ant.aereus.com", "user", "pass");
				$antOpp->setAttribute('first_name', "API");
				$antOpp->setAttribute('last_name', "Test");
				$antOpp->setMvalAttribute('groups', "213");
				$custid = $antOpp->save();

	Variables:	

======================================================================================*/
class CAntOpportunity
{
	var $m_url;
	var $m_user;
	var $m_pass;
	
	function CAntOpportunity($server, $username, $password, $account="aereus") 
	{
		$this->m_url = "https://".$server."/customer/wapi.php?auth=".base64_encode($username).":".md5($password);
	}

	function __destruct() 
	{
	}

	function setAttribute($fname, $fval)
	{
		$this->m_values[$fname] = $fval;
	}

	function setMvalAttribute($fname, $fval)
	{
		$this->m_values[$fname."[]"] = $fval;
	}

	function getAttribute($name)
	{
		return $this->m_attribs[$name];
	}

	// Open an existing lead
	function open($cid)
	{
	}

	// Save changes or enter a new lead
	function save()
	{
		$url = $this->m_url;
		$url .= "&function=opportunity_save";

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
