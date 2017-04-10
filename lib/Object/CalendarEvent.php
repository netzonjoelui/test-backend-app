<?php
/**
 * Aereus Object Calendar Event 
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * function like "sendInvitations" for calendar events
 *
 * @category  CAntObject
 * @package   CAntObject_CalendarEvent
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for managing calendar events
 */
class CAntObject_CalendarEvent extends CAntObject
{
	/**
	 * Array of attendees(members) for this event
	 *
	 * @var array(array('id', 'obj'))
	 */
	private $attendees = array();

	/**
	 * Test flag for unit tests
	 *
	 * @var bool
	 */
	public $testMode = false;

	/**
	 * Test mode buffer to check values
	 *
	 * @var array
	 */
	public $testModeBuf = null;

	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $eid 			The event id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "calendar_event", $eid, $user);
	}

	/**
	 * Before we save set some require variables
	 */
	protected function beforesaved()
	{
		// Make sure the calendar is set
		if (!$this->getValue("calendar") && $this->user)
		{
			$defCal = $this->user->getDefaultCalendar();
			if ($defCal->id)
				$this->setValue("calendar", $defCal->id);
		}
	}

	/*
	 * Save data for this object
	 *
	 * @param bool $logact	If true an action will be logged for this event. Used to make multiple saves clean in activity log.
	 */
	public function save($logact=true)
	{
		$resave = false;

		// Save data
		$ret = parent::save($logact);

		if (!$this->id)
			return false; // Parent failed to save!

		// Loop through and save new attendees
		foreach ($this->attendees as $att)
		{
			if (!$att['id'] && $att['obj']) // If null this this has not been saved yet
			{
				$att['obj']->setValue("obj_reference", "calendar_event:".$this->id);
				$mid = $att['obj']->save();
				$this->setMValue("attendees", $mid);
				$resave = true;
			}
		}

		// Check if we need to save changes again
		if ($resave)
		{
			// Resave multi-values if changed
			parent::save(false);
		}

		return $ret;
	}

	/**
	 * Function is called by the base class once the object has been fully loaded
	 */
	protected function loaded()
	{
		$attendees = $this->getValue("attendees");
		if (is_array($attendees))
		{
			foreach ($attendees as $memId)
			{
				$this->attendees[] = array("id"=>$memId, "obj"=>null);
			}
		}
	}

	/**
	 * Add a new member/attendee to this event
	 *
	 * @param array $arr_members Option array of Members ids to send to
	 */
	public function addAttendee($memberName, $role="")
	{
		$mem = new CAntObject($this->dbh, "member", null,  $this->user);

		if (strpos($memberName, ":") !== false)
		{
			$parts = explode(":", $memberName);
			if (count($parts) == 2)
			{
				$refObj = new CAntObject($this->dbh, $parts[0], $parts[1], $this->user);
				$mem->setValue("obj_member", $memberName);
				$memberName = $refObj->getName();
			}
		}

		$mem->setValue("name", $memberName);
		$mem->setValue("role", $role);

		if ($this->id)
		{
			$mem->setValue("obj_reference", "calendar_event:".$this->id);
			$mid = $mem->save();
			$this->setMValue("attendees", $mid);
			$this->attendees[] = array("id"=>$mid, "obj"=>$mem);
		}
		else
		{
			$this->attendees[] = array("id"=>null, "obj"=>$mem);
		}

		return true;
	}

	/**
	 * Get attendee by index
	 *
	 * @param int $ind The index offset of the attendee to retrieve
	 * @return CAntObject A reference to a CAntObject of type 'member'
	 */
	public function getAttendee($ind)
	{
		if ($ind>=count($this->attendees))
			return false;

		$att = $this->attendees[$ind];

		if (!$att['obj'])
			$att['obj'] = new CAntObject($this->dbh, "member", $att['id'], $this->user);

		return $att['obj'];
	}

	/**
	 * Send invitations to members
	 *
	 * @param string $membersField The field of this object containing members
	 * @param bool $onlyNew Only send inviations to new people, otherwise send updates to all
	 * @return int The number of invitations sent
	 */
	public function sendInvitations($membersField, $onlyNew=false)
	{
		// Validate field
		if ($this->def->getField($membersField) == null)
			return -1; // error, field does not exist for this object
		
		$numSent = 0;

		$arr_members = $this->getValue($membersField);
		foreach ($arr_members as $memid)
		{
			// Open existing member object
			$memObj = new CAntObject($this->dbh, "member", $memid);
			$objRef = $memObj->getValue("obj_member");
			$memName = $memObj->getValue("name");
			$isupdate = ($memObj->getValue("f_invsent")=='t') ? true : false;

			if ($isupdate && $onlyNew)
				continue; // Skip this invitation, it was already sent

			// Get the email address of the member or user
			if ($objRef)
			{
				$parts = explode(":", $objRef);
				if (count($parts)==2)
				{
					switch ($parts[0])
					{
					case 'contact_personal':
						break;
					case 'customer':
						$cust = CAntObject::factory($this->dbh, "customer", $parts[1], $this->user);
						$email_address = $cust->getDefaultEmail();
						break;
					case 'user':
						$user = new AntUser($this->dbh, $parts[1]);
						$email_address = $user->getEmail();
						break;
					}
				}
			}
			else if (strpos($memName, "@") !== false) // email
			{
				$email_address = $memName;
			}

			// Email address was found, send invitation
			if (isset($email_address))
			{
				$evname = $this->getName();
				$headers['From']  = $this->user->getEmail();
				$headers['To']  = $email_address;
				$headers['Subject']  = ($isupdate) ? "Event Update ($evname)" : "Event Invitation ($evname)";
				$headers['Content-Type'] = "multipart/alternative; boundary=\"----AntMeetingBooking----\"";
				if ($this->user)
					$headers['X-ANT-ACCOUNT-NAME'] = $this->user->accountName;
				$headers['X-ANT-CAL-INVID'] = $memid;
				$headers['X-ANT-CAL-EVENTID'] = $this->id;
				$message = $this->getInvitationMessageBody($memid, $isupdate);

				// Create new email object
				$email = new Email();
				if ($this->testMode)
				{
					if (!$this->testModeBuf)
						$this->testModeBuf = array();
					$this->testModeBuf[] = $email_address;
				}
				else
				{
					$status = $email->send($email_address, $headers, $message);
				}
				unset($email);

				$numSent++;
			}

			// Update member object
			$memObj->setValue("f_invsent", 't');
			$memObj->save();
		}

		return $numSent;
	}

	/**
	 * Create the message body for an event invitation
	 *
	 * @param int $memid	The id of the member we are sending this invitation to
	 * @mara bool $isupdate	Set this to true if the invitation has already been sent
	 */
	public function getInvitationMessageBody($memId, $isupdate=false) 
	{
		$start = $this->getValue("ts_start");
		$end = $this->getValue("ts_end");

		if ($this->getValue('all_day') == 't')
			$times = "All Day: ".date("m/d/Y", strtotime($start))." - ".date("m/d/Y", strtotime($end));
		else
			$times = $start." - ".$end;

		//Create Mime Boundry
		$mime_boundary = "----AntMeetingBooking----";
		
		//Create Email Body (HTML)
		$message = "--$mime_boundary\n";
		$message .= "Content-Type: text/plain; charset=UTF-8\n";
		$message .= "Content-Transfer-Encoding: 8bit\n\n";
		
		if ($isupdate)
			$message .= "The event ".$this->getValue('name')." was updated ";
		else
			$message .= "You have been invited to join ".$this->getValue('name');

		if ($this->user)
			$message .= " by ".$this->user->fullName."\r\n";
		$message .= "Location: ".$this->getValue('location')."\r\n";
		$message .= "Date & Time: $times\r\n";

		$message .= "\r\n\r\nClick the link below to respond.\r\n";
		$message .= $this->getAccBaseUrl()."/public/calendar/invresp.php?memid=".$memId."&eid=".$this->id;
		if ($this->getValue("notes"))
		{
			$message .= "\r\n\r\n-------------------- NOTES --------------------\r\n";
			$message .= $this->getValue("notes")."\r\n";
		}
		$message .= "\r\n--$mime_boundary\r\n";
		

		// Now add ical part
		$message .= 'Content-Type: text/calendar; name="meeting.ics"; method=REQUEST'."\r\n";
		$message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
		$message .= $this->createIcalBody($memId, "user@user.com");
		$message .= "\r\n--$mime_boundary--";

		return $message;
	}

	/**
	 * Create ical part body for event invitations
	 *
	 * @param int $memId 	An optional id of a member that this ical is being created for
	 * @param string $from 	Optional, who is the inviation coming from
	 * @return string iCal part for body including event details
	 */
	public function createIcalBody($memId=null, $from="")
	{
		$message = "";

		// Get timezone offset
		$diff_second = date('Z', strtotime("4/1/2010")); // after change to
		$sign = ($diff_second > 0) ? '+' : '-';
		$diff_second = abs($diff_second);
		$diff_hour = floor ($diff_second / 3600);
		$diff_minute = floor (($diff_second-3600*$diff_hour) / 60);
		$daylight_offset = sprintf("%s%02d%02d", $sign, $diff_hour, $diff_minute);
		$diff_second = date('Z', strtotime("12/1/2010")); // after change back
		$sign = ($diff_second > 0) ? '+' : '-';
		$diff_second = abs($diff_second);
		$diff_hour = floor ($diff_second / 3600);
		$diff_minute = floor (($diff_second-3600*$diff_hour) / 60);
		$standard_offset = sprintf("%s%02d%02d", $sign, $diff_hour, $diff_minute);

		//Create ICAL Content (Google rfc 2445 for details and examples of usage) 
		$message .= "BEGIN:VCALENDAR\n";
		$message .= "METHOD:REQUEST\n";
		$message .= "PRODID:Aereus Network Tools\n";
		$message .= "VERSION:2.0\n";
		$message .= "BEGIN:VTIMEZONE\n";
		$message .= "TZID:".date_default_timezone_get()."\n";
		$message .= "BEGIN:DAYLIGHT\n";
		$message .= "TZOFFSETFROM:".$standard_offset."\n";
		$message .= "TZOFFSETTO:".$daylight_offset."\n";
		$message .= "TZNAME:PDT\n";
		$message .= "DTSTART:19700308T020000\n";
		$message .= "END:DAYLIGHT\n";
		$message .= "BEGIN:STANDARD\n";
		$message .= "TZOFFSETFROM:".$daylight_offset."\n";
		$message .= "TZOFFSETTO:".$standard_offset."\n";
		$message .= "DTSTART:19701101T020000\n";
		//$message .= "RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU\n";
		$message .= "END:STANDARD\n";
		$message .= "END:VTIMEZONE\n";
		$message .= $this->toIcal($memId, $from)."\n";
		$message .= "END:VCALENDAR\n";

		return $message;
	}

	/**
	 * Create ical part for this event and return as string
	 *
	 * @param int $memId 	An optional id of a member that this ical is being created for
	 * @param string $from 	Optional, who is the inviation coming from
	 * @return string iCal part for event
	 */
	public function toIcal($memId=null, $from="")
	{
		$dbh = $this->dbh;

		$ical = "";
		$to = "";

		$start = $this->getValue("ts_start");
		$end = $this->getValue("ts_end");
		$notes = $this->getValue("notes");

		$dtstart= date("Ymd\THis", strtotime($start));
		$dtend= date("Ymd\THis",strtotime($end));
		$todaystamp = date("Ymd\THis");

		$ical = "BEGIN:VEVENT\n";
		if ($from)
			$ical .= "ORGANIZER:MAILTO:".$from."\n";
		$ical .= "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN="."'$to'".":MAILTO:'".$to."'\n";
		$ical .= "DTSTART;TZID=".date_default_timezone_get().":".$dtstart."\n";
		$ical .= "DTEND;TZID=".date_default_timezone_get().":".$dtend."\n";

		// Examples:
		// RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=1SU;BYMONTH=11
		// RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=2SU;BYMONTH=3
	
		if ($this->isRecurring())
		{
			$rp = $this->getRecurrencePattern();

			$type_name = "";
			switch ($rp->type)
			{
			case RECUR_DAILY:
				$type_name = "DAILY";
				break;
			case RECUR_WEEKLY:
				$type_name = "WEEKLY";
				break;
			case RECUR_MONTHLY:
			case RECUR_MONTHNTH:
				$type_name = "MONTHLY";
				break;
			case RECUR_YEARLY:
			case RECUR_YEARNTH:
				$type_name = "YEARLY";
				break;
			}

			if ($type_name)
			{
				$ical .= "RRULE:FREQ=$type_name;WKST=SU";

				if ($rp->dateEnd && $rp->timeEnd)
					$ical .= ";UNTIL=".date("Ymd\THis",strtotime($rp->dateEnd." ".$this->timeEnd));
				else if ($rp->dateEnd && !$rp->timeEnd)
					$ical .= ";UNTIL=".date("Ymd\THis",strtotime($rp->dateEnd));

				if ($rp->interval)
					$ical .= ";INTERVAL=".$rp->interval;

				$weekdays = array("SU","MO","TU","WE","TH","FR","SA");

				switch ($rp->type)
				{
				case RECUR_MONTHLY:
					if ($rp->month)
						$ical .= ";BYMONTH=".$rp->month;
					break;
				case RECUR_WEEKLY:
					$byday = "";
					if ($rp->dayOfWeekMask & WEEKDAY_SUNDAY)
						$byday .= ($byday) ? ",SU" : "SU";
					if ($rp->dayOfWeekMask & WEEKDAY_MONDAY)
						$byday .= ($byday) ? ",MO" : "MO";
					if ($rp->dayOfWeekMask & WEEKDAY_TUESDAY)
						$byday .= ($byday) ? ",YU" : "TU";
					if ($rp->dayOfWeekMask & WEEKDAY_WEDNESDAY)
						$byday .= ($byday) ? ",WE" : "WE";
					if ($rp->dayOfWeekMask & WEEKDAY_THURSDAY)
						$byday .= ($byday) ? ",TH" : "TH";
					if ($rp->dayOfWeekMask & WEEKDAY_FRIDAY)
						$byday .= ($byday) ? ",FR" : "FR";
					if ($rp->dayOfWeekMask & WEEKDAY_SATURDAY)
						$byday .= ($byday) ? ",SA" : "SA";
					if ($byday)
						$ical .= ";BYDAY=$byday";
					break;
				case RECUR_MONTHNTH:
					break;
				case RECUR_YEARNTH:
					break;
				}

				$ical .= "\n";
			}
		}

		if ($memId)
		{
			$notes .= "\r\n\r\nClick the link below to respond.\r\n";
			$notes .= $this->getAccBaseUrl()."/public/calendar/invresp.php?memid=".$memId."&eid=".$this->id;
		}

		$notes = str_replace("\r\n", "\\n", $notes);
		$notes = str_replace("\n", "\\n", $notes);
		$notes = str_replace("\r", "\\n", $notes);

		$ical .= "LOCATION:".$this->getValue("location")."\n";
		$ical .= "TRANSP:OPAQUE\n";
		$ical .= "SEQUENCE:".$this->revision."\n";
		$ical .= "UID:ANT".settingsGetAccountName().".".$this->id."\n";
		$ical .= "DTSTAMP:".$todaystamp."\n";
		$ical .= "DESCRIPTION:". $notes."\n";
		$ical .= "SUMMARY;LANGUAGE=en-US:".$this->getValue("name")."\n";
		$ical .= "PRIORITY:5\n";
		$ical .= "CLASS:PUBLIC\n";
		$ical .= "END:VEVENT"; 

		return $ical;
	}
}
