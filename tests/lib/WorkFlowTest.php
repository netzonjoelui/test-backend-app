<?php
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/aereus.lib.php/antapi.php');

class WorkFlowTest extends PHPUnit_Framework_TestCase 
{
	var $dbh = null;
	var $user = null;

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
	 * Used to execute a single test
	function getTests()
	{
		return array("testWorkflowLaunch");
	}
	 */
	 

	/**************************************************************************
	 * Function: 	testWorkflowLaunch
	 *
	 * Purpose:		Make sure workflow launches when conditions are met
	 **************************************************************************/
	function testWorkflowLaunch()
	{
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
		$cond->fieldName = "company";
		$cond->operator = "is_equal";
		$cond->condValue = "utest wf company";

		$wid = $wf->save();
		$this->assertTrue(is_numeric($wid));

		// Crate test object
		$cust = new CAntObject($this->dbh, "customer", null);
		$cust->setValue("name", "utest wf cust");
		$cust->setValue("company", "utest wf company");
		$custid = $cust->save(false);
		
		// Test current user launch condition
		$this->assertTrue($wf->conditionsMatch($cust));

		// Make sure workflow was processed
		$this->assertTrue(in_array($wf->id, $cust->processed_workflows));
        
        // Test unmet condition
        // Should not fire, since the workflow condition already met
        $wf->fConditionUnmet = true;
        $custTestCondition = new CAntObject($this->dbh, "customer", $custid);
        $this->assertFalse($wf->conditionsMatch($custTestCondition));
        
        // Change the condition so it wont meet the required condition of workflow
        $cust->setValue("company", "new utest wf company");
        $custid = $cust->save(false);
        
        // Should be false, since condition is unmet
        $this->assertFalse($wf->conditionsMatch($cust));
        
        // Change the condition so it wont meet the required condition of workflow
        $cust->setValue("company", "utest wf company");
        $custid = $cust->save(false);
        
        // Should be true, since the last condition was unmet
        $this->assertTrue($wf->conditionsMatch($cust));
        
		// Clean-up
		$wf->remove();
		$cust->removeHard();
	}

	/**
	 * Test workflow action
	 */
	function testAction()
	{
		// Create test workflow
		$wf = new WorkFlow($this->dbh);
		$wf->name = "test workflow";
		$wf->notes = "this workflow is a unit test";
		$wf->object_type = "customer";
		$wfid = $wf->save();

		// Create test action
		$act = new WorkFlow_Action($this->dbh);
		$act->type = WF_ATYPE_CREATEOBJ;
		$act->create_obj = "customer";
		$act->object_values['first_name'] = "WF Test";
		$act->workflow_id = $wfid;
		$aid = $act->save();
		unset($act);
		$act = new WorkFlow_Action($this->dbh, $aid);
		$this->assertEquals($act->type, WF_ATYPE_CREATEOBJ);

		// Clean-up
		$wf->remove();
		$act->remove();
	}

	/**
	 * Test child actions
	 */
	function testChildActions()
	{
		// Create test object
		$obj = new CAntObject($this->dbh, "customer", null, $this->user);
		$obj->setValue("name", "UnitTest:testChildActions");
		$obj->save();

		// Create test workflow
		$wf = new WorkFlow($this->dbh);
		$wf->name = "test childaction workflow";
		$wf->notes = "this workflow is a unit test";
		$wf->object_type = "customer";
		$wfid = $wf->save();

		// Create test action
		$act = new WorkFlow_Action($this->dbh);
		$act->type = WF_ATYPE_TEST;
		$act->object_values['runsubact'] = "no";
		$act->workflow_id = $wfid;
		$aid = $act->save();
		$this->assertTrue($aid>0);

		// Create child action with no event (will exectute at the end)
		$act2 = $act->addAction();
		$act2->type = WF_ATYPE_TEST;
		$act2->object_values['runsubact'] = "no";
		$aid2 = $act2->save();
		$this->assertTrue($aid2>0);

		// Create child action that exectutes on a specific event
		$act3 = $act->addAction();
		$act3->type = WF_ATYPE_TEST;
		$act3->parentActionEvent = "test";
		$act3->object_values['runsubact'] = "no";
		$aid3 = $act3->save();
		$this->assertTrue($aid3>0);

		// Test Execution
		// ------------------------------------------

		// Make sure only child with no 'event' defined fired
		$act->execute($obj);
		$this->assertTrue(in_array($aid2, $act->childActionsExecuted));
		$this->assertFalse(in_array($aid3, $act->childActionsExecuted));

		// Now make sure that child action with event fired
		$act->object_values['runsubact'] = "yes";
		$act->execute($obj);
		$this->assertTrue(in_array($aid2, $act->childActionsExecuted));
		$this->assertTrue(in_array($aid3, $act->childActionsExecuted));

		// Clean-up
		$obj->removeHard();
		$act3->remove();
		$act2->remove();
		$act->remove();
		$wf->remove();
	}

