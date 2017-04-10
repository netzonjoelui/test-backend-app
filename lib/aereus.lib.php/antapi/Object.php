<?php
/*======================================================================================
	
	Module:		AntApi_Object	

	Purpose:	Remote API for ANT objects

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		$objApi = new AntApi_Object("name.ant.aereus.com", "administrator", "Password1", "customer");
				$objApi->open($cid);
				$objApi->setValue("varname", "varval");
				$objApi->save();

	Variables:	

======================================================================================*/
if (!defined("ANTAPI_NOHTTPS"))
	define("ANTAPI_NOHTTPS", false);

class AntApi_Object
{
	/**
	 * AntApi object
	 *
	 * @var AntApi
	 */
	private $antapi = null;

	var $id; 					// object id
	var $m_resp; 				// raw response cache
	var $m_url; 				// base URL for posting data
	var $m_urlGet; 				// base URL for getting data
	var $m_server;
	var $m_user;
	var $m_pass;
	var $server;				// Updated variable name
	var $username;				// Updated variable name
	var $password;				// Updated variable name
	var $lastErrorMessage;
	var $m_values; 				// Store set data (for saving onlychanged)
	var $m_attribs; 			// For now this will store retrieved data
	var $m_attribFVals; 		// Store foreign values of fkey/object field types
	var $m_onformsaved = "";
	var $obj_type;
	var $xml_form_layout;
	var $m_fields;
	var $storeSource = "ant";	// ant (api default) | elastic | pgsql | mongodo - and any other type avail in obj_stores
    var $m_store = null;        // Local store instance
	var $m_antApiObj = null;	// Instance of Api Object

	/**
	 * Flag used to debug
	 *
	 * @var bool
	 */
	public $debug = false;
    
    /**
     * Flag used to run object index when saving
     *
     * @var bool
     */
    public $runIndexOnSave = false;
	
	/**
	 * Class constructor
	 *
	 * @param string $server The url of the antserver
	 * @param string $username The name of a user with API access
	 * @param string $password The password of the user in question
	 * @param string $obj_type Unique object type name like 'customer' or 'lead'
	 */
	function __construct($server, $username, $password, $obj_type) 
	{
		global $ANTAPI_STORE;
        
		$this->m_values = array();
		$this->m_attribs = array();
		$this->m_attribFVals = array();
		$this->m_fields = array();
		$this->obj_type = $obj_type;

		if ($ANTAPI_STORE)
			$this->storeSource = $ANTAPI_STORE;

		if ($server && $username && $password)
			$this->connect($server, $username, $password);

		$this->antapi = AntApi::getInstance($server, $username, $password);
	}

	/**
	 * Initialize connection variables
	 *
	 * @param string $server The url of the antserver
	 * @param string $username The name of a user with API access
	 * @param string $password The password of the user in question
	 */
	function connect($server, $username, $password)
	{		
        $this->m_url = "http://".$server."/controller/";
		$this->m_urlGet = "http://".$server."/objects/xml_get_object.php?auth=".base64_encode($username).":".md5($password)."&obj_type=".$this->obj_type;

		$this->m_server = $server;
		$this->m_user = $username;
		$this->m_pass = $password;
		$this->server = $this->m_server;
		$this->username = $this->m_user;
		$this->password = $this->m_pass;
	}

	function __destruct() 
	{
	}

	/**
	 * Set the value for an attribute
	 *
	 * @param string $fname The name of the property to set. Non-existant properties will be ingored
	 * @param string $fval The value to set attribute to
	 */
	function setValue($fname, $fval)
	{
		$this->m_values[$fname] = $fval;
	}

