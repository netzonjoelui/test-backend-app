<?php
/**
 * Calendar application actions
 */
require_once("lib/Object/CalendarEvent.php");
require_once("calendar/calendar_functions.awp");
require_once("controllers/ObjectListController.php");
require_once('lib/ServiceLocatorLoader.php');

/**
 * Class for controlling calendar and calendar event functions
 */
class CalendarController extends Controller
{    
    /**
     * Get calendar events by setting calendars and passing to ObjectListController
     * 
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function getEvents($params)
    {
        $calendars = array();
        $dbh = $this->ant->dbh;

        // Get calendars marked for view
        $query = new Netric\EntityQuery("calendar");
        $query->andWhere("user_id", "is_equal", $this->user->id);
        $query->andWhere("f_view", "is_equal", "t");

        Netric\EntityQuery\FormParser::buildQuery($query, null);

        $sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
        $index = $sl->get("EntityQuery_Index");

        // Execute the query
        $res = $index->executeQuery($query);

        for ($i = 0; $i < $res->getNum(); $i++)
        {
            $cal = $res->getEntity($i);
            $calendars[] = $cal->getValue("id");
        }

        /*
        // Get calendars marked for view
        $calList = new CAntObjectList($dbh, "calendar", $this->user);
        $calList->addCondition("and", "user_id", "is_equal", $this->user->id);
        $calList->addCondition("and", "f_view", "is_equal", "t");
        $num = $calList->getObjects();

        for ($i = 0; $i < $calList->getNumObjects(); $i++)
        {
            $cal = $calList->getObject($i);
            $calendars[] = $cal->id;
        }
        // Get shared calendars
        $query = "select calendar from calendar_sharing
                    where user_id='" . $this->user->id . "'
                    and accepted = 't'
                    and f_view = 't'";
                    
        $result = $dbh->Query($query);
        $num = $dbh->GetNumberRows($result);        
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            $calendars[] = $row['calendar'];
        }
        */

        // Set conditions
        if (isset($params['conditions']))
        {
            $curInd = count($params['conditions']) + 1;
        }
        else
        {
            $curInd = 0;
            $params['conditions'] = array();
        }

        // Verify required params are set
        if (!isset($params['obj_type']))
            $params['obj_type'] = "calendar_event";

        for ($i = 0; $i < count($calendars); $i++)
        {
            $curInd = $curInd++;
            $params['conditions'][] = $curInd;
            $params['condition_blogic_' . $curInd] = ($i === 0) ? "and" : "or";
            $params['condition_fieldname_' . $curInd] = "calendar";
            $params['condition_operator_' . $curInd] = "is_equal";
            $params['condition_condvalue_' . $curInd] = $calendars[$i];
        }

