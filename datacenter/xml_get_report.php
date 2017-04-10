<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("lib/COlapCube.php");
	require_once("lib/aereus.lib.php/CChart.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID = $USER->id;
	$ACCOUNT = $USER->accountId;
	$RID = $_GET['rid']; // Report id

	if (!$RID)
		echo "<error>No Report ID</error>";
	
	$obj = new CAntObject($dbh, "report", $RID, $USER);
	$DACL = new Dacl($dbh, "/objects/$OBJ_TYPE/$RID", false);
	if (!$DACL->id)
	{
		$DACL = new Dacl($dbh, "/objects/$OBJ_TYPE/$RID", true, $OBJECT_FIELD_ACLS);
		// Create dacl and set default perms
		$DACL->grantGroupAccess(GROUP_EVERYONE, "View");
	}

	// Reset id if using uname
	if (!is_numeric($RID))
		$RID = $obj->id;
	
	// Get permissions
	$f_canview = $DACL->checkAccess($USER, "View", ($USER->id==$obj->owner_id)?true:false);
	$f_canedit = $DACL->checkAccess($USER, "Edit", ($USER->id==$obj->owner_id)?true:false);
	$f_candelete = $DACL->checkAccess($USER, "Delete", ($USER->id==$obj->owner_id)?true:false);

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	echo "<report sec_view='".(($f_canview)?'t':'f')."' sec_edit='".(($f_canedit)?'t':'f')."' sec_delete='".(($f_candelete)?'t':'f')."'>";

	echo "<obj_type>".rawurlencode($obj->getValue("obj_type"))."</obj_type>";
	echo "<name>".rawurlencode($obj->getValue("name"))."</name>";
	echo "<description>".rawurlencode($obj->getValue("description"))."</description>";
	echo "<custom_report>".rawurlencode($obj->getValue("custom_report"))."</custom_report>";
	echo "<chart_type>".rawurlencode($obj->getValue("chart_type"))."</chart_type>";
	echo "<f_display_table>".rawurlencode($obj->getValue("f_display_table"))."</f_display_table>";
	echo "<f_display_chart>".rawurlencode($obj->getValue("f_display_chart"))."</f_display_chart>";
	echo "<f_calculate>".rawurlencode($obj->getValue("f_calculate"))."</f_calculate>";
	echo "<dacl_id>".$DACL->id."</dacl_id>";

	// Print dimensions
	echo "<dimensions>";
	echo "<dimension field=\"".rawurlencode($obj->getValue("dim_one_fld"))."\" group=\"".rawurlencode($obj->getValue("dim_one_grp"))."\"></dimension>";
	echo "<dimension field=\"".rawurlencode($obj->getValue("dim_two_fld"))."\" group=\"".rawurlencode($obj->getValue("dim_two_grp"))."\"></dimension>";
	echo "</dimensions>";

	// Print measures
	echo "<measures>";
	echo "<measure field=\"".rawurlencode($obj->getValue("measure_one_fld"))."\" aggregate=\"".rawurlencode($obj->getValue("measure_one_agg"))."\"></measure>";
	echo "</measures>";

	// Get view
	$query = "select id, name, description, f_default, user_id, filter_key from app_object_views where report_id='".$obj->id."'";
	$result = $dbh->Query($query);
	if ($dbh->GetNumberRows($result))
	{
		$row = $dbh->GetRow($result, 0);

		$view = new CAntObjectView();
		$view->id = $row['id'];
		$view->name = $row['name'];
		$view->description = $row['description'];
		$view->filterKey = $row['filter_key'];
		$view->fDefault = true;
		$view->userid = $row['user_id'];
		$view->loadAttribs($dbh);

		echo $view->getXml($USERID);
	}

	echo "</report>";
?>