	/**
	 * Calling this function will append a value to the property array
	 *
	 * mVals are multi-values or an array of values assigned to each property
	 *
	 * @param string $fname The name of the property to set. Non-existant properties will be ingored
	 * @param string $fval The value to add to the array
	 */
	public function setMValue($name, $value)
	{
        /*if(!isset($this->m_values[$fname]))
            $this->m_values[$fname] = array();            
		else if(!is_array($this->m_values[$fname]))
			$this->m_values[$fname] = array();
            
		$this->m_values[$fname][] = $fval;*/
        
        // Do not allow null vales to be stored
        if ($value == "" || $value==NULL)
            return false;

        if (isset($this->m_values[$name]) && is_array($this->m_values[$name]))
        {
            $fFound = false;

            // Make sure multi-fkey is unique
            for ($i = 0; $i < count($this->m_values[$name]); $i++)
            {
                if ($this->m_values[$name][$i] == $value)
                    $fFound = true;
            }

            if (!$fFound)
            {
                $this->m_values[$name][] = $value;
            }
        }
        else
        {
            $this->m_values[$name] = array();
            $this->m_values[$name][0] = $value;
        }
	}

	/**
	 * Get the value for an object field
	 *
	 * @param string $name The name of the attribute to pull
	 * @param bool $foreign If set to full, try to pull the forign title/label of a foreign key
	 * @return mixed Either a string with the value of the field or an array of ids
	 */
	public function getValue($name, $foreign=false)
	{
        $ret = null;
        
        if(isset($this->m_values[$name]))
            $ret = $this->m_values[$name];
            
		if ($foreign)
			$ret = $this->getForeignValue($name);

		return $ret;
	}

	/**
	 * Get the value name for an attribute of a foreign key or object ref
	 *
	 * If called witht the id it will pull the label for a specific id, otherwise it will
	 * create a concatinated string with labels separated by comma ',' for each value
	 *
	 * @param string $name The name of the field to pull from
	 * @param string $id Optional id used to limit the label to a single id
	 */
	public function getForeignValue($name, $id="")
	{
		if (isset($this->m_attribFVals[$name]))
		{
			$ret = "";
			if ($id)
			{
				$ret = $this->m_attribFVals[$name][$id];
			}
			else if (is_array($this->m_attribFVals))
			{
				foreach ($this->m_attribFVals[$name] as $fid=>$flabel)
				{
					if ($ret) $ret .= ", ";
					$ret .= $flabel;
				}
			}
            
			return $ret;
		}
		else
			return $this->m_values[$name];
	}

	/**
	 * Open an existing object in ANT
	 *
	 * @param int $cid The id of the object to open
	 * @param bool $forceAnt If set to true then open will query object from ANT and not from local store
	 */
	function open($cid, $forceAnt = false)
	{
		$this->id = $cid;

		if ($this->storeSource == "ant" || !$this->storeSource || $forceAnt)
		{
			$this->openAnt($cid);
		}
		else
		{
			$store = $this->getLocalStore();
			if ($store)
			{
				$data = $store->openObject($this->obj_type, $cid);
                
                if(!is_array($data))
                    return;
                
				foreach ($data as $varname=>$varval)
				{
					$this->m_values[$varname] = $varval;

					if ($data[$varname . "_fval"])
						$this->m_attribFVals[$varname] = $data[$varname . "_fval"];
				}
			}
		}

		// Set id (if uname is used then id will be important to set)
		if ($this->m_values['id'])
			$this->id = $this->m_values['id'];

		// Used for derrieved classes
		if ($this->id)
			$this->loaded();
	}

