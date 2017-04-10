<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("email/email_functions.awp");
	require_once("contacts/contact_functions.awp");
	require_once("calendar/calendar_functions.awp");
	require_once("lib/CPageShell.php");

	// ANT system vars
	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	// Module specific vars

	// Handle form submission and actions
	// ---------------------------------------------------------------
	$page = new CPageShell();

	// Print header/toolbar
	$toolbar = "<a href='home.php'><img src='/images/icons/goback_small.gif' border='0'> Back to Home</a>";
   
	echo "<div style='border-bottom:1px solid;'>
			$toolbar			
		  </div>";

	$events = CalGetAgenda($dbh, $USERID, date("m/d/Y"), date("m/d/Y", strtotime("+ 1 week")));

	echo "<table>";
	foreach ($events as $event) 
	{
		if ($event['type'] == "header") 
		{
			$name = $event['name'];
			$date = $event['date'];

			echo "<tr><td colspan='2' style='font-weight: bold; border-bottom: 1px solid;'>$name, $date</td></tr>";
		}
		else if ($event['type'] == "event") 
		{
			$name = $event['name'];
			$color = $event['color'];
			$time_start = $event['time_start'];
			$time_end = $event['time_end'];
			$rid = $event['rid'];
			$eid = $event['eid'];
			$cid = $event['calendar_id'];

			echo "<tr><td>$time_start - $time_end</td><td style='background-color:#$color;'>$name</td></tr>";
		}
	} 
	echo "</table>";

	echo "<div style='border-top:1px solid;'>
			$toolbar			
		  </div>";
?>
