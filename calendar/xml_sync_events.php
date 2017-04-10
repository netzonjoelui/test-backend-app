<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("contacts/contact_functions.awp");
	require_once("customer/customer_functions.awp");
	require_once("calendar_functions.awp");
	
	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$ACCOUNT_NAME = $USER->accountName;

	$CALID = GetDefaultCalendar($dbh, $USERID);
	

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8"
	  standalone="yes"?>'; 
	
	echo "<events>\n";

	$recur_processed = array();

	$objList = new CAntObjectList($dbh, "calendar_event", $USER);
	$objList->addMinField("name");
	$objList->addMinField("recur_id");
	$objList->addMinField("ts_updated");
	$objList->addMinField("ts_start");
	$objList->addMinField("ts_end");
	$objList->addMinField("date_start");
	$objList->addMinField("date_end");

	$objList->addCondition("and", "calendar", "is_equal", $CALID);
	$objList->addCondition("and", "ts_start", "is_not_equal", null);
	$objList->addCondition("and", "ts_end", "is_not_equal", null);
	$objList->addCondition("and", "ts_updated", "is_not_equal", null);

	$objList->getObjects(0, 2000);
	$objList->addOrderBy("ts_updated", "DESC");
	$num = $objList->getNumObjects();
	for ($i = 0; $i < $num; $i++)
	{
		$row = $objList->getObjectMin($i);
		$print = true;

		if ($row['recur_id'])
		{
			$tr_query = "select id from calendar_events_recurring_ex where recurring_id='".$row['recur_id']."' and event_id='".$row['id']."'";
			if (in_array($row['recur_id'], $recur_processed) && !$dbh->GetNumberRows($dbh->Query($tr_query)))
				$print = false;

			$recur_processed[] = $row['recur_id'];
		}

		// If syncing to outlook exclude birthdays and annivarsaries because Outlook will add them anyway
		if ($row['contact_id'] && $_GET['type']=="outlook" && ($row['name']== "Birthday" 
			|| $row['name']== "Anniversary" || substr($row['name'], 0, strlen("Spouse Birthday"))== "Spouse Birthday"))
		{
			$print = false;
		}

		if ($print)
		{
			print("<event>");
			print("<id>".$row['id']."</id>");
			print("<name>".rawurlencode($row['name'])."</name>");
			print("<ts_start>".rawurlencode($row['ts_start'])."</ts_start>");
			print("<ts_end>".rawurlencode($row['ts_end'])."</ts_end>");
			print("<ts_updated>".rawurlencode($row['ts_updated'])."</ts_updated>");
			print("</event>");
		}
	}

	/*
	$query = "select calendar_events.id, recur_id, to_char(ts_updated, 'MM/DD/YYYY HH12:MI:SS AM') as time_updated, date_start,
				name, start_block, end_block, date_start, date_end, to_char(ts_start, 'HH12:MI:SS AM') as time_start,
				calendar_event_associations.contact_id, calendar_event_associations.customer_id, calendar_event_associations.lead_id, 
				to_char(ts_end, 'HH12:MI:SS AM') as time_end
				from calendar_events left outer join calendar_event_associations on (calendar_events.id=calendar_event_associations.event_id) 
				where calendar='$CALID' and ts_start is not null order by date_start";
	$res = $dbh->Query($query);
	$num = $dbh->GetNumberRows($res);
	for ($i=0; $i<$num; $i++)
	{
		$row = $dbh->GetNextRow($res, $i);
		$print = true;

		$time_start = ($row['time_start']) ? $row['time_start'] : GetBlockName($row['start_block']);
		$time_end = ($row['time_end']) ? $row['time_end'] : GetBlockName($row['end_block']);

		if (!$row['time_updated'])
		{
			$row['time_updated'] = date("m/d/Y g:i:s a", strtotime($row['date_start']));
			$dbh->Query("update calendar_events set ts_updated='".$row['time_updated']."' where id='".$row['id']."'");
		}
		
		if ($row['recur_id'])
		{
			$tr_query = "select id from calendar_events_recurring_ex where recurring_id='".$row['recur_id']."' and event_id='".$row['id']."'";
			if (in_array($row['recur_id'], $recur_processed) && !$dbh->GetNumberRows($dbh->Query($tr_query)))
				$print = false;

			$recur_processed[] = $row['recur_id'];
		}

		// If syncing to outlook exclude birthdays and annivarsaries because Outlook will add them anyway
		if ($row['contact_id'] && $_GET['type']=="outlook" && ($row['name']== "Birthday" 
			|| $row['name']== "Anniversary" || substr($row['name'], 0, strlen("Spouse Birthday"))== "Spouse Birthday"))
		{
			$print = false;
		}

		if ($row['contact_id'])
			$row['name'] = ContactGetName($dbh, $row['contact_id']).": ".$row['name'];
		else if ($row['customer_id'])
			$row['name'] = CustGetName($dbh, $row['customer_id']).": ".$row['name'];
		else if ($row['lead_id'])
			$row['name'] = CustLeadGetName($dbh, $row['lead_id']).": ".$row['name'];

		if ($print)
		{
			print("<event>");
			print("<id>".$row['id']."</id>");
			print("<name>".rawurlencode($row['name'])."</name>");
			print("<ts_start>".rawurlencode($row['date_start']." ".$time_start)."</ts_start>");
			print("<ts_end>".rawurlencode($row['date_end']." ".$time_end)."</ts_end>");
			print("<ts_updated>".rawurlencode($row['ts_updated'])."</ts_updated>");
			print("</event>");
		}
	}
	$dbh->FreeResults($res);
	*/
		
	echo "</events>";
?>
