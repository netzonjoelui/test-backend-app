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

class AntObjectSync_PartnerTest_DEPRICATED  extends PHPUnit_Framework_TestCase
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
	 * Test add listening
	 */
	public function testAddCollection()
	{
		$dev = new AntObjectSync_Partner($this->dbh, "AntObjectSync_PartnerTest::testAddCollection", $this->user);
		$dev->addCollection("customer");

		$this->assertEquals($dev->collections[0]->objectType, "customer");
		$this->assertTrue(is_numeric($dev->collections[0]->objectTypeId));
		$this->assertNotEquals($dev->getCollection("customer"), false);

		// Now try field
		$dev->addCollection("lead", "class_id");
		$this->assertEquals($dev->collections[0]->objectType, "customer");
		$this->assertTrue(is_numeric($dev->collections[0]->objectTypeId));

		// CLeanup
		$dev->remove();
	}

	/**
	 * Test get collection
	 *
	 * @group testGetCollection
	 */
	public function testGetCollection()
	{
		$dev = new AntObjectSync_Partner($this->dbh, "testGetCollection", $this->user);
		$dev->removeCollections();

		// Test simple sync with no filed or filters
		// ----------------------------------------------------------
		
		// Test sync collection with a field - used for grouping usually
		// ----------------------------------------------------------
		/*
		$dev->addCollection("lead", "class_id");
		$this->assertEquals($dev->collections[0]['object_type'], "customer");
		$this->assertTrue(is_numeric($dev->collections[0]['object_type_id']));
		 */

		// Test with conditions
		// ----------------------------------------------------------
		$conditions = array(
			array(
				"blogic"=>"and",
				"field"=>"type_id",
				"operator"=>"is_equal",
				"condValue"=>1, // person
			),
		);
		$dev->addCollection("customer", null, $conditions);

		// Get the collection and make sure condition filter is honored
		$this->assertNotEquals($dev->getCollection("customer", null, $conditions), false);

		// Try same object type with conditions that do not match
		$conditions = array(
			array(
				"blogic"=>"and",
				"field"=>"type_id",
				"operator"=>"is_equal",
				"condValue"=>2, // account - should not match because the collection is only for type=person
			),
		);
		$this->assertEquals($dev->getCollection("customer", null, $conditions), false);

		// Try opeing collection without filter - should be false because collection is partial
		$this->assertEquals($dev->getCollection("customer"), false);


		// Cleanup
		$dev->remove();
	}

	/**
	 * Test to make sure the partner can save all values
	 */
	public function testSave()
	{
		$part = new AntObjectSync_Partner($this->dbh, "AntObjectSync_PartnerTest::testSave", $this->user);
		$part->addCollection("customer");
		$did = $part->save();
		$this->assertTrue(is_numeric($did));
		$this->assertNotEquals($part->id, null);
		$this->assertTrue(is_numeric($part->collections[0]->id));

		// Cleanup
		$part->remove();
	}

	/**
	 * Test to make sure that opening existing devices has all the needed fields
	 *
	 * @group open
	 */
	public function testOpen()
	{
		$part = new AntObjectSync_Partner($this->dbh, "unit_test_device_id testOpen", $this->user);
		$part->addCollection("customer");
		$part->addCollection("lead", "class_id");
		$part->debug = true;
		$did = $part->save();
		$this->assertTrue(is_numeric($did));
		unset($part);

		// Reopen and check values
		$part = new AntObjectSync_Partner($this->dbh, null, $this->user);
		//$part->debug = true;
		$part->load("unit_test_device_id testOpen");
		$this->assertEquals($part->ownerId, $this->user->id);
		$this->assertEquals($part->id, $did);
		$this->assertEquals(2, count($part->collections));

		foreach ($part->collections as $ent)
		{
			switch($ent->objectType)
			{
			case 'customer':
				$this->assertTrue(is_numeric($ent->objectTypeId));
				$this->assertEquals($ent->fieldName, null);
				$this->assertEquals($ent->fieldId, null);
				break;
			case 'lead':
				$this->assertTrue(is_numeric($ent->objectTypeId));
				$this->assertTrue(strlen($ent->fieldName)>0);
				$this->assertTrue(is_numeric($ent->fieldId));
				break;
			}
		}

		// Cleanup
		$part->remove();
	}

	/**
	 * Test to make sure the plugin can save all values
	 */
	public function testDelete()
	{
		$part = new AntObjectSync_Partner($this->dbh, "testDelete", $this->user);
		$part->addCollection("customer");
		$did = $part->save();
		$this->assertTrue(is_numeric($did));

		// Cleanup
		$part->remove();

		// Verify missing
		$part = new AntObjectSync_Partner($this->dbh, null, $this->user);
		$this->assertFalse($part->load("testDelete", false));
	}
}
