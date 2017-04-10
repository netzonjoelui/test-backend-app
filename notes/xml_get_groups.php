<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	function localCustGetDataPrintChildren(&$dbh, $gid)
	{
		$result = $dbh->Query("select id, parent_id, name, color from user_notes_categories where parent_id='$gid' order by name");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);

			echo "<group>";
			echo "<id>".$row['id']."</id>";
			echo "<parent_id>".$row['parent_id']."</parent_id>";
			//echo "<full_name>".rawurlencode(ic_GroupGetFullName($dbh, $row['id']))."</full_name>";
			echo "<color>".$row['color']."</color>";
			echo "<name>".rawurlencode($row['name'])."</name>";
			echo "</group>";

			localCustGetDataPrintChildren($dbh, $row['id']);
		}
		$dbh->FreeResults($result);
	}

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	echo "<response>";

	echo "<groups>";
	$result = $dbh->Query("select id, parent_id, name, color from user_notes_categories 
							where parent_id is null and user_id='$USERID' order by name");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetNextRow($result, $i);

		echo "<group>";
		echo "<id>".$row['id']."</id>";
		echo "<parent_id>".$row['parent_id']."</parent_id>";
		//echo "<full_name>".rawurlencode(ic_GroupGetFullName($dbh, $row['id']))."</full_name>";
		echo "<color>".$row['color']."</color>";
		echo "<name>".rawurlencode($row['name'])."</name>";
		echo "</group>";

		localCustGetDataPrintChildren($dbh, $row['id']);
	}
	$dbh->FreeResults($result);
	echo "</groups>";

	echo "</response>";
?>
