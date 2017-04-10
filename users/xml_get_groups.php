<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	echo "<groups>";

	$query = "select id, name from user_groups where id is not null";
	if ($_GET['gid'])
		$query .= " and id='".$_GET['gid']."' ";
	if ($_GET['uid'])
	{
		$query .= " and id in (select group_id from user_group_mem where user_id='".$_GET['uid']."') ";
	}
	if ($_GET['search'])
	{
		$search = rawurldecode($_GET['search']);
		$sparts = explode(" ", $search);

		$cond = "";
		foreach ($sparts as $part)
		{
			if ($cond) $cond .= " and ";
			$cond .= " (lower(name) like lower('%".$dbh->Escape($part)."%'))";
		}

		$query .= " and ($cond) ";
	}
	$query .= " order by name ";
	
	$result = $dbh->Query($query);
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetNextRow($result, $i);

		// Get name
		echo "<group>";
		echo "<id>".rawurlencode($row['id'])."</id>";
		echo "<name>".rawurlencode(stripslashes($row['name']))."</name>";
		echo "</group>";
	}

	echo "</groups>";
?>
