<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/AntObjectSync.php');
require_once(dirname(__FILE__).'/../../lib/Object/Folder.php');
require_once(dirname(__FILE__).'/../../lib/Object/File.php');

class AntObjectSyncTest extends PHPUnit_Framework_TestCase 
{
	var $obj = null;
	var $dbh = null;
	var $antfs = null;

	/**
	 * Setup each unit test
	 */
	public function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, -1); // -1 = administrator
	}
	
	/**
	 * Test get listening
	 *
	 * @group listen
	 */
	public function testGetListeningPartners()
	{
		$pid = "AntObjectSyncTest::testGetListeningPartners";

		$partn = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$partn->addCollection("customer");
		$partn->addCollection("customer", "groups");
		$partn->save();

		$sync = new AntObjectSync($this->dbh, "customer", $this->user);

		// Now query devices and make sure it is listening to customers
		$partners = $sync->getListeningPartners();
		$this->assertTrue(count($partners) > 0);

		// Now register for field
		$partners = $sync->getListeningPartners("groups");
		$this->assertTrue(count($partners) > 0);

		// Cleanup
		$partn = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$partn->remove();
	}

	/**
	 * Test updateObjectStat
	 *
	 * @group testUpdateObjectStat
	 */
	public function testUpdateObjectStat()
	{
		$pid = "AntObjectSyncTest::testUpdateObjectStat";

		$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "testUpdateObjectStat");
		$custid = $cust->save(false);

		$sync = new AntObjectSync($this->dbh, "customer", $this->user);
		$partner = $sync->getPartner($pid); // create partnership
		$coll = $partner->addCollection("customer");

		// Save new collection
		$ret = $sync->updateObjectStat($cust->id, 'c');
		$this->assertTrue(in_array($coll->id, $ret));

		// Cleanup
		$coll->remove();
		$partner->remove();
		$cust->removeHard();
	}

	/**
	 * Test updateGroupingStat
	 *
	 * @group testUpdateGroupingStat
	 */
	public function testUpdateGroupingStat()
	{
		$pid = "AntObjectSyncTest::testUpdateGroupingStat";

		// Add grouping
		$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$grpd = $cust->addGroupingEntry("groups", "testUpdateGroupingStat", "e3e3e3");

		$sync = new AntObjectSync($this->dbh, "customer", $this->user);
		$partner = $sync->getPartner($pid); // create partnership
		$coll = $partner->addCollection("customer", "groups");

		// Record grouping change
		$ret = $sync->updateGroupingStat("groups", $grpd['id'], 'c');
		$this->assertTrue(in_array($coll->id, $ret));

		// Cleanup
		$coll->remove();
		$partner->remove();
		$cust->removeHard();
	}
}
