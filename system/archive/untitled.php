<?php
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	require_once("../email/email_functions.awp");
	// ANT LIBz
	require_once("../lib/CDatabase.awp");
	require_once("../lib/aereus.lib.php/CAnsClient.php");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	$dbh = new CDatabase();
	$ans = new CAnsCLient();

	function sysDetachThreadToTeach($dbh, $ans, $tid)
	{
		$result = $dbh->Query("select id from email_messages where thread='$tid'");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			sysDetachEmailToTeach(&$dbh, &$ans, $row['id']);
		}
		$dbh->FreeResults($result);
	}

	function sysDetachEmailToTeach($dbh, $ans, $mid)
	{
		mkdir("../tmp/email_spam_tolearn");

		$result = $dbh->Query("select id, file_id, message from email_message_original where message_id='$mid'");
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$fname = "fullmsg".$row['id'].".eml"; // This is how the message is detached
			echo "Detaching $fname\n";

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
	
	$dbh = new CDatabase();

	// Get all spam directories
	$result = $dbh->Query("select id from email_mailboxes where flag_special='t' and name='Junk Mail'");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetNextRow($result, $i);

		// First purge threads
		$res2 = $dbh->Query("select id from email_threads where mailbox_id='".$row['id']."' and
							  time_updated < (now() - interval '29 days')::timestamp;");
		$num2 = $dbh->GetNumberRows($res2);
		for ($j = 0; $j < $num2; $j++)
		{
			$row2 = $dbh->GetNextRow($res2, $j);

			// Put message in temp "tolear" dir so spamassassin can learn from the keywords
			sysDetachThreadToTeach(&$dbh, $ans, $row2['id']);
		}
		$dbh->FreeResults($res2);

		// Get message id
		$res2 = $dbh->Query("select id from email_messages where mailbox_id='".$row['id']."' and
							  message_date < (now() - interval '29 days')::timestamp;");
		$num2 = $dbh->GetNumberRows($res2);
		for ($j = 0; $j < $num2; $j++)
		{
			$row2 = $dbh->GetNextRow($res2, $j);
			// Put message in temp "tolear" dir so spamassassin can learn from the keywords
			sysDetachEmailToTeach(&$dbh, $ans, $row2['id']);
		}
		$dbh->FreeResults($res2);
	}
	$dbh->FreeResults($result);

	/*
	$result = $dbh->Query("select id, template_task_id, project, start_date, deadline from project_tasks where template_task_id in 
						   (select id from project_template_tasks where timeline_date_due='date_started' 
						   	or timeline_date_begin='date_started') and done is false;");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetNextRow($result, $i);

		$query = "select start_interval, start_count, due_interval, due_count, 
				  timeline, type, file_id, user_id, position_id, timeline_date_begin, timeline_date_due 
				  from project_template_tasks where id='".$row['template_task_id']."'";
		$task_result = $dbh->Query($query);
		if ($dbh->GetNumberRows($task_result))
		{
			$task_row = $dbh->GetNextRow($task_result, 0);
			$tl_date_begin = ($task_row['timeline_date_begin']) ? $task_row['timeline_date_begin'] : 'date_deadline';
			$tl_date_due = ($task_row['timeline_date_due']) ? $task_row['timeline_date_due'] : 'date_deadline';

			$dstart = ProjectGetExeTime(&$dbh, ProjectGetAttrib($dbh, $tl_date_begin, $row['project']), 
										$task_row['start_count'], $task_row['start_interval'], $task_row['timeline']);
			$ddead = ProjectGetExeTime(&$dbh, ProjectGetAttrib($dbh, $tl_date_due, $row['project']), 
										$task_row['due_count'], $task_row['due_interval'], $task_row['timeline']);
			if ($ddead != $row['deadline'] || $dstart != $row['start_date'])
			{
				echo "Updating $i of $num - Deadline from ".$row['deadline']." to $ddead ";
				echo ": Start from ".$row['start_date']." to $dstart\n";
				$query = "update project_tasks set 
							start_date='".$dstart."', 
							deadline='".$ddead."' 
							where id='".$row['id']."'";
				$dbh->Query($query);
			}
		}
		$dbh->FreeResults($task_result);
	}
	$dbh->FreeResults($result);
	 */
?>
