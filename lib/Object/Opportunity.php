<?php
/**
 * Opportunity
 *
 * This mostly handles closing if status is set to a closed
 *
 * @category CAntObject
 * @package ContentFeedPost
 * @copyright Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for reminder
 */
class CAntObject_Opportunity extends CAntObject
{
	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh An active handle to a database connection
	 * @param int $eid The event id we are editing - this is optional
	 * @param AntUser $user	Optional current user
	 */
	public function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "opportunity", $eid, $user);
	}

	/**
	 * This is called just before the 'save' is processed in the base object
	 */
	protected function beforesaved()
	{
		// Check to see if this was set to the published status
		$clWonGrp = $this->getGroupingEntryByName("status_id", "Closed Won");
		$clLostGrp = $this->getGroupingEntryByName("status_id", "Closed Lost");

		if ($this->getValue("stage_id") == $clWonGrp['id'] || $this->getValue("stage_id") == $clLostGrp['id'])
			$this->setValue("f_closed", 't');

		if ($this->getValue("f_won") == $clWonGrp['id'])
			$this->setValue("f_won", 't');
	}
}
