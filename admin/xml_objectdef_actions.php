<?php
/**
 * @depricated Use controllers/ObjectControll.php
 * However, it is still used in lib/js/CAntObject.js::saveDefinition to add and remove fields
 */
require_once("../lib/AntConfig.php");
require_once("ant.php");
require_once("ant_user.php");
require_once("lib/Email.php");
require_once("lib/CAntObject.php");
require_once("email/email_functions.awp");

require_once("lib/aereus.lib.php/CCache.php");

$cache = CCache::getInstance();

$dbh = $ANT->dbh;
$USERNAME = $USER->name;
$USERID =  $USER->id;
$ACCOUNT = $USER->accountId;
$FUNCTION = $_REQUEST['function'];

// Log activity - not idle
UserLogAction($dbh, $USERID);

header("Content-type: text/xml");			// Returns XML document
echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 	

switch ($FUNCTION)
{
// ---------------------------------------------------------
// Add field
// ---------------------------------------------------------
case "save_field":
	$obj_type = $_REQUEST['obj_type'];
	$name = $_REQUEST['name'];
	$title = $_REQUEST['title'];
	$type = $_REQUEST['type'];
	$subtype = $_REQUEST['subtype'];
	$parent_field = "";
	$fkey_table_key = $_REQUEST['fkey_table_key'];
	$fkey_multi_tbl = $_REQUEST['fkey_multi_tbl'];
	$fkey_multi_this = $_REQUEST['fkey_multi_this'];
	$fkey_multi_ref = $_REQUEST['fkey_multi_ref'];
	$fkey_table_title = $_REQUEST['fkey_table_title'];
	$required = ($_REQUEST['required']) ? $_REQUEST['required'] : 'f';

	if ($name && $obj_type)
	{
		$obj = new CAntObject($dbh, $obj_type);
		$db_schema = "public";
		$oname = $obj_type;

		// Filter column name
		$numbers = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0"); 
		$words = array("one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "zero");
		$name = str_replace($numbers, $words, $name);

		// Get schema (if other than public)
		if (strpos($obj_type, ".") !== false)
		{
			$parts = explode(".", $obj_type);
			$db_schema = "zudb_".$parts[0];
			$oname = $parts[1];
		}

		// Check for special/meta types
		switch ($type)
		{			
		case 'file':
			$type = "object";
			$subtype = "file";
			break;
		case 'folder':
			$type = "object";
			$subtype = "folder";
			break;
		}
		
		$fdef = array('title'=>$title, 'type'=>$type, 'subtype'=>$subtype, 'system'=>false);

		if ($type == "fkey_multi")
		{
			$fdef['fkey_table'] = array("key"=>"id", "title"=>"name", "parent"=>"parent_id", "ref_table"=>array(
											"table"=>"object_grouping_mem", 
											"this"=>"object_id", 
											"ref"=>"grouping_id"
										));
		}

		$obj->addField($name, $fdef);
	}
	
	$retval = 1;
	break;

// ---------------------------------------------------------
// Delete field
// ---------------------------------------------------------
case "delete_field":
	$obj_type = $_REQUEST['obj_type'];
	$name = $_REQUEST['name'];
	if ($name && $obj_type)
	{
		$obj = new CAntObject($dbh, $obj_type);
		$obj->removeField($name);
		/*
		$db_schema = "public";
		$oname = $obj_type;
		// Get schema (if other than public)
		if (strpos($obj_type, ".") !== false)
		{
			$parts = explode(".", $obj_type);
			$db_schema = "zudb_".$parts[0];
			$oname = $parts[1];
		}

		// Delete column
		$dbh->Query("ALTER TABLE ".$obj->object_table." DROP COLUMN \"".$name."\";");

		// Delete from object
		$dbh->Query("delete from app_object_type_fields where type_id='".$obj->object_type_id."' and name='$name'");

		$merge_tbl_name = $oname."_".$name."_mem";
		if ($dbh->TableExists($merge_tbl_name, $db_schema))
		{
			$dbh->Query("DROP TABLE $db_schema.$merge_tbl_name;");
		}
		 */
	}

	$retval = 1;
	break;

// ---------------------------------------------------------
// Save general
// ---------------------------------------------------------
case "save_general":
	$obj_type = $_REQUEST['obj_type'];
	$title = rawurldecode($_REQUEST['title']);
	if ($title && $obj_type)
	{
		$odef = new CAntObject($dbh, $_REQUEST['obj_type']);
		if ($_REQUEST['form_layout_xml'] != $odef->fields->default_form_xml)
		{
			$dbh->Query("update app_object_types set title='".$dbh->Escape($title)."', 
						 form_layout_xml='".$dbh->Escape($_REQUEST['form_layout_xml'])."' where name='$obj_type'");
		}

		if ($_REQUEST['field_form_order'])
		{
			$result = $dbh->Query("select id from app_object_types where name='$obj_type'");
			if ($dbh->GetNumberRows($result))
			{
				$type_id = $dbh->GetValue($result, 0, "id");

				if ($type_id)
				{
					$order_fields = explode(":", $_REQUEST['field_form_order']);
					for($i = 0; $i < count($order_fields); $i++)
					{
						if ($order_fields[$i])
							$dbh->Query("update app_object_type_fields set sort_order='$i' where type_id='$type_id' and name='".$order_fields[$i]."';");
					}
				}
			}
		}

		$otid = objGetAttribFromName($dbh, $obj_type, "id");
		if ($_REQUEST['xml_team_form_layouts'] && is_array($_REQUEST['xml_team_form_layouts']) && $otid)
		{
			for ($i = 0; $i < count($_REQUEST['xml_team_form_layouts']); $i++)
			{
				if ($_REQUEST['xml_team_form_layouts'][$i])
				{
					$xml = $_REQUEST['xml_team_form_layouts_'.$_REQUEST['xml_team_form_layouts'][$i]];
					$dbh->Query("delete from app_object_type_frm_layouts where 
									type_id='$otid' and team_id='".$_REQUEST['xml_team_form_layouts'][$i]."'");

					$dbh->Query("insert into app_object_type_frm_layouts(team_id, type_id, form_layout_xml) values
									('".$_REQUEST['xml_team_form_layouts'][$i]."', '$otid', '".$dbh->Escape($xml)."');");
				}
			}
		}

		//$odef->fields->verifyDefaultFields();

		$retval = 1;
	}
	else
		$retval = "-1";

	break;
	
/* @depricated Now using object controller
// ---------------------------------------------------------
// Add optional value to a field
// ---------------------------------------------------------
case "field_add_option":
	$obj_type = $_REQUEST['obj_type'];
	$name = $_REQUEST['name'];
	$value = $_REQUEST['value'];
	if ($name && $obj_type && $value)
	{
		$obj = new CAntObject($dbh, $obj_type);
		$db_schema = "public";
		$oname = $obj_type;
		// Get schema (if other than public)
		if (strpos($obj_type, ".") !== false)
		{
			$parts = explode(".", $obj_type);
			$db_schema = "zudb_".$parts[0];
			$oname = $parts[1];
		}

		$result = $dbh->Query("select id from app_object_type_fields where name='$name' and type_id='".$obj->object_type_id."'");
		if ($dbh->GetNumberRows($result))
		{
			$fid = $dbh->GetValue($result, 0, "id");

			if ($fid)
			{
				$dbh->Query("insert into app_object_field_options(field_id, key, value) 
											values('$fid', '".$dbh->Escape($value)."', '".$dbh->Escape($value)."');");
				$cache->remove($dbh->dbname."/objectdefs/fieldoptions/".$obj->object_type_id."/".$fid);
			}
		}
	}

	$retval = 1;
	break;

// ---------------------------------------------------------
// Delete optional value to a field
// ---------------------------------------------------------
case "field_delete_option":
	$obj_type = $_REQUEST['obj_type'];
	$name = $_REQUEST['name'];
	$value = $_REQUEST['value'];
	if ($name && $obj_type && $value)
	{
		$obj = new CAntObject($dbh, $obj_type);
		$db_schema = "public";
		$oname = $obj_type;
		// Get schema (if other than public)
		if (strpos($obj_type, ".") !== false)
		{
			$parts = explode(".", $obj_type);
			$db_schema = "zudb_".$parts[0];
			$oname = $parts[1];
		}

		$result = $dbh->Query("select id from app_object_type_fields where name='$name' and type_id='".$obj->object_type_id."'");
		if ($dbh->GetNumberRows($result))
		{
			$fid = $dbh->GetValue($result, 0, "id");

			if ($fid)
			{
				$dbh->Query("delete from app_object_field_options where field_id='$fid' and key='".$dbh->Escape($value)."'");
				$cache->remove($dbh->dbname."/objectdefs/fieldoptions/".$obj->object_type_id."/".$fid);
			}
		}
	}

	$retval = 1;
	break;

// ---------------------------------------------------------
// Set required flag
// ---------------------------------------------------------
case "field_set_required":
	$obj_type = $_REQUEST['obj_type'];
	$name = $_REQUEST['name'];
	$required = $_REQUEST['required'];
	if ($name && $obj_type && $required)
	{
		$obj = new CAntObject($dbh, $obj_type);
		$query = "update app_object_type_fields set f_required='$required' where name='$name' 
								and type_id='".$obj->object_type_id."'";
		$result = $dbh->Query($query);
	}

	$retval = 1;
	break;

// ---------------------------------------------------------
// Set default value for a field
// ---------------------------------------------------------
case "field_set_default":
	$obj_type = $_REQUEST['obj_type'];
	$name = $_REQUEST['name'];
	$on = $_REQUEST['on'];
	$value = $_REQUEST['value'];
	if ($name && $obj_type)
	{
		$obj = new CAntObject($dbh, $obj_type);
		$result = $dbh->Query("select id from app_object_type_fields where name='$name' and type_id='".$obj->object_type_id."'");
		if ($dbh->GetNumberRows($result))
			$fid = $dbh->GetValue($result, 0, "id");

		if ($on && $fid)
		{
			if ($dbh->GetNumberRows($dbh->Query("select id from app_object_field_defaults where field_id='$fid'")))
			{
				$dbh->Query("update app_object_field_defaults set on_event='".$dbh->Escape($on)."', value='".$dbh->Escape($value)."'
								where field_id='$fid';");
			}
			else
			{
				$dbh->Query("insert into app_object_field_defaults(field_id, on_event, value) 
											values('$fid', '".$dbh->Escape($on)."', '".$dbh->Escape($value)."');");
			}
		}
		else if ($fid) // clear default
		{
			$dbh->Query("delete from app_object_field_defaults where field_id='$fid';");
		}
	}

	$retval = 1;
	break;

// ---------------------------------------------------------
// Get default value for a field
// ---------------------------------------------------------
case "field_get_default":
	$obj_type = $_REQUEST['obj_type'];
	$name = $_REQUEST['name'];
	$retval = "{on:\"\", value:\"\", coalesce:\"\"}"; // default to "no default"
	if ($name && $obj_type)
	{
		$obj = new CAntObject($dbh, $obj_type);
		$query = "select id from app_object_type_fields where name='$name' and type_id='".$obj->object_type_id."'";            
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
			$fid = $dbh->GetValue($result, 0, "id");

		if ($fid)
		{
			$result = $dbh->Query("select id, on_event, value, coalesce from app_object_field_defaults where field_id='$fid'");
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetRow($result, 0);
				$retval = "{on:\"".$row['on_event']."\", value:\"".addslashes($row['value'])."\", coalesce:\"".$row['coalesce']."\"}";
			}
		}
	}

	break;
 */
}

// Purge cache
if ($_REQUEST['obj_type'])
{
	$obj = new CAntObject($dbh, $_REQUEST['obj_type']);
	$obj->clearDefinitionCache();
	/*
	$cache->remove($dbh->dbname."/objects/gen/".$_REQUEST['obj_type']);

	$result = $dbh->Query("select id from app_object_types where name='".$_REQUEST['obj_type']."'");
	if ($dbh->GetNumberRows($result))
	{
		$otid = $dbh->GetValue($result, 0, "id");
		$res2 = $dbh->Query("select id from app_object_type_fields where type_id='".$otid."'");
		$num = $dbh->GetNumberRows($res2);
		for ($i = 0; $i < $num; $i++)
		{
			$cache->remove($dbh->dbname."/objectdefs/fielddefaults/$otid/".$dbh->GetValue($res2, 0, "id"));
		}

		$cache->remove($dbh->dbname."/objectdefs/".$otid);
	}
	 */
}

// Check for RPC
if ($retval)
{
	echo "<response>";
	echo "<retval>" . rawurlencode($retval) . "</retval>";
	echo "<cb_function>" . $_GET['cb_function'] . "</cb_function>";
	echo "</response>";
}
