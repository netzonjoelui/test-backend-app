<?php
/*======================================================================================
	
	Module:		CAntObjectApi (DEPRICATED - please use antapi.php and CAntApi->getObject instead)

	Purpose:	Remote API for ANT objects

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		$objApi = new CAntObjectApi("name.ant.aereus.com", "administrator", "Password1", "customer");
				$objApi->open($cid);
				$objApi->setValue("varname", "varval");
				$objApi->save();

	Variables:	

======================================================================================*/
class CAntObjectApi
{
	var $id; 					// object id
	var $m_resp; 				// raw response cache
	var $m_url; 				// base URL for posting data
	var $m_urlGet; 				// base URL for getting data
	var $m_server;
	var $m_user;
	var $m_pass;
	var $lastErrorMessage;
	var $m_values; 				// Store set data (for saving onlychanged)
	var $m_attribs; 			// For now this will store retrieved data
	var $m_attribFVals; 		// Store foreign values of fkey/object field types
	var $m_onformsaved = "";
	var $obj_type;
	var $xml_form_layout;
	var $m_fields;
	var $storeSource = "ant";	// ant (api default) | elastic | pgsql | mongodo - and any other type avail in obj_stores
	var $m_store = null;		// Local store instance
	
	function CAntObjectApi($server, $username, $password, $obj_type) 
	{
		global $ALIB_OBJAPI_LCLSTORE;

		$this->m_values = array();
		$this->m_attribs = array();
		$this->m_attribFVals = array();
		$this->m_fields = array();
		$this->obj_type = $obj_type;

		if ($ALIB_OBJAPI_LCLSTORE)
			$this->storeSource = $ALIB_OBJAPI_LCLSTORE;

		if ($server && $username && $password)
			$this->connect($server, $username, $password);
	}

	function connect($server, $username, $password)
	{		
        $this->m_url = "http://".$server."/controller/Object/";
		$this->m_urlGet = "http://".$server."/objects/xml_get_object.php?auth=".base64_encode($username).":".md5($password)."&obj_type=".$this->obj_type;

		$this->m_server = $server;
		$this->m_user = $username;
		$this->m_pass = $password;
	}

	function __destruct() 
	{
	}


	/*************************************************************************************
	*	Function:	setValue	
	*
	*	Purpose:	Set the value for an attribute
	*
	*	Arguments:	fname:string - name of the property to set. Non-existant properties will
	*								ignored by ANT
	*				fval:string	 - value to set attribute to
	**************************************************************************************/
	function setValue($fname, $fval)
	{
		$this->m_values[$fname] = $fval;
	}

	/*************************************************************************************
	*	Function:	setMValue	
	*
	*	Purpose:	MVals are multi-values or one/many to many relationships shuch as groups etc.
	*
	*	Arguments:	fname:string - name of the property to set. Non-existant properties will
	*								ignored by ANT
	*				fval:fval	 - value to set attribute to
	**************************************************************************************/
	function setMValue($fname, $fval)
	{
		if (!is_array($this->m_values[$fname]))
			$this->m_values[$fname] = array();
		$this->m_values[$fname][] = $fval;
	}

	/*************************************************************************************
	*	Function:	getValue	
	*
	*	Purpose:	Get the value for an attributed
	*
	*	Arguments:	fname:string - name of the property to get. Non-existant properties will
	*								ignored by ANT
	**************************************************************************************/
	function getValue($name, $foreign=false)
	{
		return $this->m_values[$name];
	}

	/*************************************************************************************
	*	Function:	getForeignValue
	*
	*	Purpose:	Get the value name for an attribute of a foreign key or object ref
	*
	*	Arguments:	fname:string - name of the property to get. Non-existant properties will
	*								ignored by ANT
	**************************************************************************************/
	function getForeignValue($name)
	{
		if (isset($this->m_attribFVals[$name]))
			return $this->m_attribFVals[$name];
		else
			return $this->m_values[$name];
	}

