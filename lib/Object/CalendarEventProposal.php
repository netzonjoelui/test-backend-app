<?php
/**
 * Aereus Object Calendar Event Proposal
 *
 * @category  CAntObject
 * @package   CalendarEventProposal
 * @copyright Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for managing calendar events
 */
class CAntObject_CalendarEventProposal extends CAntObject
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
	public function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "calendar_event_proposal", $eid, $user);
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
				if ($this->user)
					$headers['X-NETRIC-ACCOUNT-NAME'] = $this->user->accountName;
				$headers['X-NETRIC-CAL-INVID'] = $memid;
				$headers['X-NETRIC-CAL-EVENTPID'] = $this->id;

				$message = "You have been requested for an event proposal: $evname. Please click the link below and";
				$message .= " indicate which times whill and will not work for you.";
				$message .= "\r\n\r\nClick the link below to respond.\r\n";
				$message .= $this->getAccBaseUrl()."/public/calendar/coordresp.php?memid=".$memid."&cecid=".$this->id;

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
     * Get the calendar event proposal optional date/time and members
     */
    public function getEventProposalData()
    {   
        $dbh = $this->dbh;
		$retVal = array();

		if (!$this->id)
			return $retVal;
        
        // get the proposal optional date and time
        $result = $dbh->Query("SELECT * FROM calendar_event_coord_times WHERE cec_id='" . $this->id . "' order by ts_start");
        $num = $dbh->GetNumberRows($result);        
        $retVal["optionalTimes"] = array();
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            
            $tsStart = strtotime($row['ts_start']);
            $tsEnd = strtotime($row['ts_end']);
            
            $startDate = date("m/d/Y", $tsStart);
            $startTime = date("h:i A", $tsStart);
            
            $endDate = date("m/d/Y", $tsEnd);
            $endTime = date("h:i A", $tsEnd);
            
            $date = $startDate;
            $time = "";
            if($startDate!=$endDate)
                $date .=  " - " + $endDate;

            // all day event                            
            if($tsStart==$tsEnd)
            {                
                $tsStart = strtotime("$startDate 12:01 AM");                
            }                
            else
                $time =  "<br/>($startTime - $endTime)";
            
            $dateIndex = str_replace("/", "_", $date . $time);
            $row['sort'] = $tsStart;
            $row['ts_start'] = "$startDate $startTime";
            $row['ts_end'] = "$endDate $endTime";
            $row['date'] = "$date$time";
            $row['date_index'] = $dateIndex;            
            $retVal["optionalTimes"][$dateIndex] = $row;
        }
        $dbh->FreeResults($result);
        
        // get approved members        
        $sql = "select calendar_event_coord_att_times.*, ts_start, ts_end, name  from calendar_event_coord_att_times
                inner join calendar_event_coord_times on calendar_event_coord_att_times.time_id = calendar_event_coord_times.id
                inner join members on calendar_event_coord_att_times.att_id = members.id
                where calendar_event_coord_times.cec_id='" . $this->id . "' order by ts_start, att_id";
                
        $result = $dbh->Query($sql);
        $num = $dbh->GetNumberRows($result);        
        $retVal["dateAttendee"] = array();
        $members = array();
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            
            $tsStart = strtotime($row['ts_start']);
            $tsEnd = strtotime($row['ts_end']);
            $dateIndex = "{$tsStart}_$tsEnd";
            $dateAttendeeIndex = "{$row['time_id']}_{$row['att_id']}";
            $row["date_index"] = $dateIndex;
            $retVal["dateAttendee"][$dateAttendeeIndex] = $row;
            
            // store the names of the attendess
            $members[$row['att_id']] = $row['name'];
        }
        $dbh->FreeResults($result);
        
        $attendees = $this->getValue("attendees");
        $retVal["attendees"] = array();
        if(sizeof($attendees)>0)
        {
            foreach($attendees as $attendee)
            {
                $attendeeName = $members[$attendee];
                
                // if name is null, need to look in member object to get the name
                if(empty($attendeeName))
                {
                    $obj = CAntObject::factory($dbh, "member", $attendee);
                    $attendeeName = $obj->getName();
                }

                $retVal["attendees"][$attendee] = array("id"=>$attendee, "name"=>$attendeeName);
            }
        }
        
        return $retVal;
    }
	
	/**
	 * Get optional times for this event proposal
	 *
	 * @param string $memberId Optional member id to get times for (with response data)
	 * @return array Associative array of [['ts_start'=>epoch, 'ts_end'=>epoch', ('available'=>1|0)]]
	 */
	public function getOptionalTimes($memberId=null)
	{
        $dbh = $this->dbh;        

		if (!$this->id)
			return false;

		$ret = array();

		$query = "select * from calendar_event_coord_times where cec_id='" . $this->id . "' order by ts_start";
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		for ($i=0; $i<$num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);

            $tsStart = strtotime($row['ts_start']);
            $tsEnd = strtotime($row['ts_end']);

			$time = array(
				'id' => $row['id'],
				'ts_start' => $tsStart,
				'ts_end' => $tsEnd,
			);

			// If we are getting the response for a particular attendee, then add to array
			if ($memberId)
				$time['response'] = $this->getMemberAvailibility($memberId, $row['id']);
			
			$ret[] = $time;
		}

		return $ret;
	}

	/**
     * Save the calendar event proposal optional date and time for a member
	 *
	 * @param string $memberId The unique id of the member
	 * @param int $timeId The unique id of the time option to set
	 * @param bool $available Whether or not the member is available for this time
     */
    public function setMemberAvailability($memberId, $timeId, $available=false)
    {
        $dbh = $this->dbh;        

		if (!is_numeric($timeId))
			return false;

		if (!is_numeric($memberId))
			return false;

		// Remove old entry if it exists
		$dbh->Query("DELETE from calendar_event_coord_att_times WHERE att_id='$memberId' AND time_id='$timeId'");

		// Add new response
		$dbh->Query("insert into calendar_event_coord_att_times(att_id, time_id, response) 
									values('$memberId', '" . $timeId . "', '" . (($available) ? '1' : '0') . "');");

		return true;
    }

	/**
	 * Get the availability of a member by id
	 *
	 * @param string $memberId The unique id of the membe
	 * @param int $timeId The unique id of the time option to get
	 * @return int 0 if not available, 1 if available, -1 if not replied
	 */
	public function getMemberAvailibility($memberId, $timeId)
	{
        $dbh = $this->dbh;        

		if (!is_numeric($timeId))
			return false;

		if (!is_numeric($memberId))
			return false;

		$result = $dbh->Query("select response from calendar_event_coord_att_times where att_id='$memberId' and time_id='$timeId'");
		if ($dbh->GetNumberRows($result))
		{
			return ($dbh->GetValue($result, 0, "response") == 1) ? 1 : 0;
		}
		else
		{
			return -1;
		}
	}
}
