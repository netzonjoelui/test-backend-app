<?php
/**
 * @depricated This has been replaced with EntityDefinition but is left in place for backwards compatibility
 * Hanlde object defintions like fields and name
 *
 * @category  Object
 * @package   Definition
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("settings/settings_functions.php");
require_once("lib/aereus.lib.php/CCache.php");
require_once('lib/ServiceLocatorLoader.php');

define('OBJ_TYPE_TEXT', 'text');
define('OBJ_TYPE_INT', 'integer');
//define('OBJ_TYPE_REAL', 'real');
define('OBJ_TYPE_REAL', 'double');

$G_CACHE_ANTOBJFDEFS = array();
$G_CACHE_ANTOBJTYPES = array();

// Field masks
$FIELD_MASKS = array();
// Dates and times
$FIELD_MASKS['date'] = array("mm/dd/yyyy ", "mm/dd/yyyy");
$FIELD_MASKS['time'] = array("12", "24");
// text fields
$FIELD_MASKS['text']['phone'] = array("(nnn) nnn-nnnn", "+n (nnn) nnn-nnnn", "nnn.nnn.nnnn", "nnn-nnn-nnnn");

/*
Field Masks

phone_dash = (nnn) nnn-nnnn

money_us
money_eur

date_mmddyyyy
date_yyyy-mm-dd

time_12
time_24

timestamp_mmddyyyy_12
timestamp_yyyy-mm-dd_24
*/

class CAntObjectFields
{
	var $fields;
	var $dbh;
	var $object_type;
	var $listTitle;
	var $listViewDefaults;
	var $revision;
	var $systemViews;
	var $cache;
	var $default_form_xml;
	var $user;
	var $object_table;
	var $isPrivate;
	var $inheritDaclRef; // Define a field reference to inherit permissions from if set like cases and projects
	var $recurRules = null;
	var $aggregates = array(); // Optionally aggregate object reference fields
	var $otid = null;

	/**
	 * Is a system object
	 *
	 * @var {bool}
	 */
	public $fSystem = true; // Assume true

	/**
	 * Optional icon name
	 *
	 * Object icons are all stored in /images/icons/objects/ and must have 16, 24, 32, and 48 px variants
	 * named after the object name like. customer_16.png, customer_32.png etc...
	 *
	 * @var {string}
	 */
	public $icon = "";

	/**
	 * Flag if we are using a custom table
	 *
	 * True if we are using a table that is not part of the objects partitions
	 *
	 * @var bool
	 */
	public $useCustomTable = false;

	/**
	 * The default activity level to use when working with this object type
	 *
	 * @var int
	 */
	public $defaultActivityLevel = 3;

	/**
	 * Unique name settings string
	 *
	 * If empty then uname will not be generated automatically and id will be used
	 *
	 * @var string
	 */
	public $unameSettings = "";

