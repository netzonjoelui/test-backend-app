<?php 
	require_once("../../lib/AntConfig.php");
	require_once("ant.php");
	require_once("users/user_functions.php");
	require_once("lib/WindowFrame.awp");
	require_once("lib/CToolTable.awp");
	require_once("lib/content_table.awp");
	require_once("lib/Button.awp");
	require_once("calendar/calendar_functions.awp");
	require_once("contacts/contact_functions.awp");
	require_once("users/user_functions.php");
	require_once("lib/Email.php");
	require_once("lib/js_iframe_resize.awp");
	require_once("email/email_functions.awp");
	require_once("lib/CPageShellPublic.php");
	
	$dbh = $ANT->dbh;
	
    /*$obj = CAntObject::factory($dbh, "member", 12);
        print_r($obj->getValue("obj_member"));*/
    
    //exit();
    
    $eventProId = $_GET['cecid'];
    $invid = $_GET['memid'];
    $FWD = "memid=$invid&cecid=$eventProId";

	if (!$eventProId)
		die("There was a problem accessing this event proposal. It may have been deleted by the creator.");

    $eventProposal = CAntObject::factory($dbh, "calendar_event_proposal", $eventProId);
    
	// check if member id is actually a member of this event proposal - a simple securty measure to keep people
	// from using randon numbers
    $attendeeFound = false;
    $attendees = $eventProposal->getValue("attendees");
    foreach($attendees as $attendee)
    {
        if($attendee==$invid)
            $attendeeFound = true;
    }

	if (!$attendeeFound)
		die("You are no longer listed as attending this event. Please contact the originator of this event.");

	// Get member object
    $member = CAntObject::factory($dbh, "member", $invid);

	// Get referenced object for member if set
	$memberObj = null;
	if ($member->getValue("obj_member"))
	{
		$objRef= CAntObject::decodeObjRef($member->getValue("obj_member"));
		$memberObj = CAntObject::factory($dbh, $objRef['obj_type'], $objRef['id']);
	}
    
	$page = new CPageShellPublic("Event Coordinator", "home");
	$page->opts['print_subnav'] = false;	
	$page->PrintShell();

	$res = $dbh->Query("select name, notes, event_id, user_id from calendar_event_coord where id='$eventProId'");
	if ($dbh->GetNumberRows($res))
	{
		$EVENT_DATA = $dbh->GetNextRow($res, 0);
		$dbh->FreeResults($res);

		if ($EVENT_DATA['user_id'])
		{
			$timezonee = UserGetTimeZone($dbh, $EVENT_DATA['user_id']);
			if ($timezonee)
			{
				date_default_timezone_set($timezonee);
				$dbh->SetTimezone($timezonee);
			}
		}
	}

	// Handle comment submission
	// ---------------------------------------------------------------------------------
	if ($_POST['comment'] && $_POST['comment'] != "Enter optional comments here" && null != $memberObj)
	{
		$obja = CAntObject::factory($ANT->dbh, "comment", null);

		if ($memberObj->object_type == "user")
			$obja->setValue("owner_id", $memberObj->id);

		$obja->setValue("sent_by", $memberObj->object_type . ":" . $memberObj->id);

		$obja->setValue("obj_reference", "calendar_event_proposal:".$eventProId);
		$obja->setValue("comment", $_POST['comment']);
		$obja->setMValue("associations", "calendar_event_proposal:$eventProId");
		$cid = $obja->save();
	}

	// Handle user respose submission
	// ---------------------------------------------------------------------------------
	if ($_POST['rsvp'])
	{
		// Update times
		// ------------------------------
		$times = $eventProposal->getOptionalTimes($member->id);
		foreach ($times as $time)
		{
			if ($_POST['rsvp'] == 't')
				$resp = ($_POST['rsvp_times_' . $time['id']] == 1) ? 1 : 0; // Assume no if not set
			else
				$resp = 0;

			$eventProposal->setMemberAvailability($member->id, $time['id'], $resp);
		}

		// Set accept boolean
		if ($_POST['rsvp'] == 't')
            $member->setValue("f_accepted", "t");
		else if($_POST['rsvp'] == 'f')
            $member->setValue("f_accepted", "f");
           
		// Save accepted status
		$member->save();

		// Send notification to originating user
		$notification = CAntObject::factory($dbh, "notification");
		$notification->setValue("name", "Updated Availablity");
		$notification->setValue("description", $member->getValue("name") . " updated availability");
		$notification->setValue("obj_reference", "calendar_event_proposal:" . $eventProposal->id);
		$notification->setValue("f_popup", 'f');
		$notification->setValue("f_seen", 'f');
		$notification->setValue("owner_id", $eventProposal->getValue("user_id"));
		$notification->save();

		echo "<p class='notice'>Thank you for updating your availability! If you need to make any changes you can do so now or at any time before the event is finalized and scheduled.</p>";
	}
