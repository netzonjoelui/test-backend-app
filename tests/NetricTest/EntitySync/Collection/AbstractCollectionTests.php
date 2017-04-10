<?php
/**
 * Common Collection Tests
 */
namespace NetricTest\EntitySync\Collection;

use PHPUnit_Framework_TestCase;
use Netric\EntitySync;

/*
 * @group integration
 */
abstract class AbstractCollectionTests extends PHPUnit_Framework_TestCase 
{
	/**
     * Tennant accountAbstractCollectionTests
     * 
     * @var \Netric\Account\Account
     */
    protected $account = null;
    
    /**
     * Administrative user
     * 
     * @var \Netric\User
     */
    protected $user = null;

    /**
     * EntitySync DataMapper
     * 
     * @var \Netric\EntitySync\DataMapperInterface
     */
    protected $esDataMapper = null;

    /**
     * Commit manager
     *
     * @var \Netric\EntitySync\Commit\CommitManager
     */
    protected $commitManager = null;

    /**
     * Test partner
     *
     * @var EntitySync\Partner
     */
    protected $partner = null;
    

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->user = $this->account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);
        $this->esDataMapper = $this->account->getServiceManager()->get("EntitySync_DataMapper");
        $this->commitManager = $this->account->getServiceManager()->get("EntitySyncCommitManager");

        // Create a new partner
        $this->partner = new EntitySync\Partner($this->esDataMapper);
        $this->partner->setPartnerId("AbstractCollectionTests");
        $this->partner->setOwnerId($this->user->getId());
        $this->esDataMapper->savePartner($this->partner);
	}

    protected function tearDown()
    {
        $this->deleteLocal();

        // Cleanup partner
        $this->esDataMapper->deletePartner($this->partner, true);
    }
    
    /**
	 * Get a collection object to perform common tests
	 *
	 * @return CollectionInterface
	 */
	abstract protected function getCollection();

    /**
     * Create a new local object
     *
     * @return array('id', 'revision')
     */
    abstract protected function createLocal();

    /**
     * Change a local object
     *
     * @param $id
     */
    abstract protected function changeLocal($id);

    /**
     * Delete a local object or objects
     *
     * @param null $id If no $id is passed then delete all local objects (cleanup)
     */
    abstract protected function deleteLocal($id=null);

    /**
     * Make sure we can construct this colleciton
     */
    public function testConstruct()
    {
        $coll = $this->getCollection();
        
        $this->assertInstanceOf('\Netric\EntitySync\Collection\CollectionInterface', $coll);
    }

    /**
     * Test to make sure the collection returns a valid type
     */
    public function testGetType()
    {
        $coll = $this->getCollection();
        $this->assertTrue($coll->getType() > 0);
    }

    /**
     * Make sure we can set and get the last commit id
     */
    public function testSetAndGetLastCommitId()
    {
        $coll = $this->getCollection();
        $coll->setLastCommitId(123);
        $this->assertEquals(123, $coll->getLastCommitId());
    }

    public function testSetAndGetId()
    {
        $coll = $this->getCollection();
        $coll->setId(123);
        $this->assertEquals(123, $coll->getId());
    }

    public function testSetAndGetPartnerId()
    {
        $coll = $this->getCollection();
        $coll->setPartnerId(123);
        $this->assertEquals(123, $coll->getPartnerId());
    }

    public function testSetAndGetObjType()
    {
        $coll = $this->getCollection();
        $coll->setObjType("customer");
        $this->assertEquals("customer", $coll->getObjType());
    }

    public function testSetAndGetFieldName()
    {
        $coll = $this->getCollection();
        $coll->setFieldName("groups");
        $this->assertEquals("groups", $coll->getFieldName());
    }

    public function testSetAndGetLastSync()
    {
        $now = new \DateTime();
        $coll = $this->getCollection();
        $coll->setLastSync($now);
        $this->assertEquals($now, $coll->getLastSync());
        $this->assertEquals($now->format("Y-m-d H:i:s"), $coll->getLastSync("Y-m-d H:i:s"));
    }

    public function testSetAndGetRevision()
    {
        $coll = $this->getCollection();
        $coll->setRevision(1);
        $this->assertEquals(1, $coll->getRevision());
    }

    public function testSetAndGetConditions()
    {
        $conditions = array(
            array("blogic"=>"and", "field"=>"groups", "operator"=>"is_equal", "condValue"=>1)
        );

        $coll = $this->getCollection();
        $coll->setConditions($conditions);
        $this->assertEquals($conditions, $coll->getConditions());
    }

    /**
     * Test importing objects from a remote source/device
     */
    public function testGetImportChanged()
    {
        // Setup collection
        $collection = $this->getCollection();

        // Create and save partner with one collection watching customers
        $partner = new EntitySync\Partner($this->esDataMapper);
        $partner->setPartnerId("AbstractCollectionTests::testGetImportChanged");
        $partner->setOwnerId($this->user->getId());
        $partner->addCollection($collection);
        $this->esDataMapper->savePartner($partner);

        // Import original group of changes
        $customers = array(
            array('remote_id'=>'test1', 'remote_revision'=>1),
            array('remote_id'=>'test2', 'remote_revision'=>1),
        );
        $stats = $collection->getImportChanged($customers);
        $this->assertEquals(count($stats), count($customers));
        foreach ($stats as $ostat)
        {
            $this->assertEquals('change', $ostat['action']);
            $collection->logImported($ostat['remote_id'], $ostat['remote_revision'], 1001, 1);
        }

        // Try again with no changes
        $stats = $collection->getImportChanged($customers);
        $this->assertEquals(count($stats), 0);

        // Change the revision of one of the objects
        $customers = array(
            array('remote_id'=>'test1', 'remote_revision'=>2),
            array('remote_id'=>'test2', 'remote_revision'=>1),
        );
        $stats = $collection->getImportChanged($customers);
        $this->assertEquals(count($stats), 1);

        // Remove one of the objects
        $customers = array(
            array('remote_id'=>'test2', 'remote_revision'=>1),
        );
        $stats = $collection->getImportChanged($customers);
        $this->assertEquals(count($stats), 1);
        $this->assertEquals($stats[0]['action'], 'delete');

        // Change both revisions
        $customers = array(
            array('remote_id'=>'test1', 'remote_revision'=>2),
            array('remote_id'=>'test2', 'remote_revision'=>2),
        );
        $stats = $collection->getImportChanged($customers);
        $this->assertEquals(count($stats), 2);
        $this->assertEquals($stats[0]['action'], 'change');
        $this->assertEquals($stats[1]['action'], 'change');

        // Cleanup
        $this->esDataMapper->deletePartner($partner, true);
    }

    /**
     * Test getting changed objects
     */
    public function testGetExportChanged()
    {
        // Create and save partner with one collection watching customers
        $collection = $this->getCollection();
        $this->partner->addCollection($collection);
        $this->esDataMapper->savePartner($this->partner);
        $collection->fastForwardToHead();

        // Create a local object to work with
        $localData = $this->createLocal();
        $localId = $localData['id'];

        // Initial pull should start with all objects
        $stats = $collection->getExportChanged();
        $this->assertTrue(count($stats) >= 1);


        // Should be no changes now
        $stats = $collection->getExportChanged();
        $this->assertEquals(0, count($stats));

        // Change the local object
        $this->changeLocal($localId);

        // Make sure the one change is now returned
        $stats = $collection->getExportChanged();
        $this->assertTrue(count($stats) >= 1);
        $this->assertEquals($stats[0]['id'], $localId);
    }

    /**
     * Make sure we do not export imports because that will cause an infinite loop
     */
    public function testNotExportImport()
    {
        // Create and save partner with one collection watching customers
        $collection = $this->getCollection();
        $this->partner->addCollection($collection);
        $this->esDataMapper->savePartner($this->partner);
        $collection->fastForwardToHead();

        // Import original group of changes
        $customers = array(
            array('remote_id'=>'test1', 'remote_revision'=>1),
            array('remote_id'=>'test2', 'remote_revision'=>1),
        );
        $stats = $collection->getImportChanged($customers);
        $this->assertEquals(count($stats), count($customers));
        foreach ($stats as $ostat)
        {
            $newData = $this->createLocal();
            $collection->logImported(
                $ostat['remote_id'],
                $ostat['remote_revision'],
                $newData['id'],
                $newData['revision']
            );
        }

        // Now pull export changes which should be 0
        $stats = $collection->getExportChanged();
        $this->assertEquals(0, count($stats));

        // Make a change after the import
        $localData = $this->createLocal();
        $localId = $localData['id'];
        // Make sure the one change is now returned
        $stats = $collection->getExportChanged();
        $this->assertEquals(1, count($stats));
        $this->assertEquals($stats[0]['id'], $localId);
    }

    /**
     *  Get getting changed objects for this collection using heiarchy
     */
    //abstract public function testGetExportChangedHeiarch();

    /**
     *  Test moving with a heiarchy - should add a delete entry for old parent
     */
    //abstract public function testGetExportChangedHeiarchMoved();   

    /**
     * Test getting changed grouping entries
     *
    public function testGetChangedGroupings() 
    {
        $pid = "AntObjectSync_CollectionTest::testGetChangedGroupings";
        $obj = CAntObject::factory($this->dbh, "customer", null, $this->user);
        $grpd = $obj->addGroupingEntry("groups", "testGetDevChangedGroupings", "e3e3e3");

        $partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
        $coll = $partner->addCollection("customer", "groups");

        // Pull with no stats - should start with all objects
        $stats = $coll->getChangedGroupings();
        $this->assertTrue(count($stats) >= 1);

        // Record object change
        $ret = $coll->updateGroupingStat($grpd['id'], 'c');
        $this->assertTrue($ret);
        $stats = $coll->getChangedGroupings();
        $this->assertTrue(count($stats) >= 1);

        // Cleanup
        $coll->remove();
        $partner->remove();
        $obj->deleteGroupingEntry("groups", $grpd['id']);
    }

    /**
     * Test importing objects from a remote source/device
     *
     * @group testImportObjectsDiff
     *
    public function testImportObjectsDiff()
    {
        $pid = "AntObjectSync_CollectionTest::testGetChangedGroupings";

        // Cleanup - if already exists
        $partn = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
        $partn->remove();

        // Create new device
        $partn = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
        $coll = $partn->addCollection("customer");
        $partn->save();

        // Import orignial group of changes
        $customers = array(
            array('uid'=>'test1', 'revision'=>1),
            array('uid'=>'test2', 'revision'=>1),
        );
        $stats = $coll->importObjectsDiff($customers);
        $this->assertEquals(count($stats), count($customers));

        foreach ($stats as $ostat)
        {
            // If previously syncrhonized then object_id will be set
            $obj = CAntObject::factory($this->dbh, "customer", $ostat['object_id'], $this->user);

            switch ($ostat['action'])
            {
            case 'change':

                // This is where you would get data from the source, for the test just set data
                $obj->setValue("name", $ostat['uid']);
                $oid = $obj->save();
                $coll->updateImportObjectStat($ostat['uid'], $ostat['revision'], $oid);
                break;
            case 'delete':
                if ($ostat['object_id'])
                {
                    if ($obj->getValue("f_deleted") != 't') // If already deleted the don't purge
                        $obj->remove();

                    $coll->updateImportObjectStat($ostat['uid'], $ostat['revision'], null); // last null param will purge stat
                }
                break;
            }
        }

        // Try again with no changes
        $stats = $coll->importObjectsDiff($customers);
        $this->assertEquals(count($stats), 0);

        // Change the revision of one of the objects
        $customers = array(
            array('uid'=>'test1', 'revision'=>2),
            array('uid'=>'test2', 'revision'=>1),
        );
        $stats = $coll->importObjectsDiff($customers);
        $this->assertEquals(count($stats), 1);

        // Remove one of the objects
        $customers = array(
            array('uid'=>'test2', 'revision'=>1),
        );
        $stats = $coll->importObjectsDiff($customers);
        $this->assertEquals(count($stats), 1);
        $this->assertEquals($stats[0]['action'], 'delete');

        // Change both revisions
        $customers = array(
            array('uid'=>'test1', 'revision'=>2),
            array('uid'=>'test2', 'revision'=>2),
        );
        $stats = $coll->importObjectsDiff($customers);
        $this->assertEquals(count($stats), 2);
        $this->assertEquals($stats[0]['action'], 'change');
        $this->assertEquals($stats[1]['action'], 'change');

        // Cleanup
        foreach ($stats as $ostat)
        {
            $obj = CAntObject::factory($this->dbh, "customer", $ostat['object_id'], $this->user);
            $obj->removeHard();
        }
        $coll->remove();
        $partn->remove();
    }   

    /**
     * Test importing objects from a remote source/device with heiarchy
     *
     * @group testImportObjectsDiffHeiarch
     *
    public function testImportObjectsDiffHeiarch()
    {
        $pid = "AntObjectSync_CollectionTest::testImportObjectsDiffHeiarch";

        // Cleanup - if already exists
        $partn = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
        $partn->remove();

        // Create new device
        $partn = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
        $coll = $partn->addCollection("customer");
        $partn->save();

        // Import orignial group of changes
        $customers = array(
            array('uid'=>'test1', 'revision'=>1),
            array('uid'=>'test2', 'revision'=>1),
        );
        $stats = $coll->importObjectsDiff($customers, 1);
        $this->assertEquals(count($stats), count($customers));

        foreach ($stats as $ostat)
        {
            // If previously syncrhonized then object_id will be set
            $obj = CAntObject::factory($this->dbh, "customer", $ostat['object_id'], $this->user);

            switch ($ostat['action'])
            {
            case 'change':

                // This is where you would get data from the source, for the test just set data
                $obj->setValue("name", $ostat['uid']);
                $oid = $obj->save();
                $coll->updateImportObjectStat($ostat['uid'], $ostat['revision'], $oid, 1);
                break;
            case 'delete':
                if ($ostat['object_id'])
                {
                    if ($obj->getValue("f_deleted") != 't') // If already deleted the don't purge
                        $obj->remove();

                    $coll->updateImportObjectStat($ostat['uid'], $ostat['revision'], null, 1); // last null param will purge stat
                }
                break;
            }
        }

        // Try again with no changes
        $stats = $coll->importObjectsDiff($customers, 1);
        $this->assertEquals(count($stats), 0);

        // Change the revision of one of the objects
        $customers = array(
            array('uid'=>'test1', 'revision'=>2),
            array('uid'=>'test2', 'revision'=>1),
        );
        $stats = $coll->importObjectsDiff($customers, 1);
        $this->assertEquals(count($stats), 1);
        $coll->updateImportObjectStat($stats[0]['uid'], $stats[0]['revision'], $stats[0]['object_id'], 1);

        // Try again should be 0
        $stats = $coll->importObjectsDiff($customers, 1);
        $this->assertEquals(count($stats), 0);

        // Run with different parent id
        $stats = $coll->importObjectsDiff($customers, 2);
        $this->assertEquals(count($stats), 2);

        // Cleanup
        foreach ($stats as $ostat)
        {
            $obj = CAntObject::factory($this->dbh, "customer", $ostat['object_id'], $this->user);
            $obj->removeHard();
        }
        $coll->remove();
        $partn->remove();
    }

    /**
     * Test stat for incoming grouping
     *
     * This is often used to synchronize things like folders to groupings in Netric
     *
     * @group importGroupingDiff
     *
    public function testImportGroupingDiff()
    {
        $delimiter = ".";
        $pid = "AntObjectSync_CollectionTest::testImportGroupingDiff";

        // Cleanup - if already exists
        $partn = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
        $partn->remove();

        // Create new device
        $partn = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
        $coll = $partn->addCollection("customer", "groups");
        $partn->save();

        // Import initial list
        $folders = array(
            "INBOX",
            "INBOX.sub1",
            "INBOX.sub1.subsub1",
            "INBOX.sub2",
        );
        $ret = $coll->importGroupingDiff($folders, $delimiter); // Import folders into groupings
        $this->assertEquals(count($ret), count($folders));

        // Change the folders (add a new one)
        $folders = array(
            "INBOX",
            "INBOX.sub1",
            "INBOX.sub1.subsub1",
            "INBOX.sub2",
            "INBOX.sub3",
        );
        $ret = $coll->importGroupingDiff($folders, $delimiter); // Import folders into groupings - should add one
        $this->assertEquals(count($ret), 1);

        // Change the folders (delete one)
        $folders = array(
            "INBOX",
            "INBOX.sub1",
            "INBOX.sub1.subsub1",
            "INBOX.sub2",
            //"INBOX.sub3",
        );
        $ret = $coll->importGroupingDiff($folders, $delimiter); // Import folders into groupings - should delete one
        $this->assertEquals(count($ret), 1); // 1 deleted

        // Get groupings and make sure groupings match
        $obj = CAntObject::factory($this->dbh, "customer");

        $pntGrp = $obj->getGroupingEntryByName("groups", "INBOX");
        $this->assertTrue(is_array($pntGrp));

        $childGrp = $obj->getGroupingEntryByPath("groups", "INBOX/sub1/subsub1");
        $this->assertTrue(is_array($childGrp));

        $childGrp = $obj->getGroupingEntryByPath("groups", "INBOX/sub3"); // was deleted in last sync, should not exist
        $this->assertFalse(is_array($childGrp));


        // Cleanup
        $coll->remove();
        $partn->remove();
        $ret = $obj->deleteGroupingEntry("groups", $pntGrp['id']);
    }

    /** 
     * Test to make sure stats for just imported objects are not saved creating a circular push-pull
     *
    public function testUpdateObjectStatJustImported()
    {
        $pid = "AntObjectSync_CollectionTest::testUpdateObjectStatJustImported";

        $cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
        $cust->setValue("name", "testUpdateObjectStatJustImported");
        $custid = $cust->save(false);

        $partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
        $coll = $partner->addCollection("customer");
        $partnerId = ($partner->id) ? $partner->id : $partner->save();

        // Import orignial group of changes
        $customers = array(
            array('uid'=>'test1', 'revision'=>1),
            array('uid'=>'test2', 'revision'=>1),
        );
        $stats = $coll->importObjectsDiff($customers);
        $this->assertEquals(count($stats), count($customers));

        foreach ($stats as $ostat)
        {
            // If previously syncrhonized then object_id will be set
            $obj = CAntObject::factory($this->dbh, "customer", $ostat['object_id'], $this->user);
            $obj->setValue("name", $ostat['uid']);
            $oid = $obj->save();
            $coll->updateImportObjectStat($ostat['uid'], $obj->revision, $oid);

            // Now try to update the object stat which should fail with this collection because we just imported and nothing has changed
            $ret = $coll->updateObjectStat($obj, 'c');
            $this->assertFalse($ret);

            // Increment revision and then update - should succeed
            $obj->revision++;
            $obj->setValue("revision", $obj->revision);
            $ret = $coll->updateObjectStat($obj, 'c');
            $this->assertTrue($ret);

            // Cleanup
            $obj->removeHard();
        }

        // Cleanup
        $coll->remove();
        $partner->remove();
        $cust->removeHard();
    }

    /** 
     * Test the shortcut changesExist function works for a collection
     *
     * @group testChangesExist
     *
    public function testChangesExist()
    {
        $pid = "AntObjectSync_CollectionTest::testChangesExist";

        $cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
        $cust->setValue("name", "testChangesExist");
        $custid = $cust->save(false);

        $partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
        $partnerId = ($partner->id) ? $partner->id : $partner->save();

        // Save new collection
        $coll = new AntObjectSync_Collection($this->dbh);
        $coll->partnerId = $partnerId;
        $coll->objectType = $cust->object_type;
        $coll->objectTypeId = $cust->object_type_id;
        $cid = $coll->save();
        $this->assertTrue(is_numeric($cid));

        // First call should return true because the collection does not exist
        $this->assertTrue($coll->changesExist());

        // Initialize so changes should now be zero
        $coll->fInitialized = true;
        $this->assertFalse($coll->changesExist());

        // Record object change and changes should exist
        $ret = $coll->updateObjectStat($cust, 'c');
        $this->assertTrue($coll->changesExist());

        // Record another and call again which should use cache and not hit the DB
        $coll->debug = true;
        $ret = $coll->updateObjectStat($cust, 'c');
        $numBefore = $this->dbh->statNumQueries;
        $this->assertTrue($coll->changesExist());
        $this->assertEquals($numBefore, $this->dbh->statNumQueries);

        // Cleanup
        $coll->remove();
        $partner->remove();
        $cust->removeHard();
    }

    /** 
     * Test deletign an email to make sure it gets removed
     *
     * @group testChangesExist
     *
    public function testDeleteEmail()
    {
        $pid = "AntObjectSync_CollectionTest::testDeleteEmail";

        // Create and save the email
        $email = CAntObject::factory($this->dbh, "email_message", null, $this->user);
        $email->setValue("subject", "testChangesExist");
        $eid = $email->save(false);

        // Save new collection
        $partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
        $partnerId = ($partner->id) ? $partner->id : $partner->save();
        $coll = new AntObjectSync_Collection($this->dbh);
        $coll->partnerId = $partnerId;
        $coll->objectType = $email->object_type;
        $coll->objectTypeId = $email->object_type_id;
        $cid = $coll->save();
        $this->assertTrue(is_numeric($cid));

        // First call should return true because the collection does not exist and will pull all existing
        $this->assertTrue($coll->changesExist());

        // Initialize so changes should not be zero
        $coll->fInitialized = true;
        $this->assertFalse($coll->changesExist());

        // Delete the object and then test if changes exist
        $email->remove();
        $this->assertTrue($coll->changesExist());

        $stats = $coll->getChangedObjects();
        $this->assertEquals($stats[0]["id"], $eid);

        // Cleanup
        $coll->remove();
        $partner->remove();
        $email->removeHard();
    }

    /**
     * Test initailizing a heirarchy collection
     *
    public function testInitObjectCollection()
    {
        $pid = "AntObjectSync_CollectionTest::testInitObjectCollection";


        $antfs = new AntFs($this->dbh, $this->user);
        $fldr = $antfs->openFolder("/tests/testInitObjectCollection", true);
        $file = $fldr->openFile("testsync", true);
        $this->assertNotNull($fldr);
        $fldr2 = $antfs->openFolder("/tests/testInitObjectCollection/Sub", true);
        $this->assertNotNull($fldr2);
        $file2 = $fldr2->openFile("testsync", true);
        $this->assertNotNull($file);

        // This is a bit more complex
        $partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
        $partnerId = ($partner->id) ? $partner->id : $partner->save();

        // Save new collection
        $coll = new AntObjectSync_Collection($this->dbh);
        $coll->partnerId = $partnerId;
        $coll->objectType = $file->object_type;
        $coll->objectTypeId = $file->object_type_id;
        $coll->conditions = array(
            array("blogic"=>"and", "field"=>"folder_id", "operator"=>"is_less_or_equal", "condValue"=>$fldr->id),
        );
        $cid = $coll->save();
        $this->assertTrue(is_numeric($cid));

        // Check the first folder to make sure changes were initialized
        $stats = $coll->getChangedObjects($fldr->id);
        $this->assertEquals(count($stats), 1);

        // Check the sub folder to make sure changes were initialized
        $stats = $coll->getChangedObjects($fldr2->id);
        $this->assertEquals(count($stats), 1);

        // Cleanup
        $file->removeHard();
        $file2->removeHard();
        $fldr2->removeHard();
        $fldr->removeHard();
        $coll->remove();
        $partner->remove();
    }
    */
}