	/**
	 * EntityDefinition is replacing CAntObjectFields
	 *
	 * @var EntityDefinition
	 */
	public $entityDefinition = null;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param string $obj_name The object type name we are working with
	 */
	function __construct(CDatabase $dbh, $obj_name)
	{
		global $G_CACHE_ANTOBJTYPES, $G_CACHE_ANTOBJFDEFS;

		$this->dbh = $dbh;
		$this->systemViews = array();
		$this->detailViewFields = array(); // Fields to pull if in a condinced detail view (preview mode)
		$this->fields = array();
		$this->cache = CCache::getInstance();
		//$this->cache->setUseLocal(true); // reduce calls to memcached if we can help it
		$this->default_form_xml = "*";
		$this->default_form_mobile_xml = "*";
		$this->default_form_infobox_xml = "*";
		$this->user = null;
		$this->object_table = null;
		$this->parentField = null;
		$this->inheritDaclRef = null;
		$this->childDacls = array();
		$this->object_type = $obj_name;
		$this->listTitle = "name";
		$this->isPrivate = false;
		$this->listViewDefaults = array();
		$obj_views = null;

		// Load new EntityDefinition
        $sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
		$this->entityDefinition = $sl->get("EntityDefinitionLoader")->get($obj_name);
        
		// Replace the below basic getters
		$this->revision = $this->entityDefinition->revision;
		$this->otid = $this->entityDefinition->getId();
		$this->object_table = $this->entityDefinition->getTable();
		$this->fSystem = $this->entityDefinition->system;

		/*
		// Get fieldset revision
		if (isset($G_CACHE_ANTOBJTYPES[$this->dbh->dbname][$obj_name]) && $G_CACHE_ANTOBJTYPES[$this->dbh->dbname][$obj_name]!=false)
		{
			$this->revision = $G_CACHE_ANTOBJTYPES[$this->dbh->dbname][$obj_name][0]["revision"];
			$this->otid = $G_CACHE_ANTOBJTYPES[$this->dbh->dbname][$obj_name][0]["id"];
			$this->object_table = $G_CACHE_ANTOBJTYPES[$this->dbh->dbname][$obj_name][0]["object_table"];
			$this->fSystem = ($G_CACHE_ANTOBJTYPES[$this->dbh->dbname][$obj_name][0]["f_system"] == 't') ? true : false;
		}
		else
		{
			$result_arr = $this->cache->get($this->dbh->dbname."/objectdefs/" . $this->object_type . "/base");
			if ($result_arr)
			{
				$this->revision = $result_arr["revision"];
				$this->otid = $result_arr["id"];
				$this->object_table = $result_arr["object_table"];
				$this->fSystem = ($result_arr["f_system"] == 't') ? true : false;
			}
			else
			{
				$result = $dbh->Query("select id, object_table, revision, title, object_table, f_system
										from app_object_types where name='$obj_name'");
				if ($dbh->GetNumberRows($result))
				{
					$row = $dbh->GetRow($result, 0);
					$this->revision = $row["revision"];
					$this->otid = $row["id"];
					$this->object_table = $row["object_table"];
					$G_CACHE_ANTOBJTYPES[$this->dbh->dbname][$obj_name][0] = $row;
					$this->cache->set($this->dbh->dbname."/objectdefs/" . $this->object_type . "/base", $row);
				}
			}
		}			
		*/

		// Set default uname settings for custom objects - eventually we want to put this in object types table
		if (!$this->fSystem && !$this->unameSettings)
			$this->unameSettings = "name";
		
		if (!$this->otid)
			return; // If not in db then error

		/*
		// Set table if we are not using a custom table
		if (!$this->object_table)
		{
			$this->object_table = "objects_" . $this->object_type;

			// If revision is 0 then the table has not yet been created
			if ($this->revision <= 0)
			{
				$this->createObjectTable();
				$this->verifyDefaultFields(); // make sure object type definition has default fields

				$this->clearCache();
				$this->dbh->Query("update app_object_types set revision='1' where id='".$this->otid."'");
				$this->revision = 1;
			}
		}
		else
		{
			$this->useCustomTable = true;
		}
		 */
		$this->useCustomTable = $this->entityDefinition->isCustomTable();

		$det_view = null;
		
		 // Missing Variables. Need to define as null to fix the exception error.
		$det_view_fields = null;
		$child_dacls = null;
		$default_form_xml = $this->entityDefinition->getForm("default");;
		$default_form_mobile_xml = $this->entityDefinition->getForm("mobile");
		$default_form_infobox_xml = $this->entityDefinition->getForm("infobox");
		$default_form_popup_xml = null;

		$this->aggregates = $this->entityDefinition->aggregates;
		$this->defaultActivityLevel = $this->entityDefinition->defaultActivityLevel;
		$this->isPrivate = $this->entityDefinition->aggregates;
		$this->recurRules = $this->entityDefinition->recurRules;
		$this->inheritDaclRef = $this->entityDefinition->inheritDaclRef;
		$this->parentField = $this->entityDefinition->parentField;
		$this->unameSettings = $this->entityDefinition->unameSettings;
		$this->listTitle = $this->entityDefinition->listTitle;
		$this->icon = $this->entityDefinition->icon;

		$this->systemViews = $this->entityDefinition->getViews();

		/*
		// Check for system object
		$basePath = AntConfig::getInstance()->application_path . "/objects";
		if (file_exists($basePath . "/odefs/" . $obj_name . ".php"))
		{
			include($basePath . "/odefs/" . $obj_name . ".php");

			$this->systemViews = $obj_views; // TODO: replace with below include

			if (isset($aggregates))
				$this->aggregates = $aggregates;

			if (isset($defaultActivityLevel))
				$this->defaultActivityLevel = $defaultActivityLevel;

			if (isset($isPrivate))
				$this->isPrivate = $isPrivate;

			if (isset($recurRules))
				$this->recurRules = $recurRules;

			if (isset($inheritDaclRef))
				$this->inheritDaclRef = $inheritDaclRef;

			if (isset($parentField))
				$this->parentField = $parentField;

			if (isset($unameSettings))
				$this->unameSettings = $unameSettings;

			if (isset($listTitle))
				$this->listTitle = $listTitle;

			if (isset($icon))
				$this->icon = $icon;

			//if (file_exists(AntConfig::getInstance()->application_path . "/objects/oviews/".$obj_name.".php"))
				//include(AntConfig::getInstance()->application_path . "/objects/oviews/".$obj_name.".php");

			if (file_exists($basePath . "/oforms/" . $obj_name . "/default.php"))
				$default_form_xml = file_get_contents($basePath . "/oforms/" . $obj_name . "/default.php");

			if (file_exists($basePath . "/oforms/" . $obj_name . "/mobile.php"))
				$default_form_mobile_xml = file_get_contents($basePath . "/oforms/" . $obj_name . "/mobile.php");

			if (file_exists($basePath . "/oforms/" . $obj_name . "/infobox.php"))
				$default_form_infobox_xml = file_get_contents($basePath . "/oforms/" . $obj_name . "/infobox.php");

			if ($obj_revision > $this->revision)
				$this->updateDefinition($obj_fields, $obj_revision);
		}
		*/

		if ($det_view_fields)
		{
			$this->detailViewFields = $det_view_fields;
		}
		else if (count($this->systemViews))
		{
			$this->detailViewFields = $this->systemViews[0]->view_fields;
		}

		// Check for definition of child dacls for this object
		if (is_array($child_dacls))
			$this->childDacls = $child_dacls;

		if ($default_form_xml)
			$this->default_form_xml = $default_form_xml;
			
		if ($default_form_mobile_xml)
			$this->default_form_mobile_xml = $default_form_mobile_xml;

		if ($default_form_infobox_xml)
			$this->default_form_infobox_xml = $default_form_infobox_xml;

		// Add universal/default fields
		$fields = $this->entityDefinition->getFields();
		foreach ($fields as $fname=>$field)
		{
			$this->fields[$fname] = $field->toArray();
		}

		// Get field definitions
		// --------------------------------------------------------------------------------
		/*
		$this->fields["id"] = array('title'=>"ID", 'type'=>"number", 'id'=>"0", 'subtype'=>"", 'readonly'=>true, 'system'=>true);
		$this->fields['associations'] = array('title'=>'Associations', 'type'=>'object_multi', 'subtype'=>'', 'readonly'=>true, 'system'=>true);
		$this->fields['activity'] = array('title'=>'Activity', 'type'=>'object_multi', 'subtype'=>'activity', 'system'=>true);
		 */

		/*
		$result_arr = $this->cache->get($this->dbh->dbname."/objectdefs/".$this->otid);
		if (!$result_arr)
		{
			$result = $dbh->Query("select * from app_object_type_fields where type_id='".$this->otid."' order by title");

			if ($result)
			{
				$result_arr = pg_fetch_all($result);
				$dbh->FreeResults($result);
				$this->cache->set($this->dbh->dbname."/objectdefs/".$this->otid, $result_arr);
			}
			else
			{
				throw new Exception('Could not pull type fields from database for ' . $this->object_type);
			}
		}
		$num = count($result_arr);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $result_arr[$i];

			if (!$row['id'])
				continue;

			$objecTable = $row['subtype'];
			
			// Fix the issue on user files not using the actual object table
			if($row['subtype'] == "user_files")
			{
				$row['fkey_table_title'] = "name";
				$objecTable = "objects_file_act";
			}
				
			$this->fields[$row['name']] = array('title'=>$row['title'], 'type'=>$row['type'], 'id'=>$row['id'], 
												"mask"=>$row['mask'], "required"=>(($row['f_required']=='t')?true:false),
												"system"=>(($row['f_system']=='t')?true:false), 'use_when'=>$row['use_when'],
												'subtype'=>$row['subtype'], 'readonly'=>(($row['f_readonly']=='t')?true:false),
												'unique'=>(($row['f_unique']=='t')?true:false),
												'object_table'=>$objecTable);

																  
			if ($row['type'] == "fkey" || $row['type'] == "object" || $row['type'] == "fkey_multi")
			{
				$this->fields[$row['name']]['fkey_table'] = array("key"=>$row['fkey_table_key'], 
																  "title"=>$row['fkey_table_title'], 
																  "parent"=>$row['parent_field'],
																  "filter"=>(($row['filter'])?unserialize($row['filter']):null),
																  "autocreate"=>(($row['autocreate']=='t')?true:false),
																  "autocreatebase"=>$row['autocreatebase'],
																  "autocreatename"=>$row['autocreatename']);

				if ($row['type']=='fkey_multi' && $row['fkey_multi_tbl'])
				{
					$this->fields[$row['name']]['fkey_table']['ref_table'] = array("table"=>$row['fkey_multi_tbl'], 
																				   "this"=>$row['fkey_multi_this'], 
																				   "ref"=>$row['fkey_multi_ref']);
				}
			}

			// Check for default
			if (isset($G_CACHE_ANTOBJFDEFS[$this->dbh->dbname."_fld_".$row['id']]) 
				&& $G_CACHE_ANTOBJFDEFS[$this->dbh->dbname."_fld_".$row['id']]!=null)
			{
				$result_arr2 = $G_CACHE_ANTOBJFDEFS[$this->dbh->dbname."_fld_".$row['id']];
			}
			else
			{
				$result_arr2 = $this->cache->get($this->dbh->dbname."/objectdefs/fielddefaults/".$this->otid."/".$row['id']);
				if ($result_arr2===false)
				{
					$res2 = $dbh->Query("select * from app_object_field_defaults where field_id='".$row['id']."'");
					$result_arr2 = pg_fetch_all($res2);
					$dbh->FreeResults($res2);
					if (!$result_arr2)
						$result_arr2 = array();
					$this->cache->set($this->dbh->dbname."/objectdefs/fielddefaults/".$this->otid."/".$row['id'], $result_arr2);
				}
				$G_CACHE_ANTOBJFDEFS[$this->dbh->dbname."_fld_".$row['id']] = $result_arr2;
			}

			if (count($result_arr2))
			{
				$row2 = $result_arr2[0];

				if ($row2)
				{
					$default = array('on'=>$row2['on_event'], 'value'=>$row2['value']);
					if ($row2['coalesce'])
						$default['coalesce'] = unserialize($row2['coalesce']);
					if ($row2['where_cond'])
						$default['where'] = unserialize($row2['where_cond']);

					// Make sure that coalesce does not cause a circular reference to self
					if (isset($default['coalesce']) && $default['coalesce'])
					{
						foreach ($default['coalesce'] as $colfld)
						{
							if (is_array($colfld))
							{
								foreach ($colfld as $subcolfld)
								{
									if ($subcolfld == $row['name'])
									{
										$default = null;
										break;
									}
								}
							}
							else if ($colfld == $row['name'])
							{
								$default = null;
								break;
							}
						}
					}

					$this->fields[$row['name']]['default'] = $default;
				}
			}
			
			// Check for optional vals (drop-down)
			$result_arr2 = $this->cache->get($this->dbh->dbname."/objectdefs/fieldoptions/".$this->otid."/".$row['id']);
			if ($result_arr2===false)
			{
				$res2 = $dbh->Query("select * from app_object_field_options where field_id='".$row['id']."'");
				$result_arr2 = pg_fetch_all($res2);
				$dbh->FreeResults($res2);
				if (!$result_arr2)
					$result_arr2 = array();

				$this->cache->set($this->dbh->dbname."/objectdefs/fieldoptions/".$this->otid."/".$row['id'], $result_arr2);
			}
			for ($m = 0; $m < count($result_arr2); $m++)
			{
				$row2 = $result_arr2[$m];
				if ($row2)
				{
					if (!isset($this->fields[$row['name']]['optional_values']))
						$this->fields[$row['name']]['optional_values'] = array();

					if (!$row2['key'])
						$row2['key'] = $row2['value'];

					$this->fields[$row['name']]['optional_values'][$row2['key']] = $row2['value'];
				}
			}
		}
		*/
	}

