<?php
/**
 * Reminder objects
 *
 * @category CAntObject
 * @package REminder
 * @copyright Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/ReminderActionAbstract.php");

/**
 * Object extensions for reminder
 */
class CAntObject_Reminder extends CAntObject
{
	/**
	 * Flag that indicates if reminder should calc ts_execute by field_name on save
	 *
	 * @var bool
	 */
	protected $calcExecByField = true;

	/**
	 * Instantiated objReference
	 *
	 * @var CAntObject
	 */
	protected $targetObj = null;

	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh An active handle to a database connection
	 * @param int $eid The event id we are editing - this is optional
	 * @param AntUser $user	Optional current user
	 */
	public function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "reminder", $eid, $user);
	}

	/**
	 * This is called just before the 'save' is processed in the base object
	 */
	protected function beforesaved()
	{
		if ($this->calcExecByField && $this->getValue('field_name') && $this->getValue('obj_reference'))
			$this->setTsExecByField();

		// Update seen if the execute time changed
		if ($this->fieldValueChanged("ts_execute") && $this->getValue('f_executed') == 't')
			$this->setValue('f_executed', 'f');
	}

	/**
	 * Execute the reminder based on the type
	 */
	public function execute()
	{
		if (!$this->user && $this->getValue("owner_id"))
			$this->user = new AntUser($this->dbh, $this->getValue("owner_id"));

		// Create reminder action
		$res = false;

		switch ($this->getValue("action_type"))
		{
		case 'sms':
			$action = new ReminderAction_Sms($this);
			break;
		case 'email':
			$action = new ReminderAction_Email($this);
			break;
		case 'popup':
			$action = new ReminderAction_Popup($this);
			break;
		}
		
		$res = $action->execute();
		if ($res)
		{
			$this->calcExecByField = false;
			$this->setValue("f_executed", 't');
			$this->save();
			$this->calcExecByField = true;
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
		$this->setValue("ts_execute", date("Y-m-d h:i:s A", $tsExecute));
		$this->setValue("f_executed", 'f');
	
		// false=do not calcuate from $this->fieldName if set
		$this->calcExecByField = false;
		$this->save(); 
		$this->calcExecByField = true;
	}

	/**
	 * Set execution time by object field if fieldName is set
	 */
	public function setTsExecByField()
	{
		if (!$this->getValue("obj_reference") || !$this->getValue("field_name")  || !$this->getValue("interval")  || !$this->getValue("interval_unit"))
			return false;

		$parts = CAntObject::decodeObjRef($this->getValue("obj_reference"));

		if (!$parts['obj_type'] || !$parts['id'])
			return false;

		$obj = CAntObject::factory($this->dbh, $parts['obj_type'], $parts['id']);

		if (!$obj->id)
			return false;

		$fldVal = $obj->getValue($this->getValue("field_name"));
		$ts = @strtotime($fldVal);

		if (!$fldVal || $fldVal === false)
			return false;

		$newTime = strtotime("-" . $this->getValue("interval") . " " . $this->getValue("interval_unit"), $ts);

		$this->setValue("ts_execute", date("Y-m-d h:i:s A", $newTime));
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

			if ($this->getValue("obj_reference"))
			{
				$parts = CAntObject::decodeObjRef($this->getValue("obj_reference"));
				
				if ($parts)
					$this->targetObj = CAntObject::factory($this->dbh, $parts['obj_type'], $parts['id']);
			}
		}

		return $this->targetObj;
	}
}
