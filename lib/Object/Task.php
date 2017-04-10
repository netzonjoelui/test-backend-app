<?php
/**
 * Task object
 *
 * @category	CAntObject
 * @package		Task
 * @copyright	Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */

// ANT includes
require_once("lib/CAntObject.php");

/**
 * Object extensions for managing tasks
 */
class CAntObject_Task extends CAntObject
{
	/**
	 * Flag to indicate this was a newly created task
	 *
	 * @type {bool}
	 */
	private $newlyCreated = false;

	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $tid 			The task id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $tid=null, $user=null)
	{
		parent::__construct($dbh, "task", $tid, $user);
	}
	
	/**
	 * Function used for derrived classes to hook load event
	 */
	protected function loaded()
	{
	}

	/**
	 * This is called just before the 'save' is processed in the base object
	 */
	protected function beforesaved()
	{
		if (!$this->id)
			$this->newlyCreated = true;
	}

	/**
	 * Function used for derrived classes to hook save event
	 */
	protected function saved()
	{
		if ($this->getValue("user_id") != $this->user->id)
		{
			if ($this->fieldValueChanged("user_id"))
			{
				$desc = $this->user->getValue("full_name") . " assigned you a task called " . $this->getName();
				$name = "Task Assigned";
			}
			else
			{
				$name = "Task Updated";
				$desc = $this->user->getValue("full_name") . " updated a task you are assinged to: " . $this->getName();
			}

			// Add notification for user
			/*
			$notification = CAntObject::factory($this->dbh, "notification", null, $this->user);
			$notification->setValue("name", $name);
			$notification->setValue("description", $desc);
			$notification->setValue("obj_reference", "task:".$this->id);
			$notification->setValue("f_popup", 'f');
			$notification->setValue("f_seen", 'f');
			$notification->setValue("owner_id", $this->getValue("user_id"));
			$nid = $notification->save();
			*/
		}
	}

	/**
	 * Return default list of mailboxes which is called by verifyDefaultGroupings in base class.
	 *
	 * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
	 * @return array
	 */
	public function getVerifyDefaultGroupingsData($fieldName)
	{
		$checkfor = array();

		if ($fieldName == "category")
			$checkfor = array("Work" => "1", "Personal" => "2", "Other" => "3");

		return $checkfor;
	}

	/**
	 * Override the default because files can have different icons depending on whether or not this is completed
	 *
	 * @return string The base name of the icon for this object if it exists
	 */
	public function getIconName()
	{
		$done = $this->getValue("done");

		if ($done == 't' || $done === true)
			return "task_on";
		else
			return "task";
	}
}