	function getFieldType($name)
	{
		$arr = array("type" => null, "subtype" => null);
		
		if(isset($this->fields[$name]['type']))
			$arr['type'] = $this->fields[$name]['type'];
			
		if(isset($this->fields[$name]['subtype']))
			$arr['subtype'] = $this->fields[$name]['subtype'];
			
		return $arr;
	}

	function getNumFields()
	{
		return count($this->fields);
	}

	function getField($name)
	{
		if (isset($this->fields[$name]))
			return $this->fields[$name];
		else
			return null;
	}

	function getFields()
	{
		return $this->fields;
	}

	function getDefault($field, $value, $event='update', $obj=null)
	{
		$ret = $value;
		if(isset($field['default']['on']))
			$on = $field['default']['on'];
		
		if (isset($field['default']) && is_array($field['default']) && count($field['default']))
		{
			// Check if condition is part of the default
			if (isset($field['default']['where']) && $field['default']['where'] && $obj)
			{
				if (is_array($field['default']['where']))
				{
					foreach ($field['default']['where'] as $condFName=>$condVal)
					{
						if ($obj->getValue($condFName) != $condVal)
							$on = ""; // Do not set default
					}
				}
			}

			// Determin appropriate event and action
			switch ($on)
			{
			case 'create':
				if ($value)
					break;
				else
					$ret = $field['default']['value'];
				// Fall through to also use update
			case 'update':
				if ($on == "update")
				{
					if ($field['default']['coalesce'] && is_array($field['default']['coalesce']) && $obj)
					{
						$ret = $this->getDefaultCoalesce($field['default']['coalesce'], $obj, ($field['type'] == "alias")?true:false);
						if (!$ret)
							$ret = $field['default']['value'];
					}
					else
					{
						$ret = $field['default']['value'];
					}
				}
				break;
			case 'delete':
				if ($on == "delete")
					$ret = $field['default']['value'];
				break;
			case 'null':
				if ($ret=="" || $ret==null || $ret==$field['default']['value'])
				{

					if (isset($field['default']['coalesce']) && $field['default']['coalesce'] && is_array($field['default']['coalesce']) && $obj)
					{
						$ret = $this->getDefaultCoalesce($field['default']['coalesce'], $obj, ($field['type'] == "alias")?true:false);
						if (!$ret)
							$ret = $field['default']['value'];
					}
					else
					{
						$ret = $field['default']['value'];
					}
				}
				break;
			}
		}

		// Look for variables
		if ($ret && "<%username%>" == $ret)
		{
			if ($obj->user)
				$ret = $obj->user->name;
			else
				$ret = "";
		}

		if ($ret && "<%userid%>" == $ret)
		{
			if ($obj->user)
				$ret = $obj->user->id;
			else
				$ret = "";
		}

		if ((($field['type'] == "fkey" && $field['subtype'] == "users") 
			  || ($field['type'] == "object" && $field['subtype'] == "user")) && $ret == "-3")
		{
			if ($obj->user)
				$ret = $obj->user->id;
			else
				$ret = ""; // TODO: possibly use system or anonymous
		}

		return $ret;
	}

