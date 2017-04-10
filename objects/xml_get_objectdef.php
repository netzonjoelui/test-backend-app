<?php
/**
 * This file is depiracted but left in place for legacy AntApi calls.
 *
 * Once the api has been updated to use the new JSON Object::getDefinition contoller then we can safely delete this object
 */
require_once("../lib/AntConfig.php");
require_once("ant.php");
require_once("ant_user.php");
require_once("email/email_functions.awp");
require_once("lib/Email.php");
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectFields.php");
require_once("lib/global_functions.php");
require_once("lib/WorkFlow.php");

$dbh = $ANT->dbh;
$USERNAME = $USER->name;
$USERID =  $USER->id;
$ACCOUNT = $USER->accountId;

$ONAME = $_GET['oname'];

header("Content-type: text/xml");			// Returns XML document
echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

echo "<object>";
if ($ONAME)
{
	$obj = new CAntObject($dbh, $ONAME, null, $USER);
	$objf = $obj->fields;
	$ofields = $objf->getFields();

	if ($_GET['clearcache']) // force a refresh of cache
	{
		$obj->clearDefinitionCache();
		$obj->load();
	}

	if (!$obj->title)
	{
		$oname_tmp = $ONAME;
		if (strpos($ONAME, ".") !== false)
		{
			$parts = explode(".", $ONAME);
			$oname_tmp = $parts[1];
		}
		$obj->title = ucfirst($oname_tmp);
	}

	echo "<title>".escape($obj->title)."</title>";
	echo "<name_field>".$objf->listTitle."</name_field>";
	echo "<icon_name>".escape($obj->getIconName())."</icon_name>";

	// Print views
	// ------------------------------------------------------------
	$num = $obj->getNumViews();
	//$userdef = $USER->getSetting("/objects/views/default/".$ONAME);
	echo "<views>";
	for ($i = 0; $i < $num; $i++)
	{
		$view = $obj->getView($i);
		echo $view->getXml($USERID);
	}
	echo "</views>";
	
	$showper = $USER->getSetting("/objects/browse/showper/".$ONAME);
	if (!$showper) $showper = "0";
	echo "<showper>$showper</showper>";

	$browserMode = $USER->getSetting("/objects/browse/mode/".$ONAME);
	// Set default view modes
	if (!$browserMode) 
	{
		switch ($ONAME)
		{
		case 'email_thread':
		case 'note':
			$browserMode = "previewV";
			break;
		default:
			$browserMode = "table";
			break;
		}
	}
	echo "<browser_mode>$browserMode</browser_mode>";
	//echo "<browser_blank_state>".rawurlencode($obj->getBrowserBlankMessage())."</browser_blank_state>";

	// Child secuirty
	// ------------------------------------------------------------
	echo "<security>";
	if (is_array($obj->fields->childDacls) && count($obj->fields->childDacls))
	{
		foreach ($obj->fields->childDacls as $chldobj)
			echo "<child_object>$chldobj</child_object>";
	}
	echo "</security>";

	// Recurrence
	// ------------------------------------------------------------
	echo "<recurrence hasrecur='".(($obj->fields->recurRules)?'t':'f')."'>"; // true|false
	if ($obj->fields->recurRules)
	{
		echo "<field_time_start>".$obj->fields->recurRules['field_time_start']."</field_time_start>";
		echo "<field_time_end>".$obj->fields->recurRules['field_time_end']."</field_time_end>";
		echo "<field_date_start>".$obj->fields->recurRules['field_date_start']."</field_date_start>";
		echo "<field_date_end>".$obj->fields->recurRules['field_date_end']."</field_date_end>";
		echo "<field_recur_id>".$obj->fields->recurRules['field_recur_id']."</field_recur_id>";
	}
	echo "</recurrence>";

	// Form View Definition (this might be moved in the future)
	// ------------------------------------------------------------
	// TODO: replace with getUIML below
	/*
	$frm_xml = $USER->getObjectFormXml($ONAME);
	if (!$frm_xml)
	{
		if ($_REQUEST['mobile'])
			$frm_xml = ($obj->fields->default_form_mobile_xml)?$obj->fields->default_form_mobile_xml:"*";
		else
			$frm_xml = ($obj->form_layout_xml)?$obj->form_layout_xml:"*";
	}
	*/
	$frm_xml = $obj->getUIML($USER, ($_REQUEST['mobile'])?'mobile':'');
	echo "<form>".$frm_xml."</form>"; // The active form
	echo "<form_layout_text>".rawurlencode($obj->form_layout_xml)."</form_layout_text>";

	// Fields
	// ------------------------------------------------------------
	echo "<fields>";
	foreach ($ofields as $fname=>$field)
	{
		echo "<field>";
		echo "<name>".escape($fname)."</name>";
		echo "<title>".escape($field['title'])."</title>";
		echo "<type>".$field['type']."</type>";
		echo "<readonly>".(($field['readonly'])?'t':'f')."</readonly>";
		echo "<required>".(($field['required'])?'t':'f')."</required>";
		echo "<system>".(($field['system'])?'t':'f')."</system>";
		echo "<unique>".(($field['unique'])?'t':'f')."</unique>";
		echo "<auto>".(($field['auto'])?'t':'f')."</auto>";
		echo "<subtype>".$field['subtype']."</subtype>";
		echo "<use_when>".escape($field['use_when'])."</use_when>";
		echo "<default_value>".escape($objf->getDefault($field, "", 'null', $obj))."</default_value>";
		if ($field['fkey_table'] && is_array($field['fkey_table']))
		{
			echo "<fkey_table>";
			echo "<table>".$field['fkey_table']['table']."</table>";
			echo "<field>".$field['fkey_table']['field']."</field>";
			if ($field['fkey_table']['ref_table'] && is_array($field['fkey_table']['ref_table']))
			{
				echo "<ref_table>";
				echo "<table>".$field['fkey_table']['ref_table']['table']."</table>";
				echo "<this>".$field['fkey_table']['ref_table']['this']."</this>";
				echo "<ref>".$field['fkey_table']['ref_table']['ref']."</ref>";
				echo "</ref_table>";
			}
			echo "</fkey_table>";
		}

		// Get optionval values
		/*
		if (($field['type'] == "fkey" || $field['type'] == "fkey_multi") && is_array($field['fkey_table']) 
			&& $field['subtype'] != "user_file_categories" && $field['subtype'] != "user_files"
			&& $field['subtype'] != "customers" && $field['subtype'] != "customer")
		{
			echo "<optional_values>";
			$query = "select ".$field['fkey_table']['key']." as key";
			if ($field['fkey_table']['title'])
				$query .= ", ".$field['fkey_table']['title']." as title";
			if ($field['fkey_table']['parent'])
				$query .= ", ".$field['fkey_table']['parent']." as parent";
			$query .= " from ".$field['subtype'];
			if ($dbh->ColumnExists($field['subtype'], "account_id"))
				$query .= " where account_id='$ACCOUNT' or account_id is null ";
			$query .= " order by ".(($field['fkey_table']['title'])?$field['fkey_table']['title']:$field['fkey_table']['key']);
			$query .= " LIMIT 100";
			$result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);
				echo "<value key=\"".$row['key']."\" title=\"".escape($row['title'])."\" parent=\"".escape($row['title'])."\" heiarch=\"";
				echo ($field['fkey_table']['parent']) ? "t" : "f";
				echo "\" parent_id=\"".$row['parent']."\"></value>";
			}
			echo "</optional_values>";
		}
		else */

		// TODO: Marl, this should move to getGroupings - then the only thing that will show up here are manual option_values and alias (below)
		/*
		if ($field['type'] == "fkey_multi" && is_array($field['fkey_table']) 
			&& $field['subtype'] != "user_file_categories" && $field['subtype'] != "user_files"
			&& $field['subtype'] != "customers" && $field['subtype'] != "customer")
		{
			echo "<optional_values>";
			/*$query = "select ".$field['fkey_table']['key']." as key";
			if ($field['fkey_table']['title'])
				$query .= ", ".$field['fkey_table']['title']." as title";
			if ($field['fkey_table']['parent'])
				$query .= ", ".$field['fkey_table']['parent']." as parent";
			$query .= " from ".$field['subtype'];
			if ($dbh->ColumnExists($field['subtype'], "user_id"))
				$query .= " where user_id='$USERID' or user_id is null ";
			$query .= " order by ".(($field['fkey_table']['title'])?$field['fkey_table']['title']:$field['fkey_table']['key']);
			$query .= " LIMIT 100";
			$result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);
				echo "<value key=\"".$row['key']."\" title=\"".escape($row['title'])."\" parent=\"".escape($row['parent'])."\" heiarch=\"";
				echo ($field['fkey_table']['parent']) ? "t" : "f";
				echo "\" parent_id=\"".$row['parent']."\"></value>";
			}
			echo "</optional_values>";
		}
		else */
		if (isset($field['optional_values']) && is_array($field['optional_values']) && count($field['optional_values']))
		{
			echo "<optional_values>";
			foreach ($field['optional_values'] as $key=>$val)
			{
				if (($key || $val) && $key!="0" && $val!="0")
					echo "<value key=\"".rawurlencode($key)."\" title=\"".escape($val)."\"></value>";
			}
			echo "</optional_values>";
		}
		else if ($field['type'] == "alias")
		{
			echo "<optional_values>";
			foreach ($ofields as $afname=>$afield)
			{
				if ($afield['type'] == $field['subtype'])
				{
					echo "<value key=\"".escape($afname)."\" title=\"".escape($afield['title'])."\" parent=\"\" heiarch=\"f\" parent_id=\"\"></value>";
				}
			}
			echo "</optional_values>";
		}

		echo "</field>";
	}
	echo "</fields>";
}
echo "</object>";
