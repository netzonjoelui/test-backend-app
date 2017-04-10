<?php
/**
 * Activity Object
 *
 * This object stores all activities in netric
 *
 * @category  CAntObject
 * @package   CAntObject_Activity
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for managing activities in netric
 */
class CAntObject_Activity extends CAntObject
{
	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $eid 			The event id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "activity", $eid, $user);
	}

	/**
	 * This is called just before the 'save' is processed in the base object
	 */
	protected function beforesaved()
	{
		/**
		 * The below functionality has been replaces with \Netic\Entity\Activity
		 * - joe <sky.stebnicki@aereus.com>
		 *
		// Set association for the object
		if ($this->getValue('obj_reference'))
		{
			$parts = explode(":", $this->getValue('obj_reference'));
			if (count($parts) > 1)
			{
				$this->setMValue("associations", $this->getValue('obj_reference'));
			}
		}
		*/
	}

	/**
	 * Function used for derrived classes to hook save event
	 *
	 * This is called after CAntObject base saves all properties
	 */
	protected function saved()
	{
	}

	/**
	 * Function used for derrived classes to hook onload event
	 *
	 * This is called after CAntObject base loads all properties
	 */
	protected function loaded()
	{
	}
}
