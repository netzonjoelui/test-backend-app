<?php
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/CAntObjectList.php');
require_once(dirname(__FILE__).'/../../customer/customer_functions.awp');
require_once(dirname(__FILE__).'/../../lib/aereus.lib.php/CAntObjectApi.php');

class CAntObjectTest extends PHPUnit_Framework_TestCase
{
	var $obj = null;
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
	 * Used to execute a single test
     */
	/*function getTests()
	{
		return array("testCreateObject");
	}*/
	
	/**
	 * Test save with objects not stored in custom tables
	 */
	public function testGenSave() 
	{
		$dbh = $this->dbh;

		$obj = new CAntObject($dbh, "project_story", null, $this->user);
		$obj->setValue("name", "testGenSave");
		$oid = $obj->save(false);
		$this->assertTrue($oid > 0 );
		unset($obj);

		// Now make sure that the owner has been set (object with type)
		$obj = new CAntObject($dbh, "project_story", $oid, $this->user);
		$this->assertEquals($obj->getValue("name"), "testGenSave");
		$this->assertEquals($obj->getValue("owner_id"), $this->user->id);
		$this->assertEquals($obj->getForeignValue("owner_id"), $this->user->fullName);
		$obj->save();
		unset($obj);

		// One more time, make sure the owner has been preserved
		$obj = new CAntObject($dbh, "project_story", $oid, $this->user);
		$this->assertEquals($obj->getValue("owner_id"), $this->user->id);
		$this->assertEquals($obj->getForeignValue("owner_id"), $this->user->fullName);

		// Test object (with no type)
		$com = new CAntObject($dbh, "comment", null);
		$com->setValue("obj_reference", "project_story:$oid");
		$cid = $com->save(false);
		unset($com);
		$com = new CAntObject($dbh, "comment", $cid);
		$this->assertEquals($com->getValue("obj_reference"), "project_story:$oid");

		// Cleanup
		$obj->removeHard();
		$com->removeHard();
	}

	/**
	 * Test save for objects stored in custom tables
	 */
	public function testCustomSave() 
	{
		$dbh = $this->dbh;

		$dbh->Query("insert into customer_labels(name) values('Test r1');");
		$dbh->Query("insert into customer_labels(name) values('Test r2');");

		$result = $dbh->Query("select id from customer_labels limit 2");
		$g1 = $dbh->GetValue($result, 0, "id");
		$g2 = $dbh->GetValue($result, 1, "id");

		// Test mValue
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "my test");
		$obj->setMValue("groups", $g1);
		$obj->setMValue("groups", $g2);
		$cid = $obj->save(false);
		unset($obj);

		$obj = new CAntObject($dbh, "customer", $cid, $this->user);
		$obj->removeMValue("groups", $g2);
		$cid = $obj->save(false);
		unset($obj);

		// Make sure mvalues reload and that we are not using too many queries to load the object
		// TODO: work on the dacl to only allow one query when loading an object
		$statBefore = $dbh->statNumQueries;
		$obj = new CAntObject($dbh, "customer", $cid, $this->user);
		$obj->setValue("name", "Test Assoc");
		$this->assertFalse($obj->getMValueExists("groups", $g2));
		$this->assertTrue($obj->getMValueExists("groups", $g1));
		$this->assertTrue(($dbh->statNumQueries-$statBefore)<=2); // Loading an object should never take more than 2 queries
		//echo "<pre>Num Queries: ".($dbh->statNumQueries-$statBefore)."</pre>";

		// Test object_multi
		$cont = new CAntObject($dbh, "contact_personal", null, $this->user);
		$cont->setValue("first_name", "Test Personal Contact");
		$contactId = $cont->save();
		$this->assertTrue($contactId > 0);
		$obj->debug = true;
		$obj->setMValue("associations", "contact_personal:$contactId");
		$cid = $obj->save(false);
		unset($obj);

		$obj = new CAntObject($dbh, "customer", $cid);
		$this->assertTrue($obj->getMValueExists("associations", "contact_personal:$contactId")!=false);
        
        // Test remove the mvalue
        $obj->removeMValue("associations", "contact_personal:$contactId");
        $obj->save(false);
        unset($obj);
        
        $obj = new CAntObject($dbh, "customer", $cid);
        $this->assertFalse($obj->getMValueExists("associations", "contact_personal:$contactId")==false);
        unset($obj);
        
        // Test remove all mvalues
        $obj = new CAntObject($dbh, "customer", $cid);
        $obj->setMValue("associations", "contact_personal:$contactId");
        $obj->save(false);
        unset($obj);
        
        $obj = new CAntObject($dbh, "customer", $cid);
        $this->assertTrue($obj->getMValueExists("associations", "contact_personal:$contactId")!=false); // Make sure the mvalue was set
        $obj->removeMValues("associations"); // now remove the mvalues
        $obj->save(false);
        unset($obj);
        
        $obj = new CAntObject($dbh, "customer", $cid);
        $this->assertFalse($obj->getMValueExists("associations", "contact_personal:$contactId")==false);

		// Test object (with no type)
		$com = new CAntObject($dbh, "comment", null);
		$com->setValue("obj_reference", "contact_personal:$cid");
		$comid = $com->save(false);
		unset($com);
		$com = new CAntObject($dbh, "comment", $comid);
		$this->assertEquals($com->getValue("obj_reference"), "contact_personal:$cid");
		$com->removeHard();
		
		// Cleanup
		$cont->removeHard();
		$obj->removeHard();