	/**
	 * Open object by querying ANT API
	 *
	 * @param int $cid The id of the object to open
	 */
	function openAnt($cid)
	{
		$data = array("obj_type"=>$this->obj_type, "oid"=>$cid);
		$data = $this->sendRequest("Object", "getObject", $data);

		/*
        $url = $this->m_url . "Object/getObject?obj_type=" . $this->obj_type;
        $url .= "&auth=".base64_encode($this->username).":".md5($this->password);
		$url .= "&oid=" . $cid;

		$ch = curl_init($url); // URL of gateway for cURL to post to
		//curl_setopt($ch, CURLOPT_PROXY,"localhost:80");
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		//curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$data = json_decode($resp);
		*/
        
        if(sizeof($data) > 0)
        {
            foreach ($data as $fname=>$value)
            {
                if ($fname == "security")
                    continue;
                    
                if (substr($fname, -5) == "_fval" && (is_array($value) || is_object($value)))
                {
                    foreach ($value as $vid=>$vlbl)
                    {
                        $this->m_attribFVals[substr($fname, 0, -5)][$vid] = $vlbl;
                    }
                }
                else
                {
                    if (is_object($value) || is_array($value))
                    {
                        if (!isset($this->m_values[$fname]) || !is_array($this->m_values[$fname]))
                            $this->m_values[$fname] = array();
                        
                        $useKey = true; // As default, we will save the $array key value
                        
                        // Check if associative array
                        // If associative array, the array key values are always indexed [0, 1, 2, n]
                        if (array_keys($value) === range(0, count($value) - 1))
                            $useKey = false; // Lets use the array value instead of array key value
                            
                        foreach ($value as $vid=>$vlbl)
                        {
                            if($useKey)
                                $this->m_values[$fname][] = $vid;
                            else
                                $this->m_values[$fname][] = $vlbl;
                        }
                    }
                    else
                    {
                        $this->m_values[$fname] = $value;
                    }
                }
            }
        }
        
		// Set id (if uname is used then id will be important to set)
		if ($this->m_values['id'])
			$this->id = $this->m_values['id'];

		// Used for derrieved classes
		if ($this->id)
			$this->loaded();
	}

	/**
	 * Callback function used for derrived classes for loading additional data
	 */
	public function loaded()
	{
		// Should be overloaded by derrived classes
	}

	/**
	 * Get array of all fields
	 */
	public function getFields()
	{
		if (!is_array($this->m_fields) || !count($this->m_fields))
			$this->getDefinition();

		return $this->m_fields;
	}

	/**
	 * Get field definition by name
	 *
	 * @param string $fname The name of the field to get
	 */
	public function getField($fname)
	{
		if (!is_array($this->m_fields) || !count($this->m_fields))
			$this->getDefinition();

        if(isset($this->m_fields[$fname]))
		    return $this->m_fields[$fname];
        else
            return null;
	}

	/**
	 * Get the definition for this object
	 *
	 * @param bool $forceUpdate If set to true then local store definition will be updated
	 */
	public function getDefinition($forceUpdate=false)
	{
		$xmlDef = "";

		// First check if we can get from the local store
		$store = $this->getLocalStore();
		if ($store && !$forceUpdate)
			$xmlDef = $store->getValue("/objects/defs/" . $this->obj_type);

		if (!$xmlDef || $forceUpdate)
		{
			$url = "http://".$this->m_server."/objects/xml_get_objectdef.php?auth=".base64_encode($this->m_user).":".md5($this->m_pass);
			$url .= "&oname=".$this->obj_type."&getpubfrm=1";

			$ch = curl_init($url); // URL of gateway for cURL to post to
			//curl_setopt($ch, CURLOPT_PROXY,"localhost:80");
			curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
			//curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
			$xmlDef = curl_exec($ch); //execute post and get results
			curl_close ($ch);

			if ($store)
				$store->putValue("/objects/defs/" . $this->obj_type, $xmlDef);

			$forceUpdate = true;
		}

		// Parse definition
		$dom = new DomDocument();
		$dom->loadXml($xmlDef); 
		foreach ($dom->documentElement->childNodes as $nodes) 
		{
			if ($nodes->nodeType == 1)
			{
				switch ($nodes->nodeName)
				{
				case 'fields':
					foreach ($nodes->childNodes as $fieldNodes) 
					{
						if ($fieldNodes->nodeType == 1)
						{
							if ($fieldNodes->nodeName == "field")
							{
								$field = array();
								$fname = "";
								foreach ($fieldNodes->childNodes as $fnode) 
								{
									if ($fnode->nodeType == 1)
									{
										if ($fnode->nodeName == "name")
											$fname = rawurldecode($fnode->textContent);

										$field[$fnode->nodeName] = rawurldecode($fnode->textContent);
									}
								}
								if ($fname)
									$this->m_fields[$fname] = $field;
							}
						}
					}
					break;

				case 'form_layout_public':
					$this->xml_form_layout = rawurldecode($nodes->textContent);
					break;
				}
			}
		}

		// If we are working with a local store, then we should cache groupings
		if ($store && $forceUpdate)
		{
			foreach ($this->m_fields as $fieldName=>$field)
			{
				if ($field['type'] == "fkey" || $field['type'] == "fkey_multi")
				{
					// Clear
					$store->putValue("/objects/groupings/" . $this->obj_type . "/" . $fieldName, "");

					// Now get groupings from ANT api
					$groupingData = $this->getGroupingData($fieldName);
					if (is_array($groupingData))
					{
						$store->putValue("/objects/groupings/" . $this->obj_type . "/" . $fieldName, json_encode($groupingData));
					}
				}
			}
		}
	}
	