	function getDefaultCoalesce($cfields, $obj, $alias=false)
	{
		$ret = "";

		foreach ($cfields as $field_to_pull)
		{
			if (is_array($field_to_pull))
			{
				foreach ($field_to_pull as $subcol)
				{
					$buf = $obj->getValue($subcol);
					if ($buf)
					{
						if ($ret) $ret .= " ";

						if ($alias)
						{
							$ret = $subcol;
							break;
						}
						else
						{
							$ret .= $buf;
						}
					}
				}

			}
			else
			{
				if ($alias)
				{
					$ret = $field_to_pull;
					break;
				}
				else
				{
					$ret = $obj->getValue($field_to_pull);
				}
			}

			// Check if name was found
			if ($ret)
				break;
		}

		return $ret;
	}

	/**
	 * @depricated We now use the DataMapper - joe
	 * Make sure default/base fields common to all objects exists
	public function verifyDefaultFields()
	{
		$obj_fields = array();
		// Set default/universal fields
		$obj_fields["id"] = array('title'=>"ID", 'type'=>"number", 'id'=>"0", 'subtype'=>"", 'readonly'=>true, 'system'=>true);
		$obj_fields['associations'] = array('title'=>'Associations', 'type'=>'object_multi', 'subtype'=>'', 'readonly'=>true, 'system'=>true);
		$obj_fields['activity'] = 	array('title'=>'Activity', 'type'=>'object_multi', 'subtype'=>'activity', 'system'=>true);
		$obj_fields['comments'] = 	array('title'=>'Comments', 'type'=>'object_multi', 'subtype'=>'comment', 'readonly'=>false, 'system'=>true);
		$obj_fields['num_comments'] = array('title'=>'Num Comments', 'type'=>'number', 'subtype'=>'integer', 'readonly'=>true, 'system'=>true);
		$obj_fields['f_deleted'] = 	array('title'=>'Deleted', 'type'=>'bool', 'subtype'=>'', 'readonly'=>true, 'system'=>true);
		$obj_fields['revision'] =	array('title'=>'Revision', 'type'=>'number', 'subtype'=>'', 'readonly'=>true, 'system'=>true);
		$obj_fields['path'] = 		array('title'=>'Path', 'type'=>'text', 'subtype'=>'', 'readonly'=>true, 'system'=>true);
		$obj_fields['uname'] = 		array('title'=>'Uname', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true, 'system'=>true);
		$obj_fields['dacl'] = 		array('title'=>'Security', 'type'=>'text', 'subtype'=>'', 'readonly'=>true, 'system'=>true);
		$obj_fields['ts_entered'] =	array(
			'title'=>'Time Entered', 
			'type'=>'timestamp', 
			'subtype'=>'', 
			'readonly'=>true, 
			'system'=>true,
			'default'=>array("value"=>"now", "on"=>"create"),
		);
		$obj_fields['ts_updated'] =	array(
			'title'=>'Time Changed', 
			'type'=>'timestamp', 
			'subtype'=>'', 
			'readonly'=>true, 
			'system'=>true,
			'default'=>array("value"=>"now", "on"=>"update"),
		);

		$this->createObjFields($obj_fields);
	}
	 */