        // Now pass the query through to the object list controller
        $controller = new ObjectListController($this->ant, $this->user);
        return $controller->query($params);
    }

	/**
	 * Get a calendar by name
	 *
	 * @param array $params	An assocaitive array of parameters passed to this function. 
	 */
	public function getCalendarName($params)
	{
        $dbh = $this->ant->dbh;
        
		if (isset($params['calid']))
        {
            $sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
            $loader = $sl->get("EntityLoader");
            $cal = $loader->get("calendar", $params['calid']);

            $ret = $cal->getValue("name");
        }
        else
            $ret = array("error"=>"calid is a required param");
        
        $this->sendOutputJson($ret);
        return $ret;
	}

	/**
	 * Send notifications to members
	 *
	 * @param array $params	An assocaitive array of parameters passed to this function.
	 */
	public function sendMemberNotifications($params)
	{
		$dbh = $this->ant->dbh;
		$event = new CAntObject_CalendarEvent($dbh, $params['event_id'], $this->user);

		// Send new invitations
		if ($params['members'])
		{
			$onlynew = ($params['onlynew'] == 't') ? true : false;
			$members = json_decode($params['members'], $onlynew);
			$event->sendInvitations($members);
		}

        $ret = 1;
        
        return $this->sendOutputRaw($ret);
	}

	/**
	 * Get settings for this user
	 *
	 * @param array $params	An assocaitive array of parameters passed to this function.
	 */
	public function getUserSettings($params)
	{
		$dbh = $this->ant->dbh;

		$settings = new stdClass();

		$pref = (is_numeric($params['calendar_id'])) ? "calendar/".$params['calendar_id']."/default_view" : 'calendar/default_view';
		$buf = $this->user->getSetting($pref);
		$settings->defaultView = ($buf) ? $buf : 'day';
		$settings->startTime = $this->user->getSetting('calendar/start_day_time');
		$settings->endTime = $this->user->getSetting('calendar/end_day_time');
		$settings->calendars = CalGetViewArray($dbh, $this->user->id, (is_numeric($params['calendar_id'])) ? $params['calendar_id'] : null);
        
        $this->sendOutputJson($settings);
		return $settings;
	}
    
    /**
     * get the calendar event proposal optional date/time and members
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function getEventProposalData($params)
    {   
        $dbh = $this->ant->dbh;
        
        // get the proposal optional date and time
        $eventCoordId = $params['eventCoordId'];
		$eventProposal = CAntObject::factory($dbh, "calendar_event_proposal", $params['eventCoordId'], $this->user);
		$retVal = $eventProposal->getEventProposalData();
		/*
        $result = $dbh->Query("select * from calendar_event_coord_times where cec_id='$eventCoordId' order by ts_start");
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
                where calendar_event_coord_times.cec_id=$eventCoordId order by ts_start, att_id";
                
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
        
        $obj = new CAntObject($dbh, "calendar_event_proposal", $eventCoordId);
        $attendees = $obj->getValue("attendees");
        $retVal["attendees"] = array();
        if(sizeof($attendees)>0)
        {
            foreach($attendees as $attendee)
            {
                $attendeeName = $members[$attendee];
                
                // if name is null, need to look in member object to get the name
                if(empty($attendeeName))
                {
                    $obj = new CAntObject($dbh, "member", $attendee);
                    foreach ($obj as $objAttendee) 
                    {
                        if(is_array($objAttendee))
                        {
                            if($objAttendee["id"]==$attendee)
                                $attendeeName = $objAttendee["name"];
                        }                            
                    }
                }
                $retVal["attendees"][$attendee] = array("id"=>$attendee, "name"=>$attendeeName);
            }
                
		}
		*/
        
        return $this->sendOutputJson($retVal);
    }
    
    /**
     * Save the calendar event proposal optional date and time
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function saveEventProposalDate($params)
    {
        $dbh = $this->ant->dbh;        
        
        $retVal = array();
        foreach($_POST as $key=>$value)
        {
            $postKey = explode("_", $key);
            $$postKey[0] = $value;
                        
            if($id == 0 && !empty($tsStart) && $postKey[0] == "eventCoordId")
            {
                $dbh->Query("insert into calendar_event_coord_times(ts_start, ts_end, cec_id)
                                       values('".$dbh->Escape($tsStart)."', '".$dbh->Escape($tsEnd)."', '$eventCoordId');");
            }
        }
        
        $eventParam["eventCoordId"] = $eventCoordId;
        $this->getEventProposalData($eventParam);
        return true;
    }
    
    /**
     * delete the calendar event proposal optional date and time
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function deleteEventProposalDate($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        
        $id = $params['id'];
        
        if($id>0)
            $dbh->Query("delete from calendar_event_coord_times where id='$id'");
        
        $this->sendOutputJson($params);
        return true;
    }
    
    /**
     * delete the member of the calendar event proposal
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function closeEventProposal($params)
    {
        $dbh = $this->ant->dbh;
        
        $eventCoordId = $params['eventCoordId'];
        $obj = new CAntObject($dbh, "calendar_event_proposal", $eventCoordId);
        $obj->setValue("f_deleted", "t");
        $obj->save();
        
        return true;        
    }
    
    /**
     * Get the calendars
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function getCalendars($params)
    {
        $dbh = $this->ant->dbh;
		global $CAL_ACLS;
		$ret = array(
			"myCalendars" => array(),
			"otherCalendars" => array(),
		);

        $query = new Netric\EntityQuery("calendar");
        $query->andWhere("user_id", "is_equal", $this->user->id);

        Netric\EntityQuery\FormParser::buildQuery($query, null);

        $sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
        $index = $sl->get("EntityQuery_Index");

        // Execute the query
        $res = $index->executeQuery($query);

        for ($i = 0; $i < $res->getNum(); $i++)
        {
            $cal = $res->getEntity($i);
            $color = ($cal->getValue('color')) ? $cal->getValue('color') : '2A4BD7';
            $ret["myCalendars"][] = array(
                "id" => $cal->getValue('id'),
                "name" => $cal->getValue('name'),
                "f_view" => $cal->getValue('f_view'),
                "color" => $color,
                "default" => ($cal->getValue('def_cal')=='t') ? true : false,
            );
        }

        if (count($ret["myCalendars"]) === 0)
        {
            $loader = $sl->get("EntityLoader");
            $cal = $loader->create("calendar");

            $cal->setValue("user_id", $this->user->id);
            $cal->setValue("name", "My Calendar");
            $cal->setValue("f_view", true);
            $cal->setValue("def_cal", true);
            $cal->setValue("color", "2A4BD7");

            $loader->save($cal);

            $ret["myCalendars"][] = array(
                "id" => $cal->getValue('id'),
                "name" => $cal->getValue('name'),
                "f_view" => $cal->getValue('f_view'),
                "color" => $cal->getValue('color'),
                "default" => ($cal->getValue('def_cal')=='t') ? true : false,
            );
        }

        /*
        // Get users calendar
		// ----------------------------------------------
		$calList = new CAntObjectList($dbh, "calendar", $this->user);
		$calList->addCondition("and", "user_id", "is_equal", $this->user->id);
		$num = $calList->getObjects();
        for ($i = 0; $i < $calList->getNumObjects(); $i++)
        {
			$cal = $calList->getObject($i);
            $color = ($cal->getValue('color')) ? $cal->getValue('color') : '2A4BD7';
			$ret["myCalendars"][] = array(
				"id" => $cal->id, 
				"name" => $cal->getValue('name'), 
				"f_view" => $cal->getValue('f_view'), 
				"color" => $color, 
				"default" => ($cal->getValue('def_cal')=='t') ? true : false, 
			);
		}


		// Add default my calendar if none exists
		if (!count($ret["myCalendars"]))
		{
			$cal = CAntObject::factory($dbh, "calendar", null, $this->user);
			$cal->setValue("user_id", $this->user->id);
			$cal->setValue("name", "My Calendar");
			$cal->setValue("f_view", "t");
			$cal->setValue("def_cal", "t");
			$cal->setValue("color", "2A4BD7");
			$cid = $cal->save();

			$ret["myCalendars"][] = array(
				"id" => $cal->id, 
				"name" => $cal->getValue('name'), 
				"f_view" => $cal->getValue('f_view'), 
				"color" => $cal->getValue('color'), 
				"default" => ($cal->getValue('def_cal')=='t') ? true : false, 
			);
		}
        */

		// Get shared calendars
		// ----------------------------------------------
        $othercalendars = "";
        $query = "select calendars.id, calendars.name, users.name as uname, calendar_sharing.f_view ,calendar_sharing.color,
                    calendar_sharing.id as share_id from calendar_sharing, calendars left outer join users on 
                    (calendars.user_id = users.id)
                    where calendar_sharing.user_id='" . $this->user->id . "'
                    and calendar_sharing.accepted = 't'
                    and calendars.id=calendar_sharing.calendar";
                    
        $result = $dbh->Query($query);
        $num = $dbh->GetNumberRows($result);
        
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            $color = ($row['color']) ? $row['color'] : '81C57A';            
			if ($row['f_public'] == 't')
			{
				$name = $row['name'];
			}
			else
			{
				$name = $row['name'] . " [" . $row['uname'] . "]";
			}

			$ret["otherCalendars"][] = array(
				"id" => $row['id'], 
				"name" => $name, 
				"f_view" => $row['f_view'], 
				"color" => $row['color'],
				"uname" => $row['uname'], 
				"share_id" => $row['share_id'],
				"default" => false,
			);
        }
        
        $this->sendOutputJson($ret);            
        return $ret;
    }
    
    /**
     * set the f view
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function setFView($params)
    {
        $dbh = $this->ant->dbh;
        
        $view= $params['f_view'];
        if (($params['calendar_id'] || $params['share_id']) && $view)
        {
            $sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
            $loader = $sl->get("EntityLoader");
            $fView = ($view === 't') ? true : false;

            if (isset($params['calendar_id']) && $params['calendar_id'])
            {
                $cal = $loader->get("calendar", $params['calendar_id']);
                $cal->setValue("f_view", $fView);

                $loader->save($cal);
                    
                $ret = $params['calendar_id'];
            }
            
            if (isset($params['share_id']) && $params['share_id'])
            {
                $cal = $loader->get("calendar", $params['share_id']);
                $cal->setValue("f_view", $fView);

                $loader->save($cal);
                    
                $ret = $params['share_id'];
            }
        }
        else
            $ret = array("error"=>"f_view and calendar_id or share_id are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Set the user settings
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function userSetSetting($params)
    {
        $dbh = $this->ant->dbh;
        
        $sname = rawurldecode($params['setting_name']);
        $sval = rawurldecode($params['setting_value']);
        if ($sname && $sval)
            $this->user->setSetting($sname, $sval);
        
        $ret = 1;
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Set calendar color
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function calSetColor($params)
    {
        $dbh = $this->ant->dbh;
        $color = $params['color'];
        
        if (($params['calendar_id'] || $params['share_id']) && $color)
        {
            $sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
            $loader = $sl->get("EntityLoader");

            if (isset($params['calendar_id']) && $params['calendar_id']) {
                $cal = $loader->get("calendar", $params['calendar_id']);
                $cal->setValue("color", $color);

                $loader->save($cal);
            }
            
            if (isset($params['share_id']) && $params['share_id']) {
                $cal = $loader->get("calendar", $params['share_id']);
                $cal->setValue("color", $color);

                $loader->save($cal);
            }
            
            $ret = $color;
        }
        else
            $ret = array("error"=>"color and calendar_id or share_id are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Get comments
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function getComments($params)
    {
        $dbh = $this->ant->dbh;
        $EID = $params['eid'];
        $last_id = $params['last_cid']; // Use  this to get updates
        
        if ($last_id)
            $last_id = "and id>'$last_id'";

        $query = "select id, ts_entered, to_char(ts_entered, 'MM/DD/YYYY HH12:MI AM') as ts_entered_str,
                    entered_by, comment from calendar_event_comments where event_id='$EID' $last_id order by ts_entered";

        $result = $dbh->Query($query);
        $num = $dbh->GetNumberRows($result);
        
        $ret = array();
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            $ret[] = array("id" => $row['id'], "ts_entered_str" => rawurlencode($row['ts_entered_str']), 
                            "entered_by" => rawurlencode($row['entered_by']), "comment" => rawurlencode($row['comment']));
        }

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Create Calendar
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    function createCalendar($params)
    {
        $dbh = $this->ant->dbh;
        
        $name = stripslashes($params['name']);
        if ($name)
        {
            $query = new Netric\EntityQuery("calendar");
            $query->andWhere("name", "is_equal", $params['name']);

            Netric\EntityQuery\FormParser::buildQuery($query, null);

            $sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
            $index = $sl->get("EntityQuery_Index");

            // Execute the query
            $res = $index->executeQuery($query);

            if ($res->getNum())
            {
                // Return the calendar info if already exist
                $cal = $res->getEntity(0);
                $ret = $cal->getValue("id");
            }
            else
            {
                $loader = $sl->get("EntityLoader");
                $cal = $loader->create("calendar");

                $cal->setValue("name", $name);
                $cal->setValue("user_id", $this->user->id);
                $cal->setValue("color", "2A4BD7");

                $fView = ($view === 't') ? true : false;
                $cal->setValue("f_view", "t");

                if ($loader->save($cal))
                    $ret = $cal->getValue("id");
                else
                    $ret = array("error"=>"There was an error when saving.");
            }

            /*
            $result = $dbh->Query("select * from calendars where name = '{$params['name']}'");
            if ($dbh->GetNumberRows($result))
            {
                // Return the calendar info if already exist
                $row = $dbh->GetNextRow($result, 0);
                $ret = $row['id'];
            }                
            else
            {
                $dbh->FreeResults($result);
                $result = $dbh->Query("insert into calendars(name, date_created, user_id, f_view)
                                    values('".$dbh->Escape($name)."', 'now', '" . $this->user->id . "', 't');
                                    select currval('calendars_id_seq') as id;");
                if ($dbh->GetNumberRows($result))
                {
                    $row = $dbh->GetNextRow($result, 0);
                    $ret = $row['id'];
                }
                else
                {
                    $ret = array("error"=>"There was an error when saving.");
                }
            }
            */
        }
        else
            $ret = array("error"=>"calendar name is a required param.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    
    /**
     * Delete Calendar
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function deleteCalendar($params)
    {
        $dbh = $this->ant->dbh;
        
        $id = $params['id'];
        if ($id)
        {
            $dbh->Query("delete from calendars where id='$id' and user_id='" . $this->user->id . "'");
            $ret = 1;
        }
        else
            $ret = array("error"=>"id is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Add Share
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function addSharedCalendar($params)
    {
        $dbh = $this->ant->dbh;
        
        $calendar_id = $params['calendar_id'];
        $user_id = $params['user_id'];
        if ($calendar_id && $user_id)
        {
            $result = $dbh->Query("select id from calendar_sharing where calendar='$calendar_id' and user_id='" . $user_id . "'");
            if($dbh->GetNumberRows($result))
            {
                $shareId = $dbh->GetValue($result, 0, "id");
                $dbh->Query("update calendar_sharing set accepted='t', f_view='t' where id = '$shareId'");                
            }            
            else
            {
                $dbh->FreeResults($result);
                
                $result = $dbh->Query("insert into calendar_sharing(accepted, f_view, calendar, user_id)
										 values('t', 't', '" . $dbh->Escape($calendar_id) . "', '" . $dbh->Escape($user_id) . "');
										 select currval ('calendar_sharing_id_seq') as id;");
                             
                if($dbh->GetNumberRows($result))
                    $shareId = $dbh->GetValue($result, 0, "id");
            }
            $ret = $shareId;


			// Give at least view Permissions
			$DACL_CAL = new Dacl($dbh, "calendars/".$calendar_id);
			if ($DACL_CAL->id)
			{
				$DACL_CAL->grantUserAccess($user_id, "View");
				$DACL_CAL->save();
			}        
        }
        else
            $ret = array("error"=>"id is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Delete Share
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function deleteShare($params)
    {
        $dbh = $this->ant->dbh;
        
        $id = $params['id'];
        if ($id)
        {
            $dbh->Query("delete from calendar_sharing where id='$id'");
            $ret = 1;
        }
        else
            $ret = array("error"=>"id is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Delete Event
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function deleteEvent($params)
    {
        $dbh = $this->ant->dbh;
        $EID = null;
        $RID = null;
        
        if(isset($params['eid']))
            $EID = $params['eid'];
            
        if(isset($params['recur_id']))
            $RID = $params['recur_id'];

        if ($EID)        // Update specific event
        {
            $dbh->Query("delete from calendar_events where id='$EID'");

            if ($RID && $params['save_type'] == "this_event")                                
            {
                $dbh->Query("insert into calendar_events_recurring_ex(recurring_id, exception_date)
                                        values ('$RID', ".db_UploadDate($params['date_start']).")");
            }
            // Delete all recurring events
            else if ($RID && $params['save_type'] != "this_event")    
            {
                $dbh->Query("delete from calendar_events where recur_id='$RID'");
                $dbh->Query("delete from calendar_events_recurring where id='$RID'");
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"eid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Save Event
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function saveEvent($params)
    {
        $dbh = $this->ant->dbh;
        $EID = null;
        $RID = null;
        
        if(isset($params['eid']))
            $EID = $params['eid'];
        
        if(isset($params['recur_id']))
            $RID = $params['recur_id'];
        
        if ($params['all_day']=='t')
        {
            $start_block = 1;
            $end_block = 1440;
            $time_start = "12:00 AM";
            $time_end = "11:59 PM";
        }
        else
        {
            $start_block = CalGetTimeBlock($params['time_start']);
            $end_block = CalGetTimeBlock($params['time_end']);
            if ($start_block && $end_block<1)
                $end_block = $start_block + 30;

            $time_start = $params['time_start'];
            $time_end = $params['time_end'];
        }

        if(isset($params['eid']) && $params['eid']) // Update specific event
        {
            $orig_start_date = CalGetEventAttrib($dbh, $params['eid'], "date_start"); // used to file exceptions

            // saveAttendees(dlg, close) is called after the event is saved so this will only send to exisiting invitations - new added later
            if (isset($params['send_invitations']) && $params['send_invitations'] == 't')
                CalSendAttendeeUpdates($dbh, $this->user->id, $params['eid']);

            $query = "update calendar_events set ";
            if (isset($params['only_defined']) && $params['only_defined']) // Only update fields that are defined
            {
                $update = "";
                if (isset($params['time_start']) && $params['time_start'])
                {
                    if ($update) $update .= ", ";
                        $update .= "start_block='".$start_block."' ";
                }
                
                if (isset($params['time_end']) && $params['time_end'])
                {
                    if ($update) $update .= ", ";
                        $update .= "end_block='".$end_block."' ";
                }
                
                if (isset($params['date_start']) && $params['date_start'])
                {
                    if ($update) $update .= ", ";
                        $update .= "date_start=".db_UploadDate($params['date_start'])." ";
                }
                if (isset($params['date_end']) && $params['date_end'])
                {
                    if ($update) $update .= ", ";
                        $update .= "date_end=".db_UploadDate($params['date_end'])." ";
                }
                
                if (isset($params['ts_start']) && $params['ts_start'])
                {
                    if ($update) $update .= ", ";
                        $update .= "ts_start=".db_UploadDate($params['ts_start'])." ";
                }
                
                if (isset($params['ts_end']) && $params['ts_end'])
                {
                    if ($update) $update .= ", ";
                        $update .= "ts_end=".db_UploadDate($params['ts_end'])." ";
                }
                
                $query .= $update;
            }
            else
            {
                $location = null;
                if(isset($params['location']))
                    $location = $params['location'];
                    
                $notes = null;
                if(isset($params['notes']))
                    $location = $params['notes'];
                    
                $userStatus = null;
                if(isset($params['user_status']))
                    $location = $params['user_status'];
                    
                $query .= "start_block='".$start_block."', 
                          end_block='".$end_block."', 
                          location='".$dbh->Escape(stripslashes($location))."',
                          name='".$dbh->Escape(stripslashes($params['title']))."', 
                          sharing='".$params['sharing']."', 
                          notes='".$dbh->Escape(stripslashes($notes))."', 
                          calendar='".$params['calendar_id']."', 
                          date_start=".db_UploadDate($params['date_start']).", 
                          date_end=".db_UploadDate($params['date_end']).",
                          ts_start=".db_UploadDate($params['date_start']." ".$time_start).", 
                          ts_end=".db_UploadDate($params['date_end']." ".$time_end).",
                          user_status=".db_CheckNumber($userStatus).",
                          all_day='".$params['all_day']."'";
            }
            $query .= ", ts_updated='now' where id='".$params['eid']."';";
            $res = $dbh->Query($query);

            // File an exception to this event only
            if ($RID && $params['save_type'] == "this_event")                                
            {
                $dbh->Query("delete from calendar_events_recurring_ex where event_id='".$params['eid']."';
                             insert into calendar_events_recurring_ex(recurring_id, exception_date, event_id)
                             values('".$RID."', ".db_UploadDate($orig_start_date).", '".$params['eid']."')");
            }
            // Update recurrind id
            else if ($RID && $params['save_type'] != "this_event" && $params['recur_type'])    
            {
                $query = "update calendar_events_recurring set
                            type='".$params['recur_type']."',
                            start_block='".$start_block."', 
                            end_block='".$end_block."', 
                            location='".$dbh->Escape(stripslashes($params['location']))."', 
                            name='".$dbh->Escape($params['title'])."', 
                            sharing='".$params['sharing']."',
                            user_id='".$this->user->id."',
                            notes='".$dbh->Escape($params['notes'])."', 
                            calendar='".$params['calendar_id']."', 
                            date_start=".db_UploadDate($params['recur_date_start']).", 
                            date_end=".db_UploadDate($params['recur_date_end']).",
                            all_day='".$params['all_day']."',
                            user_status=".db_CheckNumber($params['user_status']).",
                            interval=".db_CheckNumber($params['recur_interval']).",
                            day=".db_CheckNumber($params['recur_day']).",
                            month=".db_CheckNumber($params['recur_month']).",
                            relative_type=".db_CheckNumber($params['recur_relative_type']).",
                            relative_section=".db_CheckNumber($params['recur_relative_section']).",
                            week_days[1]='".(($params['recur_day1'] == 't') ? 't' : 'f')."',
                            week_days[2]='".(($params['recur_day2'] == 't') ? 't' : 'f')."',
                            week_days[3]='".(($params['recur_day3'] == 't') ? 't' : 'f')."',
                            week_days[4]='".(($params['recur_day4'] == 't') ? 't' : 'f')."',
                            week_days[5]='".(($params['recur_day5'] == 't') ? 't' : 'f')."',
                            week_days[6]='".(($params['recur_day6'] == 't') ? 't' : 'f')."',
                            week_days[7]='".(($params['recur_day7'] == 't') ? 't' : 'f')."'
                            where id='".$RID."'";    
                $res = $dbh->Query($query);

                $dbh->Query("delete from calendar_events where recur_id='$RID' and id!='".$params['eid']."'");
                $dbh->Query("delete from calendar_events_recurring_ex where recurring_id='$RID';");
            }
            // Delete Recurring ID
            else if ($RID && !$params['recur_type'] && $params['save_type'] != "this_event") 
            {
                $dbh->Query("delete from calendar_events_recurring where id='".$RID."'");
                $dbh->Query("update calendar_events set recur_id=null where id='".$params['eid']."'");
            }

            $dbh->Query("delete from calendar_events_reminders where event_id='$EID';");
            if ($RID)
                $dbh->Query("delete from calendar_events_reminders where recur_id='".$RID."';");
        }
        else // Enter a new event
        {
            $location = null;
            if(isset($params['location']))
                $location = $params['location'];
                
            $notes = null;
            if(isset($params['notes']))
                $location = $params['notes'];
                
            $query = "insert into calendar_events(start_block, end_block, location, name, sharing, notes, 
                                                    calendar, date_start, date_end, ts_start, ts_end, all_day, ts_updated, user_id)
                        values('".$start_block."', '".$end_block."', '".$dbh->Escape(stripslashes($location))."', 
                                '".$dbh->Escape(stripslashes($params['title']))."', '".$params['sharing']."', '".$dbh->Escape(stripslashes($notes))."',
                                '".$params['calendar_id']."', ".db_UploadDate($params['date_start']).", ".db_UploadDate($params['date_end']).", 
                                ".db_UploadDate($params['date_start']." ".$time_start).", ".db_UploadDate($params['date_end']." ".$time_end).",
                                '".$params['all_day']."', 'now', '" . $this->user->id . "'); 
                      select currval('calendar_events_id_seq') as id;";
            $result = $dbh->Query($query);
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $EID = $row['id'];
            }

            // Check for lead association
            if (isset($params['lead_id']) && $params['lead_id'] && $EID)
                $dbh->Query("insert into calendar_event_associations(event_id, lead_id) values('$EID', '".$params['lead_id']."');");
                
            // Check for opportunity association
            if (isset($params['opportunity_id']) && $params['opportunity_id'] && $EID)
                $dbh->Query("insert into calendar_event_associations(event_id, opportunity_id) values('$EID', '".$params['opportunity_id']."');");
                
            // Check for conact association
            if (isset($params['contact_id']) && $params['contact_id'] && $EID)
                $dbh->Query("insert into calendar_event_associations(event_id, contact_id) values('$EID', '".$params['contact_id']."');");
                
            // Check for customer
            if (isset($params['customer_id']) && $params['customer_id'] && $EID)
                $dbh->Query("insert into calendar_event_associations(event_id, customer_id) values('$EID', '".$params['customer_id']."');");
        }

        if (isset($params['ecid']) && $params['ecid'] && $EID)
        {
            $dbh->Query("update calendar_event_coord set f_closed='t', event_id='$EID' where id='".$params['ecid']."'");
            $dbh->Query("update calendar_event_comments set event_id='$EID' where cec_id='".$params['ecid']."'");
        }

        // Now add recurring
        $saveType = null;
        if(isset($params['save_type']))
            $saveType = $params['save_type'];
            
        if (!$RID && $saveType != "this_event"  && isset($params['recur_type']) && $EID) 
        {
            $query = "insert into calendar_events_recurring(type, start_block, end_block, location, name, sharing, notes, calendar, date_start,
                            date_end, all_day, user_status, interval, day, month, relative_type, relative_section, week_days[1], week_days[2],
                            week_days[3], week_days[4], week_days[5], week_days[6], week_days[7])
                            values('".$params['recur_type']."', '".$start_block."', '".$end_block."', 
                            '".$dbh->Escape(stripslashes($params['location']))."',
                            '".$dbh->Escape($params['title'])."', '".$params['sharing']."', '".$dbh->Escape($params['notes'])."', 
                            '".$params['calendar_id']."', ".db_UploadDate($params['recur_date_start']).", ".db_UploadDate($params['recur_date_end']).",
                            '".$params['all_day']."', ".db_CheckNumber($params['user_status']).", 
                            ".db_CheckNumber($params['recur_interval']).", ".db_CheckNumber($params['recur_day']).",
                            ".db_CheckNumber($params['recur_month']).", ".db_CheckNumber($params['recur_relative_type']).",
                            ".db_CheckNumber($params['recur_relative_section']).", '".(($params['recur_day1'] == 't') ? 't' : 'f')."',
                            '".(($params['recur_day2'] == 't') ? 't' : 'f')."', '".(($params['recur_day3'] == 't') ? 't' : 'f')."',
                            '".(($params['recur_day4'] == 't') ? 't' : 'f')."', '".(($params['recur_day5'] == 't') ? 't' : 'f')."',
                            '".(($params['recur_day6'] == 't') ? 't' : 'f')."','".(($params['recur_day7'] == 't') ? 't' : 'f')."');
                      select currval('calendar_events_recurring_id_seq') as id;";
            $result = $dbh->Query($query);
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $RID = $row['id'];
                if ($RID)
                    $dbh->Query("update calendar_events set recur_id='$RID' where id='$EID'");

                // Check for lead association
                if ($params['lead_id'] && $RID)
                {
                    $dbh->Query("insert into calendar_event_associations(event_recur_id, lead_id) values('$RID', '".$params['lead_id']."');");
                }
                // Check for opportunity association
                if ($params['opportunity_id'] && $RID)
                {
                    $dbh->Query("insert into calendar_event_associations(event_recur_id, opportunity_id) values('$RID', '".$params['opportunity_id']."');");
                }
                // Check for conact association
                if ($params['contact_id'] && $RID)
                {
                    $dbh->Query("insert into calendar_event_associations(event_recur_id, contact_id) values('$RID', '".$params['contact_id']."');");
                }
                // Check for customer
                if ($params['customer_id'] && $RID)
                {
                    $dbh->Query("insert into calendar_event_associations(event_recur_id, customer_id) values('$RID', '".$params['customer_id']."');");
                }
            }
        }

        if ($EID)
        {
            // TODO: move to AntObject to fire workflow, for now just make sure cache is cleared
            $obj = new CAntObject($dbh, "calendar_event", $EID);
            $obj->clearCache();
            $obj->load(); // Reload new values
            $obj->removeMValues("associations");
            if (isset($params['associations']) && is_array($params['associations']))
            {
                foreach ($params['associations'] as $assoc)
                    $obj->setMValue("associations", $assoc);
            }
            
            if (isset($params['lead_id']) && $params['lead_id'])            
                $obj->addAssociation("lead", $params['lead_id'], "associations");
                
            if (isset($params['opportunity_id']) && $params['opportunity_id'])
                $obj->addAssociation("opportunity", $params['opportunity_id'], "associations");
                
            if (isset($params['contact_id']) && $params['contact_id'])
                $obj->addAssociation("contact_personal", $params['contact_personal'], "associations");
                
            if (isset($params['customer_id']) && $params['customer_id'])
                $obj->addAssociation("customer", $params['customer_id'], "associations");
                
            $obj->save();
        }

        // Add reminders
        // -------------------------------------------------------------------------
        if (isset($params['reminders']) && count($params['reminders']))
        {
            if ($EID)
            {
                // Deal with reminders
                foreach ($params['reminders'] as $remid)
                {
                    if (is_numeric($params['reminder_type_'.$remid]) && is_numeric($params["reminder_count_".$remid]) 
                        && is_numeric($params["reminder_interval_".$remid]) && $params["reminder_send_to_".$remid])
                    {
                        $query = "insert into calendar_events_reminders(event_id, count, interval, type, send_to)
                                    values(".db_CheckNumber($EID).", 
                                            ".db_CheckNumber($params["reminder_count_".$remid]).", 
                                            ".db_CheckNumber($params["reminder_interval_".$remid]).", 
                                            ".db_CheckNumber($params['reminder_type_'.$remid]).",
                                            '".$params["reminder_send_to_".$remid]."');
                                    select currval('calendar_events_reminders_id_seq') as id;";
                        $idres = $dbh->Query($query);
                        if ($dbh->GetNumberRows($idres))
                        {
                            $idrow = $dbh->GetNextRow($idres, 0);
                            if (is_numeric($params["reminder_count_".$remid]) && $params["reminder_interval_".$remid] && $idrow['id'])
                                CalReminderSetExeTime($dbh, $EID, $idrow['id'], $params["reminder_count_".$remid], $params["reminder_interval_".$remid]);
                            $dbh->FreeResults($idres);
                        }
                    }
                }
            }

            if ($RID && $params['save_type'] != "this_event")
            {
                // Deal with reminders
                foreach ($params['reminders'] as $remid)
                {                    
                    if (is_numeric($params['reminder_type_'.$remid]) && is_numeric($params["reminder_count_".$remid]) 
                        && is_numeric($params["reminder_interval_".$remid]) && $params["reminder_send_to_".$remid])
                    {
                        $query = "insert into calendar_events_reminders(recur_id, count, interval, type, send_to)
                                    values(".db_CheckNumber($RID).", 
                                            ".db_CheckNumber($params["reminder_count_".$remid]).", 
                                            ".db_CheckNumber($params["reminder_interval_".$remid]).", 
                                            ".db_CheckNumber($params['reminder_type_'.$remid]).",
                                            '".$params["reminder_send_to_".$remid]."');
                                    select currval('calendar_events_reminders_id_seq') as id;";
                        $idres = $dbh->Query($query);
                    }
                }
            }
        }    

        if ($EID)
            $ret = array("eid" => $EID, "rid" => $RID);
        else
            $ret = array("error"=>"error occurred.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Get Reminder Variables
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function getReminderVariables()
    {
        require_once("lib/sms.php");
        
        $dbh = $this->ant->dbh;
        
        $g_user_email = UserGetEmail($dbh, $this->user->id);
        $g_username = $this->user->name;
        $g_user_cell = str_replace(array('-', '(', ')', ' '), '', UserGetEmployeeInfo($dbh, $this->user->id, 'cell_phone'));
        $g_defcal = GetDefaultCalendar($dbh, $this->user->id);
        $g_userMobilePhone = $this->user->getSetting("mobile_phone");
        $g_userMobilePhoneCarrier = $this->user->getSetting("mobile_phone_carrier");
        
        $g_smscarriers = array();
        foreach ($SMS_CARRIERS as $c)
            $g_smscarriers[] = array($c[0], $c[1]);
        
        $ret = array('g_user_email' => $g_user_email, 'g_username' => $g_username, 'g_user_cell' => $g_user_cell,
                        'g_defcal' => $g_defcal, 'g_userMobilePhone' => $g_userMobilePhone, 'g_userMobilePhoneCarrier' => $g_userMobilePhoneCarrier,                    
                        'g_smscarriers' => $g_smscarriers);
                    
        
        $this->sendOutputJson($ret);
        return $ret;        
    }
    
    /**
     * Delete Event Reminder
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function deleteEventReminder($params)
    {
        $dbh = $this->ant->dbh;
        
        $id = $params['id'];        
        $EID = $params['eid'];        
        if($id)
        {
            $dbh->Query("delete from calendar_events_reminders where id='$id' AND event_id='$EID'");
            $ret = 1;
        }
        else
            $ret = array("error"=>"id is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * save Reminders
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function saveReminders($params)
    {
        $dbh = $this->ant->dbh;
        
        $EID = $params['eid'];        
        $ret = array();
        if($EID)
        {
            // Deal with reminders
            $ret = array();
            foreach ($params['reminders'] as $remid)
            {
                if (!is_numeric($remid) && is_numeric($params['reminder_type_'.$remid]) && is_numeric($params["reminder_count_".$remid]) 
                    && is_numeric($params["reminder_interval_".$remid]) && $params["reminder_send_to_".$remid])
                {                    
                    $query = "insert into calendar_events_reminders(event_id, count, interval, type, send_to)
                                values(".db_CheckNumber($EID).", 
                                        ".db_CheckNumber($params["reminder_count_".$remid]).", 
                                        ".db_CheckNumber($params["reminder_interval_".$remid]).", 
                                        ".db_CheckNumber($params['reminder_type_'.$remid]).",
                                        '".$params["reminder_send_to_".$remid]."');
                                select currval('calendar_events_reminders_id_seq') as id;";
                    $idres = $dbh->Query($query);
                    if ($dbh->GetNumberRows($idres))
                    {
                        $idrow = $dbh->GetNextRow($idres, 0);
                        if (is_numeric($params["reminder_count_".$remid]) && $params["reminder_interval_".$remid] && $idrow['id'])
                            CalReminderSetExeTime($dbh, $EID, $idrow['id'], $params["reminder_count_".$remid], $params["reminder_interval_".$remid]);                        
                            
                        $dbh->FreeResults($idres);
                        
                        $ret[] = array("id" => $idrow['id'], "eventId" => $EID, "reminderIndex" => $remid, "type" => $params['reminder_type_'.$remid],
                                        "count" => $params["reminder_count_".$remid], "interval" => $params["reminder_interval_".$remid],
                                        "send_to" => $params["reminder_send_to_".$remid]);
                    }
                }
            }            
        }
        else
            $ret = array("error"=>"eid is a required param");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Get the calendar event reminders
     *
     * @param array $params    An assocaitive array of parameters passed to this function.
     */
    public function getReminders($params)
    {
        $dbh = $this->ant->dbh;
        $eventId = $params['eventId'];
        $ret = array();
        
        if($eventId > 0)
        {
            $query = "select * from calendar_events_reminders where event_id = " . $dbh->EscapeNumber($eventId);
        
            $result = $dbh->Query($query);
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetNextRow($result, $i);
                $ret[] = $row;
            }
            
            $dbh->FreeResults($result);
        }
        else
            $ret = array("error"=>"event Id is a required param.");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
}
