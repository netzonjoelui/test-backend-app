<?php
	/*
	*	Move old task recurrence to the new recurrence system
	*/

	$result = $dbh_acc->Query("select id, recur_id from project_tasks where recur_id is not null and id='74311' order by id;");
	$num = $dbh_acc->GetNumberRows($result);
	$recur_handled = array();
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);
		$tid = $row['id'];
		$rid = $row['recur_id'];

		if (in_array($rid, $recur_handled))
			continue; // skip over, we've alrady converted this recurrence
		else
			$recur_handled[] = $rid;

		$obj = new CAntObject($dbh_acc, "task", $tid);
		// Make sure this has not alraedy been converted
		//if (!$obj->isRecurring())
		{
			$rp = $obj->getRecurrencePattern();

			// Get old
			$res2 = $dbh_acc->Query("select *, week_days[1] as day1,
									  week_days[2] as day2, week_days[3] as day3, week_days[4] as day4, week_days[5] as day5,
									  week_days[6] as day6, week_days[7] as day7 
									  from project_tasks_recurring where id='$rid'");			
			if ($dbh_acc->GetNumberRows($res2))
				$row2 = $dbh_acc->GetRow($res2, 0);

			// Create new from old
			$rp->interval = $row2['interval'];
			$rp->dateStart = $row2['date_start'];

			switch($row2['type']) 
			{
			case 1: // daily
				$rp->type = RECUR_DAILY;
				break;

			case 2: // weekly
				$rp->type = RECUR_WEEKLY;
				if ($row2['day1'] == 't')
					$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_SUNDAY;
				if ($row2['day2'] == 't')
					$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_MONDAY;
				if ($row2['day3'] == 't')
					$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_TUESDAY;
				if ($row2['day4'] == 't')
					$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_WEDNESDAY;
				if ($row2['day5'] == 't')
					$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_THURSDAY;
				if ($row2['day6'] == 't')
					$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_FRIDAY;
				if ($row2['day7'] == 't')
					$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_SATURDAY;
				break;

			case 3: // monthly
				if ($row2["relative_type"]) 
				{
					$rp->type = RECUR_MONTHNTH;
					$rp->instance = $row2["relative_type"]; // 1st-5th, 5th=last
					switch ($row2["relative_section"])
					{
					case 1:
						$rp->dayOfWeekMask = WEEKDAY_SUNDAY;
						break;
					case 2:
						$rp->dayOfWeekMask = WEEKDAY_MONDAY;
						break;
					case 3:
						$rp->dayOfWeekMask = WEEKDAY_TUESDAY;
						break;
					case 4:
						$rp->dayOfWeekMask = WEEKDAY_WEDNESDAY;
						break;
					case 5:
						$rp->dayOfWeekMask = WEEKDAY_THURSDAY;
						break;
					case 6:
						$rp->dayOfWeekMask = WEEKDAY_FRIDAY;
						break;
					case 7:
						$rp->dayOfWeekMask = WEEKDAY_SATURDAY;
						break;
					}
				}
			else
				{
					$rp->type = RECUR_MONTHLY;
					$rp->dayOfMonth = $row2['day'];
				}

				break;

			case 4: // yearly
				if ($row2["relative_type"]) 
				{
					$rp->type = RECUR_YEARNTH;
					$rp->instance = $row2["relative_type"]; // 1st-5th, 5th=last
					switch ($row2["relative_section"])
					{
					case 1:
						$rp->dayOfWeekMask = WEEKDAY_SUNDAY;
						break;
					case 2:
						$rp->dayOfWeekMask = WEEKDAY_MONDAY;
						break;
					case 3:
						$rp->dayOfWeekMask = WEEKDAY_TUESDAY;
						break;
					case 4:
						$rp->dayOfWeekMask = WEEKDAY_WEDNESDAY;
						break;
					case 5:
						$rp->dayOfWeekMask = WEEKDAY_THURSDAY;
						break;
					case 6:
						$rp->dayOfWeekMask = WEEKDAY_FRIDAY;
						break;
					case 7:
						$rp->dayOfWeekMask = WEEKDAY_SATURDAY;
						break;
					}
				}
				else
				{
					$rp->type = RECUR_YEARLY;
					$rp->dayOfMonth = $row2['day'];
				}
				$rp->monthOfYear = $row2['month'];
				break;
			}

			// Termination
			if ($row2["date_end"]) 
				$rp->dateEnd = $row2['date_end'];
		}
			
		//echo "-----------------------------------------------\n";
		//echo "From: ";
		//echo var_export($row2, true)."\n\n";

		//echo "TO: ";
		//echo var_export($rp, true)."\n\n";

		$obj->save(false);

		echo "Moved ".($i+1)." of $num - $rid to ".$rp->id."\n";
		unset($obj);
	}
?>
