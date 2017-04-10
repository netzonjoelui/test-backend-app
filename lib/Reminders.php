<?php
/**
 * Reminders collection
 */
class Reminders
{
	/**
	 * Handle to database
	 *
	 * @var CDatabase
	 */
	protected $dbh = null;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param int $id Optional id of reminder to load
	 */
	public function __construct($dbh, $id=null)
	{
		$this->dbh = $dbh;
	}

	/**
	 * Get list of reminders that are due
	 *
	 * @return Reminder[] Array of reminders ready to be executed
	 */
	public function getDue()
	{
	}
}