	/**
	 * @depricated This is now the save as save
	 */
	public function saveChanges()
	{
		return $this->save();
	}

	/**
	 * Save object to ANT
	 */
	public function save()
	{
		$ret = 0;

		$data = array("obj_type" => $this->obj_type);

		if ($this->id)
            $data["oid"] = $this->id;

		foreach($this->m_values as $key=>$value) 
		{
			// Handle multi-values
			if (is_array($value))
			{
				$data[$key] = array();
				foreach ($value as $subval)
					$data[$key][] = $subval;
			}
			else
			{
				$data[$key] = $value;
			}
		}
        
		$resp = $this->sendRequest("Object", "saveObject", $data, false); // do not decode the response yet

		/*
		$ret = 0;
        		
        $url = $this->m_url . "Object/saveObject?obj_type=" . $this->obj_type;
        $url .= "&auth=".base64_encode($this->username).":".md5($this->password);
                
		if ($this->id)
            $url .= "&oid=" . $this->id;

		$fields = "";
		foreach( $this->m_values as $key => $value ) 
		{
			// Handle multi-values
			if (is_array($value))
			{
				foreach ($value as $subval)
					$fields .= $key."[]=" . urlencode($subval) . "&";
			}
			else
			{
				$fields .= "$key=" . urlencode($value) . "&";
			}
		}

		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		$this->m_resp = $resp;
		curl_close ($ch);
		 */

		try
		{			
            $ret = json_decode($resp, true);

            if(!is_array($ret) && $ret > 0)
			{
                $this->id = $ret;
				// Refresh to get default values
				$this->open($this->id, true);
			}
		}
		catch (Exception $e) 
		{
			echo "AntApi_Object::save: ".$e->getMessage()." ------ ".$resp;
		}

		// Update local store if exists
		if ($this->m_store && $this->id)
			$this->m_store->storeObject($this);

		return $ret;
	}

	/**
	 * Set deleted flag for this object
	 */
	public function remove()
	{        
		if (!$this->id)
			return false;

		// Update local store if exists
		if ($this->m_store)
			$this->m_store->removeObject($this->id);
        
        $url = $this->m_url . "Object/deleteObject?obj_type=" . $this->obj_type;
        $url .= "&auth=".base64_encode($this->username).":".md5($this->password);
        
		if ($this->id)
			$url .= "&oid=".$this->id;

		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		//curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		# curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		$this->m_resp = $resp;
		curl_close ($ch);

		try
		{            
			$ret = json_decode($resp, true);            
            return $ret;
		}
		catch (Exception $e) 
		{
			echo "AntApi_Object::remove: ".$e->getMessage()." ------ ".$resp;
		}

		return 0;
	}

	/**
	 * Manually set the type of store to read from
	 *
	 * ant = pull data directly from ANT api
	 * elastic | pgsql | mysql | mongodb = pull objects from local datastore
	 *
	 * @type string $type Name of the object store
	 */
	public function setStoreSource($type)
	{
		if ($type)
			$this->storeSource = $type;
            
		switch ($this->storeSource)
		{
		case 'elastic':
			$this->m_store = new AntApi_ObjectStore_Elastic();
			break;
		case 'pgsql':
			$this->m_store = new AntApi_ObjectStore_Pgsql();
            $this->m_store->antApiObj = new AntApi($this->server, $this->username, $this->password);
			break;
		}

		return $this->m_store;
	}

	/**
	 * Set and return the local storage engine (if set)
	 *
	 * @param string $type Optional type to set if not already set
	 */
	public function getLocalStore($type=null)
	{
		if ($this->m_store != null && ($type==null || $this->storeSource == $type))
			 return $this->m_store;
		else
			return $this->setStoreSource($type);
	}

