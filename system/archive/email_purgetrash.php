<?php
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	// ANT LIBz
	require_once("../lib/CDatabase.awp");
	require_once("../email/email_functions.awp");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	$dbh = new CDatabase();

	/* This feature was disabled due to user's archiving emails in trash
	// Get all spam directories
	$result = $dbh->Query("select id from email_mailboxes where flag_special='t' and name='Trash'");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetNextRow($result, $i);

		// First purge threads
		$res2 = $dbh->Query("select id from email_threads where id in (select thread_id from email_thread_mailbox_mem where mailbox_id='".$row['id']."')							  time_updated < (now() - interval '30 days')::timestamp;");
		$num2 = $dbh->GetNumberRows($res2);
		for ($j = 0; $j < $num2; $j++)
		{
			$row2 = $dbh->GetNextRow($res2, $j);
			// Purge message
			EmailDeleteMessage($dbh, $userid, "thread", $row2['id']);
		}
		$dbh->FreeResults($res2);

		// Get message id
		$res2 = $dbh->Query("select id from email_messages where mailbox_id='".$row['id']."' and
							  message_date < (now() - interval '30 days')::timestamp;");
		$num2 = $dbh->GetNumberRows($res2);
		for ($j = 0; $j < $num2; $j++)
		{
			$row2 = $dbh->GetNextRow($res2, $j);
			// Purge message
			EmailDeleteMessage($dbh, $userid, "id", $row2['id']);
		}
		$dbh->FreeResults($res2);
	}
	$dbh->FreeResults($result);
	*/
?>