	/**
	 * Test action - invoice
	 */
	function testActionInvoice()
	{
		$inv = new CAntObject_Invoice($this->dbh, null, $this->user);
		$successGrp = $inv->addGroupingEntry("status_id", "Unit Test Success");
		//$failGrp = $inv->addGroupingEntry("status_id", "Unit Test Fail");

		// Create test workflow
		$wf = new WorkFlow($this->dbh);
		$wf->name = "test workflow";
		$wf->notes = "this workflow is a unit test";
		$wf->object_type = "customer";
		$wfid = $wf->save();

		// Create customer to work with
		$cust = new CAntObject_Customer($this->dbh, null, $this->user);
		$cust->setValue("first_name", "testActionInvoice");
		$custid = $cust->save();

		// Add credit card to customer account - expires 12 of 2020
		$cust->addCreditCard("1111111111111111", "12", "2020", "Test User", true);

		// Create a teamp product
		$product = new CAntObject($this->dbh, "product", null, $this->user);
		$product->setValue("name", "testActionInvoice product");
		$product->setValue("price", 10);
		$pid = $product->save();

		// Create test base action
		$act = new WorkFlow_Action($this->dbh);
		$act->type = WF_ATYPE_CREATEOBJ;
		$act->create_obj = "invoice";
		$act->setObjectValue('name', "WF Test Invoice");
		$act->setObjectValue('paywithdefcard', 1); // Automate payment
		$act->setObjectValue('billing_success_status', $successGrp['id']); // Automate payment
		//$act->setObjectValue('billing_fail_status', $failGrp['id']); // Automate payment
		$act->setObjectValue('owner_id', $this->user->id);
		$act->setObjectValue('ent_product_0', $pid);
		$act->setObjectValue('ent_quantity_0', 2);
		$act->workflow_id = $wfid;
		$aid = $act->save();
		
		// Create invoice subaction
		$actInv = new WorkFlow_Action_Invoice($this->dbh);
		$actInv->pgwType = PMTGW_TEST; // Force test payment gateway
		$actInv->execute($cust, $act);

		$this->assertTrue($actInv->invoiceId > 0); // Make sure invoice was created
		$this->assertTrue($actInv->pmtResult); // Make sure the payment went through for this transaction

		// Check to see if invoice has correct values
		$invoice = new CAntObject_Invoice($this->dbh, $actInv->invoiceId, $this->user);
		$this->assertEquals($invoice->getTotal(), 20);
		$this->assertEquals($invoice->getValue("status_id"), $successGrp['id']);

		// Clean-up
		$wf->remove();
		$act->remove();
		$cust->removeHard();
		$product->removeHard();
		$invoice->deleteGroupingEntry("status_id", $successGrp['id']);
		//$invoice->deleteGroupingEntry("status_id", $failGrp['id']);
		$invoice->removeHard();
	}

