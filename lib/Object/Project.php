<?php
/**
 * Aereus Project Object
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * functions for files in the AntFs
 *
 * @category CAntObject
 * @package Project
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for managing projects in ANT
 */
class CAntObject_Project extends CAntObject
{
	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $eid 			The customer id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "project", $eid, $user);
	}

	/**
	 * Close object references from $fromPid
	 *
	 * @param int $fromPid The project to copy references from
	 */
	public function cloneObjectReferences($fromPid)
	{
		$fromObj = CAntObject::factory($this->dbh, "project", $fromPid, $this->user);
		$startDate = $fromObj->getValue("ts_created");
		$deadline = $fromObj->getValue("date_deadline");
		$keyFromTs = ($deadline) ? strtotime($deadline) : strtotime($startDate);
		$thisFromTs = ($this->getValue("date_deadline")) ? strtotime($this->getValue("date_deadline")) : strtotime($this->getValue("ts_created"));

		// Copy tasks
		$tasks = new CAntObjectList($this->dbh, "task", $this->user);
		$tasks->addCondition("and", "project", "is_equal", $fromPid);
		$tasks->getObjects();
		for ($i = 0; $i < $tasks->getNumObjects(); $i++)
		{
			$task = $tasks->getObject($i);

			$newTask = $task->cloneObject();

			// Move project
			$newTask->setValue("project", $this->id);

			// Move due date
			if ($task->getValue("deadline"))
			{
				$taskTime = strtotime($task->getValue("deadline"));
				$diff = $taskTime - $keyFromTs;

				// Calculate new time
				$newTime = $diff + $thisFromTs;
				$newTask->setValue("deadline", date("m/d/Y", $newTime));
			}
			
			// Save changes
			$tid = $newTask->save();
		}
	}
}
