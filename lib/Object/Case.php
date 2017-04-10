<?php
/**
 * Case object
 *
 * @category	CAntObject
 * @package		Case
 * @copyright	Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */

// ANT includes
require_once("lib/CAntObject.php");

/**
 * Object extensions for managing cases
 */
class CAntObject_Case extends CAntObject
{
	/**
	 * Flag to indicate this was a newly created object
	 *
	 * @type {bool}
	 */
	private $newlyCreated = false;

	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $cid 			The case id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $cid=null, $user=null)
	{
		parent::__construct($dbh, "case", $cid, $user);
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
		if ($this->getValue("owner_id") != $this->user->id)
		{
			if ($this->fieldValueChanged("owner_id"))
			{
				$desc = $this->user->getValue("full_name") . " assigned you a case called " . $this->getName();
				$name = "Case Assigned";
			}
			else
			{
				$name = "Case Updated";
				$desc = $this->user->getValue("full_name") . " updated a case you are assinged to: " . $this->getName();
			}

			// Add notification for user
			$notification = CAntObject::factory($this->dbh, "notification", null, $this->user);
			$notification->setValue("name", $name);
			$notification->setValue("description", $desc);
			$notification->setValue("obj_reference", "case:".$this->id);
			$notification->setValue("f_popup", 'f');
			$notification->setValue("f_seen", 'f');
			$notification->setValue("owner_id", $this->getValue("owner_id"));
			$nid = $notification->save();
		}
	}
}
