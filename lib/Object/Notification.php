<?php
/**
 * Notification object
 *
 * @category CAntObject
 * @package Notification 
 * @copyright Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for notifications
 */
class CAntObject_Notification extends CAntObject
{
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
		parent::__construct($dbh, "notification", $eid, $user);
	}

	/**
	 * This is called just before the 'save' is processed in the base object
	 */
	protected function beforesaved()
	{
		/*
		if (!$this->id && $this->getValue("f_seen") == 'f')
		{
			// Look for unread notifications with the same user, obj_reference and name to merge
			$list = new CAntObjectList($this->dbh, "notification", $this->user);
			$list->addCondition("and", "name", "is_equal", $this->getValue("name"));
			$list->addCondition("and", "owner_id", "is_equal", $this->getValue("owner_id"));
			$list->addCondition("and", "obj_reference", "is_equal", $this->getValue("obj_reference"));
			$list->addCondition("and", "creator_id", "is_equal", $this->getValue("creator_id"));
			$list->addCondition("and", "f_seen", "is_equal", 'f');
			$list->getObjects();
			$num = $list->getNumObjects();
			for ($j = 0; $j < $num; $j++)
			{
				$notif = $list->getObject($j);
				$notif->remove();
			}
		}
		*/
	}
}
