<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");


	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID = $USER->id;
	$ACCOUNT = $USER->accountId;
	
	$RPID = $_GET['rpid'];
	
	error_reporting(0);
	
	header("Content-type: text/xml");
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
	if( $RPID<0 )
	{
		echo "<result><error>1</error><error_msg>Recurrence Pattern not found</error_msg></result>";
	}
	else
	{
		$obj = new CAntObject($dbh, "calendar_event");
		$obj->getRecurrencePattern($RPID);

		if( $obj->recurrencePattern->parentId>0 )
		{	
			$obj->id = $obj->recurrencePattern->parentId;
			$obj->load();
		}
		
		$obj->recurrencePattern->timeStart = strlen($obj->getValue('ts_start'))>0 ? date('g:i A',strtotime($obj->getValue('ts_start'))) : '';
		$obj->recurrencePattern->timeEnd = strlen($obj->getValue('ts_end'))>0 ? date('g:i A',strtotime($obj->getValue('ts_end'))) : '';
		
		$obj->recurrencePattern->day1 = ($obj->recurrencePattern->dayOfWeekMask & WEEKDAY_SUNDAY) ? 't' : 'f';
		$obj->recurrencePattern->day2 = ($obj->recurrencePattern->dayOfWeekMask & WEEKDAY_MONDAY) ? 't' : 'f';
		$obj->recurrencePattern->day3 = ($obj->recurrencePattern->dayOfWeekMask & WEEKDAY_TUESDAY) ? 't' : 'f';
		$obj->recurrencePattern->day4 = ($obj->recurrencePattern->dayOfWeekMask & WEEKDAY_WEDNESDAY) ? 't' : 'f';
		$obj->recurrencePattern->day5 = ($obj->recurrencePattern->dayOfWeekMask & WEEKDAY_THURSDAY) ? 't' : 'f';
		$obj->recurrencePattern->day6 = ($obj->recurrencePattern->dayOfWeekMask & WEEKDAY_FRIDAY) ? 't' : 'f';
		$obj->recurrencePattern->day7 = ($obj->recurrencePattern->dayOfWeekMask & WEEKDAY_SATURDAY) ? 't' : 'f';
		
		echo "<recurrencepattern>";

		echo "<objpt_json>".json_encode($obj->recurrencePattern)."</objpt_json>";

		echo "</recurrencepattern>";

	}

?>
