<?php
/**
 * Regression tests creating many objects
 */
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Help.php');

class Regression_ObjectTest extends PHPUnit_Framework_TestCase 
{
	var $dbh = null;
	var $user = null;

	/**
	 * Number of objects to create
	 *
	 * @var int
	 */
	protected $createNum = 100;

	/**
	 * List of created object ids
	 *
	 * @var array
	 */
	protected $ids = array();

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
	 * Let's work on individual updates to find the problem
	 */
	public function SINGLE_testCreateTime()
	{
		$dbh = $this->dbh;

		// Greate grouping for testing value types
		$cust = CAntObject::factory($dbh, "customer", null, $this->user);
		$grpd = $cust->addGroupingEntry("groups", "Regression Group", "e3e3e3");
		$statd = $cust->addGroupingEntry("status_id", "Regression Status", "e3e3e3");

		$start = microtime(true);
		$cust = CAntObject::factory($dbh, "customer", null, $this->user);
		$cust->debug = true;
		$cust->setValue("name", "Regression Test " . $i);
		$cust->setValue("status_id", $statd['id']);
		$cust->setMValue("groups", $grpd['id']);
		$cid = $cust->save();
		$timeToCreate = microtime(true) - $start;

		echo "\nTotal: $timeToCreate\n";
	}

	/**
	 * Test main
	 */
	public function testMain()
	{
		$start = microtime(true);
		$this->runCreate();
		$timeToCreate = microtime(true) - $start;

		$start = microtime(true);
		$this->runRetrieve();
		$timeToRetrive = microtime(true) - $start;

		$start = microtime(true);
		$this->runUpdate();
		$timeToUpdate = microtime(true) - $start;

		$start = microtime(true);
		$this->runDelete();
		$timeToSoftDelete = microtime(true) - $start;

		$start = microtime(true);
		$this->runDelete();
		$timeToHardDelete = microtime(true) - $start;

		
		echo "\n\nFinished\n--------------------------\n\n";
		echo "Create:\t\tTotal = {$timeToCreate}s, Per = " . ($timeToCreate/$this->createNum) . "/s\n";
		echo "Retrieve:\tTotal = {$timeToRetrive}s, Per = " . ($timeToRetrive/$this->createNum) . "/s\n";
		echo "Update:\t\tTotal = {$timeToUpdate}s, Per = " . ($timeToUpdate/$this->createNum) . "/s\n";
		echo "Delete [soft]:\tTotal = {$timeToSoftDelete}s, Per = " . ($timeToSoftDelete/$this->createNum) . "/s\n";
		echo "Delete [hard]:\tTotal = {$timeToHardDelete}s, Per = " . ($timeToHardDelete/$this->createNum) . "/s\n";
		echo "\n";
	}

	/**
	 * Test creating new objects
	 */
	public function runCreate()
	{
		$dbh = $this->dbh;

		// Greate grouping for testing value types
		$cust = CAntObject::factory($dbh, "customer", null, $this->user);
		$grpd = $cust->addGroupingEntry("groups", "Regression Group", "e3e3e3");
		$statd = $cust->addGroupingEntry("status_id", "Regression Status", "e3e3e3");

		// Now loop through and create number objects
		for ($i = 0; $i < $this->createNum; $i++)
		{
			$cust = CAntObject::factory($dbh, "customer", null, $this->user);
			$cust->setValue("name", "Regression Test " . $i);
			$cust->setValue("status_id", $statd['id']);
			$cust->setMValue("groups", $grpd['id']);
			$cid = $cust->save();

			$this->ids[] = $cid;
		}
	}

	/**
	 * Test opening all objects
	 */
	public function runRetrieve()
	{
		$dbh = $this->dbh;

		foreach ($this->ids as $cid)
		{
			$cust = CAntObject::factory($dbh, "customer", $cid, $this->user);
			// Loaded
		}
	}

	/**
	 * Test updating created objects
	 */
	public function runUpdate()
	{
		$dbh = $this->dbh;

		foreach ($this->ids as $cid)
		{
			$cust = CAntObject::factory($dbh, "customer", $cid, $this->user);
			$cust->setValue("name", "Regression Test " . $i . "-r2");
			$cust->save();
		}
	}

	/**
	 * Test deleting all ojbects and time
	 */
	public function runDelete()
	{
		$dbh = $this->dbh;

		foreach ($this->ids as $cid)
		{
			$cust = CAntObject::factory($dbh, "customer", $cid, $this->user);
			$cust->remove();
		}
	}
}

