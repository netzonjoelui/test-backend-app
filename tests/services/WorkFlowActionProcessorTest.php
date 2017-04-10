<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/AntService.php');
require_once(dirname(__FILE__).'/../../services/WorkFlowActionProcessor.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');

class WorkFlowActionProcessorTest extends PHPUnit_Framework_TestCase
{
	var $user = null;
	var $dbh = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, -1); // -1 = administrator
	}
	
	function tearDown() 
	{
		//@unlink('/temp/test.log');	
	}

	/**
	 * Make sure run can be processed without error. Functionality will be tested later.
	 */
	function testRun() 
	{
		$svc = new WorkFlowActionProcessor();
		$svc->closeDbh = false;
		$this->assertTrue($svc->run());
	}

	/**
	 * Test action is launched
	 */
	function testAction()
	{
		// Create test workflow
		$wf = new WorkFlow($this->dbh);
		$wf->name = "test workflow";
		$wf->notes = "this workflow is a unit test";
		$wf->object_type = "customer";
		$wf->fActive = true;
		$wf->fOnCreate = true;
		$wf->fOnUpdate = false;
		$wf->fOnDelete = false;
		$wf->fOnDaily = false;
		$wf->fSingleton = false;
		$wf->fAllowManual = false;

		// Add a current user condition
		$cond = $wf->addCondition();
		$cond->blogic = "and";
		$cond->fieldName = "owner_id";
		$cond->operator = "is_equal";
		$cond->condValue = USER_CURRENT;

		// Create test action that will be scheduled for later execution
		$act = $wf->addAction();
		$act->type = WF_ATYPE_TEST;
		$act->when_interval = 1;
		$act->when_unit = WF_TIME_UNIT_MINUTE;
		$aid = $act->save();
		$this->assertTrue(is_numeric($aid));

		// Save workflow with the new action
		$wfid = $wf->save();
		$this->assertTrue(is_numeric($wfid));

		// Create customer which should create a workflow instance with the scheduled task
		$cust = new CAntObject($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "utest wf cust");
		$cust->setValue("owner_id", $this->user->id);
		$custid = $cust->save(false);

		// Test current user launch condition
		$this->assertTrue($wf->conditionsMatch($cust));

		// Make sure workflow was processed
		$this->assertTrue(in_array($wf->id, $cust->processed_workflows));

		// Rewind the time to execute for the action to 'now'
		$this->dbh->Query("UPDATE workflow_action_schedule SET ts_execute=now() WHERE action_id='$aid'");

		// Run the action processor service
		$svc = new WorkFlowActionProcessor();
		$svc->testMode = true;
		$this->assertTrue($svc->run()); // Will return false if fails (should not happen but just in case)
		$this->assertTrue($svc->numProcessed > 0); // make sure at least the above action got processed
		$this->assertTrue(in_array($aid, $svc->actionsProcessed)); // Make sure this action was processed

		// Clean-up
		$act->remove();
		$wf->remove();
		$cust->removeHard();
	}
}
