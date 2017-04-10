<?php
// Test Framework
//require_once 'PHPUnit/Autoload.php';
//require_once(dirname(__FILE__).'/../simpletest/autorun.php');

// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');

class AntTest extends PHPUnit_Framework_TestCase 
{
	var $ant = null;
	var $user = null;
	var $dbh = null;
	var $dbhSys = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, -1); // -1 = administrator
		$this->dbhSys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);
	}
	
	function tearDown() 
	{
		//@unlink('/temp/test.log');	
	}

	/**
	 * Make sure that a customer number is created for each account
	 */
	function testGetCustomerNumber() 
	{
		$custId = null;
		
		$result = $this->dbhSys->Query("select customer_number from accounts where id='".$this->ant->accountId."'");
		if ($this->dbhSys->GetNumberRows($result))
			$custId = $this->dbhSys->GetValue($result, 0, "customer_number");

		if (!$custId)
		{
			$custId = 101010101;
			$this->dbhSys->Query("update accounts set customer_number='$custId' where id='".$this->ant->accountId."'");
		}

		// Test function
		$this->assertEquals($this->ant->getAereusCustomerId(), $custId);
	}

	/**
	 * Test getting the default domain
	 */
	function testDefaultDomain()
	{
		// Make sure we can get a domain
		$oldDefDomain = $this->ant->getEmailDefaultDomain();
		$this->assertTrue(strlen($oldDefDomain)>0);
		
		// clear the default domain and make sure it can recreate
		$this->ant->settingsSet("email/defaultdomain", "");
		$newDefDomain = $this->ant->getEmailDefaultDomain();
		$this->assertTrue(strlen($newDefDomain)>0);

		// Reset domain back if different
		if ($oldDefDomain != $newDefDomain)
			$this->ant->settingsSet("email/defaultdomain", $oldDefDomain);
	}

	/**
	 * Test create user
	 */
	function testCreateUser()
	{
		// clear the default domain and make sure it can recreate
		$rand = rand(1, 99);
		$testName = "unittest$rand";
		$user = $this->ant->createUser($testName, $testName);
		
		if ($user->getId()) {
			$this->assertTrue($user->id > 0);
			
			// Make sure we can authenticate
			$this->assertTrue($user->authenticate($testName, $testName) > 0);
			
			// Now try to create the same user and expect error
			$baduser = $this->ant->createUser($testName, $testName);
			$this->assertFalse($baduser);
			
			// Now try to create a user without a password (should return fail)
			$baduser = $this->ant->createUser($testName, "");
			$this->assertFalse($baduser);
			
			// Cleanup
			$user->removeHard();
		}
		else {
			echo "<pre>Create Account Error: ".$this->ant->lastError."</pre>";
			$this->assertTrue($ret);
			return;
		}
		
	}

	/**
	 * Test getNumUsers
	 */
	public function testGetNumUsers()
	{
		$this->assertTrue($this->ant->getNumUsers() > 0);
	}
}
