<?php
/*======================================================================================
	
	Module:		AntApi_Wiki	

	Purpose:	Remote API for ANT Infocenter handled like a wiki

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2011 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:	

	Variables:	

======================================================================================*/
class AntApi_Wiki extends AntApi_Object
{
	var $baseUrl = "/wiki/doc/"; // the url to use for linking published documents
	
	function __construct($server, $username, $password, $docid) 
	{
		parent::__construct($server, $username, $password, "infocenter_document", $docid);

		if ($docid)
			$this->open($docid);
	}

	function __destruct() 
	{
	}

	/*************************************************************************************
	*	Function:	setBaseUrl
	*
	*	Purpose:	Set the base url that will be used for linking documents
	*
	*	Params:		string $url = the url to use as a base for loading documents
	**************************************************************************************/
	public function setBaseUrl($url)
	{
		$this->baseUrl = $url;
	}

	/*************************************************************************************
	*	Function:	getBody	
	*
	*	Purpose:	Get and process the body for an infocenter_document as a wiki page
	**************************************************************************************/
	public function getBody()
	{
		$body = $this->getValue("body");

		$body = preg_replace('#\\[\\[([^|\\]]*)?\\|(.*?)\\]\\]#s', '<a href="'.$this->baseUrl.'$1">$2</a>', $body);
		//$body = preg_replace( '#\\[\\[(.*?)\\]\\]#s', '1 = $1', $body);

		return $body;
	}
}