	/**
	 * Synchronze data between ant and local store
	 *
	 * If a partner id is passed, then the object sync backend will be used which watches for
	 * changes in Netric and will send incremental updates.
	 *
	 * @param string $partnerId A unique partnerships used to make sync more efficient
	 * @param array $conditions Array of filter conditiosn for pulling - array(array('blogic', 'field', 'operator', 'value))
	 * #param string $objId If set then synchronize a single object
	 */
	public function syncLocalWithAnt($partnerId="", $conditions=array(), $objId=null)
	{
		$store = $this->getLocalStore();
		if ($store)
		{
			// Force update of local definition cache
			$this->getDefinition(true);

			if ($objId)
			{
				$obj = new AntApi_Object($this->server, $this->username, $this->password, $this->obj_type);        
				$obj->open($objId, true); // second param forces remote api query

				// Update or delete object in local store
				if (!$obj->getValue("id") || $obj->getValue("f_deleted") == "t")
				{
					$store->removeObject($this->obj_type, $objId);
				}
				else
					$store->storeObject($obj);

				return $objId;
			}
			else
			{
				// Synchronize list of objects
				if ($partnerId)
					$store->syncWithAntOSync($this->obj_type, $partnerId, $this->server, $this->username, $this->password);
				else
					$store->syncLocalWithAnt($this->obj_type, $this->server, $this->username, $this->password, $conditions);

				return 1;
			}
		}

		return -1;
	}

	/*************************************************************************************
	*	Function:	onFormSave	
	*
	*	Purpose:	Set the javascript function that will be called on saved
	**************************************************************************************/
	function onFormSave($act)
	{
		$this->m_onformsaved = $act;
	}

	/*************************************************************************************
	*	Function:	getFormSubmit	
	*
	*	Purpose:	Return the function name to submit a form for this object
	**************************************************************************************/
	function getFormSubmitFunct()
	{
		return "submitAntObjForm()";
	}

	/*************************************************************************************
	*	Function:	createForm	
	*
	*	Purpose:	Creat web form
	**************************************************************************************/
	function createForm()
	{
		global $_POST, $_SERVER;

		$this->getDefinition();

		if ($_POST['save_ant_obj'])
		{
			foreach($this->m_fields as $fname=>$field)
			{
				switch ($field['type'])
				{
				case 'text':
				case 'integer':
					$this->setValue($fname, $_POST[$fname]);
					break;
				case 'bool':
					$this->setValue($fname, isset($_POST[$fname])?'t':'f');
					break;
				}
			}
			$this->saveChanges();

			echo "<script type='text/javascript'>
					".$this->m_onformsaved.";
				  </script>";
		}
		else
		{
			echo "<script type='text/javascript'>
					function submitAntObjForm()
					{
						document.frmAntObj.submit();
					}
				  </script>";

			//echo "<div><textarea style='width:500px;height:300px;'>$this->xml_form_layout</textarea></div>";

			echo "<form method='post' name='frmAntObj' action=\"".$_SERVER['REQUEST_URI']."\">";
			echo "<input type='hidden' name='save_ant_obj' value='1'>";
			echo "<table>";

			if ($this->xml_form_layout && $this->xml_form_layout!="*")
			{
				$dom = new DomDocument();
				$dom->loadXml($this->xml_form_layout); 
				//echo "<tr><td><textarea style='width:500px;height:300px;'>";
				$this->formBuilder($dom->documentElement);
				//echo "</textarea></td></tr>";
			}
			else
			{
				foreach($this->m_values as $key=>$value)
				{
					echo "<tr><td>".$key."</td><td><input type='text' name=\"$key\" value=\"$value\" /></td></tr>";
				}
			}
			echo "</table>";
			echo "</form>";
		}
	}

