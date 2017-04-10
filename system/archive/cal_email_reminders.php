<?php
	require_once("../lib/AntConfig.php");
	require_once("../lib/CDatabase.awp");
	require_once("../users/user_functions.php");
	require_once("../calendar/calendar_functions.awp");
	require_once("../lib/Email.php");
	require_once("../email/email_functions.awp");

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	// Get database to use from account
	if ($settings_version) // limit to current version
		$res_sys = $dbh_sys->Query("select database, server from accounts where version='$settings_version' ".(($ACCOUNT_DB)?" and database='$ACCOUNT_DB'":''));
	else
		$res_sys = $dbh_sys->Query("select database, server from accounts ".(($ACCOUNT_DB)?" and database='$ACCOUNT_DB'":''));

	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		$server = $dbh_sys->GetValue($res_sys, $s, 'server');
		echo "Updating $dbname\n";

		if ($dbname)
		{
			$dbh = new CDatabase($server, $dbname);

			$query = "select calendar_events_reminders.type, calendar_events_reminders.send_to, 
						calendar_events.name,  calendar_events.start_block, 
						calendar_events.end_block, calendar_events.all_day, 
						calendar_events.date_start, calendar_events.date_end, calendar_events.location,
						calendar_events_reminders.event_id, calendar_events_reminders.id as remid from 
						calendar_events_reminders, calendars, calendar_events
						where calendar_events_reminders.event_id=calendar_events.id and calendar_events_reminders.recur_id is NULL
						and calendar_events.calendar=calendars.id and calendar_events_reminders.type != '3'
						and calendar_events_reminders.execute_time <= (SELECT CURRENT_TIMESTAMP at time zone 'America/Los_Angeles')::TIMESTAMP
						and (calendar_events_reminders.complete is not true)";
			$result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($result);
			for($i=0; $i<$num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				$dates = $row['date_start']." - ".(($row['date_end']) ? $row['date_end'] : "No End");
				$times = ($row['all_day'] == 't') ? "All Day" : GetBlockName($row['start_block'])." - ".GetBlockName($row['end_block']);
				$location = $row['location'];
				// Create message ID
				$message_id = '<'.time().'.antmail@'.$dbname.'>';
				// Set reminder complete
				//$dbh->Query("update calendar_events_reminders set complete='t' where id='".$row['remid']."'");
				$dbh->Query("delete from calendar_events_reminders where id='".$row['remid']."'");
				// Send reminder
				switch (intval($row['type']))
				{
				case 1: // email
					$from    = AntConfig::getInstance()->email['noreply'];
					$to      = $row['send_to'];
					$subject = "Event Reminder (".$row['name'].")- ".date("m/d/Y");
					$message = "Event Name: ".$row['name']."\r\n";
					$message .= "Location: $location\r\n";
					$message .= "Dates: $dates\r\n";
					$message .= "Times: $times\r\n";
					$headers['From']  = $from;
					$headers['To']  = $to;
					$headers['Subject']  = $subject;
					$headers["Message-ID"] = $message_id;
					$headers["Date"] =  date('D, j M Y H:i:s ', mktime()) . EmailTimeZone();
					// Create new email object
					$email = new Email();
					$status = $email->send($to, $headers, $message);
					unset($email);
					break;
				case 2: // text message
					$from    = $settings_admin_contact;
					$parts = explode("@", $row['send_to']);
					if (count($parts)>=2)
					{
						$to = preg_replace('/[^0-9]*/','', $parts[0]); 
						$to.= '@'.$parts[1];

						$subject = "Reminder (".$row['name'].")- ".date("m/d/Y");
						$message = $row['name']."\n";
						$message .= "Dates: $dates\n";
						$message .= "Times: $times\n";
						$headers['From']  = "$from";
						$headers['To']  = $to;
						$headers['Subject']  = $subject;
						$headers["Message-ID"] = $message_id;
						$headers["Date"] =  date('D, j M Y H:i:s ', mktime()) . EmailTimeZone();
						$email = new Email();
						$status = $email->send($to, $headers, $message);
						unset($email);
					}

					break;
				}
			}
			$dbh->FreeResults($result);
		}
	}
?>
