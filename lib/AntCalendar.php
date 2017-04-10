<?php
/**
 * Calendar object for Ant
 *
 * This function will be used for most calendar speficif functions.
 *
 * @category  Ant
 * @package   AntCalendar
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for managing calendar events
 */
class AntCalendar
{
	/**
	 * Handle to account database
	 *
	 * @var CDatabase $dbh
	 */
	private $dbh = null;

	/**
	 * Unique of this calendar
	 *
	 * @var int $id
	 */
	public $id = null;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh (required) an active have to the account database
	 * @param int $id (optional) id of calendar to load
	 */
	function __construct($dbh, $id=null)
	{
		$this->dbh = $dbh;
		$this->id = $id;
	}


	/**
	 * Get default calendar for a user
	 *
	 * @param int $userId The id of the user to get
	 * @return int The id of the default calendar for this user
	 */
	public function getUserCalendar($userId)
	{
		if (!is_numeric($userId))
			return false;

		$dbh = $this->dbh;
		$query = "select id from calendars where user_id='$userId' and def_cal='t'";
		$result = $dbh->Query($query);
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result);
			$id = $row['id'];
			$dbh->FreeResults($result);
		}
		else
		{
			$dbh->Query("insert into calendars(name, user_id, def_cal, date_created) values('My Calendar', '$userId', 't', 'now');
						 select currval('calendars_id_seq') as id;");
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result);
				$id = $row['id'];
				$dbh->FreeResults($result);
			}
		}
		
		return $id;
	}

	/**
	 * Query collection of events for this calendar
	 *
	 * @param bool $incRecurInst Set to true to pull instances of recurring events in list. Otherwise exclude
	 * @return int number of events
	 */
	public function getEvents($incRecurInst=false)
	{
		// Get non-recurring events
		// Get original recurring events

		// TODO: I think we need to index recurring properties to make pulling objects easier with recurrence
	}

	/**
	 * Get collection of events for this calendar
	 *
	 * @return array (id, name, revision) of the event
	 */
	public function getEvent($idx)
	{
	}
}
