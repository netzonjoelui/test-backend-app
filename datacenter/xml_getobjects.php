<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/Dacl.php");
	require_once("datacenter_functions.awp");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$DBID = $_GET['dbid'];

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	echo "<objects>";

	$result = $dbh->Query("select id, name from dc_database_objects where database_id='$DBID'");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetNextRow($result, $i);
		$objdef = new CAntObject($dbh, $DBID.".".$row['name']);
		$primary = (!$i) ? 't' : 'f';
		$DACL = new Dacl($dbh, "/objects/".$DBID.".".$row['name'], true, $OBJECT_FIELD_ACLS);

		// Get name
		echo "<object>";
		echo "<id>".rawurlencode($row['id'])."</id>";
		echo "<f_primary>".rawurlencode($primary)."</f_primary>";
		echo "<name>".rawurlencode($row['name'])."</name>";
		echo "<objname>".rawurlencode($DBID.".".$row['name'])."</objname>";
		echo "<title>".rawurlencode($objdef->title)."</title>";
		echo "<dacl>".$DACL->id."</dacl>";
		echo "<groups>";
		$field = $objdef->fields->getField("groups");
		if ($field)
		{
			if ($field['type'] == "fkey_multi" && $field['subtype'] && $field['fkey_table']['key'] && $field['fkey_table']['title'])
			{
				echo dc_getGroups($dbh, $field['subtype'], $field['fkey_table']['key'], $field['fkey_table']['title'], $field['fkey_table']['parent'], null);
			}
		}
		echo "</groups>";
		echo "</object>";
	}

	echo "</objects>";
?>