	/**
	 * Test action - approval
	 */
	function testActionApproval()
	{
		$cust = new CAntObject($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "UnitTestActionApproval");
		$cid = $cust->save();

		// Create test workflow
		$wf = new WorkFlow($this->dbh);
		$wf->name = "test workflow";
		$wf->notes = "this workflow is a unit test";
		$wf->object_type = "customer";
		$wfid = $wf->save();

		$act = $wf->addAction();
		$act->type = WF_ATYPE_APPROVAL;
		$aid = $act->save();

		// Add action that will change the name of the customer on approval
		$act2 = $act->addAction();
		$act2->parentActionEvent = "approved";
		$act2->type = WF_ATYPE_UPDATEFLD;
		$act2->update_field = "name";
		$act2->update_to = "UnitTestActionApproval: approved";
		$aid2 = $act2->save();

		// Execute child actions of main approval action
		$act->executeChildActions("approved", $cust);


		// Test to see if field was updated
		$this->assertEquals($cust->getValue("name"), "UnitTestActionApproval: approved");

		// Cleanup
		$cust->removeHard();
		$act->remove();
		$act2->remove();
		$wf->remove();
	}
	
	/**
	 * Test action - callpage
	 */
	function testActionCallpage()
	{
		// Create workflow
		$wf = new WorkFlow($this->dbh);
		$wf->name = "test workflow";
		$wf->notes = "this workflow is a unit test";
		$wf->object_type = "customer";
		$wf->fActive = true;
		$wf->fOnCreate = true;
		$wf->fOnUpdate = true;
		$wf->fOnDelete = false;
		$wf->fOnDaily = false;
		$wf->fSingleton = false;
		$wf->fAllowManual = false;
		
		$wfid = $wf->save();
		$this->assertTrue(is_numeric($wfid));
		$this->assertTrue($wfid > 0);
		
		// This will update customer notes field to objType:oid
		$callPageUrl = $this->ant->getAccBaseUrl() . "/tests/data/CallPageWorkflow.php?obj_type=<%object_type%>&oid=<%id%>";
		
		// Create action
		$act = new WorkFlow_Action($this->dbh);
		$act->type = WF_ATYPE_CALLPAGE;
		$act->name = "Test Workflow Callpage Action";
		$act->setObjectValue("url", $callPageUrl);
		$act->workflow_id = $wfid;
		$aid = $act->save();
		$this->assertTrue(is_numeric($aid));
		$this->assertTrue($aid > 0);
		
		// Create new Customer to trigger CallPage Workflow
		$cust = new CAntObject($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "UnitTestActionCallPage");
		$cid = $cust->save();
		$this->assertTrue($cid > 0);
		$cust->clearCache();
		
		// Workflow should have been triggerred
		// Open customer
		unset($cust);
		$cust = new CAntObject($this->dbh, "customer", $cid, $this->user);
		$notes = $cust->getValue("notes");
		
		$this->assertEquals($notes, "customer:$cid");
		
		// Cleanup
		$cust->removeHard();
		$act->remove();
		$wf->remove();
	}
	
