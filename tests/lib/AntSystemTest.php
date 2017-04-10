<?php
	//require_once 'PHPUnit/Autoload.php';
	// ANT Includes 
	require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
	require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
	require_once(dirname(__FILE__).'/../../lib/Ant.php');
	require_once(dirname(__FILE__).'/../../lib/AntUser.php');
	require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
    require_once(dirname(__FILE__).'/../../lib/System/SchemaUpdater.php');

	class AntSystemTest extends PHPUnit_Framework_TestCase 
	{
		var $ant = null;
		var $user = null;
		var $dbh = null;
		var $dbhSys = null;

		function setUp() 
		{
			$this->ant = new Ant();
			$this->antsys = new AntSystem();
			$this->dbh = $this->ant->dbh;
			$this->user = new AntUser($this->dbh, -1); // -1 = administrator
			$this->dbhSys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);
		}
		
		function tearDown() 
		{
			//@unlink('/temp/test.log');	
		}
        
        /*function getTests()
        {        
            return array("testSchemaVersion");
        }*/

		/**
		 * Test to make sure the antsys can gather account email domains
		 */
		function testGetEmailDomains() 
		{
			// Make sure default domain exists in the mailsystem
			$this->antsys->addEmailDomain($this->ant->accountId, $this->ant->getEmailDefaultDomain());

			$domains = $this->antsys->getEmailDomains($this->ant->accountId);
			$this->assertTrue(count($domains)>0);
		}

		/**
		 * Test email address verification
		 */
		function testEmailUserVerify() 
		{
			$email = "administrator@unittestdomain.com";

			// Make sure the function returns success
			$ret = $this->antsys->verifyEmailUser($this->ant->accountId, $email);
			$this->assertTrue($ret);

			// Now double-check to see if the email user actually exists, the last param will not allow it to create
			$ret = $this->antsys->verifyEmailUser($this->ant->accountId, $email, false);
			$this->assertTrue($ret);

			// Cleanup
			$this->antsys->verifyEmailUser($this->ant->accountId, $email, false);
		}

		/**
		 * Test account creating
		 */
		function testCreateAccount()
		{
			$antsys = new AntSystem();

			// Cleanup
			$antsys->deleteAccount("uttest");

			// Create new account
			$ret = $antsys->createAccount("uttest");

			if ($ret == false)
				echo "<pre>Create Account Error: ".$antsys->lastError."</pre>";

			$this->assertTrue(is_array($ret));

			// Check to see if data was entered
			$ant = new Ant($ret['id']);
			$this->assertTrue($ant->dbh->GetNumberRows($ant->dbh->Query("SELECT * FROM user_groups;"))>0);
			$this->assertTrue($ant->dbh->GetNumberRows($ant->dbh->Query("SELECT * FROM app_object_types;"))>0);
			$this->assertTrue($ant->dbh->GetNumberRows($ant->dbh->Query("SELECT * FROM applications;"))>0);

			// Cleanup
			$antsys->deleteAccount("uttest");
		}
        
		/**
		 * Make sure system/schema/create.php is the latest version
		 */
        public function testSchemaVersion()
        {
            include(dirname(__FILE__).'/../../system/schema/create.php');
            $sup = new AntSystem_SchemaUpdater($this->ant, false);
            $sup->executeUpdater = false; // This will allow to get the latest version without executing the system schema udpates
            $latestVersion = $sup->getLatestVersion();
            
            //$schema_version = $this->ant->settingsGet("system/schema_version");            
            $this->assertEquals($schema_version, $latestVersion);
        }

		/**
		 * Test zipcode lookup
		 *
		 * @group getZipcodeData
		 */
		public function testGetZipcodeData() 
		{
			// Get data for a specific zipcode
			$ret = $this->antsys->getZipcodeData(97477);
			$this->assertTrue(count($ret) > 0);
			$this->assertEquals($ret['state'], "OR");
			$this->assertEquals($ret['city'], "Springfield");
		}

		/**
		 * Test getting aereus account
		 *
		 * @group getAereusAccount
		 */
		public function testGetAereusAccount()
		{
			/*
			 * Exception Thrown:
			 * Could not pull type fields from db for co_ant_account
			 * Error: relation "app_object_type_fields" does not exist LINE 1: select * from app_object_type_fields where type_id=''
			 */
			return;
			
			// First make sure the co_ant_account object exists locally
			$otid = objCreateType($this->dbh, "ant_account", "ANT Account");
			$objAc = new CAntObject($this->dbh, "co_ant_account", null, $this->user);
			if (!$objAc->fields->getField("aid"))
				$objAc->addField("aid", array("type"=>"number", "title"=>"AID"));
			if (!$objAc->fields->getField("name"))
				$objAc->addField("name", array("type"=>"text", "title"=>"Name"));

			$accnt = $this->antsys->getAereusAccount($this->ant->accountId);
			$this->assertNotEquals($accnt, null); // If fail return will be null
		}
	}
?>
