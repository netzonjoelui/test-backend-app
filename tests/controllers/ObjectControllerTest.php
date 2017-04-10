<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/Controller.php');
require_once(dirname(__FILE__).'/../../controllers/ObjectController.php');

class ObjectControllerTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;
    var $backend = null;

    function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
    }
    
    function tearDown() 
    {
    }
    
    /*function getTests()
    {        
        return array("testGetGroupings");
    }*/
    
    /**
     * Test ANT Controller - saveObject($params)
     */
    function testSaveObject()
    {        
        $params['obj_type'] = "calendar_event_proposal";
        
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        $oid = $objController->saveObject($params);
        $this->assertTrue($oid > 0);
        
        // Cleanup
        $params['oid'] = $oid;
        $objController->deleteObject($params);
    }
    
    /**
     * Test ANT Controller - deleteObject($params)
     */
    function testDeleteObject()
    {        
        $params['obj_type'] = "calendar_event_proposal";
        
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $oid = $objController->saveObject($params);
        $this->assertTrue($oid > 0);
        
        // Cleanup
        $params['oid'] = $oid;
        $ret = $objController->deleteObject($params);
        $this->assertTrue($ret);
    }
    
    /**
     * Test ANT Controller - saveForm($params)
     */
    function testSaveForm()
    {        
        $params['obj_type'] = "calendar_event_proposal";
        $params['default'] = 1;
        $params['mobile'] = '222-2222222';
        
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $ret = $objController->saveForm($params);
        $this->assertTrue($ret);
        
        // Cleanup        
        $objController->deleteForm($params);
    }
    
    /**
     * Test ANT Controller - deleteForm($params)
     */
    function testDeleteForm()
    {        
        $params['obj_type'] = "calendar_event_proposal";
        $params['default'] = 1;
        $params['mobile'] = '222-2222222';
        
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $ret = $objController->saveForm($params);
        $this->assertEquals($ret, true);        
        
        // Cleanup        
        $objController->deleteForm($params);
        $this->assertTrue($ret);
    }
    
    /**
     * Test ANT Controller - getForms($params)
     */
    function testGetForms()
    {
        $params['obj_type'] = "activity";        
        
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $ret = $objController->getForms($params);
        $this->assertTrue(is_array($ret));
    }
       
    /**
     * Test ANT Controller - getObjects()
     */
    function testGetObjects()
    {
        $params['obj_type'] = "customer";
        
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $ret = $objController->getObjects();
        $this->assertTrue(sizeof($ret) > 0);
    }
    
    /**
     * Test ANT Controller - editObjects()
     * TODO
     */
    function testEditObjects()
    {
        // TODO
    }
    
    /**
     * Test ANT Controller - undeleteObject($params)
     */
    function testUndeleteObject()
    {
        //$params['obj_type'] = "calendar_event_proposal";
        $params['oid'] = 1;
        
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $ret = $objController->undeleteObject($params);         
        $this->assertTrue($ret > 0);
        
    }
    
    /**
     * Test ANT Controller - getPlugins($params)
     */
    function testGetPlugins()
    {
        $params['obj_type'] = "calendar_event_proposal";        
        
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $ret = $objController->getPlugins($params);
        $this->assertTrue(count($ret) > 0);
    }
    
    /**
     * Test ANT Controller - saveView($params)
     */
    function testSaveView()
    {
        $params['obj_type'] = "calendar_event_proposal";        
        $params['name'] = "UnitTest View";        
        
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $vid = $objController->saveView($params);
        $this->assertTrue($vid > 0);
        
        //Cleanup
        $params['dvid'] = $vid;
        $objController->deleteView($params);
    }
    
    /**
     * Test ANT Controller - deleteView($params)
     */
    function testDeleteView()
    {
        $params['obj_type'] = "calendar_event_proposal";        
        $params['name'] = "UnitTest View";        
        
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $vid = $objController->saveView($params);
        $this->assertTrue($vid > 0);
        
        //Cleanup
        $params['dvid'] = $vid;
        $ret = $objController->deleteView($params);
        $dvid = $this->assertTrue($ret > 0);
    }
    
    /**
     * Test ANT Controller - setViewDefault($params)
     */
    function testSetViewDefault()
    {
        $params['obj_type'] = "calendar_event_proposal";        
        $params['name'] = "UnitTest View";        
        
        // Create view
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $vid = $objController->saveView($params);
        $this->assertTrue($vid > 0);
        
        // Set view default
        $params['view_id'] = $vid;
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $ret = $objController->setViewDefault($params);
        $this->assertTrue($ret > 0);
        
        //Cleanup
        $params['dvid'] = $vid;
        $ret = $objController->deleteView($params);
        $dvid = $this->assertTrue($ret > 0);    
    }
    
    /**
     * Test ANT Controller - mergeObjects($params)
     * TODO
     */
    function testMergeObjects()
    {
        // TODO
    }
    
    /**
     * Test ANT Controller - getFkeyValName($params)     
     */
    function getFkeyValName()
    {
        $params['obj_type'] = "calendar_event_proposal";
        
        // create new calendar event proposal
        $obj = new CAntObject($this->dbh, $params['obj_type'], null, $this->user);
        $obj->setValue("name", "UnitTest CEP");
        $cepid = $obj->save();

        $params['id'] = $cepid;
        $params['field'] = "event_id";
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $ret = $objController->getFkeyValName($params);
        $this->assertTrue(count($ret) > 0);
        
        $params['field'] = "user_id";
        $ret = $objController->getFkeyValName($params);
        $this->assertEquals($ret, -1);
        
        // cleanup
        $obj->removeHard();
    }
    
    /**
     * Test ANT Controller - getFkeyDefault($params)     
     */
    function testGetFkeyDefault()
    {
        $params['obj_type'] = "calendar_event_proposal";
        $params['field'] = "event_id";
        
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $ret = $objController->getFkeyDefault($params);
        $this->assertTrue(count($ret) > 0);
    }
    
    /**
     * Test ANT Controller - getObjName($params)     
     */
    function testGetObjName()
    {
        $params['obj_type'] = "calendar_event_proposal";
        
        // create new calendar event proposal
        $obj = new CAntObject($this->dbh, $params['obj_type'], null, $this->user);
        $obj->setValue("name", "UnitTest CEP");
        $cepid = $obj->save();
        
        $params['id'] = $cepid;
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $ret = $objController->getObjName($params);
        $this->assertEquals($ret, $obj->getValue("name"));
        
        // cleanup
        $obj->removeHard();
    }
    
    /**
     * Test ANT Controller - associationAdd($params)     
     * TODO
     */
    function testAssociationAdd()
    {
        // TODO
    }
    
    /**
     * Test ANT Controller - getFolderId($params)          
     */
    function testGetFolderId()
    {
        $params['obj_type'] = "calendar_event_proposal";
        $params['field'] = "id";
        
        // create new calendar event proposal
        $obj = new CAntObject($this->dbh, $params['obj_type'], null, $this->user);
        $obj->setValue("name", "UnitTest CEP");
        $cepid = $obj->save();
        
        $params['oid'] = $cepid;
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $ret = $objController->getFolderId($params);
        $this->assertTrue($ret > 0);
        
        // cleanup
        $obj->removeHard();
    }
    
    /**
     * Test ANT Controller - getActivityTypes($params)          
     */
    function testGetActivityTypes()
    {
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $params = array();
        $ret = $objController->getActivityTypes($params);
        $this->assertTrue(count($ret) > 0);
    }
    
    /**
     * Test ANT Controller - saveActivityType($params)          
     */
    function testSaveActivityType()
    {
        $params['obj_type'] = "calendar_event_proposal";
        $params['name'] = "UnitTest ActivityType";
        
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        $atid = $objController->saveActivityType($params);
        $this->assertTrue($atid > 0);
        
        // cleanup
        $obj = new CAntObject($this->dbh, $params['obj_type'], $atid, $this->user);
        $obj->removeHard();
    }
    
    
    
    /**
     * Test ANT Controller - saveRecurrencepattern($params)
     * TODO
     */
    function saveRecurrencepattern()
    {
        // TODO
    }

    /**
     * Test moving from one grouping to another
     */
    function testMoveByGrouping()
    {
		$dbh = $this->dbh;

		// Test email messages
		// ------------------------------------------
        $eml = CAntObject::factory($this->dbh, "email_message", null, $this->user);
        $inboxid = $eml->getGroupId("Inbox");
        $junkid = $eml->getGroupId("Junk Mail");
        $sentid = $eml->getGroupId("Sent");
        $trash = $eml->getGroupId("Trash");

		// Test deletion by moving to trash
		$eml->setValue("subject", "test");
		$eml->setValue("mailbox_id", $inboxid["id"]);
		$mid = $eml->save();
		$this->assertNotNull($mid);

		$params = array();
		$params['obj_type'] = "email_message";
		$params['field_name'] = "mailbox_id";
		$params['move_from'] = $inboxid;
		$params['move_to'] = $trash;
		$params['objects'][] = $mid;
		$objController = new ObjectController($this->ant, $this->user);
		$objController->debug = true;
		$ret = $objController->moveByGrouping($params);		
		$this->assertTrue($ret);
		
		$eml->removeHard(); // Soft

		// Test moving - no delete
		$eml = new CAntObject($dbh, "email_message", null, $this->user);
		$eml->setValue("subject", "test");
		$eml->setValue("mailbox_id", $inboxid);
		$mid = $eml->save();
		$this->assertNotNull($mid);

		// Test controller
		$params = array();
		$params['obj_type'] = "email_message";
		$params['field_name'] = "mailbox_id";
		$params['move_from'] =$inboxid;
		$params['move_to'] = $junkid;
		$params['objects'][] = $mid;
		$objController = new ObjectController($this->ant, $this->user);
		$objController->debug = true;
		$ret = $objController->moveByGrouping($params);		
		$this->assertTrue($ret);
		unset($eml);
		
		$eml = new CAntObject($this->dbh, "email_message", $mid, $this->user);        
		$eml->removeHard(); // Soft

		// Test other objects
		// ------------------------------------------
		$cust = new CAntObject($this->dbh, "customer", null, $this->user);
		$data = $cust->addGroupingEntry("groups", "Unit Test Group 1", "e3e3e3");
		$eid1 = $data['id'];
		$data = $cust->addGroupingEntry("groups", "Unit Test Group 2", "e3e3e3");
		$eid2 = $data['id'];

		$cust->setValue("name", "A unit test - testMoveByGrouping");
		$cust->setMValue("groups", $eid1);
		$cid = $cust->save();
			
		// Test controller
		$params = array();
		$params['obj_type'] = "customer";
		$params['field_name'] = "groups";
		$params['move_from'] =$eid1;
		$params['move_to'] = $eid2;
		$params['objects'][] = $cid;
		$objController = new ObjectController($this->ant, $this->user);
		$objController->debug = true;
		$objController->moveByGrouping($params);
		$ret = json_decode($objController->debugOutputBuf);
		$this->assertTrue($ret > 0);
		unset($cust);

		$cust = new CAntObject($this->dbh, "customer", $cid, $this->user);
		$vals = $cust->getValue("groups");        
		$this->assertTrue(is_array($vals));
		$this->assertEquals($vals[0], $eid2);

		// cleanup
		$ret = $cust->deleteGroupingEntry("groups", $eid1);
		$ret = $cust->deleteGroupingEntry("groups", $eid2);
		$cust->removeHard();
	}
    
    function testGetGroupings()
    {
        // Instantiate Object Controller
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
        
        // Create test data
        $params = array("obj_type" => "customer", "field" => "groups", "title" => "testGroups");
        $result = $objController->createGrouping($params);
        $gid = $result['id'];
        $this->assertTrue($gid > 0);
        $this->assertEquals($result['title'], $params['title']);
        unset($result);
        
        // Test getGroupings without filter
        $params = array("obj_type" => "customer", "field" => "groups");
        $result = $objController->getGroupings($params);
        $this->assertTrue(count($result) > 0);
        unset($result);
        
        // Test getGroupings with filter                
        // Need to set apim and auth, so we can replicate the filter from api
        $params = array("obj_type" => "customer", "field" => "groups", "id" => $gid);
        $params['apim'] = "php";
        $params['auth'] = "UnitTestAuthenticationTest";
        $result = $objController->getGroupings($params);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]["id"], $gid);
        
        // Clean Data
        $params = array("obj_type" => "customer", "field" => "groups", "gid" => $gid);
        $objController->deleteGrouping($params);
    }

	/**
	 * Test get recently opened objects
	 *
	 * @group getRecent
	 */
	public function testGetRecent()
	{
		// Create an object and set it as viewed
		$cust = new CAntObject($this->dbh, "customer", null, $this->user);
		$cust->setValue("first_name", "UnitTest-testSetViewed");
		$oid = $cust->save();
		$cust->setViewed();

        // Instantiate Object Controller
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
		$recent = $objController->getRecent(array());
		$this->assertEquals($recent[0]['obj_type'], "customer");
		$this->assertEquals($recent[0]['id'], $oid);

		// Cleanup
		$cust->removeHard();
	}

	/**
	 * Test mark seen
	 */
	public function testMarkSeen()
	{
		// Create an object and set it as viewed
		$cust = new CAntObject($this->dbh, "customer", null, $this->user);
		$cust->setValue("first_name", "UnitTest-testMarkSeen");
		$oid = $cust->save();
		$cust->setViewed();

		// Add notification
		$notification = CAntObject::factory($this->dbh, "notification", null, $this->user);
		$notification->setValue("name", "testMarkSeen");
		$notification->setValue("description", "test");
		$notification->setValue("obj_reference", "customer:$oid");
		$notification->setValue("f_popup", 'f');
		$notification->setValue("f_seen", 'f');
		$notification->setValue("owner_id", $this->user->id);
		$nid = $notification->save();

        // Instantiate Object Controller
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
		$objController->markSeen(array("oid"=>$oid, "obj_type"=>"customer"));

		// Test to make sure notification was marked as seen
		$testNotif = AntObjectLoader::getInstance($this->dbh)->byId("notification", $nid);
		$this->assertEquals($testNotif->getValue("f_seen"), 't');

		// Cleanup
		$cust->removeHard();
		$notification->removeHard();
	}

	/**
	 * Test importRun
	 */
	public function testImportRun()
	{
		// First cleanup
		$olist = new CAntObjectList($this->dbh, "customer", $this->user);
		$olist->addCondition("and", "first_name", "begins_with", "UTIMP_");
		$olist->getObjects();
		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$obj = $olist->getObject($i);
			$obj->removeHard();
		}

		// Setup importer job like js wizard would
		$params = array(
			'import_data' => json_encode(array(
				"obj_type" => 'customer',
				'data_file' => str_replace("\\", "/", dirname(__FILE__)) . '/../data/imp_cust.csv',
				'map_fields' => array(
					0 => "first_name",
					1 => "last_name",
					2 => "nick_name",
				),
			)),
		);

		// Instantiate Object Controller and invoke importRun
        $objController = new ObjectController($this->ant, $this->user);
        $objController->debug = true;
		$ret = $objController->importRun($params);

		// Query imported objects
		$olist = new CAntObjectList($this->dbh, "customer", $this->user);
		$olist->addCondition("and", "first_name", "begins_with", "UTIMP_");
		$olist->getObjects();

		// Test to make sure we imported 2
		$this->assertEquals(2, $olist->getNumObjects());

		// Now cleanup import
		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$obj = $olist->getObject($i);
			$obj->removeHard();
		}
	}
}