	/**
	 * @depricated We now use the DataMapper - joe
	 * Verify all fields exist. This basically forces and updateDefinition even if nothing has changed.
	public function verifyAllFields()
	{
		return $this->updateDefinition($this->fields, $this->revision);
	}
	 */

	/**
	 * @depricated We now use the DataMapper - joe
	 * Update the object definition
	 *
	 * @param array $obj_fields The fields to verify
	 * @param int $revision The revision of the current definition
	public function updateDefinition($obj_fields, $revision)
	{
		$this->verifyDefaultFields();
		$this->createObjFields($obj_fields);

		$this->dbh->Query("update app_object_types set revision='$revision' where id='".$this->otid."'");
		$this->revision = $revision;
	}
	 */

	/**
	 * @depricated We now use the DataMapper - joe
	function createObjFields($obj_fields)
	{
		global $G_CACHE_ANTOBJTYPES;
		$dbh = $this->dbh;

		$this->clearCache();

		$sort_order = 1;
		foreach ($obj_fields as $fname=>$fdef)
		{
			if (!isset($fdef['system']))
				$fdef['system'] = true;

			$result = $dbh->Query("select id, use_when from app_object_type_fields where name='$fname' and type_id='".$this->otid."'");
			if ($dbh->GetNumberRows($result))
			{
				$fid = $dbh->GetValue($result, 0, "id");
				
				$updateFields = array();
				
				$updateFields[] = "name='$fname'";
				
				if(isset($fdef['title']))
					$updateFields[] = "title='".$dbh->Escape($fdef['title'])."'";
					
				if(isset($fdef['type']))
					$updateFields[] = "type='".$fdef['type']."'";
					
				if(isset($fdef['subtype']))
					$updateFields[] = "subtype='".$fdef['subtype']."'";
				
				if(isset($fdef['fkey_table']['key']))    
					$updateFields[] = "fkey_table_key='".$fdef['fkey_table']['key']."'";
					
				if(isset($fdef['fkey_table']['title']))
					$updateFields[] = "fkey_table_title='".$fdef['fkey_table']['title']."'";
					
				if(isset($fdef['fkey_table']['parent']))
					$updateFields[] = "parent_field='".$fdef['fkey_table']['parent']."'";
					
				if(isset($fdef['fkey_table']['ref_table']['table']))
					$updateFields[] = "fkey_multi_tbl='".$fdef['fkey_table']['ref_table']['table']."'";
					
				if(isset($fdef['fkey_table']['ref_table']['this']))
					$updateFields[] = "fkey_multi_this='".$fdef['fkey_table']['ref_table']['this']."'";
				
				if(isset($fdef['fkey_table']['ref_table']['ref']    ))
					$updateFields[] = "fkey_multi_ref='".$fdef['fkey_table']['ref_table']['ref']."'";
					
				$updateFields[] = "sort_order='$sort_order'";                    
				$updateFields[] = "autocreate='".((isset($fdef['autocreate']) && $fdef['autocreate'])?'t':'f')."'";
				
				if(isset($fdef['autocreatebase']))
					$updateFields[] = "autocreatebase='".$dbh->Escape($fdef['autocreatebase'])."'";
					
				if(isset($fdef['autocreatename']))
					$updateFields[] = "autocreatename='".$dbh->Escape($fdef['autocreatename'])."'";
				
				if(isset($fdef['use_when']))    
					$updateFields[] = "use_when='".$dbh->Escape($fdef['use_when'])."'";

				if(isset($fdef['mask']))
					$updateFields[] = "mask='".$dbh->Escape($fdef['mask'])."'";
					
				if(isset($fdef['fkey_table']['filter']) && is_array($fdef['fkey_table']['filter']))
					$updateFields[] = "filter='".$dbh->Escape(serialize($fdef['fkey_table']['filter']))."'";
					
				$updateFields[] = "f_required='".((isset($fdef['required']) && $fdef['required'])?'t':'f')."'";
				$updateFields[] = "f_readonly='".((isset($fdef['readonly']) && $fdef['readonly'])?'t':'f')."'";
				$updateFields[] = "f_system='".((isset($fdef['system']) && $fdef['system'])?'t':'f')."'";                    
				$updateFields[] = "f_unique='".((isset($fdef['unique']) && $fdef['unique'])?'t':'f')."'";                    
							
				$query = "update app_object_type_fields set " . implode(", ", $updateFields) . "where id='$fid';";
				$dbh->Query($query);

				$this->cache->remove($this->dbh->dbname."/objectdefs/fielddefaults/".$this->otid."/".$fid);
				if ($fid && isset($fdef['default']) && is_array($fdef['default']))
				{
					$dbh->Query("delete from app_object_field_defaults where field_id='$fid'");
					$dbh->Query("insert into app_object_field_defaults(field_id, on_event, value, coalesce, where_cond) 
									values('$fid', '".$fdef['default']['on']."', '".$dbh->Escape($fdef['default']['value'])."', 
									'".$dbh->Escape(serialize($fdef['default']['coalesce']))."',
									'".$dbh->Escape(serialize($fdef['default']['where']))."')");
				}

				$this->cache->remove($this->dbh->dbname."/objectdefs/fieldoptions/".$this->otid."/".$fid);
				if ($fid && isset($fdef['optional_values']) && is_array($fdef['optional_values']))
				{
					$dbh->Query("delete from app_object_field_options where field_id='$fid'");
					foreach ($fdef['optional_values'] as $okey=>$oval)
					{
						$dbh->Query("insert into app_object_field_options(field_id, key, value) 
										values('$fid', '".$dbh->Escape($okey)."', '".$dbh->Escape($oval)."')");
					}
				}
			}
			else
			{
				$key = null;
				$fKeytitle = null;
				$fKeyParent = null;
				$fKeyFilter = null;
				$fKeyRef = null;                    
				$fKeyRefTable = null;
				$fKeyRefThis = null;
				$autocreatebase = null;
				$autocreatename = null;
				$mask = null;
				$useWhen = null;
				
				if(isset($fdef['fkey_table']['key']))
					$key = $fdef['fkey_table']['key'];
				
				if(isset($fdef['fkey_table']['title']))    
					$fKeytitle = $fdef['fkey_table']['title'];
					
				if(isset($fdef['fkey_table']['parent']))    
					$fKeyParent = $fdef['fkey_table']['parent'];
					
				if(isset($fdef['fkey_table']['filter']) && is_array($fdef['fkey_table']['filter']))    
					$fKeyFilter = serialize($fdef['fkey_table']['filter']);
				
				if(isset($fdef['fkey_table']['ref_table']['ref']))
					$fKeyRef = $fdef['fkey_table']['ref_table']['ref'];
					
				if(isset($fdef['fkey_table']['ref_table']['table']))
					$fKeyRefTable = $fdef['fkey_table']['ref_table']['table'];
					
				if(isset($fdef['fkey_table']['ref_table']['this']))
					$fKeyRefThis = $fdef['fkey_table']['ref_table']['this'];
					
				if(isset($fdef['autocreatebase']))
					$autocreatebase = $fdef['autocreatebase'];
					
				if(isset($fdef['autocreatename']))
					$autocreatename = $fdef['autocreatename'];
					
				if(isset($fdef['mask']))
					$mask = $fdef['mask'];
					
				if(isset($fdef['use_when']))
					$useWhen = $fdef['use_when'];
					
				$autocreate = "f";
				$required = "f";
				$readonly = "f";
				$unique = "f";
					
				if(isset($fdef['autocreate']) && $fdef['autocreate'])
					$autocreate = "t";
					
				if(isset($fdef['required']) && $fdef['required'])
					$required = "t";
					
				if(isset($fdef['readonly']) && $fdef['readonly'])
					$readonly = "t";

				if(isset($fdef['unique']) && $fdef['unique'])
					$unique = "t";
					
				$query = "insert into app_object_type_fields(type_id, name, title, type, subtype, fkey_table_key, fkey_table_title, parent_field,
						  fkey_multi_tbl, fkey_multi_this, fkey_multi_ref, sort_order, f_system, autocreate, autocreatebase, autocreatename,
						  mask, f_required, f_readonly, filter, use_when, f_unique)
						  values('".$this->otid."', '$fname', '".$dbh->Escape($fdef['title'])."', '".$fdef['type']."', '".$fdef['subtype']."',
						  '$key', '$fKeytitle', '$fKeyParent', '$fKeyRefTable', '$fKeyRefThis', '$fKeyRef', 
						  '$sort_order', '".(($fdef['system'])?'t':'f')."',
						  '$autocreate', '".$dbh->Escape($autocreatebase)."', '".$dbh->Escape($autocreatename)."',
						  '".$dbh->Escape($mask)."', '$required', '$readonly',
						  '".$dbh->Escape($fKeyFilter)."',
						  '".$dbh->Escape($useWhen)."',
						  '$unique');
						  select currval('app_object_type_fields_id_seq') as id;";

				$result = $dbh->Query($query);
				if ($dbh->GetNumberRows($result))
				{
					$fdefCoalesce = null;
					$fdefWhere = null;
					
					if(isset($fdef['default']['coalesce']))
						$fdefCoalesce = $fdef['default']['coalesce'];
						
					if(isset($fdef['default']['where']))
						$fdefWhere = $fdef['default']['where'];
					
					$fid = $dbh->GetValue($result, 0, "id");

					if ($fid && isset($fdef['default']) && is_array($fdef['default']))
					{
						$dbh->Query("insert into app_object_field_defaults(field_id, on_event, value, coalesce, where_cond) 
										values('$fid', '".$fdef['default']['on']."', '".$dbh->Escape($fdef['default']['value'])."', 
										'".$dbh->Escape(serialize($fdefCoalesce))."',
										'".$dbh->Escape(serialize($fdefWhere))."')");
					}

					if ($fid && isset($fdef['optional_values']) && is_array($fdef['optional_values']))
					{
						foreach ($fdef['optional_values'] as $okey=>$oval)
						{
							$dbh->Query("insert into app_object_field_options(field_id, key, value) 
											values('$fid', '".$dbh->Escape($okey)."', '".$dbh->Escape($oval)."')");
						}
					}
				}
			}

			// Make sure column exists
			$this->checkObjColumn($fname, $fdef['type'], $fdef['subtype']);

			$sort_order++;
		}
	}
	*/

