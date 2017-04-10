<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Member.php');

class CAntObject_MemberTest extends PHPUnit_Framework_TestCase
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
	 * When a member is saved with an email address, a customer should be created
	 */
	public function testDynamicCustomerCreation()
	{
		$email = "testDynamicCustomerCreation@testDynamicCustomerCreation.org";

		// First cleanup
		$list = new CAntObjectList($this->dbh, "customer", $this->user);
		$list->addCondition("and", "email", "is_equal", $email);
		$list->getObjects();
		for ($i = 0; $i < $list->getNumObjects(); $i++)
		{
			$cust = $list->getObject($i);
			$cust->removeHard();
		}

		// Create member with email address and verify that customer is created
		$member = CAntObject::factory($this->dbh, "member", null, $this->user);
		$member->setValue("name", $email);
		$member->save();
		$obj = $member->getValue("obj_member");
		$this->assertFalse(empty($obj));

		// Load the customer
		$cinfo = CAntObject::decodeObjRef($member->getValue("obj_member"));
		$cust = CAntObject::factory($this->dbh, "customer", $cinfo['id'], $this->user);

		// Now create a second member with the same email address and make sure the previous customer is used
		$member2 = CAntObject::factory($this->dbh, "member", null, $this->user);
		$member2->setValue("name", $email);
		$member2->save();
		$cinfo = CAntObject::decodeObjRef($member2->getValue("obj_member"));
		$cust2 = CAntObject::factory($this->dbh, "customer", $cinfo['id'], $this->user);
		$this->assertEquals($cust->id, $cust2->id);

		// Create a third member with just a name and no email address and no customer should be created
		$member3 = CAntObject::factory($this->dbh, "member", null, $this->user);
		$member3->setValue("name", "testDynamicCustomerCreation");
		$member3->save();
		$obj = $member3->getValue("obj_member");
		$this->assertTrue(empty($obj));
		
		// Cleanup
		$cust->removeHard();
		$member->removeHard();
		$member2->removeHard();
		$member3->removeHard();
	}
}