	/*************************************************************************************
	*	Function:	open	
	*
	*	Purpose:	Open an existing object, right now only the id is set which enforces an update rather than an insert
	*
	*	Arguments:	cid:number - a valid ANT customer ID
	**************************************************************************************/
	function open($cid)
	{
		$this->id = $cid;

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
		foreach ($dom->documentElement->childNodes as $fieldnode) 
		{
			//if node is an element (nodeType == 1) loop further
			if ($fieldnode->nodeType == 1)
			{
				// Check if we are in a multi-value
				if (is_array($fieldnode->childNodes) && count($fieldnode->childNodes))
				{
					foreach ($fieldnode->childNodes as $mval) 
					{
						if ($mval->nodeType == 1)
						{
							if (!is_array($this->m_values[$fieldnode->nodeName]))
								$this->m_values[$fieldnode->nodeName] = array();

							if ($mval->getAttribute("key"))
								$this->m_values[$fieldnode->nodeName][] = rawurldecode($mval->getAttribute("key"));

							$this->m_attribFVals[$fieldnode->nodeName] = rawurldecode($mval->textContent);
						}
						else // Not a multi-value. If fkey then name will be populated for a label
						{
							$this->m_values[$fieldnode->nodeName] = rawurldecode($fieldnode->textContent);
							if ($fieldnode->getAttribute("name"))
								$this->m_attribFVals[$fieldnode->nodeName] = rawurldecode($fieldnode->getAttribute("name"));
						}
					}
				}
				else // Not a multi-value. If fkey then name will be populated for a label
				{
					$this->m_values[$fieldnode->nodeName] = rawurldecode($fieldnode->textContent);
					if ($fieldnode->getAttribute("name"))
						$this->m_attribFVals[$fieldnode->nodeName] = rawurldecode($fieldnode->getAttribute("name"));
				}
			}
		}
	}

	/*************************************************************************************
	*	Function:	getFields	
	*
	*	Purpose:	Return array of fields
	**************************************************************************************/
	function getFields()
	{
		if (!is_array($this->m_fields) || !count($this->m_fields))
			$this->getDefinition();

		return $this->m_fields;
	}

	/*************************************************************************************
	*	Function:	getField
	*
	*	Purpose:	Return a field definition by name
	**************************************************************************************/
	function getField($fname)
	{
		if (!is_array($this->m_fields) || !count($this->m_fields))
			$this->getDefinition();

		return $this->m_fields[$fname];
	}

	/*************************************************************************************
	*	Function:	getDefinition	
	*
	*	Purpose:	Open the definition file from ANT
	*
	*	Arguments:	
	**************************************************************************************/
	function getDefinition()
	{
		$url = "http://".$this->m_server."/objects/xml_get_objectdef.php?auth=".base64_encode($this->m_user).":".md5($this->m_pass);
		$url .= "&oname=".$this->obj_type."&getpubfrm=1";

		$ch = curl_init($url); // URL of gateway for cURL to post to
		//curl_setopt($ch, CURLOPT_PROXY,"localhost:80");
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		$dom = new DomDocument();
		$dom->loadXml($resp); 
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
		$ret = 0;
        		
		$url = $this->m_url . "saveObject?obj_type=".$this->obj_type."&auth=".base64_encode($this->m_user).":".md5($this->m_pass);
		if ($onlychange)
			$url .= "&onlychange=1";
		if ($this->id)
			$url .= "&oid=".$this->id;
            
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

		try
		{
			$ret = json_decode($resp);
		}
		catch (Exception $e) 
		{
			echo "CAntObjectApi::save: ".$e->getMessage()." ------ ".$resp;
		}

		// Update local store if exists
		if ($this->m_store && $this->id)
			$this->m_store->storeObject($this);

		return $ret;
	}

	/*************************************************************************************
	*	Function:	remove	
	*
	*	Purpose:	Set deleted flag for this object
	*
	*	Arguments:	
	**************************************************************************************/
	function remove()
	{
		if (!$this->id)
			return false;

		// Update local store if exists
		if ($this->m_store)
			$this->m_store->removeObject($this->id);
        		
        $url = $this->m_url . "deleteObject?obj_type=".$this->obj_type."&auth=".base64_encode($this->m_user).":".md5($this->m_pass);
		if ($this->id)
			$url .= "&oid=".$this->id;
            
		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		$this->m_resp = $resp;
		curl_close ($ch);

		try
		{
			$ret = json_decode($resp);
		}
		catch (Exception $e) 
		{
			echo "CAntObjectApi::remove: ".$e->getMessage()." ------ ".$resp;
		}

		return 0;
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
		if ($type)
			$this->storeSource = $type;

		switch ($this->storeSource)
		{
		case 'elastic':
			$this->m_store = new CAOAS_elastic($this);
			break;
		case 'pgsql':
			$this->m_store = new CAOAS_pgsql($this);
			break;
		}

		return $this->m_store;
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
		if ($this->m_store != null && ($type==null || $this->storeSource == $type))
			 return $this->m_store;
		else
			return $this->setStoreSource($type);
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
}
?>
