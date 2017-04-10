<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../controllers/ObjectSyncController.php');

class ObjectSyncControllerTest extends PHPUnit_Framework_TestCase
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
    
    /**
     * Test ObjectList::query action
     */
    public function testGetChangedObjects()
    {        
		$pid = "ObjectSyncControllerTest::testGetChangedObjects";
		$partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$coll = $partner->addCollection("customer");
		$partner->save();

		// Create customer just in case there are none already in the database
		$obj = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$obj->setValue("name", "testGetChangedObjects");
		$cid = $obj->save();

		// Synchronize objects - first run should put all existing objects into the stat table
        $objController = new ObjectSyncController($this->ant, $this->user);
		$objController->debug = true;
        $changes = $objController->getChangedObjects(array("partner_id"=>$pid, "obj_type"=>"customer"));
		$this->assertTrue(is_array($changes));
		$this->assertTrue(count($changes) > 0);

		// Now reset all stats
		$coll->resetStats();
		$cid = $obj->save(); // Should add to device stat to sync
        $changes = $objController->getChangedObjects(array("partner_id"=>$pid, "obj_type"=>"customer"));
		$this->assertEquals(1, count($changes));
		$this->assertEquals($changes[0]['id'], $cid);
		$this->assertEquals($changes[0]['action'], 'change');

		// Cleanup
		$obj->removeHard();
		$partner->remove();
    }

    /**
     * Test ObjectList::query action
     */
    public function testGetChangedObjectsXml()
    {        
		$pid = "ObjectSyncControllerTest::testGetChangedObjects";
		$partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$coll = $partner->addCollection("customer");
		$partner->save();

		// Create customer just in case there are none already in the database
		$obj = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$obj->setValue("name", "testGetChangedObjects");
		$cid = $obj->save();

		// Synchronize objects - first run should put all existing objects into the stat table
        $objController = new ObjectSyncController($this->ant, $this->user);
		$objController->debug = true;
        $ret = $objController->getChangedObjects(array("partner_id"=>$pid, "obj_type"=>"customer", "output"=>"xml"));
		$xml = simplexml_load_string($ret);
		$this->assertEquals("change", (string)$xml->item[0]->action);

		// Cleanup
		$obj->removeHard();
		$partner->remove();
    }

    /**
     * Test ObjectList::query action
     */
    public function testCreatePartnership()
	{
		// Synchronize objects - first run should put all existing objects into the stat table
        $objController = new ObjectSyncController($this->ant, $this->user);
		$objController->debug = true;
        $pid = $objController->createPartnership(array());
		$this->assertNotEquals($pid, null);
		$this->assertTrue(strlen($pid) > 0);

		// Cleanup
		$partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$partner->remove();
	}

	/**
     * Test collectionChangesExist action
     */
    public function testCollectionChangesExist()
    {        
		$pid = "ObjectSyncControllerTest::collectionChangesExist";

		// Cleanup - if already exists
		$partn = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$partn->remove();

		// Create partner and collection
		$partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$coll = $partner->addCollection("customer");
		$coll->fInitialized = true;
		$coll->save();
		$partner->save();

        $objController = new ObjectSyncController($this->ant, $this->user);
		$objController->debug = true;

		// Create customer which will add to the stat
		$obj = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$obj->setValue("name", "collectionChangesExist");
		$cid = $obj->save();

		// Synchronize objects - first run should put all existing objects into the stat table
        //$objController->getChangedObjects(array("partner_id"=>$pid, "obj_type"=>"customer"));
        $changes = $objController->collectionChangesExist(array("partner_id"=>$pid, "obj_type"=>"customer"));
		$this->assertTrue($changes);

		// Now reset all stats
		$coll->resetStats();
        $changes = $objController->collectionChangesExist(array("partner_id"=>$pid, "obj_type"=>"customer"));
		$this->assertFalse($changes);

		// Cleanup
		$obj->removeHard();
		$partner->remove();
    }
}
