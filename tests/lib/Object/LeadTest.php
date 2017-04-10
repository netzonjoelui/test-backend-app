<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Lead.php');

class CAntObject_LeadTest extends PHPUnit_Framework_TestCase
{
	var $ant = null;
	var $user = null;
	var $dbh = null;
	var $dbhSys = null;

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, USER_SYSTEM);
	}

	/**
	 * Test converting a lead to an organization, person, and opportunity
	 */
	public function testConvert()
	{
		// Create a new lead
		$lead = CAntObject::factory($this->dbh, "lead", null, $this->user);
		$lead->setValue("first_name", "Test");
		$lead->setValue("last_name", "Convert");
		$lead->setValue("company", "Convert Corp");
		$lead->setValue("notes", "Enter some sales notes here");
		$lid = $lead->save();

		// Convert the lead and allow it to create organization, person, and opportunity automatically
		$lead->convert();
		$custId = $lead->getValue("converted_customer_id");
		$this->assertTrue(strlen($custId)>0);
		$cust = CAntObject::factory($this->dbh, "customer", $custId, $this->user);

		$orgId = $cust->getValue("primary_account");
		$this->assertTrue(strlen($orgId)>0);
		$org = CAntObject::factory($this->dbh, "customer", $orgId, $this->user);

		$oppId = $lead->getValue("converted_opportunity_id");
		$this->assertTrue(strlen($oppId)>0);
		$opp = CAntObject::factory($this->dbh, "opportunity", $oppId, $this->user);

		// Test converted flag
		$this->assertEquals($lead->getValue("f_converted"), 't');

		// Cleanup
		$opp->removeHard();
		$cust->removeHard();
		$org->removeHard();
		$lead->removeHard();
	}
}
