<?php 
	require_once("../../lib/AntConfig.php");
	require_once("ant.php");
	require_once("settings/settings_functions.php");
	require_once("lib/CDatabase.awp");
	require_once("lib/WindowFrame.awp");
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
                                      
    $invid = $_GET['memid'];	
	$eventId = $_GET['eid'];
	$FWD = "memid=$invid&eid=$eventId";
    
	$page = new CPageShellPublic("Event Invitation", "home");
	$page->opts['print_subnav'] = false;
	$page->PrintShell();	

	// Get event object
    $event = CAntObject::factory($dbh, "calendar_event", $eventId);

	// Get member object
    $member = CAntObject::factory($dbh, "member", $invid);

	// Get referenced object for member if set
	$memberObj = null;
	if ($member->getValue("obj_member"))
	{
		$objRef= CAntObject::decodeObjRef($member->getValue("obj_member"));
		$memberObj = CAntObject::factory($dbh, $objRef['obj_type'], $objRef['id']);
	}
    

	if ($eventId)
	{
        $obj = CAntObject::factory($dbh, "calendar_event", $eventId);        
        $arrData = array("name"=>"name", "start_block"=>"start_block", "end_block"=>"end_block", "location"=>"location", "notes"=>"notes", 
                    "recur_id"=>"recur_id", "date_start"=>"ts_start", "date_end"=>"ts_end", "all_day"=>"all_day");
    
        foreach($arrData as $key=>$value)        
            $EVENT_DATA[$key] = $obj->getValue($value);
	}

	// Handle comment submission
	// ---------------------------------------------------------------------------------
	if ($_POST['comment'] && $_POST['comment'] != "Enter optional comments here" && null != $memberObj)
	{
		$obja = CAntObject::factory($ANT->dbh, "comment", null);

		if ($memberObj->object_type == "user")
			$obja->setValue("owner_id", $memberObj->id);

		$obja->setValue("sent_by", $memberObj->object_type . ":" . $memberObj->id);

		$obja->setValue("obj_reference", "calendar_event:$eventId");
		$obja->setValue("comment", $_POST['comment']);
		$obja->setMValue("associations", "calendar_event:$eventId");
		$cid = $obja->save();
	}

	// Handle user respose submission
	// ---------------------------------------------------------------------------------
	if ($_POST['rsvp'])
	{
		if ($_POST['rsvp'] == 't')
		{
			// Set accept boolean
            $member->setValue("f_accepted", "t");
			
			// TODO: add event if user selected to 'add to my calendar'
		}
		else
		{
            $member->setValue("f_accepted", "f");
		}
		
		$member->save();

		// Send notification to originating user
		$notification = CAntObject::factory($dbh, "notification");
		$notification->setValue("name", "Replied to Invitation");
		$notification->setValue("description", 
			$member->getValue("name") . " " . (($member->getValue("f_accepted")=='t')?'accepted':'declined') . " your invitaion"
		);
		$notification->setValue("obj_reference", "calendar_event:" . $eventId);
		$notification->setValue("f_popup", 'f');
		$notification->setValue("f_seen", 'f');
		$notification->setValue("owner_id", $event->getValue("user_id"));
		$notification->save();

		echo "<p class='notice'>Thank you for updating your availability! If you need to make any changes you can do so now or at any time before the event is past.</p>";
	}
?>
<script language='javascript'>
	function ToggleView(id)
	{
		var tp = document.getElementById('mid'+id);
		var btn = document.getElementById('bid'+id);
		if (tp.style.display == "none")
		{
			tp.style.display = 'block';
			btn.style.display = 'none';
		}
		else
		{
			tp.style.display = "none";
			btn.style.display = 'block';
		}
	}
</script>
<?php
	if ($invid && $eventId)
	{	
		// Display left column
		// ----------------------------------------------------------------------------------------------
		echo "<div class='g4'>";

		echo "<fieldset><legend>RSVP Status</legend>";
		if ($member->getValue("f_accepted") == 't')
			print("<div class='alert'>You have accepted this invitation. However, you can change your response below</div>");
		else
			print("<div>Please accept or decline the invitation below</div>");
				
		print("<form name='invitation' method='post' action='invresp.php?$FWD'>
				<br />
				<input type='radio' name='rsvp' value='t' ".(($member->getValue("f_accepted")!='f')?'checked':'')."> 
				<span class='formLabel'>I will be attending this event</span>
				<br />
				<input type='radio' name='rsvp' value='f' ".(($member->getValue("f_accepted")=='f')?'checked':'')."> 
				<span class='formLabel'>I will not be attending this event</span>
				<br />
				<br />");

		if (null != $memberObj)
				print ("<textarea rows='5' name='comment' style='width: 90%;'>Enter optional comments here</textarea>");
		/*
		if ($member->getValue("user_id"))
		{
			echo "<div>";
			// Make sure there is a default calendar
			$def = GetDefaultCalendar($dbh, $member->getValue("user_id"));
			print("<br>Add to my calendar: <select name='calendar'><option value='0'>Do Not Create Event</option>");
			$res = $dbh->Query("select id, name, def_cal from calendars where user_id='".$member->getValue("user_id")."'");
			$num = $dbh->GetNumberRows($res);
			for ($i=0; $i<$num; $i++)
			{
				$row3 = $dbh->GetNextRow($res, $i);
				echo "<option value='".$row3['id']."'";
				if ($row3['def_cal'] == 't') echo " selected";
				echo ">".$row3['name']."</option>";
			}
			$dbh->FreeResults($res);
			print("</select>");
			echo "</div>";
		}	
		 */

		echo "<div style='padding-top:5px;'>";
		echo "<button type='submit' name='save'>Update</button>";
		echo "</div>";

		print("</form>");

		echo "</fieldset>";

		// Print attendees
		// --------------------------------------------------------------------
		echo "<fieldset><legend>Attendees</legend>";
        
        $attendees = $event->getValue("attendees");        
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

		// Display details
		// ----------------------------------------------------------------------------------------------
		echo "<div class='g8'>";
		echo "<fieldset><legend>Event Details</legend>";
		echo "<table style='font-size:14px;'>";
		echo "<tr><td class='formLabel'>Event Name:</td><td>".$event->getValue("name")."</td></tr>";
		echo "<tr><td class='formLabel'>Location:</td><td>".$event->getValue("location")."</td></tr>";
		echo "<tr>
				<td class='formLabel'>Date &amp; Time:</td>
				<td>
					". $event->getValue('ts_start') . " - " . $event->getValue('ts_end') . "
					".(($event->getValue('all_day') == 't') ? " All Day" : '')."</td>
			  </tr>";
		echo "<tr><td class='formLabel'>Notes/Description:</td></tr>";
		echo "<tr><td colspan='2'>".str_replace("\n", "<br />", $event->getValue("notes"))."</td></tr>";
		echo "</table>";
		echo "</fieldset>";

		echo "<div style='height:10px;'></div>";

		// Comments
		// --------------------------------------------------------

		echo "<fieldset><legend>Comments</legend>";
		$olist = new CAntObjectList($ANT->dbh, "comment");
		$olist->addCondition("and", "obj_reference", "is_equal", "calendar_event:$eventId");
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
			echo "<form name='frm_comment' method='post' action='/public/calendar/invresp.php?$FWD'>";
			echo "<textarea name='comment' style='width:98%;height:50px;'></textarea>";
			echo "<button name='add_comment'>Add Comment</button>";
			echo "</form>";
		}

		echo "</fieldset>";

		echo "</div>";
	}
	else
	{
		print("<strong>There was a problem accessing this invitation. It is possible the events has been deleted.</strong>");
	}
?>
