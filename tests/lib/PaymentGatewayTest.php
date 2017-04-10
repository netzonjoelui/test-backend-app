<?php
// SimpleTest Framework
//require_once 'PHPUnit/Autoload.php';
//require_once(dirname(__FILE__).'/../simpletest/autorun.php');

// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/PaymentGateway.php');
require_once(dirname(__FILE__).'/../../lib/PaymentGatewayManager.php');
require_once(dirname(__FILE__).'/../../lib/aereus.lib.php/antapi.php');

//class PaymentGatewayTest extends UnitTestCase 
class PaymentGatewayTest extends PHPUnit_Framework_TestCase 
{
	var $dbh = null;
	var $user = null;
	var $ant = null;
	
	var $sl = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
		
		$this->sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
		
		$this->markTestSkipped('Cannot connect to the payment gateway.');
	}
	
	function tearDown() 
	{
	}

	/*
 	function getTests()
	{        
		return array("testGwmLinkPoint");
	}
	 */


	/**************************************************************************
	 * Function: 	testWorkflowLaunch
	 *
	 * Purpose:		Make sure workflow launches when conditions are met
	 **************************************************************************/
	function testPaymentGateway()
	{	
		// Get gateway
		$gw = PaymentGatewayManager::getGateway($this->dbh, PMTGW_TEST); // Force test type

		// Set customer information
		$gw->firstName = "Test";
		$gw->lastName = "User";
		$gw->street = "123 Private Stret";
		$gw->city = "Springfield";
		$gw->state = "Oregon";
		$gw->zip = "97477";

		// Set credit card information
		$gw->cardNumber = "1111111111111111";
		$gw->cardExpiresMonth = 9; // September of 2013
		$gw->cardExpiresYear = 2013; // September of 2013

		// Validate card
		$this->assertTrue($gw->validate("1.00"));

		// charge card
		$this->assertTrue($gw->charge("1.00", "A test transaction"));

		// Refund transaction
		$this->assertTrue($gw->credit("1.00", $gw->respTransId, "Refunding last transaction"));
	}

	/**************************************************************************
	* Function:    testAuthDotNet
	*
	* Purpose:             Test authorize.net with developer test account
	**************************************************************************/
	function testAuthDotNet()
	{
		$gw = new PaymentGateway_AuthDotNet("5Ssf87uWU", "9Tn9vLE9v7Lh75R7");
		$gw->setTestMode(true);

		// Set customer information
		$gw->firstName = "Some";
		$gw->lastName = "User";
		$gw->street = "123 Private Stret";
		$gw->city = "Springfield";
		$gw->state = "Oregon";
		$gw->zip = "97477";

		// Set credit card information
		$gw->cardNumber = "4222222222222"; // Test visa number
		$gw->cardExpiresMonth = 9; // September of 2013
		$gw->cardExpiresYear = 2014; // September of 2013

		$amount = rand(1, 100);

		// Validate card
		$this->assertTrue($gw->validate(number_format($amount, 2)));

		// charge card
		$resp = $gw->charge(number_format($amount, 2), "A test transaction");
		$this->assertTrue($resp);
		$this->assertNotEquals($gw->respTransId, 0);

		// Refund transaction
		$resp = $gw->credit(number_format($amount, 2), $gw->respTransId, "Refunding last transaction");
		//$this->assertTrue($resp);
		// TODO: This if failing with "This transaction cannot be processed." and no additional information
		// needs to be further researched.
	}
				
	
	/**************************************************************************
	 * Function: 	testLinkPoint
	 *
	 * Purpose:		Test linkpoint/global gateway
	 **************************************************************************/
	function testLinkPoint()
	{
		$testPem = file_get_contents(AntConfig::getInstance()->application_path . "/tests/data/linkpoint.pem");
		$storeNumber = "1909811714";
		$gw = new PaymentGateway_LinkPoint($storeNumber, $testPem);
		$gw->setTestMode(true);

		// First make sure the temporary pem file was created
		$this->assertTrue(file_exists(AntConfig::getInstance()->data_path. "/tmp/$storeNumber-lp.pem"));


		// Set customer information
		$gw->firstName = "Some";
		$gw->lastName = "User";
		$gw->street = "123 Private Stret";
		$gw->city = "Springfield";
		$gw->state = "Oregon";
		$gw->zip = "97477";

		// Set credit card information
		$gw->cardNumber = "4222222222222"; // Test visa number
		$gw->cardExpiresMonth = "09"; // September of 2014
		$gw->cardExpiresYear = "2014"; // September of 2014

		$amount = rand(1, 100);

		// Validate card
		$this->assertTrue($gw->validate(number_format($amount, 2)));

		// charge card
		$this->assertTrue($gw->charge(number_format($amount, 2), "A test transaction"));
		$this->assertNotNull($gw->respTransId);

		// Refund transaction
		$this->assertTrue($gw->credit(number_format($amount, 2), $gw->respTransId, "Refunding last transaction"));
	}

	/**
	 * Test payment gateway manager: linkpoint
	 *
	 * @group testGwmLinkPoint
	 */
	public function testGwmLinkPoint()
	{
		$ant = $this->sl->getAnt();
		
		$existStoreNumber = $ant->settingsGet("/general/paymentgateway/linkpoint/store", $this->dbh);
		$existPem = $ant->settingsGet("/general/paymentgateway/linkpoint/pem", $this->dbh);

		// Set test params
		$testPem = file_get_contents(APPLICATION_PATH . "/tests/data/linkpoint.pem");
		$storeNumber = "1909811714";

		$ant->settingsSet("/general/paymentgateway/linkpoint/store", encrypt($storeNumber), $this->dbh);
		$ant->settingsSet("/general/paymentgateway/linkpoint/pem", encrypt($testPem), $this->dbh);

		//$gw = new PaymentGateway_LinkPoint($storeNumber, $testPem);
		$gw = PaymentGatewayManager::getGateway($this->dbh, PMTGW_LINKPOINT);
		$gw->setTestMode(true);

		// First make sure the temporary pem file was created
		$this->assertTrue(file_exists(AntConfig::getInstance()->data_path . "/tmp/$storeNumber-lp.pem"));

		// Set customer information
		$gw->firstName = "GIG'A";
		$gw->lastName = "CAUSE LLC";
		$gw->street = "P.O. Box 40515";
		$gw->city = "Eugene";
		$gw->state = "OR";
		$gw->zip = "97404";

		// Set credit card information
		$gw->cardNumber = "4635521001089630"; // Test visa number
		$gw->cardExpiresMonth = "11"; // September of 2013
		$gw->cardExpiresYear = "2015"; // September of 2013

		$amount = rand(1, 10);

		// Validate card
		$this->assertTrue($gw->validate(number_format($amount, 2)));

		// charge card
		$ret = $gw->charge(number_format($amount, 2), "A test transaction");
		if (!$ret)
			echo "Error: " . $gw->respReason;
		$this->assertTrue($ret);

		$this->assertNotNull($gw->respTransId);

		// Refund transaction
		$this->assertTrue($gw->credit(number_format($amount, 2), $gw->respTransId, "Refunding last transaction"));

		// Restore original values
		Ant::settingsSet("/general/paymentgateway/linkpoint/store", $existStoreNumber, $this->dbh);
		Ant::settingsSet("/general/paymentgateway/linkpoint/pem", $existPem, $this->dbh);
	}
}
