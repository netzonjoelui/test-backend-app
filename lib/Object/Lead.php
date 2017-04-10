<?php
/**
 * Aereus Object Lead
 *
 * @category  CAntObject
 * @package   Lead
 * @copyright Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for managing calendar events
 */
class CAntObject_Lead extends CAntObject
{
	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $eid 			The lead id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	public function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "lead", $eid, $user);
	}

	/**
	 * This is called just before the 'save' is processed in the base object
	 */
	protected function beforesaved()
	{
		if (($this->getValue("converted_opportunity_id") || $this->getValue("converted_customer_id")) && $this->getValue("f_converted") != 't')
		{
			$this->setValue("f_converted", 't');
			$this->setValue('ts_converted', date(DATE_ISO8601));
		}
	}

	/**
	 * Function is called by the base class once the object has been fully loaded
	 */
	protected function loaded()
	{
	}

	/**
	 * Convert a lead to an organization, a person, and an opportunity
	 *
	 * This function will set multiple variables in the lead, and close it which is kind of like locking it.
	 * It will save the lead, incrementing the revision, and create an activity.
	 *
	 * @param bool $createOpp If true create a sales opportunity from this lead
	 * @param string $oppName Optional manual name to use for the opportunity
	 * @param string $orgId Optional ID of an exisiting organization
	 * @param string $perId Optional ID of existing person to use
	 * @return bool true on success
	 */
	public function convert($createOpp=true, $oppName="", $orgId=null, $perId=null)
	{
		// Create account / organization
		if (!$orgId && $this->getValue('company'))
		{
			$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
			$cust->setValue("name", $this->getValue('company'));
			$cust->setValue("phone_work", $this->getValue('phone'));
			$cust->setValue("phone_home", $this->getValue('phone2'));
			$cust->setValue("phone_cell", $this->getValue('phone3'));
			$cust->setValue("phone_fax", $this->getValue('fax'));
			$cust->setValue("job_title", $this->getValue('job_title'));
			$cust->setValue("website", $this->getValue('website'));
			$cust->setValue("notes", $this->getValue('notes'));
			$cust->setValue("email2", $this->getValue('email'));
			$cust->setValue("email_default", "email2");
			$cust->setValue("owner_id", $this->user->id);
			$cust->setValue("type_id", CUST_TYPE_ACCOUNT);
			$cust->setValue("business_street", $this->getValue('street'));
			$cust->setValue("business_street2", $this->getValue('street2'));
			$cust->setValue("business_city", $this->getValue('city'));
			$cust->setValue("business_state", $this->getValue('state'));
			$cust->setValue("business_zip", $this->getValue('zip'));
			$orgId = $cust->save();
		}

		// Create person
		if (!$perId)
		{
			$cust2 = CAntObject::factory($this->dbh, "customer", null, $this->user);
			$cust2->setValue("first_name", $this->getValue('first_name'));
			$cust2->setValue("last_name", $this->getValue('last_name'));
			$cust2->setValue("phone_work", $this->getValue('phone'));
			$cust2->setValue("phone_home", $this->getValue('phone2'));
			$cust2->setValue("phone_cell", $this->getValue('phone3'));
			$cust2->setValue("phone_fax", $this->getValue('fax'));
			$cust2->setValue("job_title", $this->getValue('job_title'));
			$cust2->setValue("website", $this->getValue('website'));
			$cust2->setValue("notes", $this->getValue('notes'));
			$cust2->setValue("email2", $this->getValue('email'));
			$cust2->setValue("email_default", "email2");
			$cust2->setValue("owner_id", $this->user->id);
			$cust2->setValue("type_id", CUST_TYPE_CONTACT);
			$cust2->setValue("business_street", $this->getValue('street'));
			$cust2->setValue("business_street2", $this->getValue('street2'));
			$cust2->setValue("business_city", $this->getValue('city'));
			$cust2->setValue("business_state", $this->getValue('state'));
			$cust2->setValue("business_zip", $this->getValue('zip'));

			if ($orgId)
				$cust2->setValue("primary_account", $orgId);

			$perId = $cust2->save();
		}

		// Create opportunity
		$oid = null;
		if ($createOpp)
		{
			if (!$oppName)
				$oppName = "From lead " . $this->id;

			$opp = CAntObject::factory($this->dbh, "opportunity", null, $this->user);
			$opp->setValue("name", $oppName);
			$opp->setValue("owner_id", $this->user->id);
			$opp->setValue("customer_id", $perId);
			$opp->setValue("lead_source_id", $this->getValue('source_id'));
			if ($this->getValue("campaign_id"))
				$opp->setValue("campaign_id", $this->getValue('campaign_id'));
			$opp->setValue("notes", $this->getValue('notes'));
			$opp->setValue("lead_id", $this->id);
			$oid = $opp->save();

			$this->setValue('converted_opportunity_id', $oid);
		}

		// Check if default converted status grouping exists
		$convertedStatus = $this->getGroupingEntryByName("status_id", "Closed: Converted");
		if ($convertedStatus['id'])
			$this->setValue("status_id", $convertedStatus['id']);

		$this->setValue("converted_customer_id", $perId);
		$this->save();

		return array(
			"account_id" => $orgId,
			"contact_id" => $perId,
			"opportunity_id" => $oid,
		);
	}
}
