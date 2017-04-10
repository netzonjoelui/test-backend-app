<?php
	//require_once 'PHPUnit/Autoload.php';
	// ANT Includes 
	require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
	require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
	require_once(dirname(__FILE__).'/../../lib/Ant.php');
	require_once(dirname(__FILE__).'/../../lib/AntUser.php');
	require_once(dirname(__FILE__).'/../../lib/CAntObject.php');

	class AntUserTest extends PHPUnit_Framework_TestCase 
	{
		var $user = null;
		var $dbh = null;

		function setUp() 
		{
			$this->ant = new Ant();
			$this->dbh = $this->ant->dbh;
			$this->user = new AntUser($this->dbh, -1, $this->ant); // -1 = administrator
		}
		
		function tearDown() 
		{
			//@unlink('/temp/test.log');	
		}

		function testSave() 
		{
			$this->assertTrue(true);
		}

		function testVerifyDefaultDomains() 
		{
			$dbh = $this->dbh;
			$antsystem = new AntSystem();
			$testdomain = "unittest.com";

			// Create a test domain
			$ret = $antsystem->addEmailDomain($this->ant->accountId, $testdomain);
			$this->assertTrue($ret);

			// Will create an account for this user
			$email = $this->user->name . "@" . $testdomain;
			$this->user->verifyEmailDomainAccounts();
			$result = $dbh->Query("SELECT * from email_accounts where user_id='".$this->user->id."' and address='$email'");
			$this->assertTrue($this->dbh->GetNumberRows($result) > 0);

			// Cleanup test domain
			$ret = $antsystem->deleteEmailDomain($this->ant->accountId, $testdomain);
			$this->assertTrue($ret);
			$result = $dbh->Query("DELETE FROM email_accounts WHERE user_id='".$this->user->id."' AND address='$email'");
		}

		function testGetUserAereusCustomerId() 
		{
			$dbh = $this->dbh;
			$antsystem = new AntSystem();

			$custid = $this->user->getAereusCustomerId();
			$this->assertTrue(is_numeric($custid));
		}

		/**
		 * Test the loading of email accounts
		 */
		public function testGetEmailAccounts() 
		{
			$accounts = $this->user->getEmailAccounts();
			$this->assertTrue(count($accounts) > 0);
		}
	}
?>
