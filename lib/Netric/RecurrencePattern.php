<?php
/**
 * Generic recurrence pattern used with entities and various other objects in netric
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */
namespace Netric;
/*
save:
if isChanged && !exception
	move/set parentId and processedTo to this & delete all future
	save recur pattern

on object delete with recurrence_patter:
	check if object is last in series, and delete recur pattern if true

MICROSOFT DEF:
olRecursDaily:		Duration , EndTime, Interval, NoEndDate, Occurrences, PatternStartDate, PatternEndDate,StartTime
olRecursWeekly:		DayOfWeekMask , Duration, EndTime, Interval, NoEndDate, Occurrences, PatternStartDate,PatternEndDate, StartTime
olRecursMonthly:	DayOfMonth , Duration, EndTime, Interval, NoEndDate, Occurrences, PatternStartDate,PatternEndDate, StartTime
olRecursMonthNth:	DayOfWeekMask , Duration, EndTime, Interval, Instance, NoEndDate, Occurrences,PatternStartDate, PatternEndDate, StartTime
olRecursYearly:		DayOfMonth , Duration, EndTime, Interval, MonthOfYear, NoEndDate, Occurrences,PatternStartDate, PatternEndDate, StartTime
olRecursYearNth:	DayOfWeekMask, MonthOfYear, Duration, EndTime, Interval, Instance, NoEndDate, Occurrences,PatternStartDate, PatternEndDate, StartTime

ANT DEF

RECUR_DAILY:		interval, dateStart, dateEnd
RECUR_WEEKLY:		dayOfWeekMask , interval, dateStart, dateEnd
RECUR_MONTHLY:		dayOfMonth , interval, dateStart, dateEnd
RECUR_MONTHNTH:		dayOfWeekMask , interval, dateStart, dateEnd, instance(nth)
RECUR_YEARLY:		dayOfMonth, interval, monthOfYear, dateStart, dateEnd
RECUR_YEARNTH:		dayOfWeekMask, interval, monthOfYear, dateStart, dateEnd, instance(nth)

*/
// Define recurrance types
define("RECUR_DAILY", 1);
define("RECUR_WEEKLY", 2);
define("RECUR_MONTHLY", 3);
define("RECUR_MONTHNTH", 4);
define("RECUR_YEARLY", 5);
define("RECUR_YEARNTH", 6);

define("WEEKDAY_SUNDAY", 1);
define("WEEKDAY_MONDAY", 2);
define("WEEKDAY_TUESDAY", 4);
define("WEEKDAY_WEDNESDAY", 8);
define("WEEKDAY_THURSDAY", 16);
define("WEEKDAY_FRIDAY", 32);
define("WEEKDAY_SATURDAY", 64);

class RecurrencePattern
{
	/**
	 * Type of recurrence applied to this pattern
	 *
	 * @var const self::RECUR_*
	 */
	private $recurType = null;
	const RECUR_DAILY = 1;
	const RECUR_WEEKLY = 2;
	const RECUR_MONTHLY = 3;
	const RECUR_MONTHNTH = 4;
	const RECUR_YEARLY = 5;
	const RECUR_YEARNTH = 6;

	/**
	 * Inverval every (num) days/weeks/months/years - defaults to 1
	 * 
	 * @var int
	 */
	private $interval = 1;

	/**
	 * When the recurrence starts
	 *
	 * @var \DateTime
	 */
	private $dateStart = null;

	/**
	 * When the recurrence ends, if null then no end
	 *
	 * @var \DateTime
	 */
	private $dateEnd = null;

	/**
	 * All day event flag
	 *
	 * @var bool
	 */
	private $fAllDay = false;

	/**
	 * If type is RECUR_MONTHLY or RECUR_YEARLY dayOfMonth can be set
	 *
	 * @var int 1-31
	 */
	private $dayOfMonth = null;

	/**
	 * Recor on a specific month of the year if type is yearly or yearnth
	 * 
	 * @var int 1-12
	 */
	private $monthOfYear = null;

	/**
	 * Bitmask used to turn on and off days of the week
	 *
	 * @var int bitmask of self::WEEKDAY_*
	 */
	private $dayOfWeekMask = null;
	const WEEKDAY_SUNDAY = 1;
	const WEEKDAY_MONDAY = 2;
	const WEEKDAY_TUESDAY = 4;
	const WEEKDAY_WEDNESDAY = 8;
	const WEEKDAY_THURSDAY = 16;
	const WEEKDAY_FRIDAY = 32;
	const WEEKDAY_SATURDAY = 64;

	/**
	 * The duration of the pattern in minutes
	 * 
	 * @var int
	 */
	private $duration = null;

	/**
	 * The 1st-5th(last) RECUR_MONTHNTH and RECUR_YEARNTH
	 *
	 * @var int 1-5
	 */
	private $instance = null;

	// App properties (should probably be private)
	var $fActive = true;	// Recurrance pattern is active or archived
	var $object_type_id;	// The object type associated with this recurrence instance
	var $object_type;		// The object type associated with this recurrence instance
	var $parentId;			// ID of originating object - orginal event or task where recur was started
	var $calendarId;		// (optional) calnedar id of this recurrence instance
	var $dateProcessedTo;	// Last date processed to. Useful for write-ahead processing of instance creation
	var $id;				// This instance id (if alrady created)
	var $useId;				// Reserved ID to use. Created via getNextId
	var $fieldDateStart;	// Required date start field
	var $fieldTimeStart; 	// Optional, can be same as date for timestamp
	var $fieldDateEnd;		// Required date end field
	var $fieldTimeEnd;		// Optional, can be same as date for timestamp
	var $arrChangeLog = array();
	var $debug = false;		// Used for debugging functions

