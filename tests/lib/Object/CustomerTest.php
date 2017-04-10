<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Customer.php');

class CAntObject_CustomerTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;

    function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
    }
    
	/**
	 * Test facebook
	 */
	public function testFacebookImage()
	{
		// This test is skipped since we do not have a test domain for ANS right now
		return;
		
		$cust = new CAntObject_Customer($this->dbh, null, $this->user);
		$cust->setValue("name", "Aereus FB Test");
		//$cust->setValue("facebook", "https://www.facebook.com/aereus");
		$cust->setValue("facebook", "https://www.facebook.com/pages/Advanced-Energy-Systems/143871748968797?fref=ts");
		$cid = $cust->save();

		$this->assertTrue(is_numeric($cust->getValue("image_id")));

		// Cleanup
		$cust->removeHard();
	}

	/**
	 * Test find by email
	 */
	public function testFindCustomerByEmail()
	{

		// This test is skipped since we do not have a test domain for ANS right now
		return;
		
		$email1 = "testFindCustomerByEmail@testFindCustomerByEmail.org";
		$email2 = "testFindCustomerByEmail2@testFindCustomerByEmail2.org";

		// First cleanup
		$list = new CAntObjectList($this->dbh, "customer", $this->user);
		$list->addCondition("and", "email", "is_equal", $email1);
		$list->addCondition("or", "email", "is_equal", $email1 . "nonexist"); // just in case - see test below
		$list->getObjects();
		for ($i = 0; $i < $list->getNumObjects(); $i++)
		{
			$cust = $list->getObject($i);
			$cust->removeHard();
		}

		// Create test customer
		$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "testFindCustomerByEmail test");
		$cust->setValue("email", $email1);
		$cust->setValue("email2", $email2);
		$cid = $cust->save();

		// Test email1
		$find = CAntObject_Customer::findCustomerByEmail($this->dbh, $email1, $this->user);
		$this->assertEquals($find->id, $cid);

		// Test email2
		$find = CAntObject_Customer::findCustomerByEmail($this->dbh, $email2, $this->user);
		$this->assertEquals($find->id, $cid);

		// Test non-existing email - should never exist anyway
		$find = CAntObject_Customer::findCustomerByEmail($this->dbh, $email1 . "nonexist", $this->user);
		$this->assertFalse($find);

		// Create a second cust, which is private, and make sure it gets picked first
		$cust2 = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$cust2->setValue("name", "testFindCustomerByEmail test");
		$cust2->setValue("email", $email1);
		$cust2->setValue("email2", $email2);
		$cust2->setValue("f_private", 't');
		$cid2 = $cust2->save();

		// Pulling wihtout private should get first entry, however, private should prefer second
		$find = CAntObject_Customer::findCustomerByEmail($this->dbh, $email1, $this->user);
		$this->assertEquals($find->id, $cid2);

		// Cleanup
		$cust->removeHard();
		$cust2->removeHard();
	}

	/**
	 * Test primary account and contact links
	 *
	 * If a primary_account is set for a contact, and the account has no primary contact
	 * then the primary_account.primary_contact should be updated to the first contact created
	 */
	public function testPrimaryAccountContact()
	{
		// This test is skipped since we do not have a test domain for ANS right now
		return;
		// Create account
		$acct = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$acct->setValue("name", "Test Primary Account");
		$acct->setValue("type_id", CUST_TYPE_ACCOUNT);
		$aid = $acct->save();
		unset($acct);

		// Now create contact and set primary account
		$con = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$con->setValue("first_name", "Primary");
		$con->setValue("last_name", "Contact");
		$con->setValue("type_id", CUST_TYPE_CONTACT);
		$con->setValue("primary_account", $aid);
		$cid = $con->save();

		// Open account and set if primary contact was set
		$acct = CAntObject::factory($this->dbh, "customer", $aid, $this->user);
		$this->assertEquals($cid, $acct->getValue("primary_contact"));

		// Cleanup
		$con->removeHard();
		$acct->removeHard();
	}
}
