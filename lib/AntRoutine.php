<?php
/**
 * Similar to AntService class except it is desinged to run a routine on a regular basis but exit between runs.
 *
 * Use this whenever you want to run a periodic routine without having it continually run but
 * still want the routine to make sure only one instance of the routine is running at the same time.
 *
 * Basically this is exact same thing as a service but when implemented it will not be in a 'while' loop
 * and is often put into a cron job for periodic processing.
 *
 * @category  Ant
 * @package   Routine
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntService.php");

class AntRoutine extends AntService
{
	/**
	 * Class constructor
	 */
	public function __construct($ant=null)
	{
		if ($ant)
			$this->ant = $ant;

		// By default only one instance of this should be able to run at once
		$this->singleton = true;
	}
}
