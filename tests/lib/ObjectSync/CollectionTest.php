<?php
/**
 * Test the sync partnership class
 *
 * @depricated We are now using EntitySync and ObjectSync is no longer used in production
 */

// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once('lib/CDatabase.awp');
require_once('lib/Ant.php');
require_once('lib/AntUser.php');
require_once('lib/CAntObject.php');
require_once('lib/CAntObjectList.php');
require_once('lib/AntObjectSync.php');

class AntObjectSync_CollectionTest_DEPRICATED extends PHPUnit_Framework_TestCase
{
	var $obj = null;
	var $dbh = null;

	/**
	 * Setup test
	 */
	public function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, -1); // -1 = administrator
		
		$this->markTestSkipped('Depricated.');
	}

	/**
	 * Test save and load
	 */
	public function testSaveAndLoad()
	{
		$pid = "AntObjectSync_CollectionTest::testSave";

		$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);

		$partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$partnerId = ($partner->id) ? $partner->id : $partner->save();

		// Save new collection
		$coll = new AntObjectSync_Collection($this->dbh);
		$coll->partnerId = $partnerId;
		$coll->objectType = $cust->object_type;
		$coll->objectTypeId = $cust->object_type_id;
		$coll->conditions = array(
			array("blogic"=>"and", "field"=>"type_id", "operator"=>"is_equal", "condValue"=>'2'),
		);
		$cid = $coll->save();
		$this->assertTrue(is_numeric($cid));

		// Load and check values
		unset($coll);
		$coll = new AntObjectSync_Collection($this->dbh, $cid);
		$this->assertEquals($coll->partnerId, $partnerId);
		$this->assertEquals($coll->objectType, $cust->object_type);
		$this->assertEquals($coll->objectTypeId, $cust->object_type_id);
		$this->assertEquals(count($coll->conditions), 1);

		// Cleanup
		$coll->remove();
		$partner->remove();
	}

	/** 
	 * Test conditionsMatchObj function which challenges an object against collection conditions
	 */
	public function testConditionsMatchObj()
	{
		$pid = "AntObjectSync_CollectionTest::testConditionsMatchObj";

		$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "testConditionsMatchObj");
		$cust->setValue("type_id", 1);
		$custid = $cust->save(false);

		$partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$partnerId = ($partner->id) ? $partner->id : $partner->save();

		// Save new collection
		$coll = new AntObjectSync_Collection($this->dbh);
		$coll->partnerId = $partnerId;
		$coll->objectType = $cust->object_type;
		$coll->objectTypeId = $cust->object_type_id;
		$coll->conditions = array(
			array("blogic"=>"and", "field"=>"type_id", "operator"=>"is_equal", "condValue"=>'1'),
		);
		$cid = $coll->save();
		$this->assertTrue(is_numeric($cid));

		// Challenge object - should match because type_id is 1
		$this->assertTrue($coll->conditionsMatchObj($cust));

		// Change object and rechallenge object - should match because type_id is 1
		$cust->setValue("type_id", "2");
		$cust->save(false);
		$this->assertFalse($coll->conditionsMatchObj($cust));

		// Cleanup
		$coll->remove();
		$partner->remove();
		$cust->removeHard();
	}

	/** 
	 * Test conditionsMatchObj with a file due to some missed stats in testing the client
	 *
	 * @group testConditionsMatchObjFile
	 */
	public function testConditionsMatchObjFile()
	{
		$pid = "AntObjectSync_CollectionTest::testConditionsMatchObjFile";


		$antfs = new AntFs($this->dbh, $this->user);
		$fldr = $antfs->openFolder("/tests/testConditionsMatchObjFile", true);
		$this->assertNotNull($fldr);
		$fldr2 = $antfs->openFolder("/tests/testConditionsMatchObjFile/Exclude", true);
		$this->assertNotNull($fldr2);
		$file = $fldr->openFile("testsync", true);
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
			array("blogic"=>"and", "field"=>"folder_id", "operator"=>"is_equal", "condValue"=>$fldr->id),
			array("blogic"=>"and", "field"=>"folder_id", "operator"=>"is_not_equal", "condValue"=>$fldr2->id),
		);
		$cid = $coll->save();
		$this->assertTrue(is_numeric($cid));

		// Challenge object - should match because type_id is 1
		$this->assertTrue($coll->conditionsMatchObj($file));

		// Move file to the excluded dir
		$file->move($fldr2);
		$this->assertFalse($coll->conditionsMatchObj($file));

		// Cleanup
		$file->removeHard();
		$fldr2->removeHard();
		$fldr->removeHard();
		$coll->remove();
		$partner->remove();
	}

	/** 
	 * Test logging change from local data store and queue to be propogated
	 *
	 * @group testUpdateObjectStat
	 */
	public function testUpdateObjectStat()
	{
		$pid = "AntObjectSync_CollectionTest::testUpdateObjectStat";

		$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "testUpdateObjectStat");
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

		// Record object change
		$ret = $coll->updateObjectStat($cust, 'c');
		$this->assertTrue($ret);

		// Cleanup
		$coll->remove();
		$partner->remove();
		$cust->removeHard();
	}


	/** 
	 * Test logging change from local data store and queue to be propogated for groupings
	 */
	public function testUpdateGroupingStat()
	{
		$pid = "AntObjectSync_CollectionTest::testUpdateGroupingStat";

		// Add grouping
		$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$grpd = $cust->addGroupingEntry("groups", "testUpdateGroupingStat", "e3e3e3");

		$partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$partnerId = ($partner->id) ? $partner->id : $partner->save();

		// Save new collection
		$coll = new AntObjectSync_Collection($this->dbh);
		$coll->partnerId = $partnerId;
		$coll->objectType = $cust->object_type;
		$coll->objectTypeId = $cust->object_type_id;
		$coll->fieldName = "groups";
		$cid = $coll->save();
		$this->assertTrue(is_numeric($cid));

		// Record object change
		$ret = $coll->updateGroupingStat($grpd['id'], 'c');
		$this->assertTrue($ret);

		// Cleanup
		$coll->remove();
		$partner->remove();
		$cust->removeHard();
	}

	/**
	 * Test getting changed objects for this collection
	 */
	public function testGetChangedObjects() 
	{
		$pid = "AntObjectSync_CollectionTest::testGetChangedObjects";

		// Create customer just in case there are none already in the database
		$obj = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$obj->setValue("name", "testGetChangedObjects");
		$cid = $obj->save();

		$partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$coll = $partner->addCollection("customer");

		// Pull with no stats - should start with all objects
		$stats = $coll->getChangedObjects();
		$this->assertTrue(count($stats) >= 1);
		$coll->resetStats();

		// Record object change
		$ret = $coll->updateObjectStat($obj, 'c');
		$this->assertTrue($ret);
		$stats = $coll->getChangedObjects();
		$this->assertTrue(count($stats) >= 1);

		// Cleanup
		$coll->remove();
		$partner->remove();
		$obj->removeHard();
	}

	/**
	 * Get getting changed objects for this collection using heiarchy
	 */
	public function testGetChangedObjectsHeiarch() 
	{
		$pid = "AntObjectSync_CollectionTest::testGetChangedObjectsHeiarch";

		// Create folder and file
		$antfs = new AntFs($this->dbh, $this->user);
		$fldr = $antfs->openFolder("/tests/testGetChangedObjectsHeiarch", true);
		$this->assertNotNull($fldr);
		$file = $fldr->openFile("testsync", true);
		$this->assertNotNull($file);

		// Add file to collection
		$partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$coll = $partner->addCollection("file");
		$coll->fInitialized = true; // Restrain pulling all existing files for performance reasons
		$ret = $coll->updateObjectStat($file, 'c');

		// Now try to pull with wrong parent id (folder 2)
		$fldr2 = $antfs->openFolder("/tests/testGetChangedObjectsHeiarch/F2", true);
		$stats = $coll->getChangedObjects($fldr2->id);
		$this->assertEquals(count($stats), 0);

		// Try to pull with right parent id (folder 1)
		$stats = $coll->getChangedObjects($fldr->id);
		$this->assertEquals(count($stats), 1);

		// Cleanup
		$coll->remove();
		$partner->remove();
		$fldr2->removeHard();
		$file->removeHard();
		$fldr->removeHard();
	}

	/**
	 * Get getting changed objects for this collection using heiarchy and folders
	 */
	public function testGetChangedObjectsHeiarchFldr() 
	{
		$pid = "AntObjectSync_CollectionTest::testGetChangedObjectsHeiarch";

		// Create folder and file
		$antfs = new AntFs($this->dbh, $this->user);
		$fldr = $antfs->openFolder("/tests/testGetChangedObjectsHeiarch", true);
		$this->assertNotNull($fldr);
		$file = $fldr->openFile("testsync", true);
		$this->assertNotNull($file);

		// Add file to collection
		$partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$coll = $partner->addCollection("file");
		$coll->fInitialized = true; // Restrain pulling all existing files for performance reasons
		$ret = $coll->updateObjectStat($file, 'c');

		// Now try to pull with wrong parent id (folder 2)
		$fldr2 = $antfs->openFolder("/tests/testGetChangedObjectsHeiarch/F2", true);
		$stats = $coll->getChangedObjects($fldr2->id);
		$this->assertEquals(count($stats), 0);

		// Try to pull with right parent id (folder 1)
		$stats = $coll->getChangedObjects($fldr->id);
		$this->assertEquals(count($stats), 1);

		// Cleanup
		$coll->remove();
		$partner->remove();
		$fldr2->removeHard();
		$file->removeHard();
		$fldr->removeHard();
	}

	/**
	 * Test moving with a heiarchy - should add a delete entry for old parent
	 *
	 * @group testMovedObjectsHeiarch
	 */
	public function testMovedObjectsHeiarch() 
	{
		$pid = "AntObjectSync_CollectionTest::testMovedObjectsHeiarch";

		// Create folder and file
		$antfs = new AntFs($this->dbh, $this->user);
		$fldr = $antfs->openFolder("/tests/testGetChangedObjectsHeiarch", true);
		$this->assertNotNull($fldr);
		$file = $fldr->openFile("testsync", true);
		$this->assertNotNull($file);
		$fldr2 = $antfs->openFolder("/tests/testGetChangedObjectsHeiarch/F2", true);

		// Add file to collection
		$partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$coll = $partner->addCollection("file");
		$coll->fInitialized = true; // Restrain pulling all existing files for performance reasons
		$partner->save();
		$ret = $coll->updateObjectStat($file, 'c'); // stat initial file

		// Now move to new folder which should create two stat entries
		$file->move($fldr2);

		// Pull changes from new folder and look for add/change
		$stats = $coll->getChangedObjects($fldr2->id);
		$found = false;
		foreach ($stats as $stat)
		{
			if ($stat['id'] == $file->id && $stat['action'] == 'change')
				$found = true;
		}
		$this->assertTrue($found);

		// Pull changes from old folder and look for delete
		$stats = $coll->getChangedObjects($fldr->id);
		$found = false;
		foreach ($stats as $stat)
		{
			if ($stat['id'] == $file->id && $stat['action'] == 'delete')
				$found = true;
		}
		$this->assertTrue($found);


		// Cleanup
		$coll->remove();
		$partner->remove();
		$fldr2->removeHard();
		$file->removeHard();
		$fldr->removeHard();
	}

	/**
	 * Test moving with a heiarchy - should add a delete entry for old parent
	 *
	 * @group testMovedObjectsHeiarchEmail
	 */
	public function testMovedObjectsHeiarchEmail() 
	{
		$pid = "AntObjectSync_CollectionTest::testMovedObjectsHeiarchEmail";

		// Create folder and file
		$obj = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$grp1 = $obj->addGroupingEntry("mailbox_id", "testMovedObjectsHeiarchEmail1");
		$grp2 = $obj->addGroupingEntry("mailbox_id", "testMovedObjectsHeiarchEmail2");
		$obj->setValue("subject", "My test email");
		$obj->setValue("mailbox_id", $grp1['id']);
		$mid = $obj->save();

		// Add message to collection
		$partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);
		$coll = $partner->addCollection("email_message");
		$coll->fInitialized = true; // Restrain pulling all existing messages for performance reasons
		$partner->save();
		$ret = $coll->updateObjectStat($obj, 'c'); // stat initial stat

		// Now move to new folder which should create two stat entries
		$obj->setValue("mailbox_id", $grp2['id']);
		$mid = $obj->save();

		// Pull changes from new folder and look for add/change
		$stats = $coll->getChangedObjects($grp2['id']);
		$found = false;
		foreach ($stats as $stat)
		{
			if ($stat['id'] == $obj->id && $stat['action'] == 'change')
				$found = true;
		}
		$this->assertTrue($found);

		// Pull changes from old folder and look for delete
		$stats = $coll->getChangedObjects($grp1['id']);
		$found = false;
		foreach ($stats as $stat)
		{
			if ($stat['id'] == $obj->id && $stat['action'] == 'delete')
				$found = true;
		}
		$this->assertTrue($found);


		// Cleanup
		$coll->remove();
		$partner->remove();
		$obj->deleteGroupingEntry("groups", $grp1['id']);
		$obj->deleteGroupingEntry("groups", $grp2['id']);
		$obj->removeHard();
	}

	/**
	 * Test getting changed grouping entries
	 */
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
	 */
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
	 */
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
	 */
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
	 */
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
	 */
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
	 */
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
	 */
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
}