	function removeField($fname)
	{
		$dbh = $this->dbh;

		// TODO: check for system

		$dbh->Query("delete from app_object_type_fields where name='$fname' and type_id='".$this->otid."'");
		$dbh->Query("ALTER TABLE ".$this->object_table." DROP COLUMN $fname;");

		$this->clearCache();
	}

	/**
	 * @depricated We now use the DataMapper - joe
	 * Make sure column exists for a field
	 *
	 * @param string $colname The name of the column/property to check
	 * @param string $ftype The type of the field we are checking for
	 * @param string $subtype The optional subtype
	public function checkObjColumn($colname, $ftype, $subtype)
	{
		if (!$this->dbh->ColumnExists($this->object_table, $colname))
		{
			$index = ""; // set to create dynamic indexes

			switch ($ftype)
			{
			case 'text':
				if ($subtype)
				{
					if (is_numeric($subtype))
					{
						$type = "character varying($subtype)";
						$index = "btree";
					}
					else
					{
						// Handle special types
						switch ($subtype)
						{
						case 'email':
							$type = "character varying(256)";
							$index = "btree";
							break;
						case 'zipcode':
							$type = "character varying(32)";
							$index = "btree";
							break;
						default:
							$type = "text";
							$index = "gin";
							break;
						}
					}
				}
				else
				{
					$type = "text";
					$index = "gin";
				}

				// else leave it as text
				break;
			case 'alias':
				$type = "character varying(128)";
				$index = "btree";
				break;
			case 'timestamp':
				$type = "timestamp with time zone";
				$index = "btree";
				break;
			case 'date':
				$type = "date";
				$index = "btree";
				break;
			case 'integer':
				$type = "integer";
				$index = "btree";
				break;
			case 'numeric': // If ftype is already numeric, it should set the type
				$type = "numeric";
				$index = "btree";
				break;
			case 'int':
			case 'integer':
			case 'number':
				if ($subtype)
					$type = $subtype;
				else
					$type = "numeric";
					
				$index = "btree";
				break;
			case 'fkey':
				$type = "integer";
				$index = "btree";
				break;

			case 'fkey_multi':
				$type = "text"; // store json

				//$type = "integer[]";
				//$index = "GIN";
				break;

			case 'object_multi':
				$type = "text"; // store json

				//$type = "text[]";
				//$index = "GIN";
				break;

			case 'bool':
			case 'boolean':
				$type = "bool DEFAULT false";
				break;

			case 'object':
				if ($subtype)
				{
					$type = "bigint";
					$index = "btree";
				}
				else
				{
					$type = "character varying(512)";
					$index = "btree";
				}
				break;

			default:
				$type = ""; // do not try to enter it if we don't know what it is
				break;
			}
			
			if ($type)
			{
				$query = "ALTER TABLE ".$this->object_table." ADD COLUMN $colname $type";
				$this->dbh->Query($query);

				// Store cached foreign key names
				if ($ftype == "fkey" || $ftype == "object" || $ftype == "fkey_multi"  || $ftype == "object_multi")
				{
					$this->dbh->Query("ALTER TABLE ".$this->object_table." ADD COLUMN ".$colname."_fval text");
				}
			}
		}
		else
		{
			// Make sure that existing foreign fields have local _fval caches
			if ($ftype == "fkey" || $ftype == "object" || $ftype == "fkey_multi"  || $ftype == "object_multi")
			{
				if (!$this->dbh->ColumnExists($this->object_table, $colname . "_fval"))
					$this->dbh->Query("ALTER TABLE ".$this->object_table." ADD COLUMN ".$colname."_fval text");
			}
		}
	}
	*/

