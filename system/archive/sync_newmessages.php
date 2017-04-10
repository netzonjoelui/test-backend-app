<?php
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	// ANT LIB
	require_once("../lib/CDatabase.awp");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	ini_set("max_execution_time", "28800");	
	
	$dbh = new CDatabase();

	$result = $dbh->Query("select id from email_mailboxes");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetNextRow($result, $i);
		$boxnum = $row['id'];

		// Get number of new messages for this mailbox
		echo "running query for $boxnum<br>\n";
		$query = "select count(*) as cnt from email_messages where
					mailbox_id='$boxnum' and flag_seen='f';";
		$result2 = $dbh->Query($query);
		if ($dbh->GetNumberRows($result2))
		{
			$row2 = $dbh->GetNextRow($result2, 0);
			$cnt = $row2['cnt'];
			$dbh->FreeResults($result2);

			//echo "updating $boxnum to $cnt<br>\n";
			$dbh->Query("update email_mailboxes set i_newmessages='$cnt' where id='$boxnum'");
		}
	}
	$dbh->FreeResults($result);
?>
