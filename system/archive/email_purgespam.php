<?php
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	require_once("../email/email_functions.awp");
	// ANT LIBz
	require_once("../lib/CDatabase.awp");
	require_once("../lib/aereus.lib.php/CAnsClient.php");

	$ans = new CAnsCLient();
	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	function sysDetachThreadToTeach($dbh, $ans, $tid)
	{
		$result = $dbh->Query("select id from email_messages where thread='$tid'");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			sysDetachEmailToTeach($dbh, $ans, $row['id']);
		}
		$dbh->FreeResults($result);
	}

	function sysDetachEmailToTeach($dbh, $ans, $mid)
	{
		@mkdir("../tmp/email_spam_tolearn");

		$result = $dbh->Query("select id, file_id, message from email_message_original where message_id='$mid'");
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$fname = "fullmsg".$row['id'].".eml"; // This is how the message is detached

			if ($row['file_id'])
			{
				if ($ans->fileExists($fname, $row['file_id'], "/userfiles"))
				{
					// Get URL to download the file
					$path = $ans->getFileUrl($fname, $row['file_id'], "/userfiles", 0);
					$full_message = file_get_contents($path);
				}
			}
			else if ($row['message'])
			{
				$full_message = $row['message'];
			}

			if ($full_message)
			{
				file_put_contents("../tmp/email_spam_tolearn/$fname", $full_message);
			}
		}
		$dbh->FreeResults($result);
	}

	error_reporting(E_ERROR | E_WARNING | E_PARSE);

	if ($ACCOUNT_DB)
		$res_sys = $dbh_sys->Query("select id, database from accounts where database='$ACCOUNT_DB'");
	else
		$res_sys = $dbh_sys->Query("select id, database from accounts where f_use_ans is not false");
	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		$acid = $dbh_sys->GetValue($res_sys, $s, 'id');
		echo "Updating $dbname\n";

		if ($dbname)
		{
			$dbh = new CDatabase($settings_db_server, $dbname, $settings_db_user, $settings_db_password, $settings_db_type);

			// Get all spam directories
			$result = $dbh->Query("select id from email_mailboxes where flag_special='t' and name='Junk Mail'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);

				// First purge threads
				$res2 = $dbh->Query("select id from email_threads where 
										id in (select thread_id from email_thread_mailbox_mem where mailbox_id='".$row['id']."') 
										and time_updated < (now() - interval '15 days')::timestamp;");
				$num2 = $dbh->GetNumberRows($res2);
				for ($j = 0; $j < $num2; $j++)
				{
					$row2 = $dbh->GetNextRow($res2, $j);
					// Purge message
					EmailDeleteMessage($dbh, $userid, "thread", $row2['id']);

					// Put message in temp "tolear" dir so spamassassin can learn from the keywords
					sysDetachThreadToTeach($dbh, $ans, $row2['id']);
				}
				$dbh->FreeResults($res2);

				// Get message id
				$res2 = $dbh->Query("select id from email_messages where mailbox_id='".$row['id']."' and
									  message_date < (now() - interval '15 days')::timestamp;");
				$num2 = $dbh->GetNumberRows($res2);
				for ($j = 0; $j < $num2; $j++)
				{
					$row2 = $dbh->GetNextRow($res2, $j);
					// Put message in temp "tolear" dir so spamassassin can learn from the keywords
					sysDetachEmailToTeach($dbh, $ans, $row2['id']);

					// Purge message
					EmailDeleteMessage($dbh, $userid, "id", $row2['id']);
				}
				$dbh->FreeResults($res2);
			}
			$dbh->FreeResults($result);
		}
	}
?>