	public function escapeUseWithFieldVal($val)
	{
		$val = str_replace("-", "minus", $val);
		$val = str_replace("+", "plus", $val);

		return $val;
	}

	/**
	 * Clear out all caches containing this definition
	 */
	public function clearCache()
	{
		// below is all legacy now
		global $G_CACHE_ANTOBJTYPES;
		$this->cache->remove($this->dbh->dbname."/objectdefs/".$this->otid);
		$this->cache->remove($this->dbh->dbname."/objectdefs/".$this->object_type."/base"); // purge revision
		$this->cache->remove($this->dbh->dbname."/objects/gen/".$this->object_type); // purge revision
		$G_CACHE_ANTOBJTYPES[$this->dbh->dbname][$this->object_type] = null;

		foreach ($this->fields as $field)
		{
			if ($field['id'])
			{
				$this->cache->remove($this->dbh->dbname . "/objectdefs/fielddefaults/" . $this->otid . "/" . $field['id']);
				$G_CACHE_ANTOBJFDEFS[$this->dbh->dbname."_fld_".$field['id']] = null;
			}
		}

		AntObjectDefLoader::getInstance($this->dbh)->clearDef($this->object_type);
	}

	/**
	 * @depricated We now use the DataMapper - joe
	 * Make sure column exists for a field
	 *
	 * @param string $fname The name of the column/property to check
	public function createFieldIndex($fname)
	{
		$field = $this->getField($fname);

		if (!$field)
			return false;

		$colname = $fname;
		$ftype = $field['type'];
		$subtype = $field['subtype'];

		if ($this->dbh->ColumnExists($this->object_table, $colname) && $this->otid)
		{
			$index = ""; // set to create dynamic indexes

			switch ($ftype)
			{
			case 'text':
				$index = ($subtype) ? "btree" : "gin";
				break;
			case 'timestamp':
			case 'date':
			case 'integer':
			case 'numeric':
			case 'number':
			case 'fkey':
			case 'object':
				$index = "btree";
				break;

			case 'fkey_multi':
				$type = "text"; // store json

				//$type = "integer[]";
				//$index = "GIN";
				break;

			case 'object_multi':
				$type = "text"; // store json

				//$type = "text[]";
				//$index = "GIN";
				break;

			case 'bool':
			case 'boolean':
			default:
				break;
			}

			// Create dynamic index
			if ($index)
			{
				// If we are using generic obj partitions then make sure _del table is updated as well
				if (!$this->useCustomTable)
				{
					$indexCol = $colname;

					if ($ftype == "text" && $subtype) 
						$indexCol = "lower($colname)";
					else if ($ftype == "text" && !$subtype && $index == "gin")
						$indexCol = "to_tsvector('english', $colname)";

					$this->dbh->Query("CREATE INDEX ".$this->object_table."_act_".$colname."_idx
										  ON ".$this->object_table."_act
										  USING $index
										  (".$indexCol.");");

					$this->dbh->Query("CREATE INDEX ".$this->object_table."_del_".$colname."_idx
										  ON ".$this->object_table."_del
										  USING $index
										  (".$indexCol.");");
					

					// Update indexed flag for this field
					$this->dbh->Query("UPDATE app_object_type_fields SET f_indexed='t' WHERE type_id='".$this->otid."' andname='$fname'");

				}
			}

			return true;
		}

		return false;
	}
	 */

