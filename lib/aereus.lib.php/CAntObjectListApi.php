<?php
/*======================================================================================
	
	Module:		CAntObjectListApi (DEPRICATED - please use antapi.php and CAntApi->getObjectList instead)

	Purpose:	Remote API for getting a lit of ANT objects

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Depends:	CAntObjectApi, CAntObjectApiStore

	Usage:		$olist = CAntObjectListApi($server, $username, $password, "customer");
				$olist->addCondition("and", "id", "is_equal", $custid);
				$olist->getObjects();
				if ($olist->getNumObjects())
					$obj = $olist->getObject(0);

	Variables:	

======================================================================================*/
class CAntObjectListApi
{
	var $m_user;
	var $m_pass;
	var $m_server;
	var $m_url;
	var $m_objectList;
	var $m_conditions;
	var $objDef = null;			// Object used to pull data definition
	var $obj_type;
	var $resultType = null;		// update | sync | or null for normal
	var $resultTypeArgs = null; // Optional argument value passed with result type like ts_last_updated=1/1/2010
	var $numTotalObjects = 0;

	function CAntObjectListApi($server, $username, $password, $obj_type, $obj=null)
	{
		global $ALIB_OBJAPI_LCLSTORE;

		$this->m_user = $username;
		$this->m_pass = $password;
		$this->m_server = $server;
		$this->obj_type = $obj_type;
		$this->m_objectList = array();
		$this->m_conditions = array();
		$this->m_orderby = array();

		// Set local object for pulling definition and store data
		if ($obj)
			$this->obj = $obj;
		else
			$this->obj = new CAntObjectApi($server, $username, $password, $obj_type);

		$this->m_url = "http://".$server."/objects/xml_query.php?auth=".base64_encode($username).":".md5($password)."&obj_type=$obj_type&updatemode=1";
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
		$condition = array();
		$condition['blogic'] = $blogic;
		$condition['field'] = $fieldname;
		$condition['operator'] = $operator;
		$condition['value'] = $condvalue;

		$this->m_conditions[] = $condition;
	}

	/*************************************************************************************
	*	Function:	addSortOrder	
	*
	*	Purpose:	Add a condition to this query
	*
	*	Arguments:	fname:string - name of the property to set. Non-existant properties will
	*								ignored by ANT
	*				fval:string	 - value to set attribute to
	**************************************************************************************/
	function addSortOrder($field, $direction="ASC")
	{
		$this->m_orderby[] = "$field $direction";
	}

	/*************************************************************************************
	*	Function:	setResultType	
	*
	*	Purpose:	Set a special query type
	*				normal 	= pull full details with response - all fields returned
	*				update 	= pull only id and revision with results
	*				sync 	= pull full details of items updated since $args
	*
	*	Arguments:	fname:string - name of the property to set. Non-existant properties will
	*								ignored by ANT
	*				fval:string	 - value to set attribute to
	**************************************************************************************/
	function setResultType($type, $args="")
	{
		$this->resultType = $type;
		$this->resultTypeArgs  = $args;
	}

	/*************************************************************************************
	*	Function:	setStoreSource	
	*
	*	Purpose:	Manually set the type of store to read from
	*				ant 	= pull data from ANT api
	*				elastic | pgsql | mysql | mongodb | etc.. = all pull local datastore
	*
	*	Arguments:	fname:string - name of the property to set. Non-existant properties will
	*								ignored by ANT
	*				fval:string	 - value to set attribute to
	**************************************************************************************/
	function setStoreSource($type)
	{
		return $this->obj->setStoreSource($type);
	}

	/*************************************************************************************
	*	Function:	getLocalStore
	*
	*	Purpose:	Set and return the appropriate local storage engine
	*
	*	Params:		string $type = storage type - if null then global $ALIB_OBJAPI_LCLSTORE is used
	**************************************************************************************/
	function getLocalStore($type=null)
	{
		return $this->obj->getLocalStore($type);
	}

