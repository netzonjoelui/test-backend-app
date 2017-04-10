<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$EMAILUSERID = EmailGetUserId($dbh, $USERID);

	$inbx_id = EmailGetSpecialBoxId($dbh, $EMAILUSERID, "Inbox");
	$junk_id = EmailGetSpecialBoxId($dbh, $EMAILUSERID, "Junk Mail");
	$sent_id = EmailGetSpecialBoxId($dbh, $EMAILUSERID, "Sent");
	$draft_id = EmailGetSpecialBoxId($dbh, $EMAILUSERID, "Drafts");
	$trash_id = EmailGetSpecialBoxId($dbh, $EMAILUSERID, "Trash");

	function localEmailGetDataPrintChildren(&$dbh, $gid)
	{
		$result = $dbh->Query("select id, name, flag_special, color from email_mailboxes where parent_box='$gid' order by name");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);

			echo "<group>";
			echo "<id>".$row['id']."</id>";
			echo "<parent_id>".$gid."</parent_id>";
			echo "<color>".$row['color']."</color>";
			echo "<name>".rawurlencode($row['name'])."</name>";
			echo "<flag_special>".rawurlencode($row['flag_special'])."</flag_special>";
			echo "</group>";

			localEmailGetDataPrintChildren($dbh, $row['id']);
		}
		$dbh->FreeResults($result);
	}

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	echo "<response>";

	echo "<groups>";

	echo "<group><id>$inbx_id</id><parent_id></parent_id><color></color><name>Inbox</name><flag_special>t</flag_special></group>";
	localEmailGetDataPrintChildren($dbh, $inbx_id);
	echo "<group><id>$junk_id</id><parent_id></parent_id><color></color><name>Junk Mail</name><flag_special>t</flag_special></group>";
	localEmailGetDataPrintChildren($dbh, $junk_id);
	echo "<group><id>$trash_id</id><parent_id></parent_id><color></color><name>Trash</name><flag_special>t</flag_special></group>";
	localEmailGetDataPrintChildren($dbh, $trash_id);
	echo "<group><id>$draft_id</id><parent_id></parent_id><color></color><name>Drafts</name><flag_special>t</flag_special></group>";
	localEmailGetDataPrintChildren($dbh, $draft_id);
	echo "<group><id>$sent_id</id><parent_id></parent_id><color></color><name>Sent</name><flag_special>t</flag_special></group>";
	localEmailGetDataPrintChildren($dbh, $sent_id);

	$result = $dbh->Query("select id, name, flag_special, color from email_mailboxes where email_user='$EMAILUSERID' 
							and parent_box is null and flag_special is not true order by name");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetNextRow($result, $i);

		echo "<group>";
		echo "<id>".$row['id']."</id>";
		echo "<parent_id></parent_id>";
		echo "<color>".$row['color']."</color>";
		echo "<name>".rawurlencode($row['name'])."</name>";
		echo "<flag_special>".rawurlencode($row['flag_special'])."</flag_special>";
		echo "</group>";

		localEmailGetDataPrintChildren($dbh, $row['id']);
	}
	$dbh->FreeResults($result);
	echo "</groups>";

	echo "</response>";
?>
