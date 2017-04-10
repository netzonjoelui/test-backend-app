<?php
/**
 * Phone call object
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * functions for files in the AntFs
 *
 * @category CAntObject
 * @package PhoneCall
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for managing a phonecall in netric
 */
class CAntObject_PhoneCall extends CAntObject
{
	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $eid 			The phone call id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "phone_call", $eid, $user);
	}

	/**
	 * This is called just before the 'save' is processed in the base object
	 */
	protected function beforesaved()
	{
	}

	/**
	 * Function used for derrived classes to hook save event
	 *
	 * This is called after CAntObject base saves all properties
	 */
	protected function saved()
	{
		// Set last_contacted in customer
		if ($this->getValue('customer_id'))
		{
			$cust = CAntObject::factory($this->dbh, "customer", $this->getValue('customer_id'), $this->user);

			$old = $cust->getValue("last_contacted");
			$new = ($this->getValue("ts_entered")) ? $this->getValue("ts_entered") : "now";

			if ($old)
			{
				// Only update if the new time is later than previously set
				if (strtotime($old) >= strtotime($new))
					return;
			}

			$cust->setValue("last_contacted", $new);
			$cust->save();
		}
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
