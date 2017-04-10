<?php

require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');    
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/Olap.php');	
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');    
require_once(dirname(__FILE__).'/../../controllers/CustomerController.php');
require_once(dirname(__FILE__).'/../../controllers/ObjectController.php');
require_once(dirname(__FILE__).'/../../lib/aereus.lib.php/antapi.php');        

class AntApi_ObjectStoreTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;
	var $ant = null;

	/**
	 * The url of the ANT sever (usually set to localhost)
	 *
	 * @var string
	 */
	public $antServer = "";

	/**
	 * User to be used when connecting to ant
	 *
	 * @var string
	 */
	public $antUser = "";

	/**
	 * password to be used when connecting to ant
	 *
	 * @var string
	 */
	public $antPass = "";

	/**
	 * Setup unit tests
	 */
	public function setUp() 
	{
		// Elastic local store
		if (AntConfig::getInstance()->object_index['host'] && AntConfig::getInstance()->object_index['type'] == "elastic")
		{
			global $ANTAPI_STORE_ELASTIC_IDX, $ANTAPI_STORE_ELASTIC_HOST;

			$ANTAPI_STORE_ELASTIC_IDX = "tmp_ant_uni_test";
			$ANTAPI_STORE_ELASTIC_HOST = AntConfig::getInstance()->db['host'];
		}

		// PGSQL local store
		if (AntConfig::getInstance()->db['host'] && AntConfig::getInstance()->db['type'] == "pgsql")
		{
			global $ANTAPI_STORE_PGSQL_HOST, $ANTAPI_STORE_PGSQL_DBNAME, $ANTAPI_STORE_PGSQL_USER, $ANTAPI_STORE_PGSQL_PASSWORD;

			$ANTAPI_STORE_PGSQL_HOST = AntConfig::getInstance()->db['host'];
			$ANTAPI_STORE_PGSQL_DBNAME = "tmp_ant_uni_test";
			$ANTAPI_STORE_PGSQL_USER = AntConfig::getInstance()->db['user'];
			$ANTAPI_STORE_PGSQL_PASSWORD = AntConfig::getInstance()->db['password'];
		}

		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);

		$this->antServer = $this->ant->getAccBaseUrl(false);
		$this->antUser = $this->user->name;
		$this->antPass = "Password1";
	}

	/**
	 * Test local store object
	 *
	 * @group general
	 */
	public function testObjectLocalStore()
	{
		global $ANTAPI_STORE_ELASTIC_HOST, $ANTAPI_STORE_PGSQL_HOST;

		// First cleanup if uname already exists
		$obj = new CAntObject($this->dbh, "customer", "uname:api-unit-test-unique-name", $this->user);
		if ($obj->id)
			$obj->removeHard();

		if ($ANTAPI_STORE_ELASTIC_HOST)
		{
			$store = new AntApi_ObjectStore_Elastic();
			$store->deleteIndex();
		}
        
        // Include fkey multi in testing
        $dbh = $this->dbh;
        $dbh->Query("insert into customer_labels(name) values('Test r1');");
        $dbh->Query("insert into customer_labels(name) values('Test r2');");
        
        $groups = array();
        $result = $dbh->Query("select id from customer_labels limit 2");
        $gid1 = $dbh->GetValue($result, 0, "id");
        $gid2 = $dbh->GetValue($result, 1, "id");
		
		// First lets save an object for testing
		// --------------------------------------------------
		$obj = new AntApi_Object($this->antServer, $this->antUser, $this->antPass, "customer");
		$obj->setStoreSource("ant"); // Make sure this is posted straight to ANT and not stored in any local data sources
		$obj->setValue("first_name", "test");
		$obj->setValue("last_name", "test");
		$obj->setValue("uname", "api-unit-test-unique-name");
        $obj->setMValue("groups", $gid1);
        $obj->setMValue("groups", $gid2);
		$oid = $obj->save();
		$this->assertNotEquals($oid, false); // API did not fail

		// elastic - Test basic object create/update/delete
		// --------------------------------------------------
		if ($ANTAPI_STORE_ELASTIC_HOST)
		{
			$store = new AntApi_ObjectStore_Elastic();
            $this->runLocalStoreObjectTest($store, $obj);
		}

		// pgsql - Test basic object create/update/delete
		// --------------------------------------------------
		if ($ANTAPI_STORE_PGSQL_HOST)
		{
			global $ANTAPI_STORE_PGSQL_DBNAME; // Set which database to save the data
			$store = new AntApi_ObjectStore_Pgsql();
            $this->runLocalStoreObjectTest($store, $obj, array($gid1, $gid2));
		}

		// Cleanup
		$obj = new CAntObject($this->dbh, $oid);
		$obj->removeHard();
        
        $dbh->Query("delete from customer_labels where name='Test r1';");
        $dbh->Query("delete from customer_labels where name='Test r2';");
	}

	/**
	 * Helper routine to test each store
	 *
	 * @param AntApi_ObjectStore_* $store
     * @param AntApi_Object $obj The object we are working with
	 * @param Array $groups     Groups of customer to be tested
	 */
	private function runLocalStoreObjectTest($store, $obj, $groups)
	{
		$oid = $obj->id;

		// Test store
		$ret = $store->storeObject($obj);
		$this->assertTrue($ret);

		// Test open by id
		$data = $store->openObject("customer", $oid);
		$this->assertEquals($data['first_name'], "test");
		$this->assertEquals($data['last_name'], "test");

		// Test open by uname
		$data = $store->openObject("customer", "uname:api-unit-test-unique-name");
		$this->assertEquals($data['first_name'], "test");
		$this->assertEquals($data['last_name'], "test");

		// Test Basic Query
		$store->addCondition("and", "id", "is_equal", $oid);
		$arrObjects = $store->queryObjects($obj);
		$this->assertEquals(count($arrObjects), 1);
        
		// Test facet
		$store->addFacetField("name");
		$arrObjects = $store->queryObjects($obj);
		$this->assertEquals($store->facetCounts['name']['test test'], 1);
        
        // Test fkey multi query
        $store->clearCondition();
        $store->addCondition("and", "groups", "is_equal", $groups[0]);
        $arrObjects = $store->queryObjects($obj);
        $this->assertEquals(count($arrObjects), 1);
        
        // Test fkey multi query with 2 fkey
        $store->addCondition("and", "groups", "is_equal", $groups[1]);
        $arrObjects = $store->queryObjects($obj);
        $this->assertEquals(count($arrObjects), 1);
		
		// Test remove
		$ret = $store->removeObject("customer", $oid);
		$this->assertTrue($ret);
		$this->assertFalse($store->openObject("customer", $oid));

		// Test settings
		$store->putValue("unit/test/setting", "testval");
		$this->assertEquals($store->getValue("unit/test/setting"), "testval");
	}
    
    /**
     * Helper routine to test each store for ant api searcher
     *
     * @param string $type      Type of localstore. either pgsql or elastic     
     */
    private function runLocalStoreObjectSearcherTest($type)
    {
        $searcher = new AntApi_Searcher($this->antServer, $this->antUser, $this->antPass);
        $searcher->storeType = $type;
        $docType = $searcher->addType("task");
        
        $result = $searcher->query("edited");
        $this->assertTrue(is_array($result));
        $this->assertTrue(sizeof($result) > 0);
        $this->assertEquals($result[0]["type"], "task");
        $this->assertEquals($result[0]["title"], "Test Sync Test (edited)");
    }

	/**
	 * Test sync with timestamp
	 */
	public function testLocalStoreSync()
	{
		global $ANTAPI_STORE_ELASTIC_HOST, $ANTAPI_STORE_ELASTIC_HOST, $ANTAPI_STORE_PGSQL_HOST;

		$lastUpdated = gmdate("Y-m-d\\TG:i:s\\Z", strtotime("-5 minutes", time())); // set back so results are always returned
		
		// First lets save an object for testing
		// --------------------------------------------------
		$obj = new AntApi_Object($this->antServer, $this->antUser, $this->antPass, "task");
		$obj->setStoreSource("ant"); // Make sure this is posted straight to ANT and not stored in any local data sources
		$obj->setValue("name", "Test Sync Task");
		$obj->setValue("notes", "Test Notes");
		$oid = $obj->save();
		$this->assertNotEquals($oid, false); // API did not fail
		
		// Test addition
		// --------------------------------------------------
		if ($ANTAPI_STORE_ELASTIC_HOST)
		{
			$store = new AntApi_ObjectStore_Elastic($obj); // Pass obj only for field defs
			$store->syncLocalWithAnt("task", $this->antServer, $this->antUser, $this->antPass);

			// Check to see if this is in the local store
			$data = $store->openObject("task", $oid);
			$this->assertEquals($data['name'], "Test Sync Task");
		}

		if ($ANTAPI_STORE_PGSQL_HOST)
		{
			$store = new AntApi_ObjectStore_Pgsql($obj); // Pass obj only for field defs
			$store->syncLocalWithAnt("task", $this->antServer, $this->antUser, $this->antPass);

			// Check to see if this is in the local store
			$data = $store->openObject("task", $oid);
			$this->assertEquals($data['name'], "Test Sync Task");
		}

		// Test update
		// --------------------------------------------------
		$objLcl = new CAntObject($this->dbh, "task", $oid, $this->user);
		$objLcl->setValue("name", "Test Sync Test (edited)");
		$oid = $objLcl->save();

        // Setup cache to remove the object cached file.
        $cache = new CCache();
        $taskCache = $this->dbh->dbname."/object/task/$oid";
        $cache->remove($taskCache); // Clear the cache before sync
        
		if ($ANTAPI_STORE_ELASTIC_HOST)
		{
			$store = new AntApi_ObjectStore_Elastic($obj); // Pass obj only for field defs
			$store->syncLocalWithAnt("task", $this->antServer, $this->antUser, $this->antPass);

			// Check to see if this is in the local store
			$data = $store->openObject("task", $oid);
			$this->assertEquals($data['name'], "Test Sync Test (edited)");
            
            // After Sync, test for localstore searcher
            $this->runLocalStoreObjectSearcherTest("elastic");
		}
        
		if ($ANTAPI_STORE_PGSQL_HOST)
		{
			$store = new AntApi_ObjectStore_Pgsql($obj); // Pass obj only for field defs
			
			// Add condition to sync only the test data
            $store->clearCondition();
			$store->addCondition("and", "id", "is_equal", $oid);			
			$store->syncLocalWithAnt("task", $this->antServer, $this->antUser, $this->antPass);
            
			// Check to see if this is in the local store
			$data = $store->openObject("task", $oid);            
			$this->assertEquals($data['name'], "Test Sync Test (edited)");
            
            // After Sync, test for localstore searcher
            $this->runLocalStoreObjectSearcherTest("pgsql");
		}

		// Test deletion on ANT locally
		// --------------------------------------------------
		$objLcl = new CAntObject($this->dbh, "task", $oid, $this->user);
		$objLcl->remove();
		unset($objLcl);
        
        $cache->remove($taskCache); // Clear cache before sync with local
        
		// Sync changes to local store
		if ($ANTAPI_STORE_ELASTIC_HOST)
		{
			$store = new AntApi_ObjectStore_Elastic();
			$store->syncLocalWithAnt("task", $this->antServer, $this->antUser, $this->antPass);

			// Check to see if this has been deleted from the local store
			$this->assertFalse($store->openObject("task", $oid));
		}

		if ($ANTAPI_STORE_PGSQL_HOST)
		{
			$store = new AntApi_ObjectStore_Pgsql();
			
			// Add condition to sync only the test data
			$store->addCondition("and", "id", "is_equal", $oid);			
			$store->syncLocalWithAnt("task", $this->antServer, $this->antUser, $this->antPass);
            
			// Check to see if this has been deleted from the local store
            $result = $store->openObject("task", $oid);
			$this->assertFalse($result);
		}

		// Cleanup
		// --------------------------------------------------
		$obj = new CAntObject($this->dbh, "task", $oid, $this->user);
		$obj->removeHard();
	}

	/**
	 * Test sync with ObjectSync framework (new)
	 *
	 * @group testLocalStoreSyncOsync
	 */
	public function testLocalStoreSyncOsync()
	{
		global $ANTAPI_STORE_ELASTIC_HOST, $ANTAPI_STORE_ELASTIC_HOST, $ANTAPI_STORE_PGSQL_HOST;
		$partnerId = "AntApi_ObjectStoreTest::testLocalStoreSyncOsync";

		// Cleanup partner just in case
		$partner = new AntObjectSync_Partner($this->dbh, $partnerId, $this->user);
		$coll = $partner->getCollection("task", null, null, true);
		$coll->resetStats(); // reset so we are only handling recent updates not existing tasks

		// First lets save an object for testing
		// --------------------------------------------------
		$obj = new AntApi_Object($this->antServer, $this->antUser, $this->antPass, "task");
		$obj->setStoreSource("ant"); // Make sure this is posted straight to ANT and not stored in any local data sources
		$obj->setValue("name", "Test Sync Task");
		$obj->setValue("notes", "Test Notes");
		$oid = $obj->save();
		$this->assertNotEquals($oid, false); // API did not fail
		
		// Test addition
		// --------------------------------------------------
		if ($ANTAPI_STORE_ELASTIC_HOST)
		{
			$store = new AntApi_ObjectStore_Elastic($obj); // Pass obj only for field defs
			$store->syncWithAntOSync("task", $partnerId, $this->antServer, $this->antUser, $this->antPass);

			// Check to see if this is in the local store
			$data = $store->openObject("task", $oid);
			$this->assertEquals($data['name'], "Test Sync Task");
		}

		if ($ANTAPI_STORE_PGSQL_HOST)
		{
			$store = new AntApi_ObjectStore_Pgsql($obj); // Pass obj only for field defs
			$store->syncWithAntOSync("task", $partnerId, $this->antServer, $this->antUser, $this->antPass);

			// Check to see if this is in the local store
			$data = $store->openObject("task", $oid);
			$this->assertEquals($data['name'], "Test Sync Task");
		}

		// Test update
		// --------------------------------------------------
		$objLcl = new CAntObject($this->dbh, "task", $oid, $this->user);
		$objLcl->setValue("name", "Test Sync Test (edited)");
		$oid = $objLcl->save();

        // Setup cache to remove the object cached file.
        $cache = new CCache();
        $taskCache = $this->dbh->dbname."/object/task/$oid";
        $cache->remove($taskCache); // Clear the cache before sync
        
		if ($ANTAPI_STORE_ELASTIC_HOST)
		{
			$store = new AntApi_ObjectStore_Elastic($obj); // Pass obj only for field defs
			$store->syncWithAntOSync("task", $partnerId, $this->antServer, $this->antUser, $this->antPass);

			// Check to see if this is in the local store
			$data = $store->openObject("task", $oid);
			$this->assertEquals($data['name'], "Test Sync Test (edited)");
            
            // After Sync, test for localstore searcher
            $this->runLocalStoreObjectSearcherTest("elastic");
		}
        
		if ($ANTAPI_STORE_PGSQL_HOST)
		{
			$store = new AntApi_ObjectStore_Pgsql($obj); // Pass obj only for field defs
			
			// Add condition to sync only the test data
            $store->clearCondition();
			$store->addCondition("and", "id", "is_equal", $oid);			
			$store->syncWithAntOSync("task", $partnerId, $this->antServer, $this->antUser, $this->antPass);
            
			// Check to see if this is in the local store
			$data = $store->openObject("task", $oid);            
			$this->assertEquals($data['name'], "Test Sync Test (edited)");
            
            // After Sync, test for localstore searcher
            $this->runLocalStoreObjectSearcherTest("pgsql");
		}

		// Test deletion on ANT locally
		// --------------------------------------------------
		$objLcl = new CAntObject($this->dbh, "task", $oid, $this->user);
		$objLcl->remove();
		unset($objLcl);
        
        $cache->remove($taskCache); // Clear cache before sync with local
        
		// Sync changes to local store
		if ($ANTAPI_STORE_ELASTIC_HOST)
		{
			$store = new AntApi_ObjectStore_Elastic();
			$store->syncWithAntOSync("task", $partnerId, $this->antServer, $this->antUser, $this->antPass);

			// Check to see if this has been deleted from the local store
			$this->assertFalse($store->openObject("task", $oid));
		}

		if ($ANTAPI_STORE_PGSQL_HOST)
		{
			$store = new AntApi_ObjectStore_Pgsql();
			
			// Add condition to sync only the test data
			$store->addCondition("and", "id", "is_equal", $oid);			
			$store->syncWithAntOSync("task", $partnerId, $this->antServer, $this->antUser, $this->antPass);
            
			// Check to see if this has been deleted from the local store
            $result = $store->openObject("task", $oid);
			$this->assertFalse($result);
		}

		// Cleanup
		// --------------------------------------------------
		$obj = new CAntObject($this->dbh, "task", $oid, $this->user);
		$obj->removeHard();
		$partner = new AntObjectSync_Partner($this->dbh, $partnerId, $this->user);
		$partner->remove();
	}

	/**
	 * Test grouping data saving and updating
	 *
	 * @group testSaveGroupingData
	 */
	public function testSaveGroupingData()
	{
		$obj = new CAntObject($this->dbh, "customer", null, $this->user);
		$gdata = $obj->addGroupingEntry("groups", "testSaveGroupingData", "e3e3e3");

		// Open object with API
		$apiObj = new AntApi_Object($this->antServer, $this->antUser, $this->antPass, "customer");
		$this->assertTrue(count($apiObj->getGroupingData("groups")) > 0);

		// Now move to pgsql and force the definition update
		$apiObj->setStoreSource("pgsql");
		$apiObj->getDefinition(true);

		// Check the store directly
		$store = $apiObj->getLocalStore();
		$this->assertTrue(strlen($store->getValue("/objects/groupings/customer/groups")) > 0);

		// Check object to make sure pull from local groupings is decoded with json
		$this->assertTrue(count($apiObj->getGroupingData("groups")) > 0);

		// Cleanup
		$obj->deleteGroupingEntry("groups", $gdata['id']);
	}

	/**
	 * Test get hierarchy down functino
	 *
	 * @group testGetHeiarchyDown
	 */
	public function testGetHeiarchyDown()
	{
		$obj = new CAntObject($this->dbh, "customer", null, $this->user);
		$gdata1 = $obj->addGroupingEntry("groups", "testSaveGroupingData1", "e3e3e3");
		$gdata2 = $obj->addGroupingEntry("groups", "testSaveGroupingData2", null, null, $gdata1['id']); // child

		// Open object with API and local store
		$apiObj = new AntApi_Object($this->antServer, $this->antUser, $this->antPass, "customer");
		$store = new AntApi_ObjectStore_Pgsql();

		// Get list - should return both ids
		$groupList = $store->getHeiarchyDown($apiObj, "groups", $gdata1['id']);
		$this->assertTrue(in_array($gdata1['id'], $groupList));
		$this->assertTrue(in_array($gdata2['id'], $groupList));

		// Get list for last element - will only have last
		$groupList = $store->getHeiarchyDown($apiObj, "groups", $gdata2['id']);
		$this->assertFalse(in_array($gdata1['id'], $groupList));
		$this->assertTrue(in_array($gdata2['id'], $groupList));
		
		// Cleanup
		$obj->deleteGroupingEntry("groups", $gdata2['id']);
		$obj->deleteGroupingEntry("groups", $gdata1['id']);
	}
}