	/**
	 * Locked timestamp
	 *
	 * @var int (epoch)
	 */
	public $epLocked = 0;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh An active handle to the account database
	 * @param int $id The unique id of a previously saved recur pattern
	 */
	function __construct($dbh, $id=null)
	{
		$this->dbh = $dbh;
		$this->id = $id;

		if ($id)
			$this->load($id);
	}

	/**************************************************************************
	*	Function: 	save
	*
	*	Purpose: 	Insert into the recurrance table
	**************************************************************************/
	function save()
	{
		if (!$this->validatePattern())
		{
			return false;
		}

		$dbh = $this->dbh;
		$toUpdate = array();
		$toupdate['object_type_id'] = $dbh->EscapeNumber($this->object_type_id);
		$toupdate['object_type'] = "'".$dbh->Escape($this->object_type)."'";
		$toupdate['date_processed_to'] = $dbh->EscapeDate($this->dateProcessedTo);
		$toupdate['parent_object_id'] = $dbh->EscapeNumber($this->parentId);

		$toupdate['type'] = $dbh->EscapeNumber($this->recurType);
		$toupdate['interval'] = $dbh->EscapeNumber($this->interval);
		$toupdate['date_start'] = $dbh->EscapeDate($this->dateStart);
		$toupdate['date_end'] = $dbh->EscapeDate($this->dateEnd);
		$toupdate['t_start'] = $dbh->EscapeDate($this->timeStart);
		$toupdate['t_end'] = $dbh->EscapeDate($this->timeEnd);
		$toupdate['dayofmonth'] = $dbh->EscapeNumber($this->dayOfMonth);
		//$toupdate['duration'] = $dbh->EscapeNumber($this->duration);
		$toupdate['instance'] = $dbh->EscapeNumber($this->instance);
		$toupdate['monthofyear'] = $dbh->EscapeNumber($this->monthOfYear);
		$toupdate['f_active'] = ($this->fActive) ? "'t'" : "'f'";
		$toupdate['ep_locked'] = $this->epLocked;

		if ($this->id && $dbh->GetNumberRows($dbh->Query("select id from object_recurrence WHERE id=".$dbh->EscapeNumber($this->id))))
		{
			$upd = "";
			foreach ($toupdate as $fname=>$fval)
			{
				if ($upd) $upd .= ", ";
				$upd .= $fname."=".$fval;
			}

			if ($upd) $upd .= ", ";
			$upd .= "dayofweekmask[1]='".(($this->dayOfWeekMask & WEEKDAY_SUNDAY) ? 't' : 'f')."', ";
			$upd .= "dayofweekmask[2]='".(($this->dayOfWeekMask & WEEKDAY_MONDAY) ? 't' : 'f')."', ";
			$upd .= "dayofweekmask[3]='".(($this->dayOfWeekMask & WEEKDAY_TUESDAY) ? 't' : 'f')."', ";
			$upd .= "dayofweekmask[4]='".(($this->dayOfWeekMask & WEEKDAY_WEDNESDAY) ? 't' : 'f')."', ";
			$upd .= "dayofweekmask[5]='".(($this->dayOfWeekMask & WEEKDAY_THURSDAY) ? 't' : 'f')."', ";
			$upd .= "dayofweekmask[6]='".(($this->dayOfWeekMask & WEEKDAY_FRIDAY) ? 't' : 'f')."', ";
			$upd .= "dayofweekmask[7]='".(($this->dayOfWeekMask & WEEKDAY_SATURDAY) ? 't' : 'f')."'";

			$query = "UPDATE object_recurrence SET $upd where id='".$this->id."'; select '".$this->id."' as id;";
		}
		else
		{
			if ($this->useId)
				$toupdate['id'] = $this->useId;

			$flds = "";
			$vls = "";
			foreach ($toupdate as $fname=>$fval)
			{
				if ($flds) 
				{	
					$flds .= ", ";
					$vls .= ", ";
				}

				$flds .= $fname;
				$vls .= $fval;
			}

			if ($flds) 
			{	
				$flds .= ", ";
				$vls .= ", ";
			}

			$flds	.= "dayofweekmask[1],";
			$vls	.="'".(($this->dayOfWeekMask & WEEKDAY_SUNDAY) ? 't' : 'f')."', ";
			$flds	.= "dayofweekmask[2],";
			$vls	.="'".(($this->dayOfWeekMask & WEEKDAY_MONDAY) ? 't' : 'f')."', ";
			$flds	.= "dayofweekmask[3],";
			$vls	.="'".(($this->dayOfWeekMask & WEEKDAY_TUESDAY) ? 't' : 'f')."', ";
			$flds	.= "dayofweekmask[4],";
			$vls	.="'".(($this->dayOfWeekMask & WEEKDAY_WEDNESDAY) ? 't' : 'f')."', ";
			$flds	.= "dayofweekmask[5],";
			$vls	.="'".(($this->dayOfWeekMask & WEEKDAY_THURSDAY) ? 't' : 'f')."', ";
			$flds	.= "dayofweekmask[6],";
			$vls	.="'".(($this->dayOfWeekMask & WEEKDAY_FRIDAY) ? 't' : 'f')."', ";
			$flds	.= "dayofweekmask[7]";
			$vls	.="'".(($this->dayOfWeekMask & WEEKDAY_SATURDAY) ? 't' : 'f')."'";

			$query = "INSERT INTO object_recurrence($flds) VALUES($vls); ";
			
			if ($this->useId)
				$query .= "select '".$this->useId."' as id;";
			else
				$query .= "select currval('object_recurrence_id_seq') as id;";
		}

		//echo $query;
		$result = $dbh->Query($query);
		if (!$this->id)
		{
			if ($dbh->GetNumberRows($result))
			{
				$this->id = $dbh->GetValue($result, 0, "id");
			}
		}

		if ($this->debug)
			echo "<pre>SAVE: ".var_export($toupdate,true)."</pre>";

		return $this->id;
	}