	/**
	 * @depricated We now use the DataMapper - joe
	 * Object tables are created dynamically to inherit from the parent object table
	public function createObjectTable()
	{
		$dbh = $this->dbh;
		$base = "objects_".$this->object_type;
		$tables = array("objects_".$this->object_type."_act", "objects_".$this->object_type."_del");

		// Make sure the table does not already exist
		if ($dbh->TableExists($base))
			return;

		// Base table for this object type
		$query = "CREATE TABLE $base () INHERITS (objects);";
		$dbh->Query($query);

		// Active
		$query = "CREATE TABLE ".$tables[0]."
					(
						CONSTRAINT ".$tables[0]."_pkey PRIMARY KEY (id),
						CHECK(object_type_id='".$this->otid."' and f_deleted='f')
					) 
					INHERITS ($base);";
		$dbh->Query($query);

		// Deleted / Archived
		$query = "CREATE TABLE ".$tables[1]."
					(
						CONSTRAINT ".$tables[1]."_pkey PRIMARY KEY (id),
						CHECK(object_type_id='".$this->otid."' and f_deleted='t')
					) 
					INHERITS ($base);";
		$dbh->Query($query);

		// Create indexes for system columns
		foreach ($tables as $tbl)
		{
			/** Not needed because of primary key constraint above
			$dbh->Query("CREATE INDEX ".$tbl."_oid_idx
								  ON $tbl
								  USING btree
								  (id);");
			**/

			/*
			$dbh->Query("CREATE INDEX ".$tbl."_owner_id_idx
							  ON $tbl
							  USING btree
							  (owner_id);");
			 /

			$dbh->Query("CREATE INDEX ".$tbl."_uname_idx
						  ON $tbl
						  USING btree (lower(uname))
						  where uname is not null;");

			$dbh->Query("CREATE INDEX ".$tbl."_tsv_fulltext_idx
						  ON $tbl
						  USING gin (tsv_fulltext)
						  where tsv_fulltext is not null;");

			/*
			$dbh->Query("CREATE INDEX ".$tbl."_ts_entered_idx
						  ON $tbl
						  USING btree (ts_entered)
						  where ts_entered is not null;");

			$dbh->Query("CREATE INDEX ".$tbl."_ts_updated_idx
						  ON $tbl
						  USING btree (ts_updated)
						  where ts_updated is not null;");
			/
		}
	}
	*/
}

class CAntObjectFieldOpt
{
	var $value;
	var $title;

	function CAntObjectFieldOpt($val, $title)
	{
		$this->value = $val;
		$this->title = $title;
	}
}
