<?php
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	// ANT LIB
	require_once("../lib/CDatabase.awp");
	require_once("../email/email_functions.awp");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	$dbh = new CDatabase();

	$USERID = null;
	$settings_account_number = null;

	function adminGetReceivers($dbh, $tid)
	{
		$ret = "";

		$result = $dbh->Query("select send_to from email_messages where thread='$tid'");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			$rec = str_replace(",", ";", $row['send_to']);
			$ret = ($ret) ? $ret.";".$rec : $rec;
		}
		$dbh->FreeResults($result);

		return $ret;
	}

	// Set receivers for each thread
	$result = $dbh->Query("select id from email_threads where receivers is null");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetNextRow($result, $i);
	
		$dbh->Query("update email_threads set receivers='".$dbh->Escape(adminGetReceivers($dbh, $row['id']))."' where id='".$row['id']."'");
	}
	$dbh->FreeResults($result);
?>

