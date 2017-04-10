<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("infocenter/ic_functions.php");
	require_once("customer/customer_functions.awp");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$OBJ_TYPE = $_GET['obj_type'];
	$OID = $_GET['oid'];
	
	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	$obj = CAntObject::factory($dbh, $OBJ_TYPE, $OID);

	// Get permissions
	$f_canview = $obj->dacl->checkAccess($USER, "View", ($USER->id==$obj->owner_id)?true:false);
	$f_canedit = $obj->dacl->checkAccess($USER, "Edit", ($USER->id==$obj->owner_id)?true:false);
	$f_candelete = $obj->dacl->checkAccess($USER, "Delete", ($USER->id==$obj->owner_id)?true:false);

	echo "<object sec_view='".(($f_canview)?'t':'f')."' sec_edit='".(($f_canedit)?'t':'f')."'";
	echo " icon_name=\"".escape($obj->getIconName())."\" sec_delete='".(($f_candelete)?'t':'f')."'>";
	
	// Load definition
	$fields = $obj->fields;

	$ofields = $obj->fields->getFields();
	foreach ($ofields as $fname=>$field)
	{
		echo "<$fname";
		$val = $obj->getValue($fname);
		
		if (($field['type']=='fkey' || $field['type']=='object') && $val )
		{
			echo " key=\"".rawurlencode($val)."\""; // Same as value but use for xml_query consistency

			if ($field['type']=='fkey')
			{
				switch ($field['subtype'])
				{
				case 'customers':
					echo " name=\"".rawurlencode(CustGetName($dbh, $val))."\"";
					break;
				case 'customer_leads':
					echo " name=\"".rawurlencode(CustLeadGetName($dbh, $val))."\"";
					break;
				default:
					echo " name=\"".rawurlencode($obj->getForeignValue($fname, $val))."\"";
					break;
				}
			}
			else
			{
				echo " name=\"".rawurlencode($obj->getForeignValue($fname, $val))."\"";
			}
		}

		if ($fname == "id")
			echo " allowopen='".(($f_canview)?'1':'0')."' ";

		echo ">";

		if ($field['type']=='fkey_multi' || $field['type']=='object_multi' || ($field['type']=='object' && !$field['subtype']))
		{
			if (is_array($val) && count($val))
			{
				foreach ($val as $subval)
				{
					echo "";
					if ($field['type']=='object_multi' || $field['type']=='object')
					{
						$parts = explode(":", $subval);
						if (count($parts)==2)
						{
							echo "<value key='".$parts[0].":".$parts[1]."'>".rawurlencode($obj->getForeignValue($fname, $subval))."</value>";
						}
						else if ($field['subtype'])
						{
							echo "<value key='".$subval."'>".rawurlencode($obj->getForeignValue($fname, $field['subtype'].":".$subval))."</value>";
						}
					}
					else
					{
						echo "<value key='$subval'>".rawurlencode($obj->getForeignValue($fname, $subval))."</value>";
					}
				}
			}
		}
		else
		{
			echo rawurlencode($val);
		}

		echo "</$fname>";
	}
	echo "</object>";
?>
