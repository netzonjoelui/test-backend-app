<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/SalesOrder.php');
require_once(dirname(__FILE__).'/../../../lib/PaymentGateway.php');
require_once(dirname(__FILE__).'/../../../lib/PaymentGatewayManager.php');
require_once(dirname(__FILE__).'/../../../lib/aereus.lib.php/antapi.php');

class CAntObject_SalesOrderTest extends PHPUnit_Framework_TestCase
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
	
	function tearDown() 
	{
	}
    
	/*
    function getTests()
    {        
        return array("testPayWithCard");        
    }    
	 */

	/**
	 * Make sure that totals are working correctly for invoice objects
	 */
	function testTotal()
	{
		$inv = new CAntObject_SalesOrder($this->dbh, null, $this->user);
		$inv->addItem("Test item 1", 10.50, 2);
		$inv->addItem("Test item 2", 5, 5);

		$this->assertEquals($inv->getSubtotal(), 46);
	}

	/**
	 * Test saving details for a new ivnoice
	 */
	function testSaveDetails()
	{
		$inv = new CAntObject_SalesOrder($this->dbh, null, $this->user);
		$inv->addItem("Test item 1", 10.50, 2);
		$inv->addItem("Test item 2", 5, 5);
		$invid = $inv->save();

		unset($inv);

		$inv = new CAntObject_SalesOrder($this->dbh, $invid, $this->user);
		$this->assertEquals($inv->getNumItems(), 2);
		$this->assertEquals($inv->getSubtotal(), 46);

		$inv->removeHard();
	}

	/**
	 * Test paying an invoice with a credit card
	 */
	function testPayWithCard()
	{	
		// Get gateway
		$gw = PaymentGatewayManager::getGateway($this->dbh, PMTGW_TEST); // Force test type

		$inv = new CAntObject_SalesOrder($this->dbh, null, $this->user);
		$invid = $inv->save();

		// Set fake credit card info
		$cardData = array(
			"number" => "1111111111111111",
			"exp_month" => "11",
			"exp_year" => "2020",
		);

		// Set customer data
		$custData = array(
			"first_name" => "Some",
			"last_name" => "User",
			"street" => "123 Private Street",
			"city" => "Springfield",
			"state" => "Oregon",
			"zip" => "97477"
		);
		
		$ret = $inv->payWithCard($gw, $cardData, $custData);

		$this->assertTrue($ret);
	}
}