	/*************************************************************************************
	*	Function:	getObjects
	*
	*	Purpose:	Execute query
	*
	*	Arguments:	offset:int	- Start offset
	*				limit:int	- maximum number of objects to pull
	**************************************************************************************/
	function getObjects($offset=0, $limit=null)
	{
		if ($this->obj->storeSource == "ant" || !$this->obj->storeSource)
		{
			return $this->getObjectsAnt($offset, $limit);
		}
		else
		{
			$store = $this->getLocalStore();
			$this->m_objectList = array(); // Clear any existing objects
			$this->numTotalObjects = $store->queryObjects($this->m_objectList, "", $this->m_conditions);
		}
	}

	/*************************************************************************************
	*	Function:	getObjectsAnt
	*
	*	Purpose:	Execute query through wapi to pull results directly from ANT
	*
	*	Arguments:	offset:int	- Start offset
	*				limit:int	- maximum number of objects to pull
	**************************************************************************************/
	function getObjectsAnt($offset=0, $limit=null)
	{
		$url = $this->m_url;
		if ($offset)
			$url .= "&offset=$offset";
		if ($limit)
			$url .= "&limit=$offset";
		if ($this->resultType)
			$url .= "&type=".$this->resultType;

		// Check for sync query and send lastupdated if available
		if ($this->resultType == "sync" && $this->resultTypeArgs)
			$url .= "&ts_lastsync=".$this->resultTypeArgs;

		$fields = "fval=0";
		for ($i = 0; $i < count($this->m_conditions); $i++)
		{
			$ind = $i+1;

			$fields .= "&conditions[]=$ind";
			$fields .= "&condition_blogic_$ind=" . urlencode($this->m_conditions[$i]['blogic']);
			$fields .= "&condition_fieldname_$ind=" . urlencode($this->m_conditions[$i]['field']);
			$fields .= "&condition_operator_$ind=" . urlencode($this->m_conditions[$i]['operator']);
			$fields .= "&condition_condvalue_$ind=" . urlencode($this->m_conditions[$i]['value']);
		}

		for ($i = 0; $i < count($this->m_orderby); $i++)
		{
			$fields .= "&order_by[]=".$this->m_orderby[$i];
		}
		
		/*echo $url."<br />";
		echo $url; exit();*/

		$ch = curl_init($url); // URL of gateway for cURL to post to
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
				switch($node->nodeName)
				{
				case 'num';
					$this->numTotalObjects = rawurldecode($node->textContent);
					break;
				case 'object';
					$obj = array();
					foreach ($node->childNodes as $objectAttrib)
					{
						if ($objectAttrib->nodeType == 1)
							$obj[$objectAttrib->nodeName] = rawurldecode($objectAttrib->textContent);
					}
					$this->m_objectList[] = $obj;
					break;
				}
			}
		}
	}

	/*************************************************************************************
	*	Function:	getNumObjects	
	*
	*	Purpose:	Get total number of objects
	*
	**************************************************************************************/
	function getNumObjects()
	{
		return count($this->m_objectList);
	}

	/*************************************************************************************
	*	Function:	getTotalNumObjects	
	*
	*	Purpose:	Get total number of objects
	*
	**************************************************************************************/
	function getTotalNumObjects()
	{
		return $this->numTotalObjects;
	}

	/*************************************************************************************
	*	Function:	getObject	
	*
	*	Purpose:	Get an object at the specificied index
	*
	*	Arguments:	idx:int	- Index of object to retrieve
	*
	**************************************************************************************/
	function getObject($idx)
	{
		if ($idx >= $this->getNumObjects())
			return null;

		if (!$this->m_objectList[$idx]["id"])
			return null;

		$obja = new CAntObjectApi($this->m_server, $this->m_user, $this->m_pass, $this->obj_type);
		$obja->open($this->m_objectList[$idx]["id"]);
		return $obja;
	}

	/*************************************************************************************
	*	Function:	getObjectMin
	*
	*	Purpose:	Just get the data array for this object, but do not create a class
	*
	*	Arguments:	idx:int	- Index of object to retrieve
	*
	**************************************************************************************/
	function getObjectMin($idx)
	{
		if ($idx >= $this->getNumObjects())
			return null;

		if (!$this->m_objectList[$idx]["id"])
			return null;

		return $this->m_objectList[$idx];
	}
}