	/**************************************************************************
	*	Function: 	saveFromObj
	*
	*	Purpose: 	Save called from recurring object
	***************************************************************************/
	function saveFromObj($obj)
	{
		$dbh = $this->dbh;
		
		if ($obj->id)
		{
			$workingObj = $obj;

			// Move current object ask parent
			if ($this->parentId != $obj->id)
				$this->parentId = $obj->id;

			$curStart = $workingObj->getValue($obj->def->recurRules['field_date_start']);

			if (!$curStart)
				return false; // should never happen but just in case, start_field value of cur obj is required for recurrence

			$tsStart = @strtotime($curStart);
			if ($tsStart === false)
				return false; // make sure we are dealing with a valid date

			$this->dateProcessedTo = date("m/d/Y", $tsStart);

			// Delete all future objects in this series if event is pre-existing
			if ($this->id)
			{
				$objList = new CAntObjectList($dbh, $obj->object_type, $obj->user);
				$objList->addCondition("and", $obj->def->recurRules['field_recur_id'], "is_equal", $this->id);
				$objList->addCondition("and", $obj->def->recurRules['field_date_start'], "is_greater", $this->dateProcessedTo);
				$objList->addCondition("and", "id", "is_not_equal", $this->parentId); // just to be safe, never delete parent object
				$objList->getObjects();
				for ($i = 0; $i < $objList->getNumObjects(); $i++)
				{
					$objInst = $objList->getObject($i);
					//echo "<pre>Deleting future event ".$objInst->id."</pre>";
					$objInst->recurrenceException = true; // Prevent loops
					$objInst->removeHard(); // purge objects
				}
			}

			$this->save();
		}

		return $this->id;
	}

	/**************************************************************************
	*	Function: 	getNextId
	*
	*	Purpose: 	Get a unique ID
	***************************************************************************/
	function getNextId()
	{
		$dbh = $this->dbh;
		$ret = false;

		$query = "select nextval('object_recurrence_id_seq') as id;";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
		{
			$ret = $dbh->GetValue($result, 0, "id");
			$this->useId = $ret;
		}

		return $ret;
	}

	/**************************************************************************
	*	Function: 	load
	*
	*	Purpose: 	Load recurrance pattern from database
	***************************************************************************/
	function load($id)
	{
		$dbh = $this->dbh;
		$query = "select object_type_id, object_type, date_processed_to, parent_object_id, type, interval, date_start, 
					date_end, dayofmonth, instance, monthofyear, ep_locked,
					dayofweekmask[1] as day1, dayofweekmask[2] as day2, dayofweekmask[3] as day3, dayofweekmask[4] as day4,
					dayofweekmask[5] as day5, dayofweekmask[6] as day6, dayofweekmask[7] as day7
					from object_recurrence where id='".$id."'";
		//echo "<pre>$query</pre>";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetRow($result, 0);

			foreach ($row as $name=>$val)
				$this->arrChangeLog[$name] = $val;

			$this->object_type_id = $row['object_type_id'];
			$this->object_type = $row['object_type'];
			$this->dateProcessedTo = $row['date_processed_to'];
			$this->parentId = $row['parent_object_id'];
			$this->recurType = $row['type'];
			$this->interval = $row['interval'];
			$this->dateStart = $row['date_start'];
			$this->dateEnd = $row['date_end'];
            
            if(isset($row['calendar_id']))
			    $this->calendarId = $row['calendar_id'];
                
            if(isset($row['t_start']))
			    $this->timeStart = $row['t_start'];
                
            if(isset($row['t_end']))
			    $this->timeEnd = $row['t_end'];
            
            if(isset($row['dayofmonth']))
			    $this->dayOfMonth = $row['dayofmonth'];
                
            if(isset($row['duration']))
			    $this->duration = $row['duration'];
                
            if(isset($row['instance']))
			    $this->instance = $row['instance'];
                
            if(isset($row['monthofyear']))
			    $this->monthOfYear = $row['monthofyear'];

            if(isset($row['ep_locked']))
			    $this->epLocked = $row['ep_locked'];

            $this->fAllDay = (isset($row['all_day']) && $row['all_day']=='t') ? true : false;
            
			if ($row['day1'] == 't')
				$this->dayOfWeekMask = $this->dayOfWeekMask | WEEKDAY_SUNDAY;
			if ($row['day2'] == 't')
				$this->dayOfWeekMask = $this->dayOfWeekMask | WEEKDAY_MONDAY;
			if ($row['day3'] == 't')
				$this->dayOfWeekMask = $this->dayOfWeekMask | WEEKDAY_TUESDAY;
			if ($row['day4'] == 't')
				$this->dayOfWeekMask = $this->dayOfWeekMask | WEEKDAY_WEDNESDAY;
			if ($row['day5'] == 't')
				$this->dayOfWeekMask = $this->dayOfWeekMask | WEEKDAY_THURSDAY;
			if ($row['day6'] == 't')
				$this->dayOfWeekMask = $this->dayOfWeekMask | WEEKDAY_FRIDAY;
			if ($row['day7'] == 't')
				$this->dayOfWeekMask = $this->dayOfWeekMask | WEEKDAY_SATURDAY;

			$dbh->FreeResults($result);

			// Load recurrence rules
			if ($row['object_type'])
			{
				$odef = new CAntObject($dbh, $row['object_type']);
				$this->fieldDateStart = $odef->def->recurRules['field_date_start'];
				$this->fieldTimeStart = $odef->def->recurRules['field_time_start'];
				$this->fieldDateEnd = $odef->def->recurRules['field_date_end'];
				$this->fieldTimeEnd = $odef->def->recurRules['field_time_end'];
			}

			return true;
		}

