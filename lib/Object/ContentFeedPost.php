<?php
/**
 * Content feed post object type.
 *
 * This mostly handles publishing flag based on status
 *
 * @category CAntObject
 * @package ContentFeedPost
 * @copyright Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for reminder
 */
class CAntObject_ContentFeedPost extends CAntObject
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
		parent::__construct($dbh, "content_feed_post", $eid, $user);
	}

	/**
	 * This is called just before the 'save' is processed in the base object
	 */
	protected function beforesaved()
	{
		// Check to see if this was set to the published status
		$publishedGrp = $this->getGroupingEntryByName("status_id", "Published");
		if ($this->getValue("status_id") == $publishedGrp['id'])
			$this->setValue("f_publish", 't');
		else
			$this->setValue("f_publish", 'f');
	}
}
