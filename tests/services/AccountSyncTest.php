<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/AntService.php');
require_once(dirname(__FILE__).'/../../lib/AntRoutine.php');
require_once(dirname(__FILE__).'/../../services/AccountSync.php');
require_once(dirname(__FILE__).'/../../lib/aereus.lib.php/antapi.php');

class AccountSyncTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;

	/**
	 * Setup vars and initialize object types
	 */
	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
		$this->api = new AntApi(AntConfig::getInstance()->aereus['server'], 
								   AntConfig::getInstance()->aereus['user'],
								   AntConfig::getInstance()->aereus['password']);

		// Make sure the co_ant_account object exists locally
		$otid = objCreateType($this->dbh, "ant_account", "ANT Account");
		$objAc = new CAntObject($this->dbh, "co_ant_account", null, $this->user);
		if (!$objAc->fields->getField("aid"))
			$objAc->addField("aid", array("type"=>"number", "title"=>"AID"));
		if (!$objAc->fields->getField("num_users"))
			$objAc->addField("num_users", array("type"=>"number", "title"=>"Num Users"));
		if (!$objAc->fields->getField("name"))
			$objAc->addField("name", array("type"=>"text", "title"=>"Name"));
		if (!$objAc->fields->getField("customer"))
			$objAc->addField("customer", array("type"=>"object", "subtype"=>"customer", "title"=>"Customer"));
		if (!$objAc->fields->getField("bill_next_date"))
			$objAc->addField("bill_next_date", array("type"=>"date", "title"=>"Bill Next Date"));
		if (!$objAc->fields->getField("bill_last_date"))
			$objAc->addField("bill_last_date", array("type"=>"date", "title"=>"Bill Last Date"));
		if (!$objAc->fields->getField("edition"))
			$objAc->addField("edition", array("type"=>"text", "subtype"=>"64", "title"=>"Edition"));
		if (!$objAc->fields->getField("edition_discount"))
			$objAc->addField("edition_discount", array("type"=>"text", "subtype"=>"64", "title"=>"Discount"));

		// Make sure a customer exists for the current account
		$apiAcc = $this->ant->getAereusAccount();
		if (!$apiAcc->getValue("customer"))
		{
			$cust = $this->api->getObject("customer");
			$cust->setValue("name", "AccountSyncTest");
			$cid = $cust->save();
			$apiAcc->setValue("customer", $cid);
			$apiAcc->save();
		}
	}
	
	/**
	 * Test billing
	 */
	public function testBillAccount() 
	{
		$dbh = $this->dbh;
		$apiAntAccount = $this->ant->getAereusAccount();

		// Test running billing before next bill date
		$apiAntAccount->setValue("bill_next_date", date("m/d/Y", strtotime("+1 month")));
		$svc = new AccountSync($this->ant);
		$ret = $svc->billAccount($apiAntAccount);
		$this->assertEquals($ret, false);

		// Set next date to before today which should create a new invoice when never billed before
		$apiAntAccount->setValue("bill_next_date", date("m/d/Y", strtotime("-1 month")));
		$apiAntAccount->setValue("bill_last_date", "");
		$svc = new AccountSync($this->ant);
		$inv = $svc->billAccount($apiAntAccount);
		$this->assertTrue(is_numeric($inv));
		// last bill date should match next bill date
		$this->assertEquals($apiAntAccount->getValue("bill_last_date"), date("m/d/Y", strtotime("-1 month"))); 
		// Next date should be this month
		$this->assertEquals($apiAntAccount->getValue("bill_next_date"), date("m/d/Y"));

		// Now test scenario where billing has failed for a few days
		// but make sure the next date is set from the last bill date, not the current successful date
		$nextTs = strtotime("-30 days");
		$apiAntAccount->setValue("bill_next_date", date("m/d/Y", $nextTs));
		$lastTs = strtotime("-45 days");
		$apiAntAccount->setValue("bill_last_date", date("m/d/Y", $lastTs));
		$svc = new AccountSync($this->ant);
		$inv = $svc->billAccount($apiAntAccount);
		$this->assertTrue(is_numeric($inv));
		// last bill date should match next bill date
		$this->assertEquals($apiAntAccount->getValue("bill_last_date"), date("m/d/Y", $nextTs)); 
		// Next date should be this month
		$this->assertEquals($apiAntAccount->getValue("bill_next_date"), date("m/d/Y", strtotime("+1 month", $lastTs)));

		// Clean up
		$invObj = $this->api->getObject("invoice", $inv);
		$invObj->remove();
		$invObj->remove();
	}

	/**
	 * Test discount
	 */
	public function testApplyBillingDiscount() 
	{
		$dbh = $this->dbh;
		$apiAntAccount = $this->ant->getAereusAccount();
		$apiAntAccount->setValue("bill_next_date", date("m/d/Y", strtotime("-1 month")));
		$apiAntAccount->setValue("bill_last_date", date("m/d/Y", strtotime("-2 month")));
		$numUsers = 10; // Number of users to test
		$proPrice = $this->ant->getEditionPrice(EDITION_PROFESSIONAL);
		$svc = new AccountSync($this->ant);

		// Create mock invoice
		$inv = new AntApi_Invoice(AntConfig::getInstance()->aereus['server'], 
								  AntConfig::getInstance()->aereus['user'],
								  AntConfig::getInstance()->aereus['password']);
		$inv->setValue("name", "Netric Subscription");

		// nonprofit - first 5 users are free
		$inv->addItem("User Account", 10, $proPrice);
		$svc->applyBillingDiscount($inv, "nonprofit", $proPrice, $numUsers);
		$this->assertEquals((($numUsers-5) * $proPrice), $inv->getSubTotal());

		// Reset
		$inv->clear();

		// entforpro - enterprise edition for the professional price
		$inv->addItem("User Account", 10, $this->ant->getEditionPrice(EDITION_ENTERPRISE));
		$svc->applyBillingDiscount($inv, "entforpro", $this->ant->getEditionPrice(EDITION_ENTERPRISE), $numUsers);
		$this->assertEquals(($numUsers * $proPrice), $inv->getSubTotal());

		// Reset
		$inv->clear();

		// 10per - 10% discount
		$inv->addItem("User Account", 10, $proPrice);
		$svc->applyBillingDiscount($inv, "10per", $proPrice, $numUsers);
		$this->assertEquals(($numUsers * $proPrice - (($numUsers * $proPrice) * .10)), $inv->getSubTotal());

		// Reset
		$inv->clear();

		// free - complimentary account
		$inv->addItem("User Account", 10, $proPrice);
		$svc->applyBillingDiscount($inv, "free", $proPrice, $numUsers);
		$this->assertEquals(0, $inv->getSubTotal());
	}
}
