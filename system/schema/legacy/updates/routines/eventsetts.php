<?php
	// Copy all project messages to discussions
	$result = $dbh_acc->Query("select calendar_events.id, start_block, end_block, date_start, date_end,
								calendar, calendars.user_id from calendar_events, calendars where ts_start is null
								and calendar_events.calendar=calendars.id");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);
		$tzCode = UserGetTimeZone($dbh_acc, $row['user_id']);
		$tzName = timezone_name_from_abbr($tzCode);

		if ($tzName)
		{
			$dbh_acc->SetTimezone($tzName);
			date_default_timezone_set($tzName);
		}

		$time_start_str = GetBlockName($row['start_block']);
		$time_end_str = GetBlockName($row['end_block']);
 
		if ($row['date_start'] && $row['date_end'] && $time_start_str && $time_end_str && $row['user_id'])
		{
			$dbh_acc->Query("update calendar_events set ts_start='".$row['date_start']." $time_start_str',
							ts_end='".$row['date_end']." $time_end_str', user_id='".$row['user_id']."'
							where id='".$row['id']."'");
			echo "Updated event ".$row['id']."\n";
		}
		else
		{
			echo "Skipped event ".$row['id']." due to missing data!\n";
		}
	}
?>
