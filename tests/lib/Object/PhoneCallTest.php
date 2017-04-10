<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');

class AntObject_PhoneCallTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;
	var $ant = null;

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
	}

	/**
	 * Test deafult user-level groupings for category
	 */
	public function testCustomerUpdateLastContacted()
	{
		$dbh = $this->dbh;
		$cust = CAntObject::factory($dbh, "customer", null, $this->user);            
		$cust->setValue("name", "testCustomerUpdateLastContacted");
		$cid = $cust->save();

		$call = CAntObject::factory($dbh, "phone_call", null, $this->user);            
		$call->setValue("name", "Test Phone Call");
		$call->setValue("customer_id", $cid);
		$call->save();

		// Reload customer and test
		$cust = null;
		$cust = CAntObject::factory($dbh, "customer", $cid, $this->user);            
		$this->assertTrue(strlen($cust->getValue("last_contacted")) > 0);

		// Cleanup
		$cust->removeHard();
		$call->removeHard();
	}
}
