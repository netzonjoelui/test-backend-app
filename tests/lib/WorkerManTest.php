<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/AntLog.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/WorkerMan.php');

class WorkerManTest extends PHPUnit_Framework_TestCase 
{
	var $obj = null;
	var $dbh = null;
	var $antfs = null;

	/**
	 * Setup unit test
	 */
	protected function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
	}

	/**
	 * Test running background process
	 *
	 * @group testBackground
	 */
	public function testBackground() 
	{
		// Add job to worker queue and run immediately (because we are in test mode)
		$wman = new WorkerMan($this->dbh);
		$wman->debug = true;
		$wman->setTestMode(true); // place it test mode which will act like background but it's actually just like run with db interface
		$jobid = $wman->runBackground("tests/background", "test");
		$this->assertTrue(is_numeric($jobid));

		// Get results
		$ret = $wman->getRetval($jobid);
		$this->assertEquals($ret, strrev("test"));

		// Cleanup
		$ret = $wman->clearJob($jobid);
	}

	/**
	 * Test the scheduler
	 *
	 * @group testScheduleBackground
	 */
	public function testScheduleBackground()
	{
		// Add job to worker queue
		$wman = new WorkerMan($this->dbh);
		$jobid = $wman->scheduleBackground("tests/background", "test", date("m/d/Y", strtotime("tomorrow")));
		$this->assertTrue(is_numeric($jobid));

		// Open job and test value
		$job = $wman->getJob($jobid);
		$this->assertEquals($job->workload(), "test");

		// Try running - but it should skip becase it's not tomorrow yet
		$worker = new Worker($this->dbh);
		$worker->work($jobid);
		$this->assertEquals($worker->returnCode(), WORKER_ERROR); // will fail if job not found

		// Cleanup
		$ret = $wman->clearJob($jobid);
	}
    
    /**
     * Test deferred
     */
    public function testDeferJob()
	{
		// Add job to worker queue
		$wman = new WorkerMan($this->dbh);
        $wman->setTestMode(true); // place it test mode which will act like background but it's actually just like run with db interface
		$jobid = $wman->runBackground("tests/deferred", "test");
		$this->assertTrue(is_numeric($jobid));

        // Make sure the job is still there / defered for later
		$job = $wman->getJob($jobid);
		$this->assertEquals($job->workload(), "test");
        
        // Second run should fail because it will not run for 60 seconds
		$worker = new Worker($this->dbh);
		$worker->work($jobid);
		$this->assertEquals($worker->returnCode(), WORKER_ERROR); // will fail if job not found

		// Cleanup
		$ret = $wman->clearJob($jobid);
	}
}
