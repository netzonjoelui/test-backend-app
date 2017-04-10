<?php
/**
 * Member object used to link different kinds of objects as members of another object
 *
 * @category CAntObject
 * @package Notification 
 * @copyright Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for member
 */
class CAntObject_Member extends CAntObject
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
	 * @param int $eid The member id we are editing - this is optional
	 * @param AntUser $user	Optional current user
	 */
	public function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "member", $eid, $user);
	}

	/**
	 * This is called just before the 'save' is processed in the base object
	 */
	protected function beforesaved()
	{
		// Check to see if member is a plain email address and add to customers
		if (!$this->getValue('obj_member'))
		{
			$name = $this->getValue("name");

			if (filter_var($name, FILTER_VALIDATE_EMAIL))
			{
				// See if we already have a customer that matches this email
				$find = CAntObject_Customer::findCustomerByEmail($this->dbh, $name, $this->user);
				if ($find != false)
				{
					$this->setValue("obj_member", "customer:" . $find->id);
				}
				else
				{
					// Add new customer
					$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
					$cust->setValue("name", $name);
					$cust->setValue("email", $name);
					if ($this->user && !$this->user->isSystemUser())
						$cust->setValue("f_private", 't');
					$cid = $cust->save();

					if ($cid)
						$this->setValue("obj_member", "customer:" . $cid);
				}
			}
		}
	}
}