		return false;
	}

	/**************************************************************************
	*	Function: 	isChanged
	*
	*	Purpose: 	check to see if values have changed since last save
	***************************************************************************/
	function isChanged()
	{
		if (count($this->arrChangeLog) == 0) // Check for new
		{
			return true;
		}

		// Now check previously set values to see if this has changed
		if ($this->arrChangeLog['interval'] != $this->interval)
			return true;
		else if ($this->arrChangeLog['type'] != $this->recurType)
			return true;
		else if ($this->arrChangeLog['date_start'] != $this->dateStart)
			return true;
		else if ($this->arrChangeLog['date_end'] != $this->dateEnd)
			return true;
		else if ($this->arrChangeLog['dayofmonth'] != $this->dayOfMonth)
			return true;
		else if ($this->arrChangeLog['monthofyear'] != $this->monthOfYear)
			return true;
		else if ($this->arrChangeLog['instance'] != $this->instance)
			return true;
		else if ($this->arrChangeLog['day1']=='t' && !($this->dayOfWeekMask & WEEKDAY_SUNDAY))
			return true;
		else if ($this->arrChangeLog['day1']=='f' && ($this->dayOfWeekMask & WEEKDAY_SUNDAY))
			return true;
		else if ($this->arrChangeLog['day2']=='t' && !($this->dayOfWeekMask & WEEKDAY_MONDAY))
			return true;
		else if ($this->arrChangeLog['day2']=='f' && ($this->dayOfWeekMask & WEEKDAY_MONDAY))
			return true;
		else if ($this->arrChangeLog['day3']=='t' && !($this->dayOfWeekMask & WEEKDAY_TUESDAY))
			return true;
		else if ($this->arrChangeLog['day3']=='f' && ($this->dayOfWeekMask & WEEKDAY_TUESDAY))
			return true;
		else if ($this->arrChangeLog['day4']=='t' && !($this->dayOfWeekMask & WEEKDAY_WEDNESDAY))
			return true;
		else if ($this->arrChangeLog['day4']=='f' && ($this->dayOfWeekMask & WEEKDAY_WEDNESDAY))
			return true;
		else if ($this->arrChangeLog['day5']=='t' && !($this->dayOfWeekMask & WEEKDAY_THURSDAY))
			return true;
		else if ($this->arrChangeLog['day5']=='f' && ($this->dayOfWeekMask & WEEKDAY_THURSDAY))
			return true;
		else if ($this->arrChangeLog['day6']=='t' && !($this->dayOfWeekMask & WEEKDAY_FRIDAY))
			return true;
		else if ($this->arrChangeLog['day6']=='f' && ($this->dayOfWeekMask & WEEKDAY_FRIDAY))
			return true;
		else if ($this->arrChangeLog['day7']=='t' && !($this->dayOfWeekMask & WEEKDAY_SATURDAY))
			return true;
		else if ($this->arrChangeLog['day7']=='f' && ($this->dayOfWeekMask & WEEKDAY_SATURDAY))
			return true;

		return false;
	}

	/**************************************************************************
	*	Function: 	remove
	*
	*	Purpose: 	Delete this recurring pattern
	***************************************************************************/
	function remove()
	{
		if (!$this->id)
			return false;

		$this->dbh->Query("delete from object_recurrence where id='".$this->id."'");

		return true;
	}

	/**************************************************************************
	*	Function: 	removeSeries
	*
	*	Purpose: 	Delete all objects in this series
	***************************************************************************/
	function removeSeries($refObj=null)
	{
		if (!$this->id || !$this->object_type)
			return false;

		if ($refObj)
			$objDef = $refObj;
		else
			$objDef = new CAntObject($this->dbh, $this->object_type);

		// Delete all objects in the series
		$objList = new CAntObjectList($this->dbh, $this->object_type);
		$objList->addCondition("and", $objDef->def->recurRules['field_recur_id'], "is_equal", $this->id);
		if ($objDef->id)
			$objList->addCondition("and", "id", "is_not_equal", $objDef->id);
		$objList->getObjects();
		for ($i = 0; $i < $objList->getNumObjects(); $i++)
		{
			$obj = $objList->getObject($i);
			$obj->recurrenceException = true; // Prevent loops
			$obj->remove(); // series of objects
		}
	}

	/**************************************************************************
	*	Function: 	createInstances
	*
	*	Purpose: 	Loop through and created recurring object until $toDate
	***************************************************************************/
	function createInstances($toDate, $debug=false)
	{
		// Make sure we are working with a valid pattern
		if (!$this->validatePattern())
			return 0;

		// Make sure we are not locked by another process within the last 2 minutes
		// so we don't duplicate recurring events
		if ($this->epLocked && $this->epLocked >= (time() - 120))
			return 0;

		// Lock this pattern to prevent overlap
		$this->epLocked = time();
		$this->save();

		$dbh = $this->dbh;
		$numCreated = 0;
		
		$toTime = strtotime($toDate);
		$nextDate  = $this->getNextStart(); // will skip over current instance and jump to 'date_processed_to'
		if (!$nextDate)
			return 0;

		$tsNextDate = strtotime($nextDate);

		
		while($tsNextDate<=$toTime)
		{
			$objOrig = new CAntObject($dbh, $this->object_type, $this->parentId);
			$user = ($objOrig->owner_id!=null) ? new AntUser($dbh, $objOrig->owner_id) :  null;
			$objNew = new CAntObject($dbh, $this->object_type, NULL, $user);
			
			// Set start date and time
			$date_start = $nextDate;
			$time_start = $this->timeStart;

			if ($this->debug)
				echo "DS: $nextDate TS Field: ".$this->fieldTimeStart."\n";

			if ($this->fieldTimeStart)
			{
				// Get time from time_start timestamp
				if (!$time_start && $objOrig->getValue($this->fieldTimeStart))
				{
					if (@strtotime($objOrig->getValue($this->fieldTimeStart))!== false)
					{
						$time_start = date("h:i A", strtotime($objOrig->getValue($this->fieldTimeStart)));
					}
				}

				if ($this->fieldTimeStart == $this->fieldDateStart)
				{
					$objNew->setValue($this->fieldDateStart, $date_start." ".$time_start);
				}
				else
				{
					$objNew->setValue($this->fieldDateStart, $date_start);
					$objNew->setValue($this->fieldTimeStart, $time_start);
				}
			}
			else
			{
				$objNew->setValue($this->fieldDateStart, $date_start);
			}

			// Set end date and time
			$date_end = $nextDate;
			$time_end = $this->timeEnd;
			if ($this->fieldTimeEnd)
			{
				// Get time from time_end timestamp
				if (!$time_end && $objOrig->getValue($this->fieldTimeEnd))
				{
					if (@strtotime($objOrig->getValue($this->fieldTimeEnd))!== false)
					{
						$time_end = date("h:i A", strtotime($objOrig->getValue($this->fieldTimeEnd)));
					}
				}

				if ($this->fieldTimeEnd == $this->fieldDateEnd)
				{
					$objNew->setValue($this->fieldDateEnd, $date_end." ".$time_end);
				}
				else
				{
					$objNew->setValue($this->fieldDateEnd, $date_start);
					$objNew->setValue($this->fieldTimeEnd, $time_end);
				}
			}
			else
			{
				$objNew->setValue($this->fieldDateEnd, $date_end);
			}

			// Copy remaining fields
			// ---------------------------------------------------------------
			$all_fields = $objOrig->def->getFields();
			foreach ($all_fields as $fname=>$fdef)
			{
				if ($fname!=$this->fieldDateStart && $fname!=$this->fieldDateEnd
					&& $fname!=$this->fieldTimeStart && $fname!=$this->fieldTimeEnd
					&& ($fdef->readonly!=true || $fname=='associations') // Copy associations
					&& $fname!='activity') // Do not copy activity
				{
					if ($fdef->type == "fkey_multi")
					{
						$vals = $objOrig->getValue($fname);
						if (is_array($vals) && count($vals))
						{
							foreach ($vals as $val)
							{
									$objNew->setMValue($fname, $val);
							}
						}
					}
					else
					{
						$objNew->setValue($fname, $objOrig->getValue($fname));
					}
				}
			}
			// Set recurrence field (read only)
			$objNew->setValue($objNew->fields->recurRules['field_recur_id'], $this->id);
			$objNew->recurrenceException = true; // No need to reporcess
			$oid = $objNew->save();
			$numCreated++;

			if ($this->debug)
			{
				echo "Created new object $oid<br />\n";
				echo "&nbsp;&nbsp;&nbsp;\n";
				//echo $objNew->getValue($objNew->fields->recurRules['field_recur_id']);
				//echo "<br />\n";
				//echo "&nbsp;&nbsp;&nbsp;\n";
				echo $objNew->fields->recurRules['field_date_start']." = ";
				echo $objNew->getValue($objNew->fields->recurRules['field_date_end']);
				echo "<br />\n";
				echo "<br />\n";
				flush();
			}
			
			$nextDate = $this->getNextStart();
			if ($nextDate)
				$tsNextDate = strtotime($nextDate);
			else
				break; // kill the loop
		}
		
		$this->dateProcessedTo = $toDate; // Update processed to so we don't duplicate efforts
		$this->epLocked = 0;
		$this->save();
		return $numCreated;
	}
	
	/**************************************************************************
	*	Function: 	getNextStart
	*
	*	Purpose: 	Get the timestamp for the next instance start date
	***************************************************************************/
	function getNextStart()
	{
		switch ($this->recurType)
		{
		case RECUR_DAILY:
			return $this->getNextStartDaily();
			break;
		case RECUR_WEEKLY:
			return $this->getNextStartWeekly();
			break;
		case RECUR_MONTHLY:
			return $this->getNextStartMonthly();
			break;
		case RECUR_MONTHNTH:
			return $this->getNextStartMonthlyNth();
			break;
		case RECUR_YEARLY:
			return $this->getNextStartYearly();
			break;
		case RECUR_YEARNTH:
			return $this->getNextStartYearlyNth();
			break;
		}

		return false; // RecurrenceType was not set for this object
	}

	/**************************************************************************
	*	Function: 	getNextStartDaily
	*
	*	Purpose: 	Step through to the next start date for daily recurrance
	***************************************************************************/
	function getNextStartDaily()
	{
		if (!$this->dateStart || !$this->interval)
			return false;

		if (!$this->dateProcessedTo)
		{
			$ret = date("m/d/Y", strtotime($this->dateStart));
			$this->dateProcessedTo = $ret;

			return $ret;
		}

		$tsBegin = strtotime($this->dateStart);
		$tsCur = strtotime($this->dateProcessedTo);

		// Step over tsCur if not beginning
		$tsTmp = $tsBegin;
		while ($tsTmp <= $tsCur)
		{
			$tsTmp = strtotime("+ ".$this->interval." days", $tsTmp);
		}
		$tsCur = $tsTmp;

		if ($this->dateEnd)
		{
			$end = strtotime($this->dateEnd);
			if ($tsCur > $end)
			{
				$this->dateProcessedTo = date("m/d/Y", $tsCur);
				$tsCur = false;
			}
		}

		if ($tsCur)
		{
			$this->dateProcessedTo = date("m/d/Y", $tsCur);
			return date("m/d/Y", $tsCur);
		}
		else
		{
			return false;
		}
	}

	/**************************************************************************
	*	Function: 	getNextStartWeekly
	*
	*	Purpose: 	Step through to the next start date for daily recurrance
	*
	*	Variables:	dayOfWeekMask, interval, dateStart, dateEnd
	***************************************************************************/
	function getNextStartWeekly()
	{
		if (!$this->dayOfWeekMask || !$this->dateStart || !$this->interval)
			return false;

		$maxloops = 100000; // Prevent infiniate loops
		$ret = null;
		$tsBegin = strtotime($this->dateStart);
		if ($this->dateProcessedTo)
			$tsCur = strtotime("+1 day", strtotime($this->dateProcessedTo));
		else
			$tsCur = $tsBegin;
		
		$loops = 0;
		do
		{
			// Step through the week and look for a match
			$tsTmp = $tsCur;
			$dow = date("w", $tsCur); // get starting day of week - 0 for Sunday, 6 for Saturday
			for ($i = (int)$dow; $i<=6; $i++) // Loop while we don't have a match and we are still within the week
			{
				switch ($i)
				{
				case 0:
					$test = WEEKDAY_SUNDAY;
					break;
				case 1:
					$test = WEEKDAY_MONDAY;
					break;
				case 2:
					$test = WEEKDAY_TUESDAY;
					break;
				case 3:
					$test = WEEKDAY_WEDNESDAY;
					break;
				case 4:
					$test = WEEKDAY_THURSDAY;
					break;
				case 5:
					$test = WEEKDAY_FRIDAY;
					break;
				case 6:
					$test = WEEKDAY_SATURDAY;
					break;
				}

				if ($this->dayOfWeekMask & $test)
				{
					$ret = $tsTmp;
					break;
				}
				else
					$tsTmp = strtotime("+1 day", $tsTmp);
			}

			if (!$ret)
			{
				// Increment week
				$tsCur = strtotime("+ ".$this->interval." weeks", $tsCur);
				// rewined to beginning of next week - Sunday (0)
				$dow = date("w", $tsCur); // day of week - 0 for Sunday, 6 for Saturday
				if ($dow)
					$tsCur = strtotime("- $dow days", $tsCur);
			}
			$loops++;
		} while(!$ret && $loops<$maxloops);

		if ($this->dateEnd)
		{
			$end = strtotime($this->dateEnd);
			if ($ret > $end)
			{
				$this->dateProcessedTo = date("m/d/Y", $ret);
				$ret = false;
			}
		}

		if ($ret)
		{
			$this->dateProcessedTo = date("m/d/Y", $ret);
			return date("m/d/Y", $ret);
		}
		else
		{
			return false;
		}
	}

	/**************************************************************************
	*	Function: 	getNextStartMonthly
	*
	*	Purpose: 	Step through to the next start date for monthly recurrance
	*
	*	Variables:	dayOfMonth, interval, dateStart, dateEnd
	***************************************************************************/
	function getNextStartMonthly()
	{
		if (!$this->dateProcessedTo)
		{
			$tsStart = strtotime($this->dateStart);

			// Deal will a different start date from the dayOfMonth
			if (strtotime(date("m", $tsStart)."/".$this->dayOfMonth."/".date("Y", $tsStart)) != $tsStart)
			{
				$nextmonth = strtotime("+ 1 months", strtotime(date("m", $tsStart)."/1/".date("Y", $tsStart)));
				$lastDayOfMonth = date("j", strtotime("-1 day", strtotime(date("m", $nextmonth)."/1/".date("Y", $nextmonth))));

				if ((int)$lastDayOfMonth>=$this->dayOfMonth) // Not a valid date then skip to next month
				{
					$ret = date("m/d/Y", strtotime(date("m", $tsStart)."/".$this->dayOfMonth."/".date("Y", $tsStart)));
				}
				else
				{
					$this->dateProcessedTo = date("m/d/Y", strtotime(date("m", $tsStart)."/".$lastDayOfMonth."/".date("Y", $tsStart)));
					$ret = $this->getNextStartMonthly();
				}
			}
			else
			{
				$ret = date("m/d/Y", strtotime($this->dateStart));
			}

			$this->dateProcessedTo = $ret;
			return $ret;
		}

		$tsBegin = strtotime($this->dateStart);
		$tsCur = strtotime($this->dateProcessedTo);

		// Step over tsCur if not beginning
		$tsTmp = $tsBegin;
		while ($tsTmp <= $tsCur)
		{	
			$nextmonth = strtotime("+ ".$this->interval." months", strtotime(date("m", $tsTmp)."/1/".date("Y", $tsTmp)));
			$lastDayOfMonth = date("j", strtotime("-1 day", strtotime("+1 month", $nextmonth)));
			

			if ((int)$lastDayOfMonth>=$this->dayOfMonth) // Not a valid date then skip to next month
			{
				$tsTmp = strtotime(date("m", $nextmonth)."/".$this->dayOfMonth."/".date("Y", $nextmonth));
			}
			else
			{
				$tsTmp = strtotime(date("m", $nextmonth)."/".$lastDayOfMonth."/".date("Y", $nextmonth));
				if ($tsTmp > $tsCur) // Step over this if needed to the next month
					$tsCur = $tsTmp; 
			}

		}
		$tsCur = $tsTmp;

		if ($this->dateEnd)
		{
			$end = strtotime($this->dateEnd);
			if ($tsCur > $end)
			{
				$this->dateProcessedTo = date("m/d/Y", $tsCur);
				$tsCur = false;
			}
		}

		if ($tsCur)
		{
			$this->dateProcessedTo = date("m/d/Y", $tsCur);
			return date("m/d/Y", $tsCur);
		}
		else
		{
			return false;
		}
	}

	/**************************************************************************
	*	Function: 	getNextStartMonthlyNth
	*
	*	Purpose: 	Step through to the next start date for monthlynth recurrance
	*
	*	Variables:	instance(nth), dayOfWeekMask, interval, dateStart, dateEnd
	***************************************************************************/
	function getNextStartMonthlyNth()
	{
		$maxloops = 100; // Prevent infiniate loops
		$ret = null;
		$tsBegin = strtotime($this->dateStart);
		if ($this->dateProcessedTo)
			$tsCur = strtotime("+1 day", strtotime($this->dateProcessedTo));
		else
			$tsCur = $tsBegin;
		
		$loops = 0;
		do
		{
			// Step through the month and look for a match
			$tsTmp = $tsCur;
			$day = date("j", $tsCur); // Get the current day
			$nextmonth = strtotime("+ 1 months", strtotime(date("m", $tsCur)."/1/".date("Y", $tsCur)));
			$lastDayOfMonth = date("j", strtotime("-1 day", $nextmonth));
			for ($i = (int)$day; $i<=(int)$lastDayOfMonth; $i++) // Loop while we don't have a match and we are still within the week
			{
				$dow = date("w", $tsTmp); // get starting day of week - 0 for Sunday, 6 for Saturday
				switch ($dow)
				{
				case 0:
					$test = WEEKDAY_SUNDAY;
					break;
				case 1:
					$test = WEEKDAY_MONDAY;
					break;
				case 2:
					$test = WEEKDAY_TUESDAY;
					break;
				case 3:
					$test = WEEKDAY_WEDNESDAY;
					break;
				case 4:
					$test = WEEKDAY_THURSDAY;
					break;
				case 5:
					$test = WEEKDAY_FRIDAY;
					break;
				case 6:
					$test = WEEKDAY_SATURDAY;
					break;
				}

				$curMonth = date("n", $tsTmp);
				$curYear = date("Y", $tsTmp);
				$curDay = date("j", $tsTmp);
				$currentInstance =  calGetWkDayInMonth($curYear, $curMonth, $curDay); // 2nd Monday, 1st Tuesday etc...
				$f_lastwkdayinmonth = calDateIsLastWkDayInMonth($curYear, $curMonth, $curDay); // Last thursday etc...
				//if ($f_lastwkdayinmonth)
					//echo "Last ".date("l", $tsTmp)." in ".date("m", $tsTmp)." is the ".date("j", $tsTmp)."<br />";
				if ($this->dayOfWeekMask & $test && ($currentInstance==$this->instance || ($this->instance==5 && $f_lastwkdayinmonth)))
				{
					$ret = $tsTmp;
					break;
				}
				else
					$tsTmp = strtotime("+1 day", $tsTmp);
			}

			if (!$ret)
			{
				// Increment week
				$tsCur = strtotime("+ ".$this->interval." months", strtotime(date("m", $tsCur)."/1/".date("Y", $tsCur)));
			}

			$loops++;
		} while(!$ret && $loops<$maxloops);

		if ($this->dateEnd)
		{
			$end = strtotime($this->dateEnd);
			if ($ret > $end)
			{
				$this->dateProcessedTo = date("m/d/Y", $ret);
				$ret = false;
			}
		}

		if ($ret)
		{
			$this->dateProcessedTo = date("m/d/Y", $ret);
			return date("m/d/Y", $ret);
		}
		else
		{
			return false;
		}
	}

	/**************************************************************************
	*	Function: 	getNextStartYearly
	*
	*	Purpose: 	Step through to the next start date for yearly recurrance
	*
	*	Variables:	dayOfMonth, interval, monthOfYear, dateStart, dateEnd
	***************************************************************************/
	function getNextStartYearly()
	{
		if (!$this->monthOfYear || !$this->dayOfMonth || !$this->interval)
			return false;

		if (!$this->dateProcessedTo)
		{
			$tsStart = strtotime($this->dateStart);
			$ret = strtotime($this->monthOfYear."/".$this->dayOfMonth."/".date("Y", $tsStart));
			if ($tsStart > $ret)
			{
				$this->dateProcessedTo = date("m/d/Y", $tsStart);
				$ret = $this->getNextStartMonthly();
			}
			else
				$this->dateProcessedTo = date("m/d/Y", $ret);

			return date("m/d/Y", $ret);
		}

		// Get real start point
		$tsBegin = strtotime($this->dateStart);
		if ((int)date("j", $tsBegin) != $this->dayOfMonth)
		{
			$tmp = strtotime($this->monthOfYear."/".$this->dayOfMonth."/".date("Y", $tsBegin));
			if ($tsBegin > $tmp)
				$tsBegin = strtotime("+1 year", $tmp);
			else
				$tsBegin = $tmp;
		}

		$tsCur = strtotime($this->dateProcessedTo);

		// Step over tsCur if not beginning
		$tsTmp = $tsBegin;
		while ($tsTmp <= $tsCur)
		{
			$tsTmp = strtotime("+ ".$this->interval." years", $tsTmp);
		}
		$tsCur = $tsTmp;

		if ($this->dateEnd)
		{
			$end = strtotime($this->dateEnd);
			if ($tsCur > $end)
			{
				$this->dateProcessedTo = date("m/d/Y", $tsCur);
				$tsCur = false;
			}
		}

		if ($tsCur)
		{
			$this->dateProcessedTo = date("m/d/Y", $tsCur);
			return date("m/d/Y", $tsCur);
		}
		else
		{
			return false;
		}
	}

	/**************************************************************************
	*	Function: 	getNextStartYearlyNth
	*
	*	Purpose: 	Step through to the next start date for yearly recurrance
	*
	*	Variables:	dayOfWeekMask, interval, monthOfYear, dateStart, dateEnd, instance(nth)
	***************************************************************************/
	function getNextStartYearlyNth()
	{
		if (!$this->monthOfYear || !$this->instance || !$this->interval)
			return false;

		$maxloops = 100000; // Prevent infiniate loops
		$ret = null;

		// Get real start point
		$tsBegin = strtotime($this->dateStart);
		if ((int)date("n", $tsBegin) != $this->monthOfYear)
		{
			$tmp = strtotime($this->monthOfYear."/1/".date("Y", $tsBegin));
			if ($tsBegin > $tmp)
				$tsBegin = strtotime("+1 year", $tmp);
			else
				$tsBegin = $tmp;
		}

		if ($this->dateProcessedTo)
			$tsCur = strtotime("+1 day", strtotime($this->dateProcessedTo));
		else
			$tsCur = $tsBegin;
		
		$loops = 0;
		do
		{
			// Step through the month and look for a match
			$tsTmp = $tsCur;
			$day = date("j", $tsCur); // Get the current day
			$nextmonth = strtotime("+ 1 months", strtotime(date("m", $tsCur)."/1/".date("Y", $tsCur)));
			$lastDayOfMonth = date("j", strtotime("-1 day", $nextmonth));
			for ($i = (int)$day; $i<=(int)$lastDayOfMonth; $i++) // Loop while we don't have a match and we are still within the month
			{
				$dow = date("w", $tsTmp); // get starting day of week - 0 for Sunday, 6 for Saturday
				switch ($dow)
				{
				case 0:
					$test = WEEKDAY_SUNDAY;
					break;
				case 1:
					$test = WEEKDAY_MONDAY;
					break;
				case 2:
					$test = WEEKDAY_TUESDAY;
					break;
				case 3:
					$test = WEEKDAY_WEDNESDAY;
					break;
				case 4:
					$test = WEEKDAY_THURSDAY;
					break;
				case 5:
					$test = WEEKDAY_FRIDAY;
					break;
				case 6:
					$test = WEEKDAY_SATURDAY;
					break;
				}

				$curMonth = date("n", $tsTmp);
				$curYear = date("Y", $tsTmp);
				$curDay = date("j", $tsTmp);
				$currentInstance =  calGetWkDayInMonth($curYear, $curMonth, $curDay); // 2nd Monday, 1st Tuesday etc...
				$f_lastwkdayinmonth = calDateIsLastWkDayInMonth($curYear, $curMonth, $curDay); // Last thursday etc...
				//if ($f_lastwkdayinmonth)
					//echo "Last ".date("l", $tsTmp)." in ".date("m", $tsTmp)." is the ".date("j", $tsTmp)."<br />";
				if ($this->dayOfWeekMask & $test && ($currentInstance==$this->instance || ($this->instance==5 && $f_lastwkdayinmonth)))
				{
					$ret = $tsTmp;
					break;
				}
				else
					$tsTmp = strtotime("+1 day", $tsTmp);
			}

			if (!$ret)
			{
				// Increment year
				$tsCur = strtotime("+ ".$this->interval." years", strtotime($this->monthOfYear."/1/".date("Y", $tsCur)));
			}

			$loops++;
		} while(!$ret && $loops<$maxloops);

		if ($this->dateEnd)
		{
			$end = strtotime($this->dateEnd);
			if ($ret > $end)
			{
				$this->dateProcessedTo = date("m/d/Y", $ret);
				$ret = false;
			}
		}

		if ($ret)
		{
			$this->dateProcessedTo = date("m/d/Y", $ret);
			return date("m/d/Y", $ret);
		}
		else
		{
			return false;
		}
	}

	/**************************************************************************
	*	Function: 	validatePattern
	*
	*	Purpose: 	Make sure this is a valid pattern given the type
	***************************************************************************/
	function validatePattern()
	{
		if (!$this->dateStart) // Required by all
			return false;

		switch ($this->recurType)
		{
		case RECUR_DAILY:
			if ($this->interval)
				return true;
			break;
		case RECUR_WEEKLY:
			if ($this->dayOfWeekMask && $this->interval)
				return true;
			break;
		case RECUR_MONTHLY:
			if ($this->dayOfMonth && $this->interval)
				return true;
			break;
		case RECUR_MONTHNTH:
			if ($this->dayOfWeekMask && $this->instance && $this->interval)
				return true;
			break;
		case RECUR_YEARLY:
			if ($this->monthOfYear && !$this->dayOfMonth && $this->interval)
				return true;
			break;
		case RECUR_YEARNTH:
			if ($this->monthOfYear && $this->instance && $this->interval)
				return true;
			break;
		}

		return false; // RecurrenceType was not set for this object
	}

}