	/**
	 * Test workflow function - replaceMergeVars
	 */
	function testReplaceMergeVars()
	{
		// Instantiate Objects
		$act = new WorkFlow_Action($this->dbh);            
		
		$obj = new CAntObject($this->dbh, "task", null, $this->user);
		$obj->setValue("name", "unitTestTask");
		$obj->save();
		
		// Test replaceMergeVars with array variables
		$ovars["userId"] = array("<%user_id%>");
		$ovars["objectLink"] = array("<%object_link%>");
		$ovars["objectType"] = array("<%object_type%>");
		$act->replaceMergeVars($ovars, $obj);
		$this->assertEquals($ovars["userId"][0], $this->user->id);
		$this->assertEquals($ovars["objectLink"][0], $this->ant->getAccBaseUrl()."/obj/" . $obj->object_type . '/' . $obj->id);
		
		//print_r($ovars);
		
		// Test replaceMergeVars with string variables
		unset($ovars);
		$ovars["userId"] = "<%user_id%>";
		$ovars["objectLink"] = "<%object_link%>";
		$ovars["objectType"] = "<%object_type%>";
		$act->replaceMergeVars($ovars, $obj);
		$this->assertEquals($ovars["userId"], $this->user->id);
		$this->assertEquals($ovars["objectLink"], $this->ant->getAccBaseUrl()."/obj/" . $obj->object_type . '/' . $obj->id);
		$this->assertEquals($ovars["objectType"], $obj->object_type);
		
		// Clean Data
		$obj->removeHard();
        
        unset($obj);
        unset($act);
        unset($ovars);
        
        // Test Customer default email
        $act = new WorkFlow_Action($this->dbh);            
        
        $obj = new CAntObject($this->dbh, "customer", null, $this->user);
        $obj->setValue("first_name", "unitTestCustomer");
        $obj->setValue("email", "unit@test.com");
        $obj->setValue("email_default", "email");
        $obj->save();
        
        // Test replaceMergeVars with array variables
        $ovars["emailDefault"] = array("<%email_default%>");
        $act->replaceMergeVars($ovars, $obj);
        $this->assertEquals($ovars["emailDefault"][0], "unit@test.com");

        // Clean Data
        $obj->removeHard();
	}

	/**
	 * Test WorkFlow_Action_UpdateField execution
	 */
	public function testActionUpdateField()
	{
		$cust = new CAntObject($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "testActionUpdateField");
		$cust->setValue("owner_id", $this->user->id);
		$cid = $cust->save();

		// Create base action
		$act = new WorkFlow_Action($this->dbh);
		$act->type = WF_ATYPE_UPDATEFLD;
		$act->update_field = "name";
		$act->update_to = "testActionUpdateField2";
		//$aid = $act->save();

		// Update field to a value
		// ---------------------------------
		$actUpdate = new WorkFlow_Action_UpdateField($this->dbh);
		$actUpdate->execute($cust, $act);

		// Make sure the object value was changed
		$this->assertEquals($cust->getValue("name"), $act->update_to);

		// Update field to a referenced value
		// ---------------------------------
		$act->update_to = "<%owner_id.name%>"; // change to user name
		$ovars = array();
		$act->replaceMergeVars($ovars, $cust);

		$actUpdate = new WorkFlow_Action_UpdateField($this->dbh);
		$actUpdate->execute($cust, $act);

		// Make sure the object value was changed
		$this->assertEquals($cust->getValue("name"), $this->user->getValue("name"));
		$this->assertEquals($ovars['update_to'], $this->user->getValue("name"));

		// Cleanup
		$cust->removeHard();
	}

	/**
	 * Test WorkFlow_Action_UpdateField execution
	 */
	public function testActionAsssignRR()
	{	
		$cust = new CAntObject($this->dbh, "lead", null, $this->user);
		$cust->setValue("name", "testActionAsssignRR");
		$cust->setValue("owner_id", $this->user->id); // first assign to administrator
		$cid = $cust->save();

		// Craete a new user
		$testUser = $this->ant->getUserByEmail("testactionassignrr@testactionassignrr.com");
		if (!$testUser)
		{
			$testUser = $this->ant->createUser("testactionassignrr", 'test');
			$testUser->setValue("email", "testactionassignrr@testactionassignrr.com");
			$testUser->save();
		}

		// Create base action
		$act = new WorkFlow_Action($this->dbh);
		$act->type = WF_ATYPE_ASSIGNRR;
		$act->update_field = "owner_id";
		$act->update_to = "testactionassignrr," . $this->user->name;
		//$aid = $act->save();

		// Update field to a value
		// ---------------------------------
		$actUpdate = new WorkFlow_Action_AssignRR($this->dbh);
		$actUpdate->execute($cust, $act);

		// Make sure the object value was changed
		$this->assertEquals($cust->getValue("owner_id"), $testUser->id);

		// Check to make sure the activity update_to was reordered so administrator would ge the next lead
		$this->assertEquals($this->user->name . ",testactionassignrr", $act->update_to);

		// Cleanup
		$cust->removeHard();
	}
}
