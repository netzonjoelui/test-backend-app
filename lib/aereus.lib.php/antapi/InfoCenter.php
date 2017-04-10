<?php
/*======================================================================================
	
	Module:		AntApi_InfoCenter	

	Purpose:	Remote API for ANT infocenter document library

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2011 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		

	Variables:	

======================================================================================*/
class AntApi_InfoCenter
{
	var $fid; // feed id
	var $username;
	var $password;
	var $server;
	var $lastErrorMessage;
	var $objList;
	var $groups = null; // Once categories are pulled, this will become an array
	var $rootGroup = null;
	var $rootGroupFound = false;
	
	function __construct($server, $username, $password, $rootGid=null) 
	{
		$this->username = $username;
		$this->password = $password;
		$this->server = $server;
		//$this->fid = $feed_id;

		$this->objList = new AntApi_ObjectList($this->server, $this->username, $this->password, "infocenter_document");
		if ($rootGid)
		{
			$this->objList->addCondition("and", "groups", "is_equal", $rootGid);
			$this->rootGroup = $rootGid;
		}
	}

	function __destruct() 
	{
	}

	/*************************************************************************************
	*	Function:	addCondition	
	*
	*	Purpose:	Add a condition to this query
	*
	*	Arguments:	fname:string - name of the property to set. Non-existant properties will
	*								ignored by ANT
	*				fval:string	 - value to set attribute to
	**************************************************************************************/
	function addCondition($blogic, $fieldname, $operator, $condvalue)
	{
		$this->objList->addCondition($blogic, $fieldname, $operator, $condvalue);
	}

	/*************************************************************************************
	*	Function:	addSortOrder	
	*
	*	Purpose:	Add sort order to this query
	*
	*	Arguments:	field:string		- name of field to sort by
	*				direction:string	- ascending or descending
	**************************************************************************************/
	function addSortOrder($field, $direction="ASC")
	{
		$this->objList->addSortOrder($field, $direction);
	}

	/*************************************************************************************
	*	Function:	getDocuments
	*
	*	Purpose:	Execute query and get document results
	*
	*	Arguments:	offset:int	- Start offset
	*				limit:int	- maximum number of objects to pull
	**************************************************************************************/
	function getDocuments($offset, $limit)
	{
		$ret = $this->objList->getObjects($offset, $limit);

		return $ret;
	}

	/*************************************************************************************
	*	Function:	getDocument	
	*
	*	Purpose:	Return a AntApi_Wiki object
	*
	**************************************************************************************/
	function getDocument($ind)
	{
		$prow = $this->objList->getObjectMin($ind);
		return new AntApi_Wiki($this->server, $this->username, $this->password, $prow['id']);
	}

	/*************************************************************************************
	*	Function:	getGroups
	*
	*	Purpose:	Get list of groups for this document library
	*
	**************************************************************************************/
	function getGroups()
	{
		$docObj = new AntApi_Object($this->server, $this->username, $this->password, "infocenter_document");
		$grps = $docObj->getGroupingData("groups");

		if (is_array($grps))
		{
			if ($this->rootGroup)
			{
				$this->groups = $this->findRootGroup($grps);
			}
			else
			{
				$this->groups = $grps;
			}
		}

		/*
		if (!is_array($this->groups))
		{
			$this->groups = array(); // Initialize

			$url = "http://".$this->server."/api/php/Object/getGroupings?auth=".base64_encode($this->username).":".md5($this->password);
			$url .= "&obj_type=infocenter_document";
			$url .= "&field=groups";

			$ch = curl_init($url); // URL of gateway for cURL to post to
			curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
			$resp = curl_exec($ch); //execute post and get results
			curl_close ($ch);

			if ($resp)
			{
				$grps = json_decode($resp);

				if (is_array($grps))
				{
					if ($this->rootGroup)
					{
						$this->groups = $this->findRootGroup($grps);
					}
					else
					{
						$this->groups = $grps;
					}
				}
				//$this->parseGroups($grps, $this->groups);
			}
		}
		 */

		return $this->groups;
	}

	/*************************************************************************************
	*	Function:	parseGroups
	*
	*	Purpose:	Traverse Groups and get all children from the root
	*
	**************************************************************************************/
	function findRootGroup($grps)
	{
		if (!is_array($grps))
			return null;

		foreach ($grps as $grp)
		{
			if ($grp->id == $this->rootGroup)
			{
				return $grp;
			}
			else if (is_array($grp->children))
			{
				$ret = $this->findRootGroup($grp->children);
				if ($ret)
					return $ret;
			}
		}

		return null;

		/*
		if (!is_array($documentElement->childNodes))
			return false;

		foreach ($documentElement->childNodes as $node) 
		{
			if ($node->nodeType == 1 && $node->nodeName == "value") 
			{
				$grp = new stdClass();
				$grp->id = $node->getAttribute("id");
				$grp->title = $node->getAttribute("title");
				$grp->name = $node->getAttribute("viewname");
				$grp->children = array();

				// Check for child groups
				foreach ($node->childNodes as $childNode) 
				{
					if ($childNode->nodeType == 1 && $childNode->nodeName == "children") 
						$this->parseGroups($childNode, $grp->children);
				}

				if ($this->rootGroup && !$this->rootGroupFound && $grp->id == $this->rootGroup)
				{
					$this->rootGroupFound = true;

					$parentGroup[] = $grp;

					break; // Skip all other elements at this level because root is here
				}
				else if (!$this->rootGroup || $this->rootGroupFound)
				{
					$parentGroup[] = $grp;
				}
			}
		}
		 */

		return true;
	}

	/*************************************************************************************
	*	Function:	getDocById
	*
	*	Purpose:	Return a AntApi_Wiki object
	*
	**************************************************************************************/
	function getDocById($did)
	{
		return new AntApi_Wiki($this->server, $this->username, $this->password, $did);
	}
}