		$dbh->Query("delete from customer_labels where name='Test r1';");
		$dbh->Query("delete from customer_labels where name='Test r2';");
	}

	/**
	 * Test saving an object that was created prior to the new caching
	 */
	public function testSavedExisting() 
	{
		$dbh = $this->dbh;

		$cust = CAntObject::factory($dbh, "customer", null, $this->user);
		$grpd = $cust->addGroupingEntry("groups", "Unit Test Group", "e3e3e3");
		$statd = $cust->addGroupingEntry("status_id", "Unit Test Status", "e3e3e3");
		
		$g1 = $grpd['id'];
		$s1 = $statd['id'];

		// Manually insert a customer
		$result = $dbh->Query("insert into customers(name, status_id) values('big unit test', '$s1');select currval('customers_id_seq') as id;");
		$cid = $dbh->GetValue($result, 0, "id");
		
		if ($dbh->GetNumberRows($result))
		{
			$cid = $dbh->GetValue($result, 0, "id");
			$dbh->Query("insert into customer_label_mem(customer_id, label_id) VALUES('$cid', '$g1');");
		}
		
		// Save customer groups using CAntObject
		$obj = new CAntObject($dbh, "customer", $cid, $this->user);
		$obj->setValue("name", "testSavedExisting");
		$savedId = $obj->save();            
		$this->assertEquals($savedId, $cid);
		unset($obj);
		
		// Open and make sure the object has a value, then save it
		$obj = new CAntObject($dbh, "customer", $cid, $this->user);
		$this->assertTrue($obj->getMValueExists("groups", $g1));
		$this->assertEquals($obj->getForeignValue("groups"), "Unit Test Group");
		$this->assertEquals($obj->getValue("status_id"), $s1);
		$this->assertEquals($obj->getForeignValue("status_id"), "Unit Test Status");
		$obj->save();
		unset($obj);

		// Open again and make sure the object has a value for groups
		$obj = new CAntObject($dbh, "customer", $cid, $this->user);
		$this->assertTrue($obj->getMValueExists("groups", $g1));
		$obj->save();


		// Now test with object references both with and with subtypes
		$act = CAntObject::factory($dbh, "activity", null, $this->user);

		// Manually insert an activity
		$result = $dbh->Query("insert into objects_activity_act(name, user_id) values('big unit test', '".$this->user->id."');
								select currval('objects_id_seq') as id;");
		$aid = $dbh->GetValue($result, 0, "id");
		$field = $act->def->getField('obj_reference');
		$fieldUser = $act->def->getField('user_id');
		$dbh->Query("insert into object_associations(type_id, object_id, assoc_type_id, assoc_object_id, field_id) 
						VALUES('".$act->object_type_id."', '$aid', '".$obj->object_type_id."', 
						'$cid', '".$field->id."');");
		$dbh->Query("insert into object_associations(type_id, object_id, assoc_type_id, assoc_object_id, field_id) 
						VALUES('".$act->object_type_id."', '$aid', '".$this->user->userObj->object_type_id."', 
						'".$this->user->id."', '".$fieldUser->id."');");

		$obj2 = new CAntObject($dbh, "activity", null, $this->user);
        $obj2->id = $aid;
        $obj2->load();
		$this->assertEquals($obj2->getValue("obj_reference"), "customer:$cid");
		$this->assertEquals($obj2->getForeignValue("obj_reference"), "testSavedExisting");
		$this->assertEquals($obj2->getValue("user_id"), $this->user->id);
		$this->assertEquals($obj2->getForeignValue("user_id"), $this->user->name);

		// Cleanup
		$obj->deleteGroupingEntry("groups", $grpd['id']);
		$obj->deleteGroupingEntry("status_id", $statd['id']);
		$obj->removeHard();

		$act->removeHard();
	}

	/**
	 * Test save where type=object_multi with a subtype
	 */
	function testSaveObjMultiWithType() 
	{
		$dbh = $this->dbh;

		$obj = new CAntObject($dbh, "calendar_event", null, $this->user);
		$obj->setValue("name", "Unit Test Calendar Event");
		$cid = $obj->save();

		// Add a member
		$mem = new CAntObject($dbh, "member", null, $this->user);
		$mem->setValue("name", "Uni Test Calendar Event Member");
		$mem->setValue("obj_reference", "calendar_event:$cid");
		$mid = $mem->save();

		$obj->setMValue("attendees", $mid);
		$cid = $obj->save();
		unset($obj);

		// Check to see if the attendees object is set
		$obj = new CAntObject($dbh, "calendar_event", $cid, $this->user);
		$this->assertTrue(count($obj->getValue("attendees")) > 0);
		$this->assertTrue($obj->getMValueExists("attendees", $mid)!=false);

		// Cleanup
		$mem->removeHard();
		$obj->removeHard();
	}

	// Set f_delted flag
	function testDeleteSoft() 
	{
		$dbh = $this->dbh;

		$obj = new CAntObject($dbh, "customer");
		$obj->setValue("name", "my test");
		$cid = $obj->save(false);
		$obj->remove(); // Soft delete

		if ($dbh->GetNumberRows($dbh->Query("select id from customers where id='$cid' and f_deleted='t'")))
			$this->assertTrue(true);
		else
			$this->assertTrue(false);

		$obj->remove(); // Hard delete
		unset($obj);
	}

	// Make sure default owner is set
	function testDefaultOwner() 
	{
		$dbh = $this->dbh;
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->save(false);
		$this->assertEquals($obj->owner_id, $this->user->id);
		$obj->removeHard();
		unset($obj);
	}

	// Delete record from database
	function testDeleteHard() 
	{
		$dbh = $this->dbh;

		$obj = new CAntObject($dbh, "customer");
		$obj->setValue("name", "my test");
		$cid = $obj->save(false);
		$obj->remove(); // Soft delete
		unset($obj);
		$obj = new CAntObject($dbh, "customer", $cid);
		$obj->remove(); // Hard delete

		if (!$dbh->GetNumberRows($dbh->Query("select id from customers where id='$cid'")))
			$this->assertFalse(false);
		else
			$this->assertFalse(true);
	}	
	
	/*
	function testIndex()
	{
		$dbh = $this->dbh;

		$obj = new CAntObject($dbh, "customer");
		$obj->setValue("name", "my test");
		$obj->save(false);

		$result = $dbh->Query("select keywords from object_index_fulltext where type_id='".$obj->object_type_id."' and object_id='".$obj->id."'");
		if ($result)
			$val = $dbh->GetValue($result, 0, "keywords");

		$result = $dbh->Query("select object_id from object_index where object_type_id='".$obj->object_type_id."' and object_id='".$obj->id."'");
		$this->assertTrue($dbh->GetNumberRows($result)>0);

		$obj->remove();
		$obj->remove();
	}
	*/

	/*
	function testIndexDelete()
	{
		$dbh = $this->dbh;

		$obj = new CAntObject($dbh, "customer");
		$obj->setValue("name", "my test");
		$obj->save(false);
		$id = $obj->id;
		$object_type_id = $obj->object_type_id;
		$obj->remove();

		$result = $dbh->Query("select keywords from object_index where type_id='".$object_type_id."' and object_id='".$id."'");
		if ($result)
			$val = $dbh->GetValue($result, 0, "keywords");

		$this->assertNull($val);
	}
	 */

	// Make sure that objects are smart enough to clear cache on revision mismatch
	// DEPRICATED: move this to object_indexed query
	function testRevisionCacheSync()
	{
		/*
		$dbh = $this->dbh;

		// Create new cust - will set revision to 1
		$obj = new CAntObject($dbh, "customer");
		$obj->setValue("name", "my test");
		$custid = $obj->save(false);

		$dbh->Query("update customers set revision='3' where id='$custid'");

		// Load list if all customers
		$objList = new CAntObjectList($dbh, "customer", null);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->getObjects();
		$obj2 = $objList->getObject(0); // should update cache
		$rev = $obj2->getValue("revision");

		$this->assertEquals((int)$rev, 3);

		unset($objList);
		//$obj->remove();
		//$obj->remove();
		*/
		$this->assertTrue(true);
	}
	
	/**
	 * Test default values for fields
	 *
	 * @group testDefaults
	 */
	public function testDefaults()
	{
		$dbh = $this->dbh;

		// set default to 1
		$obj = new CAntObject($dbh, "customer");
		$dbh->Query("delete from app_object_field_defaults where field_id in 
						(select id from app_object_type_fields where name='type_id' and type_id='".$obj->object_type_id."');");
		$dbh->Query("insert into app_object_field_defaults(field_id, on_event, value) 
						values((select id from app_object_type_fields where name='type_id' and type_id='".$obj->object_type_id."'),
								'null', '1');");
		$obj->clearDefinitionCache();
		unset($obj);

		// Test for default value
		$obj = new CAntObject($dbh, "customer");
		$obj->debug = true;
		$this->assertEquals(1, (int)$obj->getValue("type_id"));

		// set default to 2
		$obj = new CAntObject($dbh, "customer");
		$dbh->Query("delete from app_object_field_defaults where field_id in 
						(select id from app_object_type_fields where name='type_id' and type_id='".$obj->object_type_id."');");
		$dbh->Query("insert into app_object_field_defaults(field_id, on_event, value) 
						values((select id from app_object_type_fields where name='type_id' and type_id='".$obj->object_type_id."'),
								'null', '2');");
		$obj->clearDefinitionCache();
		unset($obj);

		// Test for default value
		$obj = new CAntObject($dbh, "customer");
		$this->assertEquals((int)$obj->getValue("type_id"), 2);

		// Now test default owner_id which defaults to USER_CURRENT
		// -----------------------------------------------
		// Test new create
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "test def own");
		$custid = $obj->save(false);
		$this->assertEquals((int)$obj->getValue("owner_id"), $this->user->id);
		unset($obj);

		// Test open
		$obj = new CAntObject($dbh, "customer", $custid, $this->user);
		$this->assertEquals((int)$obj->getValue("owner_id"), $this->user->id);
		
		// Test not current user
		/*$uid = $dbh->GetValue($dbh->Query("select id from users where id>0 limit 1"), 0, "id");
		$this->assertNotNull($uid);*/
		
		// Create new user for testing instead of querying existing users.
		$objUser = new CAntObject($dbh, "user", null, $this->user);
		$objUser->setValue("name", "unit test user");
		$uid = $obj->save(false);
		$this->assertTrue($uid > 0);
		
		if ($uid)
		{
			$obj->setValue("owner_id", $uid);
			$obj->save(false);
			unset($obj);
			$obj = new CAntObject($dbh, "customer", $custid, $this->user);
			$this->assertEquals((int)$obj->getValue("owner_id"), $uid);
		}

		// Clean Data
		$obj->removeHard();
		$objUser->removeHard();
		unset($obj);

		// Now test default where condition
		// -----------------------------------------------
		$obj = new CAntObject($dbh, "task", null, $this->user);
		$obj->setValue("name", "Test Default Completed");
		$tid = $obj->save();
		$this->assertEquals($obj->getValue("date_completed"), "");

		// Set done and then save - should update date_completed to 'now'
		$obj->setValue("done", "t");
		$obj->save();
		$this->assertNotEquals($obj->getValue("date_completed"), "");

		$obj->removeHard();
	}

	/**************************************************************************
	 * Function: 	testDaclInheritance
	 *
	 * Purpose:		Test security inheritance from associated objects. Will
	 * 				test using cases and projects because case is set to inherit
	 * 				dacl from project_id:object field
	 **************************************************************************/
	function testDaclInheritance()
	{
		global $OBJECT_FIELD_ACLS;

		/**
		 * joe: We are currently not using object dacl inheritance
		 * We leave this for future reference
		 */
		return;

		$dbh = $this->dbh;

		$daclProject = new Dacl($dbh, "/objects/project");
		$daclProject->save();
		$daclCase = new Dacl($dbh, "/objects/case");
		$daclCase->save();

		// Create test project
		$proj = new CAntObject($dbh, "project", null, $this->user);
		$proj->setValue("name", "Test");
		$pid = $proj->save(false);
		$daclProjectCase = new Dacl($dbh, "/objects/project/$pid/case");
		$daclProjectCase->save();

		// Test case dacl
		$case = new CAntObject($dbh, "case", null, $this->user);
		$this->assertEquals($case->dacl->id, $daclCase->id); // Default
		$case->setValue("project_id", $pid);
		// Now dacl should be $daclProjectCase 
		$this->assertEquals($case->dacl->id, $daclProjectCase->id);
		$case->save(false);

		// NOTE: this is no longer the case
		// After saving dacl should be inheriting but unique $daclProjectCase 
		//$this->assertEquals($case->dacl->id, $daclProjectCase->id);
		//$this->assertEquals($case->dacl->in, $daclProjectCase->id);

		// Clean-up
		$proj->remove();
		$proj->remove();
		$case->remove();
		$case->remove();
	}

	/**************************************************************************
	 * Function: 	testHasComments
	 *
	 * Purpose:		Test the hasComments and setHasComments functions
	 **************************************************************************/
	function testHasComments()
	{
		$dbh = $this->dbh;

		// Test cache set on call
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "TestHasComments");
		$custid = $obj->save();

		$comm = new CAntObject($dbh, "comment", null, $this->user);
		$comm->setValue("obj_reference", "customer:$custid");
		$comm->setValue("comment", "Test comment");
		$commentid = $comm->save();

		$this->assertTrue($obj->hasComments());

		$obj->remove();
		$obj->remove();
		$comm->remove();
		$comm->remove();
	}

	/**************************************************************************
	 * Function: 	testHasComments
	 *
	 * Purpose:		Test the hasComments and setHasComments functions
	 **************************************************************************/
	function testDeleteSecurity() 
	{
		$dbh = $this->dbh;
		$anonymous  = new AntUser($dbh, USER_ANONYMOUS); // should only be a member of the everyone group

		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "my test");
		$cid = $obj->save(false);
		unset($obj);

		$obj = new CAntObject($dbh, "customer", $cid, $anonymous);
		$this->assertFalse($obj->remove());
		unset($obj);

		$obj = new CAntObject($dbh, "customer", $cid, $this->user);
		$obj->remove();
		$obj->remove();
	}
	
	

	/**************************************************************************
	 * Function: 	testFieldMask
	 *
	 * Purpose:		Test the mask or formatting feature for a field
	 **************************************************************************/
	function testAutoAggregate() 
	{
		$dbh = $this->dbh;

		// sum
		// ------------------------------------------------------
		$objTask = new CAntObject($dbh, "task", null, $this->user);
		$objTask->setValue("name", "utest - aggregate test");
		$tid = $objTask->save();
		unset($objTest);
		$objTime1 = new CAntObject($dbh, "time", null, $this->user);
		$objTime1->setValue("hours", 1);
		$objTime1->setValue("task_id", $tid);
		$objTime1->save();
		$objTime2 = new CAntObject($dbh, "time", null, $this->user);
		$objTime2->setValue("task_id", $tid);
		$objTime2->setValue("hours", 1);
		$objTime2->save();

		// The task cost_action should be set to 2
		$objTask = new CAntObject($dbh, "task", $tid, $this->user);
		//$this->assertEquals($objTask->getValue("cost_actual"), 2); 07/10/2012 Marl - Uncomment this test after fixing the "Task Time Log" bug issue

		// Cleanup
		$objTime1->removeHard();
		$objTime2->removeHard();
		$objTask->removeHard();

		// avg
		// ------------------------------------------------------
		$objPro = new CAntObject($dbh, "product", null, $this->user);
		$objPro->setValue("name", "ptest - aggregate test");
		$pid = $objPro->save();
		unset($objTest);
		$objReview1 = new CAntObject($dbh, "product_review", null, $this->user);
		$objReview1->setValue("rating", 1);
		$objReview1->setValue("product", $pid);
		$objReview1->save();
		$objReview2 = new CAntObject($dbh, "product_review", null, $this->user);
		$objReview2->setValue("rating", 3);
		$objReview2->setValue("product", $pid);
		$objReview2->save();

		// The product rating should be an avg of 2
		$objPro = new CAntObject($dbh, "product", $pid, $this->user);
		//$this->assertEquals($objPro->getValue("rating"), 2);  07/10/2012 Marl - Uncomment this test after fixing the "Task Time Log" bug issue

		// Cleanup
		$objReview1->removeHard();
		$objReview2->removeHard();
		$objPro->removeHard();
	}

	/**
	 * Test autocreate folder
	 */
	public function testAutoCreateFolder()
	{
		// Test mValue
		$obj = new CAntObject($this->dbh, "customer", null, $this->user);
		$obj->save();

		$fldr = $obj->getValue("folder_id");
		$this->assertFalse(empty($fldr));

		$obj->removeHard();
	}

	/**************************************************************************
	 * Function: 	testFieldMask
	 *
	 * Purpose:		Test the mask or formatting feature for a field
	 **************************************************************************/
	function testFieldMask() 
	{
		date_default_timezone_set("America/Los_Angeles");

		// Test default phone
		$obj = new CAntObject($this->dbh, "customer", null, $this->user);
		$obj->setValue("phone_cell", "5415415411");
		$this->assertEquals($obj->getValue("phone_cell"), "(541) 541-5411");

		// Test default timestamp
		$obj = new CAntObject($this->dbh, "customer", null, $this->user);
		$obj->setValue("last_contacted", "1-1-2011 1:15 am PST");
		$this->assertEquals($obj->getValue("last_contacted"), "01/01/2011 01:15:00 am PST");

	}

	

	/**************************************************************************
	 * Function: 	testSaveView
	 *
	 * Purpose:		Test view save
	 **************************************************************************/
	function testSaveView() 
	{
		$dbh = $this->dbh;
		
		$view = new CAntObjectView();
		$view->name = "Test";
		$view->description = "My Test Description";
		$view->view_fields = array("id", "name");
		$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", USER_CURRENT);
		$view->sort_order[] = new CAntObjectSort("name", "desc");
		$vid = $view->save($dbh, "customer");

		if ($dbh->GetNumberRows($dbh->Query("select id from app_object_views where id='$vid'")))
			$this->assertTrue(true);
		else
			$this->assertTrue(false);

		$dbh->Query("delete from app_object_views where id='$vid'");
	}

	/**
	 * Test getUniqueName function
	 *
	 * @group testGetUniqueName
	 */
	public function testGetUniqueName()
	{
		$dbh = $this->dbh;

		// Test creating a unique name without saving - no id
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "get unique name test");
		$this->assertTrue(strlen($obj->getUniqueName())>0);
		$obj->save();
		$this->assertTrue(strlen($obj->getValue("uname"))>0);

		// Test duplicate uname should create a unique random number
		$obj2 = new CAntObject($dbh, "customer", null, $this->user);
		$obj2->setValue("name", "get unique name test"); // Should match uname
		$obj2->save();
		$this->assertTrue(strlen($obj2->getValue("uname"))>0);
		$this->assertNotEquals($obj->getValue("uname"), $obj2->getValue("uname"));

		// Cleanup
		$obj->removeHard();
		$obj2->removeHard();
		//$obj3->removeHard();
	}

	/**
	 * Test verify unique name function
	 *
	 * @group testVerifyUniqueName
	 */
	public function testVerifyUniqueName()
	{
		$dbh = $this->dbh;

		// First delete all left over items to avoid collisions
		$list = new CAntObjectList($dbh, "content_feed_post");
		$list->addCondition("and", "title", "is_equal", "verify unique name test");
		$list->getObjects();
		for ($i = 0; $i < $list->getNumObjects(); $i++)
		{
			$obj = $list->getObject($i);
			$obj->removeHard();
		}
		unset($list);

		// Create test feed
		$feed = CAntObject::factory($dbh, "content_feed", null, $this->user);
		$feed->setValue("title", "testVerifyUniqueName");
		$fid = $feed->save();

		// Create a new post and put it in the feed namespace by setting the field
		$obj = CAntObject::factory($dbh, "content_feed_post", null, $this->user);
		$obj->setValue("title", "verify unique name test");
		$obj->setValue("feed_id", $fid);
		$obj->save();
		$origUname = $obj->getValue("uname");
		$this->assertTrue(strlen($origUname)>0);

		// Create a new object without the feed and the same uname (should work)
		$obj2 = CAntObject::factory($dbh, "content_feed_post", null, $this->user);
		$obj2->setValue("title", "verify unique name test");
		$this->assertTrue($obj2->verifyUniqueName($origUname));

		// Now try to create a new object with the same feed id which should fail
		$obj2 = CAntObject::factory($dbh, "content_feed_post", null, $this->user);
		$obj2->setValue("title", "verify unique name test");
		$obj2->setValue("feed_id", $fid);
		$this->assertFalse($obj2->verifyUniqueName($origUname));

		// Now save the duplicate and make sure the uname is different
		$obj2->save();
		$this->assertNotEquals($obj->getValue("uname"), $obj2->getValue("uname"));
		

		// Cleanup
		$obj->removeHard();
		$obj2->removeHard();
		$feed->removeHard();
	}

	/**
	 * Test opening an object by a unique name
	 *
	 * @group testOpenByName
	 */
	public function testOpenByName() 
	{
		$dbh = $this->dbh;

		// Create test site
		$site = CAntObject::factory($dbh, "cms_site", null, $this->user);
		$site->setValue("name", "testOpenByName");
		$sid = $site->save();

		// Save new object and generate a uname
		$obj = new CAntObject($dbh, "cms_page", null, $this->user);
		$obj->setValue("name", "UNAMETEST");
		$obj->setValue("site_id", $sid);
		$oid = $obj->save();
		$uname = $obj->getValue("uname");

		// Open by uname where namespace site=$sid, and parent=null
		$obj2 = CAntObject::factory($dbh, "cms_page", "uname:$sid::$uname", $this->user);
		$this->assertEquals($obj->id, $obj2->id);

		// Open by uname where namespace site=null, and parent=null
		$obj2 = CAntObject::factory($dbh, "cms_page", "uname:::$uname", $this->user);
		$this->assertNotEquals($obj->id, $obj2->id);

		// Cleanup
		$obj->removeHard();
		$site->removeHard();
	}

	/**
	 * Test verify unique name on an object that does not use unique names
	 *
	 * @group testVerifyNoUniqueName
	 */
	public function testVerifyNoUniqueName()
	{
		$dbh = $this->dbh;

		// Crate a new email message which has no unique name
		$obj = CAntObject::factory($dbh, "email_message", null, $this->user);
		$obj->setValue("subject", "verify unique name test");
		$obj->save();
		$this->assertEquals($obj->getValue("uname"), "");

		// Save again and make sure the unique name is just set to the id
		$obj->save();
		$this->assertEquals($obj->getValue("uname"), $obj->id);

		// Cleanup
		$obj->removeHard();
	}

	/**************************************************************************
	 * Function: 	testRecurNextStart
	 *
	 * Purpose:		test the nextstart function for accuracy
	 **************************************************************************/
	function testRecurWeeklyBitwise() 
	{
		$rp = new CRecurrencePattern($this->dbh);
		$rp->type = RECUR_WEEKLY;
		$rp->interval = 1;
		$rp->dateStart = "1/2/2011"; // First sunday
		$rp->dateEnd = "1/15/2011";
		$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_MONDAY;
		$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_WEDNESDAY;
		// Test before save
		$this->assertNotNull($rp->dayOfWeekMask & WEEKDAY_MONDAY);
		$this->assertNotNull($rp->dayOfWeekMask & WEEKDAY_WEDNESDAY);

		// Save and unset for reloading
		$rid = $rp->save();
		unset($rp);

		// Open and test
		$rp = new CRecurrencePattern($this->dbh, $rid);
		$this->assertEquals($rp->type, RECUR_WEEKLY);
		$this->assertNotNull($rp->dayOfWeekMask & WEEKDAY_MONDAY);
		$this->assertNotNull($rp->dayOfWeekMask & WEEKDAY_WEDNESDAY);

		// Cleanup
		$rp->remove();
	}

	/**************************************************************************
	 * Function: 	testRecurNextStart
	 *
	 * Purpose:		test the nextstart function for accuracy
	 **************************************************************************/
	function testRecurNextStartDay() 
	{
		$dbh = $this->dbh;
		
		// Daily
		// -----------------------------------------
		$rp = new CRecurrencePattern($dbh);
		$rp->type = RECUR_DAILY;
		$rp->interval = 1;
		$rp->dateStart = "1/1/2010";
		$rp->dateEnd = "3/1/2010";
		
		// First instance should be today
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/01/2010");
		// Set today to processed, instance should be tomorrow
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/02/2010");
		// Change interval to skip a day - same processed to above
		$rp->dateProcessedTo = "1/2/2010";
		$rp->interval = 2;
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/03/2010");

		// Weekly
		// -----------------------------------------
		$rp = new CRecurrencePattern($dbh);
		$rp->type = RECUR_WEEKLY;
		$rp->interval = 1;
		$rp->dateStart = "1/2/2011"; // First sunday
		$rp->dateEnd = "1/15/2011";
		$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_SUNDAY;
		$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_WEDNESDAY;

		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/02/2011"); // Sun
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/05/2011"); // Wed
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/09/2011"); // Sun
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/12/2011"); // Wed
		// Next should fail because it is beyond the endDate
		$tsNext = $rp->getNextStart();
		$this->assertFalse($tsNext);

		// Monthly
		// -----------------------------------------
		$rp = new CRecurrencePattern($dbh);
		$rp->type = RECUR_MONTHLY;
		$rp->interval = 1;
		$rp->dayOfMonth= 1;
		$rp->dateStart = "1/1/2011";
		$rp->dateEnd = "";

		// The frist of each month
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/01/2011");
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "02/01/2011");
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "03/01/2011");

		// Skip over non-existant dates
		$rp = new CRecurrencePattern($dbh);
		$rp->type = RECUR_MONTHLY;
		$rp->interval = 1;
		$rp->dayOfMonth= 30;
		$rp->dateStart = "1/1/2011";
		$rp->dateEnd = "";
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/30/2011");
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "03/30/2011"); // Should skip of ver 2/30 because does not exist
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "04/30/2011");

		// MonthlyNth (the nth weekday(s) of every month)
		// -----------------------------------------
		$rp = new CRecurrencePattern($dbh);
		$rp->type = RECUR_MONTHNTH;
		$rp->interval = 1;
		$rp->instance = 4; // The 4th Sunday of each month
		$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_SUNDAY;
		$rp->dateStart = "1/1/2011";
		$rp->dateEnd = "";

		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/23/2011"); // The 4th Sunday in January
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "02/27/2011"); // The 4th Sunday in February

		// Test last
		$rp = new CRecurrencePattern($dbh);
		$rp->type = RECUR_MONTHNTH;
		$rp->interval = 1;
		$rp->instance = 5; // The last monday of each month
		$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_MONDAY;
		$rp->dateStart = "1/1/2011";
		$rp->dateEnd = "";

		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/31/2011"); // The 4th Sunday in January
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "02/28/2011"); // The 4th Sunday in February


		// Yearly - on the dayofmonth/monthofyear
		// -----------------------------------------
		$rp = new CRecurrencePattern($dbh);
		$rp->type = RECUR_YEARLY;
		$rp->interval = 1;
		$rp->dayOfMonth = 8;
		$rp->monthOfYear = 10;
		$rp->dateStart = "1/1/2011";
		$rp->dateEnd = "1/1/2013";

		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "10/08/2011");
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "10/08/2012");
		$tsNext = $rp->getNextStart();
		$this->assertFalse($tsNext); // Past the dateEnd

		// YearlyNth
		// -----------------------------------------
		$rp = new CRecurrencePattern($dbh);
		$rp->type = RECUR_YEARNTH;
		$rp->interval = 1;
		$rp->instance = 4; // The 4th Sunday of January
		$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_SUNDAY;
		$rp->monthOfYear = 1;
		$rp->dateStart = "1/1/2011";
		$rp->dateEnd = "1/1/2013";

		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/23/2011");
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, "01/22/2012");
		$tsNext = $rp->getNextStart();
		$this->assertFalse($tsNext); // Past the dateEnd
	}

	/**************************************************************************
	 * Function: 	testRecurInternalFunctions
	 *
	 * Purpose:		Test internal functions like save, ischanged, etc...
	 **************************************************************************/
	function testRecurInternalFunctions() 
	{
		$dbh = $this->dbh;

		// Create calendar event for testing
		$obj = new CAntObject($dbh, "calendar_event", null, $this->user);
		$obj->setValue("name", "testRecurInternalFunctions");
		$obj->setValue("ts_start", "1/2/2011 12:00 PM PST");
		$obj->setValue("ts_end", "1/2/2011 12:30 PM PST");
		$eid = $obj->save();

		// Test save & open
		$rp = new CRecurrencePattern($dbh);
		$rp->type = RECUR_WEEKLY;
		$rp->interval = 1;
		$rp->dateStart = "1/1/2011";
		$rp->dateEnd = "1/30/2011";
		$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_SUNDAY;
		$rp->object_type_id = $obj->object_type_id;
		$rp->object_type = $obj->object_type;
		$rp->parentId = $eid;
		$rp->fieldDateStart = "ts_start";
		$rp->fieldTimeStart = "ts_start";
		$rp->fieldDateEnd = "ts_end";
		$rp->fieldTimeEnd = "ts_end";
		$rpid = $rp->save();
		unset($rp);

		// Test open
		$rp = new CRecurrencePattern($dbh, $rpid);
		$this->assertEquals($rp->type, RECUR_WEEKLY);
		$this->assertEquals($rp->interval, 1);
		$this->assertEquals(strtotime($rp->dateStart), strtotime("1/1/2011"));
		$this->assertEquals(strtotime($rp->dateEnd), strtotime("1/30/2011"));

		// Test isChanged with above opened rp
		$this->assertFalse($rp->isChanged());
		$rp->interval = 2;
		$this->assertTrue($rp->isChanged());
		unset($rp);
		
		// Test isChanged with weekdaymask
		$rp = new CRecurrencePattern($dbh, $rpid);
		$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_WEDNESDAY; // Add wednesday
		$this->assertTrue($rp->isChanged());

		// Test delete
		$this->assertTrue($rp->remove());

		$obj->remove();
		$obj->remove();
	}

	/**
	 * @group recur
	 */
	function testRecur() 
	{
		$dbh = $this->dbh;
		
		$obj = new CAntObject($dbh, "task", null, $this->user);
		$obj->setValue("name", "My Recurring Task");
		$obj->setValue("start_date", "1/1/2011");
		$obj->setValue("deadline", "1/1/2011");
		$rp = $obj->getRecurrencePattern();
		$rp->type = RECUR_DAILY;
		$rp->interval = 1;
		$rp->dateStart = "1/1/2011";
		$rp->dateEnd = "3/1/2011";
		$obj->save();

		$this->assertNotNull($rp->id);

		$created = $rp->createInstances("1/3/2011");
		$this->assertEquals($created, 2); // should create two additional objects

		$obj->remove();

		// Make sure recurrence pattern is flagged inactive
		$this->assertEquals($dbh->GetNumberRows($dbh->Query("select * from object_recurrence where id='".$rp->id."' and f_active is false")), 1);

		$obj->remove();

		// Make sure recurrence pattern is purged
		$this->assertEquals($dbh->GetNumberRows($dbh->Query("select * from object_recurrence where id='".$rp->id."'")), 0);


		// Now test using CAntObjectList::checkForRecurrence
		$obj = new CAntObject($dbh, "task", null, $this->user);
		$obj->setValue("name", "My Recurring Task");
		$obj->setValue("start_date", "1/1/2011");
		$obj->setValue("deadline", "1/1/2011");
		$rp = $obj->getRecurrencePattern();
		$rp->type = RECUR_DAILY;
		$rp->interval = 1;
		$rp->dateStart = "1/1/2011";
		$rp->dateEnd = "1/3/2011";
		$oid = $obj->save();

		$this->assertNotNull($rp->id);

		$objList = new CAntObjectList($dbh, "task", $this->user);
		$objList->addCondition("and", "start_date", "is_less", "5/1/2011"); // Should fire create instances
		$objList->getObjects();

		// Make sure there are three recurring tasks created
		$this->assertEquals(3, $dbh->GetNumberRows($dbh->Query("select id from project_tasks where recurrence_pattern='".$rp->id."'")));

		// Cleanup
		$rp->removeSeries();
		$obj->removeHard();

		// Test with an event due to using timestamps rather than dates
		$obj = new CAntObject($dbh, "calendar_event", null, $this->user);
		$obj->setValue("name", "My Recurring Event");
		$obj->setValue("ts_start", "1/5/2011 09:00 AM");
		$obj->setValue("ts_end", "1/5/2011 10:00 AM");
		$rp = $obj->getRecurrencePattern();
		$rp->type = RECUR_WEEKLY;
		$rp->interval = 1;
		$rp->dateStart = "1/5/2011"; // First Wednesday
		$rp->dateEnd = "1/19/2011"; // Third Wednesday
		$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_WEDNESDAY;
		$obj->save();

		$this->assertNotNull($rp->id);

		$objList = new CAntObjectList($dbh, "calendar_event", $this->user);
		$objList->addCondition("and", "ts_start", "is_greater_or_equal", "1/1/2011"); // Should fire create instances
		$objList->addCondition("and", "ts_start", "is_less_or_equal", "5/1/2011"); // Should fire create instances
		$objList->getObjects();

		// Make sure there are three recurring tasks created
		$this->assertEquals(3, $dbh->GetNumberRows($dbh->Query("select id from calendar_events where recurrence_pattern='".$rp->id."'")));

		// Cleanup
		$rp->removeSeries();
		$obj->removeHard();
	}
	
	/**************************************************************************
	 * Function: 	testRevisionHistory
	 *
	 * Purpose:		Test object revision history
	 **************************************************************************/
	function testRevisionHistory() 
	{
		$dbh = $this->dbh;
		
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "My First Revision");
		$oid = $obj->save();

		$revid = $dbh->GetValue($dbh->Query("select id from object_revisions where object_id='$oid' and object_type_id='".$obj->object_type_id."'"));
		$this->assertTrue($revid > 0 && $revid !=null);
		//$this->assertEquals($dbh->GetValue($dbh->Query("select field_value from object_recurrence where id='".$rp->id."'")), 0);
	}


	/**
	 * Test the addition of fields when use_when is defined
	 */
	public function testFields()
	{
		$dbh = $this->dbh;
		
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$fdef = array(
			'title'=>'unittest_custom', 
			'type'=>'text', 
			'subtype'=>'32', 
			'system'=>false, 
			'use_when'=>"owner_id:".$this->user->id
		);
		$newName = $obj->addField("unittest_custom", $fdef);
		unset($obj);

		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$field = $obj->def->getField('unittest_custom_owner_id_minus1');
		$this->assertNotEquals($field, false);
		$this->assertEquals($field->getUseWhen(), "owner_id:".$this->user->id);
		$obj->removeField("unittest_custom_owner_id_minus1");
		$field = $obj->def->getField('unittest_custom_owner_id_minus1');
		$this->assertTrue(empty($field));
	}
	
	/**************************************************************************
	 * Function: 	testGetUIML
	 *
	 * Purpose:		Test retrieving forms of different type
	 **************************************************************************/
	function testGetUIML()
	{
		$dbh = $this->dbh;
		
		// Test retrieving default, user, and team forms
		$this->user = new AntUser($this->dbh, -1);
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$ret = $obj->getUIML($this->user, null);
		$this->assertNotEquals($ret, "");
		unset($obj);
		
		$this->user = new AntUser($this->dbh, 15);
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$ret = $obj->getUIML($this->user, null);
		$this->assertNotEquals($ret, "");
		unset($obj);
		
		$this->user = new AntUser($this->dbh, 14);
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$ret = $obj->getUIML($this->user, null);
		$this->assertNotEquals($ret, "");
		unset($obj);
		
		$this->user = new AntUser($this->dbh, 15);
		$obj = new CAntObject($dbh, "task", null, $this->user);
		$ret = $obj->getUIML($this->user, null);
		$this->assertNotEquals($ret, "");
		unset($obj);
		
		$this->user = new AntUser($this->dbh, -1);
		$obj = new CAntObject($dbh, "user", null, $this->user);
		$ret = $obj->getUIML($this->user, null);
		$this->assertNotEquals($ret, "");
		unset($obj);
		
		// Test retrieving mobile forms
		$this->user = new AntUser($this->dbh, -1);
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$ret = $obj->getUIML($this->user, 1);
		$this->assertNotEquals($ret, "");
		unset($obj);
		
		$this->user = new AntUser($this->dbh, -1);
		$obj = new CAntObject($dbh, "task", null, $this->user);
		$ret = $obj->getUIML($this->user, 1);
		$this->assertNotEquals($ret, "");
		unset($obj);
		
		$this->user = new AntUser($this->dbh, -1);
		$obj = new CAntObject($dbh, "user", null, $this->user);
		$ret = $obj->getUIML($this->user, 1);
		$this->assertNotEquals($ret, "");
		unset($obj);
	}

	/**************************************************************************
	 * Function: 	testGroupings
	 *
	 * Purpose:		Test generic grouping functionality
	 **************************************************************************/
	function testGroupings()
	{
		$dbh = $this->dbh;

		$cust = new CAntObject($dbh, "customer", null, $this->user);

		// Test add new category
		$data = $cust->addGroupingEntry("groups", "Unit Test Group", "e3e3e3");
		$this->assertTrue(is_array($data));
		$this->assertTrue($data['id'] > 0);
		$eid = $data['id'];

		// Test get grouping data

		// Test update color
		$ret = $cust->updateGroupingEntry("groups", $eid, null, "cccccc");
		$this->assertTrue($ret);
		$this->assertEquals($this->dbh->GetValue($this->dbh->Query("select color from customer_labels where id='$eid'"), 0, "color"), "cccccc");
		
		// Test delete
		$ret = $cust->deleteGroupingEntry("groups", $eid);
		$this->assertTrue($ret);
	}

	/**
	 * Test the object functionality that will return data as an array
	 */
	function testGetDataArray()
	{
		$dbh = $this->dbh;

		$cust = new CAntObject($dbh, "customer", null, $this->user);
		$cust->setValue("first_name", "UnitTest-testGetDataArray");
		$grp = $cust->addGroupingEntry("groups", "Unit Test Group", "e3e3e3");
		$cust->setMValue("groups", $grp['id']);

		$cust->debug = true;
		$data = $cust->getDataArray();

		$this->assertEquals($data['first_name'], "UnitTest-testGetDataArray");
		$this->assertEquals($data['groups_fval'][$grp['id']], "Unit Test Group");

		// Test delete
		$ret = $cust->deleteGroupingEntry("groups", $grp['id']);
		$this->assertTrue($ret);
	}

	/**
	 * Test the object_groupings table functionality (where all groupings go into one table)
	 */
	function testGroupingGen()
	{
		// "project_story" does not exist anymore in app_object_types
		return; 
		$dbh = $this->dbh;

		$cust = new CAntObject($dbh, "project_story", null, $this->user);
		$cust->setValue("name", "UnitTest-testGrouping");

		// Priority
		$grp = $cust->addGroupingEntry("priority_id", "Unit Test Group", "e3e3e3");
		$cust->setMValue("priority_id", $grp['id']);
		$data = $cust->getDataArray();
		$this->assertEquals($data['name'], "UnitTest-testGrouping");
		$this->assertEquals($data['priority_id_mvfval'][$grp['id']], "Unit Test Group");

		// Type
		$grp2 = $cust->addGroupingEntry("type_id", "Unit Test Group", "e3e3e3");
		$cust->setMValue("type_id", $grp2['id']);
		$data = $cust->getDataArray();
		$this->assertEquals($data['name'], "UnitTest-testGrouping");
		$this->assertEquals($data['type_id_mvfval'][$grp2['id']], "Unit Test Group");

		// Test delete
		$ret = $cust->deleteGroupingEntry("priority_id", $grp['id']);
		$this->assertTrue($ret);

		$ret = $cust->deleteGroupingEntry("type_id", $grp2['id']);
		$this->assertTrue($ret);

		// Cleanup
		$cust->removeHard();
	}
	
	/**
	 * Test objects_moved functionality
	 */
	public function testMovedTo()
	{
		$dbh = $this->dbh;

		// Create a test object
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "My First Object");
		$oid = $obj->save();

		// Create a second test object just for the id
		$obj2 = new CAntObject($dbh, "customer", null, $this->user);
		$obj2->setValue("name", "My Second Object");
		$oid2 = $obj2->save();

		// Insert into merged/moved log
		$obj2->setMovedTo($oid);
		$obj2->removeHard(); // Purge completely

		// Now load object with purged id and it should load object one because of the log
		$obj3 = new CAntObject($dbh, "customer", $oid2);

		// Should load the first object
		$this->assertEquals($obj3->id, $obj->id);
		$this->assertEquals($obj3->getValue("name"), $obj->getValue("name"));

		// Cleanup -- only one object is left
		$dbh->Query("DELETE FROM objects_moved WHERE object_type_id='".$obj->object_type_id."' AND object_id='$oid'");
		$obj->removeHard();
	}

	/**
	 * Test revision data
	 *
	 * @group testRevisionData
	 */
	public function testRevisionData()
	{
		$dbh = $this->dbh;

		// Create a test object
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "Rev1 Test");
		$oid = $obj->save();
		$rev1 = $obj->revision;

		// Make change
		$obj = new CAntObject($dbh, "customer", $oid, $this->user);
		$obj->setValue("name", "Rev2 Test");
		$oid = $obj->save();
		$rev2 = $obj->revision;

		// Get revision data
		$data = $obj->getRevisionData();
		$this->assertEquals($data[$rev1]['name'], "Rev1 Test");
		$this->assertEquals($data[count($data)]['name'], "Rev2 Test");
	}

	/**
	 * Test factory method used for loading objects
	 */
	public function testFactory()
	{
		$obj = CAntObject::factory($this->dbh, "calendar_event");
		$this->assertTrue($obj instanceof CAntObject_CalendarEvent);
	}

	/**
	 * Benchmark and test AntObject classes and functions
	 *
	function testObjectPerformance()
	{
		$num = 100; // number of objects to test
		$dbh = $this->dbh;
		$objects = array();
		$oids = array();

		// First test creation of $num objects
		$start = microtime(true);
		for($i = 0; $i < $num; $i++)
		{
			$cust = new CAntObject($dbh, "customer", null, $this->user);
			$cust->setValue("first_name", "UnitTestPerformance");
			$cust->setValue("last_name", "UnitTestPerformanceLast");
			$oids[] = $cust->save(false); // Test save without logging the activity
		}
		$end = microtime(true);
		$this->assertTrue(($end-$start)<$num); // Must be faster than 1 second per object

		// Test object list performance
		// --------------------------------------------
		

		// Now continue testing object performance
		// --------------------------------------------

		// Test opening the objects
		$start = microtime(true);
		foreach ($oids as $oid)
		{
			$objects[] = new CAntObject($dbh, "customer", $oid, $this->user);
		}
		$end = microtime(true);
		$this->assertTrue(($end-$start)<($num/10)); // Must be faster than .10 second per object

		// Test remove hard and cleanup
		$start = microtime(true);
		foreach ($objects as $obj)
		{
			$obj->removeHard();
		}
		$end = microtime(true);
		$this->assertTrue(($end-$start)<($num/10)); // Must be faster than .10 second per object
	}
	 */

	/**
	 * Test deafult user-level groupings
	 *
	 * @group testVerifyDefaultGroupings
	 */
	public function testVerifyDefaultGroupings()
	{
		$dbh = $this->dbh;
		$emailObj = CAntObject::factory($dbh, "email_thread", null, $this->user);            
		
		$defaultGroups = array();
		// Manually get the mailboxes from table
		$query = "select * from email_mailboxes where user_id = -1 and f_system = 't'";
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			
			$defaultGroups[] = array("title" => $row["name"], "system" => true);
		}
		
		// Lets check if default mailboxes are already been created.
		$result = $emailObj->verifyDefaultGroupings("mailbox_id", $defaultGroups);
		
		// The result should be 4, since there are 4 default mailboxes for each user
		$this->assertTrue(sizeof($result) >= 4);
	}

	/**
	 * Test obj reference cache
	 */
	function testObjReference() 
	{
		$dbh = $this->dbh;
        
		// Create an object to reference
		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$obj->setValue("name", "testGenSave");
		$oid = $obj->save(false);

		// Create an activity with the reference
		$objAct = new CAntObject($dbh, "activity", null, $this->user);
		$objAct->setValue("obj_reference", "customer:$oid");
		$aid = $objAct->save(false);
		$this->assertTrue($aid > 0 );
		unset($objAct);

		// Close and then open again
		$objAct = new CAntObject($dbh, "activity", $aid, $this->user);
		$this->assertEquals($objAct->getValue("obj_reference"), "customer:$oid");
		unset($objAct);

		// Test with the list cache
		$list = new CAntObjectList($dbh, "activity", $this->user);
		$list->addCondition("and", "id", "is_equal", $aid);
		$list->getObjects();
		$this->assertEquals($list->getNumObjects(), 1);
		$objAct = $list->getObject(0);
		$this->assertEquals($objAct->getValue("obj_reference"), "customer:$oid");
        
		// Cleanup
		$obj->removeHard();
		$objAct->removeHard();
	}

	/**
	 * Test the recently viewed array
	 *
	 * @group setViewed
	 */
	public function testSetViewed()
	{
		$dbh = $this->dbh;

		$cust = new CAntObject($dbh, "customer", null, $this->user);
		$cust->setValue("first_name", "UnitTest-testSetViewed");
		$oid = $cust->save();

		$cust->setViewed();

		$cval = $cust->cache->get($this->dbh->dbname . "/RecentObjects/" . $this->user->id);
		$this->assertEquals($cval[0], "customer:$oid");
		$prev = count($cval);

		// Make sure marking the same object does not enter a new item
		$cust->setViewed();
		$cval = $cust->cache->get($this->dbh->dbname . "/RecentObjects/" . $this->user->id);
		$this->assertEquals($prev, count($cval));

		// Cleanup
		$cust->removeHard();
	}

	/**
	 * Test getting an object by a hierarchy path
	 *
	 * @group testLoadByPath
	 */
	public function testLoadByPath()
	{
		// Cleanup just in case the first level already exists
		$objDef = CAntObject::factory($this->dbh, "project", null, $this->user);
		$obj = $objDef->loadByName("testLoadByPathLevel1");
		if ($obj && $obj->id)
			$obj->removeHard();
		$obj = $objDef->loadByPath("/testLoadByPathLevel1/Level2");
		if ($obj && $obj->id)
			$obj->removeHard();

		// Create parent project
		$obj1 = CAntObject::factory($this->dbh, "project", null, $this->user);
		$obj1->setValue("name", "testLoadByPathLevel1");
		$obj1->save();

		// Create child project
		$obj2 = CAntObject::factory($this->dbh, "project", null, $this->user);
		$obj2->setValue("name", "Level2");
		$obj2->setValue("parent", $obj1->id);
		$obj2->save();

		// Now load by path name
		$obj3 = $obj1->loadByPath("/testLoadByPathLevel1/Level2");
		$this->assertEquals($obj3->id, $obj2->id);

		// Cleanup
		$obj2->removeHard();
		$obj1->removeHard();
	}

	/**
	 * Test proces temp files
	 *
	 * @group testProcessTempFiles
	 */
	public function testProcessTempFiles()
	{
		// Cannot test since there is no test server for ANS
		return;
		
		$antfs = new AntFs($this->dbh, $this->user);

		// Create temp file
		$fldr = $antfs->openFolder("%tmp%", true);
		$file = $fldr->openFile("test", true);
		$size = $file->write("test contents");
		$this->assertNotNull($file);

		// Crate a new post and put it in the feed namespace by setting the field
		$obj = CAntObject::factory($this->dbh, "content_feed_post", null, $this->user);
		$obj->setValue("title", "verify processTempFiles");
		$obj->setValue("image", $file->id); // Save a temp file
		$obj->save();

		// The file should have moved
		$fileOpened = $antfs->openFileById($file->id);
		$this->assertFalse($fileOpened->isTemp());
		$this->assertNotEquals($fileOpened->getValue("folder_id"), $fldr->id);
		unset($fileOpened);

		// Cleanup
		$file->removeHard();
		$obj->removeHard();

		// ------------------------------------------
		// Make sure we don't move non-temp files
		// ------------------------------------------

		// Create a non-temp
		$fldr = $antfs->openFolder("/test", true);
		$file = $fldr->openFile("test", true);
		$size = $file->write("test contents");
		$this->assertNotNull($file);

		// Crate a new post and put it in the feed namespace by setting the field
		$obj = CAntObject::factory($this->dbh, "content_feed_post", null, $this->user);
		$obj->setValue("title", "verify processTempFiles");
		$obj->setValue("image", $file->id); // Save a temp file
		$obj->save();

		// The file should not have moved
		$fileOpened = $antfs->openFileById($file->id);
		$this->assertEquals($fileOpened->getValue("folder_id"), $fldr->id);
		unset($fileOpened);

		// Cleanup
		$file->removeHard();
		$obj->removeHard();
	}

	/**
	 * Test clone
	 */
	public function testCloneObject()
	{
		$obj1 = CAntObject::factory($this->dbh, "task", null, $this->user);
		$obj1->setValue("name", "clone test");
		$obj1->save();

		$obj2 = $obj1->cloneObject();
		$this->assertEquals($obj2->getValue("name"), $obj1->getValue("name"));
		$this->assertNotEquals($obj2->id, $obj1->id);

		// TODO: test other field types

		// Cleanup
		$obj1->removeHard();
		$obj2->removeHard();
	}

	/**
	 * Test de-referencing object values
	 *
	 * @group testGetValueDeref
	 */
	public function testGetValueDeref()
	{
		$obj = CAntObject::factory($this->dbh, "task", null, $this->user);
		$obj->setValue("user_id", $this->user->id);
		$obj->save();

		$this->assertEquals($this->user->getValue("name"), $obj->getValueDeref("user_id.name"));

		// Cleanup
		$obj->removeHard();
	}

    /**
     * Test getting followers
     */
    public function testGetFollowers()
    {
		$obj = CAntObject::factory($this->dbh, "task", null, $this->user);
		$obj->setValue("user_id", $this->user->id);
		$obj->save();

        // Test to see if user is in followers
        $this->assertTrue(in_array("user:" . $this->user->id, $obj->getFollowers()));

		// Cleanup
		$obj->removeHard();
    }
}