?>

<script language='javascript'>
	function ToggleTimes(attending)
	{
		var dv = document.getElementById("times_frm");
		dv.style.display = (attending) ? 'block' : 'none';
	}
</script>
<?php
	if ($eventProposal->getValue('event_id'))
	{
		echo "<p align='center' class='notice'>";
		echo "This meeting proposal has been converted to the following event:<br /><br />";
		echo "<h3>";
		$event = CAntObject::factory($dbh, "calendar_event", $eventProposal->getValue('event_id'));
		echo $event->getName();
		echo "</h3>";
		echo "</p>";
	}
	else
	{	
		// Display left column
		// ----------------------------------------------------------------------------------------------
		echo "<div class='g4'>";

		// Display right column
		// ----------------------------------------------------------------------------------------------
		echo "<fieldset><legend>My Availablity</legend>";

		switch ($member->getValue('accepted'))
		{
		case 't';
			print("<div class='alert'>You have already submitted your availability. However, you can change your response below</div>");
			break;
		case 'f':
			print("<div class='alert'>You have declined to attend this event. However, you can change your response below</div>");
			break;
		default:
			print("<div>Please select which dates and time will and will not work for you</div>");
			break;
		}
				
		print("<form name='invitation' method='post' action='coordresp.php?$FWD'>");
		echo "<input type='radio' name='rsvp' value='t' ".(($member->getValue('accepted')!='f')?'checked':'')." onclick='ToggleTimes(true)'> 
			  <span class='formLabel'>I will be attending this event</span>
			  <br />
			  <input type='radio' name='rsvp' value='f' ".(($member->getValue('accepted')=='f')?'checked':'')." onclick='ToggleTimes(false)'> 
			  <span class='formLabel'>I will not be attending this event</span>
			  <br /><br />";

		echo "<div id='times_frm' style='".(($member->getValue('accepted')=='f')?'display:none;':'')."'>";

		$tbl = new CToolTable;
		
		$tbl->StartHeaders();
		$tbl->AddHeader("Date &amp; Time");
		$tbl->AddHeader("Works?", 'center');
		$tbl->EndHeaders();

		$times = $eventProposal->getOptionalTimes($member->id);
		foreach ($times as $time)
		{
            $tsStart = $time['ts_start'];
            $tsEnd = $time['ts_end'];
            
            $startDate = date("m/d/Y", $tsStart);
            $startTime = date("h:i A", $tsStart);
            
            $endDate = date("m/d/Y", $tsEnd);
            $endTime = date("h:i A", $tsEnd);
            
            $date = $startDate;            
            if($startDate!=$endDate)
                $date .=  " - " + $endDate;
                
            if($tsStart==$tsEnd)
                $timeStr =  "All Day Event";
            else
                $timeStr =  "($startTime - $endTime)";
            
			$lbl = "<div class='b'>$date</div>";
			$lbl .= "<div class='small'>$timeStr</div>";
			$frm = "<input type='radio' name='rsvp_times_" . $time['id'] . "' value='1' ".(($time['response']==1)?'checked':'')."> Yes
				    <input type='radio' name='rsvp_times_" . $time['id'] . "' value='0' ".(($time['response']!=1)?'checked':'')."> No";
			$tbl->StartRow();
			$tbl->AddCell($lbl, null, "center");
			$tbl->AddCell($frm, NULL, 'center');
			$tbl->EndRow();
		}

		$tbl->PrintTable();

		echo "</div>";

		if (null != $memberObj)
		{
			print("<br />
					<textarea rows='5' name='comment' style='width: 98%;'>Enter optional comments here</textarea>");
		}

		echo "<div style='padding-top:5px;'>";
		echo "<button type='submit' name='save'>Update My Availablity</button>";
		echo "</div>";

		print("</form>");

		echo "</fieldset>";


		// Get attendees		
		echo "<fieldset><legend>Attendees</legend>";
        
        $obj = CAntObject::factory($dbh, "calendar_event_proposal", $eventProId);        
        $attendees = $obj->getValue("attendees");        
        $arrMember = array();
        foreach($attendees as $attendee)
        {
            $objMem = CAntObject::factory($dbh, "member", $attendee);
            $accepted = $objMem->getValue("f_accepted");
            $objUser = $objMem->getValue("obj_member");
            $attendeeName = $objMem->getValue("name");
            $attendeeRole = $objMem->getValue("role");
            
            if(empty($objUser))
            {
                foreach ($objMem as $memData) 
                {
                    if(is_array($memData))
                    {                        
                        if($memData["id"]==$attendee)
                            $attendeeName = $memData["name"];
                    }                            
                }
            }
            $arrMember[$accepted][] = array($attendeeName, $attendeeRole);            
        }
        
        if(sizeof($arrMember["t"])>0)
            echo "<h4>Confirmed</h4>";
        
        foreach($arrMember["t"] as $confirmed)
        {
            echo $confirmed[0];
            if($confirmed[1])
                echo "(" . $confirmed[1] . ")";
			echo "<br />";
        }
        
        if(sizeof($arrMember["f"])>0)
            echo "<h4 style='margin-top: 20px;'>Awaiting Reply or Declined</h4>";
                
        foreach($arrMember["f"] as $awaiting)
        {
            echo $awaiting[0];
            if($awaiting[1])
                echo " (" . $awaiting[1] . ")";
			echo "<br />";
        }
        
		echo "</fieldset>";

		echo "</div>";

		// Display right column
		// ----------------------------------------------------------------------------------------------
		echo "<div class='g8'>";

		/**
		 * Event details
		 */
		echo "<fieldset><legend>Event Details</legend>";
		echo "<table style='font-size:14px;'>";
		echo "<tr><td class='formLabel'>Event Name:</td><td>".$EVENT_DATA['name']."</td></tr>";
		echo "<tr><td class='formLabel'>Notes/Description:</td></tr>";
		echo "<tr><td colspan='2'>".str_replace("\n", "<br />", $EVENT_DATA['notes'])."</td></tr>";
		echo "</table>";
		echo "</fieldset>";

		// Show current attendee responses
		// -------------------------------------------------------------
		echo "<fieldset><legend>Attendee Availablity</legend>";

		$data = $eventProposal->getEventProposalData();
		$tbl = new CToolTable;
		$tbl->StartHeaders();
		$tbl->AddHeader("");
		
        // Optional times
		foreach ($data['optionalTimes'] as $time)
		{
			$start = strtotime($time['ts_start']);
			$end = strtotime($time['ts_end']);

			if (date("m/d/Y", $start) == date("m/d/Y", $end))
				$endStr = date("h:m A", $end);
			else
				$endStr = date("m/d/Y h:m A", $end);

			$tbl->AddHeader(date("m/d/Y", $start) . " " . date("h:m A", $start) . " - " . $endStr, 'center');
		}

		$tbl->EndHeaders();


        // Date - Attendee Data
        foreach($data["attendees"] as $attendeeData)
        {
            if(!empty($attendeeData["name"]))
            {
                $firstCell = true;
                foreach($data["optionalTimes"] as $optionalDateData)
                {
                    $dateAttendeeIndex = $optionalDateData["id"] . "_" . $attendeeData["id"];
                    $dateAttendeeData = $data["dateAttendee"][$dateAttendeeIndex];                
                    
                    // store the names of the attendess                    
                    switch($dateAttendeeData['response'])
                    {       
                        case "1":
                            $responseStatus = 1;
                            $responseText = "Available";
                            break;
                        case "0":
                            $responseStatus = 3;
                            $responseText = "Unavailable";
                            break;
                        default:
                            $responseStatus = 2;
                            $responseText = "No Reply";
                            break;
                    }                
                    
                    $dv = "<div class='CalendarUserStatus$responseStatus tc' style='height:100%;font-weight:bold;padding:3px;'>$responseText</div>";
                    
                    if($firstCell)
                    {                        
                        $tbl->StartRow();
                        $tbl->AddCell($attendeeData["name"]);                        
                    }                    
                    $tbl->AddCell($dv);
                    
                    $firstCell = false;                
                }
                $tbl->EndRow();
            }            
        }
        
		$tbl->PrintTable();

		echo "</fieldset>";


		// Comment History
		// --------------------------------------------------------
		echo "<fieldset><legend>Comments</legend>";

		$olist = new CAntObjectList($ANT->dbh, "comment");
		$olist->addCondition("and", "obj_reference", "is_equal", "calendar_event_proposal:$eventProId");
		$olist->addOrderBy("ts_entered", "asc");
		$olist->getObjects();
		$num2 = $olist->getNumObjects();
		for ($m = 0; $m < $num2; $m++)
		{
			$comment = $olist->getObject($m);
			include("public/partials/comment.php");

			echo "<div class='hspacer2Hr'></div>";
		}

		// Add new comment if the member is linked to an object - user or customer usually
		// --------------------------------------------------------
		if ($memberObj)
		{
			echo "<form name='frm_comment' method='post' action='/public/calendar/coordresp.php?$FWD'>";
			echo "<textarea name='comment' style='width:98%;height:50px;'></textarea>";
			echo "<button name='add_comment'>Add Comment</button>";
			echo "</form>";
		}

		echo "</fieldset>";

		echo "</div>";

		echo "<div style='clear:both;'></div>";
	}
?>
