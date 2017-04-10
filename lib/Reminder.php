<?php
/**
 * Reminder entity
 *
 * A reminder is a timestamp triggered reminder that notifies a user in a few pre-defined ways
 *
 * @category  Netric
 * @package   Reminder
 * @copyright Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
include("lib/Reminder/Email.php");
include("lib/Reminder/Popup.php");
include("lib/Reminder/Sms.php");

class Reminder
{
	/**
	 * Handle to database
	 *
	 * @var CDatabase
	 */
	protected $dbh = null;

	/**
	 * User this reminder is being executed for
	 *
	 * @var AntUser
	 */
	protected $user = null;

	/**
	 * Unique id of reminder if saved
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * Override user_id to get params - not common
	 *
	 * This can be an object reference (obj_type:id), an email address, or a phone number for sms
	 *
	 * @var string
	 */
	public $sendTo = "";

	/**
	 * The type of action to be executed
	 *
	 * @var string 'email' | 'sms' | 'popup'
	 */
	public $actionType = "";

	/**
	 * The object we are referencing
	 *
	 * @var string (obj_type:id)
	 */
	public $objReference = "";

	/**
	 * Instantiated objReference
	 *
	 * @var CAntObject
	 */
	protected $targetObj = null;

	/**
	 * Interval before if using the referenced object field for trigger
	 *
	 * @var int
	 */
	public $interval = null;

	/**
	 * Unit to apply to interval
	 *
	 * @var string 'minutes' | 'hours' | 'days' | 'weeks' | 'months'
	 */
	public $intervalUnit = "";

	/**
	 * Field name if using it for a trigger
	 *
	 * Must be a field of type timestamp
	 *
	 * @var string
	 */
	public $fieldName = "";

	/**
	 * Execution timestamp
	 *
	 * @var timestamp
	 */
	public $tsExecute = null;

	/**
	 * User id
	 *
	 * @var string
	 */
	public $userId = "";

	/**
	 * Is exectued
	 *
	 * @var bool
	 */
	public $fExecuted = false;

	/**
	 * Display notes
	 *
	 * @var string
	 */
	public $notes = "";

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param int $id Optional id of reminder to load
	 */
	public function __construct($dbh, $id=null)
	{
		$this->dbh = $dbh;

		if ($id)
			$this->load($id);
	}

	/**
	 * Save this reminder
	 *
	 * @param bool $calcExecByField If true the execution time will calculated by $this->fieldName
	 * @return int Id of the reminder on success, false on failure
	 */
	public function save($calcExecByField = true)
	{
		if ($calcExecByField && $this->fieldName && $this->objReference)
			$this->setTsExecByField();

		$fieldVals = array(
			'obj_reference' => "'" . $this->dbh->Escape($this->objReference) . "'",
			'interval' => $this->dbh->EscapeNumber($this->interval),
			'interval_unit' => "'" . $this->dbh->Escape($this->intervalUnit) . "'",
			'field_name' => "'" . $this->dbh->Escape($this->fieldName) . "'",
			'ts_execute' => $this->dbh->EscapeDate(date("Y-m-d H:i:s", $this->tsExecute)),
			'user_id' => $this->dbh->EscapeNumber($this->userId),
			'f_executed' => ($this->fExecuted) ? "'t'" : "'f'",
			'send_to' => "'" . $this->dbh->Escape($this->sendTo) . "'",
			'action_type' => "'" . $this->dbh->Escape($this->actionType) . "'",
			'notes' => "'" . $this->dbh->Escape($this->notes) . "'",
		);

		$sql = "";
		if ($this->id)
		{
			$fldSql = "";

			foreach ($fieldVals as $fname=>$fval)
			{
				if ($fldSql) $fldSql .= ",";
				$fldSql .= $fname . "=" . $fval;
			}

			$sql = "UPDATE reminders SET $fldSql WHERE id='" . $this->id . "'";
		}
		else
		{
			$flds = "";
			$vals = "";

			foreach ($fieldVals as $fname=>$fval)
			{
				if ($flds) $flds .= ",";
				if ($vals) $vals .= ",";

				$flds .= $fname;
				$vals .= $fval;
			}

			$sql = "INSERT INTO reminders($flds) VALUES($vals); select currval('reminders_id_seq') as id;";
		}

		$result = $this->dbh->Query($sql);
		if ($result)
		{
			if (!$this->id)
				$this->id = $this->dbh->GetValue($result, 0, "id");

			return $this->id;
		}

		return false;
	}

	/**
	 * Load a reminder by id
	 *
	 * @return bool true on success, false on failure
	 */
	public function load($id)
	{
		if (!is_numeric($id))
			return false;

		$result = $this->dbh->Query("SELECT * FROM reminders WHERE id='$id'");
		if ($this->dbh->GetNumberRows($result))
		{
			$row = $this->dbh->GetRow($result, 0);

			$this->id = $row['id'];
			$this->objReference = $row['obj_reference'];
			$this->interval = $row['interval'];
			$this->intervalUnit = $row['interval_unit'];
			$this->tsExecute = strtotime($row['ts_execute']);
			$this->userId = $row['user_id'];
			$this->fExecuted = ($row['f_executed'] == 't') ? true : false;
			$this->sendTo  = $row['send_to'];
			$this->actionType = $row['action_type'];
			$this->fieldName = $row['field_name'];
			$this->notes = $row['notes'];

			return true;
		}

		// Not found
		return false;
	}

	/**
	 * Execute the reminder based on the type
	 */
	public function execute()
	{
		if (!$this->user && $this->userId)
			$this->user = new AntUser($this->dbh, $this->userId);

		// Create reminder action
		$res = false;

		switch ($this->actionType)
		{
		case 'sms':
			$action = new Reminder_Sms($this);
			break;
		case 'email':
			$action = new Reminder_Email($this);
			break;
		case 'popup':
			$action = new Reminder_Popup($this);
			break;
		}
		
		$res = $action->execute();
		if ($res)
		{
			$this->fExecuted = true;
			$this->save(false);
			return $res;
		}

		return false;
	}

	/**
	 * Delay the reminder for a certain period of time
	 *
	 * @param timestamp $tsExecute The time to delay execution until
	 */
	public function delay($tsExecute)
	{
		$this->tsExecute = $tsExecute;
		$this->fExecuted = false;
		$this->save(false); // false=do not calcuate from $this->fieldName if set
	}

	/**
	 * Delete this reminder
	 */
	public function remove()
	{
		if (!is_numeric($this->id))
			return false;

		$this->dbh->Query("DELETE FROM reminders WHERE id='" . $this->id . "'");

		return true;
	}

	/**
	 * Set execution time by object field if fieldName is set
	 */
	public function setTsExecByField()
	{
		if (!$this->objReference || !$this->fieldName || !$this->interval || !$this->intervalUnit)
			return false;

		$parts = CAntObject::decodeObjRef($this->objReference);

		if (!$parts['obj_type'] || !$parts['id'])
			return false;

		$obj = CAntObject::factory($this->dbh, $parts['obj_type'], $parts['id']);

		if (!$obj->id)
			return false;

		$fldVal = $obj->getValue($this->fieldName);
		$ts = @strtotime($fldVal);

		if (!$fldVal || $fldVal === false)
			return false;

		$this->tsExecute = strtotime("-" . $this->interval . " " . $this->intervalUnit, $ts);
		return true;
	}

	/**
	 * Get referenced object
	 */
	public function getReferencedObject()
	{
		if ($this->targetObj == null)
		{
			$obj = null;

			if ($this->objReference)
			{
				$parts = CAntObject::decodeObjRef($this->objReference);
				
				if ($parts)
					$this->targetObj = CAntObject::factory($this->dbh, $parts['obj_type'], $parts['id']);
			}
		}

		return $this->targetObj;
	}
}