	/*************************************************************************************
	*	Function:	formBuilder	
	*
	*	Purpose:	Build form from xml
	**************************************************************************************/
	function formBuilder($nodes, $wasrow=false)
	{
		global $_POST;


		foreach ($nodes->childNodes as $node) 
		{
			if ($node->nodeType == 1)
			{
				if ($node->nodeName!="row" && $node->nodeName!="column" && !$wasrow)
					echo "<tr valign='top'>";

				switch ($node->nodeName)
				{
				case 'tab':
					break;
				case 'plugin':
					break;
				case 'objectsref':
					break;
				case 'all_additional':
					break;
				case 'spacer':
					echo "<div style='height:10px;'></div>";
					break;
				case 'row':
					echo "<tr valign='top'>";
					$this->formBuilder($node, true);
					echo "</tr>";
					break;
				case 'column': // cell
					echo "<td><table>";
					$this->formBuilder($node);
					echo "</table></td>";
					break;
				case 'fieldset':
					$name = rawurldecode($node->getAttribute("name"));
					echo "<td>";
					echo "<fieldset>";
					echo "<legend>$name</legend><table>";
					$this->formBuilder($node);
					echo "</table></fieldset>";
					echo "</td>";
					break;
				case 'field':
					echo "<td>";
					$this->formBuilderPrintField($node);
					echo "</td>";
					break;
				}

				if ($node->nodeName!="row" && $node->nodeName!="column" && !$wasrow)
					echo "</tr>";
			}
		}	
	}

	/*************************************************************************************
	*	Function:	formBuilderPrintField	
	*
	*	Purpose:	Print out field form
	**************************************************************************************/
	function formBuilderPrintField($node)
	{
		global $_POST;

		$hidelabel = ($node->getAttribute("hidelabel")=='t') ? true : false;
		$multiline = ($node->getAttribute("multiline")=='t') ? true : false;

		$fname = rawurldecode($node->getAttribute("name"));
		$field = $this->m_fields[$fname];

		if ($_POST[$fname])
			$val = $_POST[$fname];
		else
			$val = $this->getValue($fname);

		if ($field)
		{
			echo "<table>";
			echo "<tr>";

			if (!$hidelabel)
			{
				echo "<td style='width:200px;padding:0px;'>";
				echo $field['title'].":";
				echo "</td>";
			}

			echo "<td style='padding:0px;'>";
			switch ($field['type'])
			{
			case 'bool':
				echo "<input type='checkbox' name='$fname' value='$val' ".(($val=='t')?'checked':'').">";
				break;
			case 'integer':
			case 'text':
				echo "<input type='text' name='$fname' value=\"$val\">";
				break;
			}
			echo "</td>";

			echo "</tr>";
			echo "</table>";
		}
	}
    
    /**
     * Forces to save Object in local store
     */
    public function forceSaveLocal()
    {
        $store = $this->getLocalStore();
        if ($store)
            $store->storeObject($this);
    }

	/**
	 * Get data for a grouping field (fkey)
	 *
	 * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
	 * @param array $conditions Array of conditions used to slice the groupings
	 */
	public function getGroupingData($fieldName, $conditions=array(), $filter=array())
	{
		$conditions['field'] = $fieldName;
		$conditions['obj_type'] = $this->obj_type;

		// Get from local store if exists
		$store = $this->getLocalStore();
		if ($store)
		{
			$groupingDataStr = $store->getValue("/objects/groupings/" . $this->obj_type . "/" . $fieldName);
			if ($groupingDataStr != "") // if empty array cached will = "[]" so empty means not ever initialized
			{
				$groupingData = json_decode($groupingDataStr);
				return $groupingData;
			}
		}

		return $this->sendRequest("Object", "getGroupings", $conditions);
	}

	/**
	 * Internal function for sending and receiving data
	 *
	 * @param string $controller The controller name to call
	 * @param string $action The action function to call
	 * @param array $data Data to be sent (POST) to the server
	 * @param bool $decode If try then json decode result
	 */
	protected function sendRequest($controller, $action, $data, $decode=true)
	{
        $this->antapi->runIndexOnSave = $this->runIndexOnSave;
		$ret = $this->antapi->sendRequest($controller, $action, $data);
		return ($decode) ? json_decode($ret) : $ret;
	}
}

/* The below is used only for backwards compatibility */
class CAntApiObject extends AntApi_Object
{
}
?>
