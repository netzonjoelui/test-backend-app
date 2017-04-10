<?php
/**
 * Define common tests that will need to be run with all data mappers.
 *
 * In order to implement the unit tests, a datamapper test case just needs
 * to extend this class and create a getDataMapper class that returns the
 * datamapper to be tested
 */
namespace NetricTest\Entity\DataMapper;

use Netric;
use Netric\Entity\Entity;
use Netric\Entity\DataMapperInterface;
use Netric\Entity\Recurrence\RecurrencePattern;
use PHPUnit_Framework_TestCase;

abstract class DmTestsAbstract extends PHPUnit_Framework_TestCase 
{
	/**
     * Tennant account
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
	 * Setup each test
	 */
	protected function setUp() 
	{
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->user = $this->account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);
	}
    
    /**
	 * Setup datamapper for the parent DataMapperTests class
	 *
	 * @return DataMapperInterface
	 */
	abstract protected function getDataMapper();

	/**
	 * Utility function to populate custome entity for testing
	 *
	 * @return Entity
	 */
	protected function createCustomer()
	{
		$customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		// text
		$customer->setValue("name", "Entity_DataMapperTests");
		// bool
		$customer->setValue("f_nocall", true);
		// object
		$customer->setValue("owner_id", $this->user->getId(), $this->user->getName());
		// object_multi
		// timestamp
		$contactedTime = mktime(0, 0, 0, 12, 1, 2013);
		$customer->setValue("last_contacted", $contactedTime);

		return $customer;
	}

	/**
	 * Test loading an object by id and putting it into cache
	 */
	public function testGetById()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
		{
			// Do not run if we don't have a datamapper to work with
			$this->assertTrue(true);
			return;
		}

        // Create a few test groups
        $groupingsStat = $dm->getGroupings("customer", "status_id");
        $statGrp = $groupingsStat->getByName("Unit Test Status");
        if (!$statGrp)
        	$statGrp = $groupingsStat->create("Unit Test Status");
        $groupingsStat->add($statGrp);
        $dm->saveGroupings($groupingsStat);
        
        $groupingsGroups = $dm->getGroupings("customer", "groups");
        $groupsGrp = $groupingsGroups->getByName("Unit Test Group");
        if (!$groupsGrp)
        	$groupsGrp = $groupingsGroups->create("Unit Test Group");
        $groupingsGroups->add($groupsGrp);
        $dm->saveGroupings($groupingsGroups);

		// Create an entity and initialize values
		$customer = $this->createCustomer();
		// fkey
		$customer->setValue("status_id", $statGrp->id, $statGrp->name);
		// fkey_multi - groups
		$customer->addMultiValue("groups", $groupsGrp->id, $groupsGrp->name);
		// Cache returned time
		$contactedTime = $customer->getValue("last_contacted");
		$cid = $dm->save($customer, $this->user);

		// Get entity definition
		$ent = $this->account->getServiceManager()->get("EntityFactory")->create("customer");

		// Load the object through the loader which should cache it
		$ret = $dm->getById($ent, $cid);
		$this->assertTrue($ret);
		$this->assertEquals($ent->getId(), $cid);
		$this->assertEquals($ent->getValue("id"), $cid);
		$this->assertEquals($ent->getValue("name"), "Entity_DataMapperTests");
		$this->assertTrue($ent->getValue("f_nocall"));
		$this->assertEquals($ent->getValue("owner_id"), $this->user->getId());
		$this->assertEquals($ent->getValueName("owner_id"), $this->user->getName());
		$this->assertEquals($ent->getValue("status_id"), $statGrp->id);
		$this->assertEquals($ent->getValueName("status_id"), "Unit Test Status");
		$this->assertEquals($ent->getValue("groups"), array($groupsGrp->id));
		$this->assertEquals($ent->getValueName("groups"), "Unit Test Group");
		$this->assertEquals($ent->getValue("last_contacted"), $contactedTime);

		// Cleanup
		$groupingsStat->delete($statGrp->id);
        $dm->saveGroupings($groupingsStat);
        
        $groupingsGroups->delete($groupsGrp->id);
        $dm->saveGroupings($groupingsGroups);
        
		$dm->delete($ent, true);
	}

	/**
	 * Test loading an object by id and putting it into cache
	 */
	public function testSave()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
		{
			// Do not run if we don't have a datamapper to work with
			$this->assertTrue(true);
			return;
		}

        // Create a few test groups
        $groupingsStat = $dm->getGroupings("customer", "status_id");
        $statGrp = $groupingsStat->create("Unit Test Status");
        $groupingsStat->add($statGrp);
        $dm->saveGroupings($groupingsStat);
        
        $groupingsGroups = $dm->getGroupings("customer", "groups");
        $groupsGrp = $groupingsGroups->create("Unit Test Group");
        $groupingsGroups->add($groupsGrp);
        $dm->saveGroupings($groupingsGroups);

		// Create an entity and initialize values
		$customer = $this->createCustomer();
		// fkey
		$customer->setValue("status_id", $statGrp->id, $statGrp->name);
		// fkey_multi - groups
		$customer->addMultiValue("groups", $groupsGrp->id, $groupsGrp->name);
		// Cache returned time
		$contactedTime = $customer->getValue("last_contacted");
		$cid = $dm->save($customer, $this->user);
		$this->assertNotEquals(false, $cid);

		// Get entity definition
		$ent = $this->account->getServiceManager()->get("EntityFactory")->create("customer");

		// Load the object through the loader which should cache it
		$ret = $dm->getById($ent, $cid);
		$this->assertTrue($ret);
		$this->assertEquals($ent->getId(), $cid);
		$this->assertEquals($ent->getValue("id"), $cid);
		$this->assertEquals($ent->getValue("name"), "Entity_DataMapperTests");
		$this->assertTrue($ent->getValue("f_nocall"));
		$this->assertEquals($ent->getValue("owner_id"), $this->user->getId());
		$this->assertEquals($ent->getValueName("owner_id"), $this->user->getName());
		$this->assertEquals($ent->getValue("status_id"), $statGrp->id);
		$this->assertEquals($ent->getValueName("status_id"), $statGrp->name);
		$this->assertEquals($ent->getValue("groups"), array($groupsGrp->id));
		$this->assertEquals($ent->getValueName("groups"), $groupsGrp->name);
		$this->assertEquals($ent->getValue("last_contacted"), $contactedTime);

		// Cleanup
		$groupingsStat->delete($statGrp->id);
        $dm->saveGroupings($groupingsStat);
        $groupingsGroups->delete($groupsGrp->id);
        $dm->saveGroupings($groupingsGroups);
		$dm->delete($ent, true);
	}

	public function testSaveClearMultiVal()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
		{
			// Do not run if we don't have a datamapper to work with
			$this->assertTrue(true);
			return;
		}

		// Create a few test groups
		$groupingsStat = $dm->getGroupings("customer", "status_id");
		$statGrp = $groupingsStat->create("Unit Test Status");
		$groupingsStat->add($statGrp);
		$dm->saveGroupings($groupingsStat);

		$groupingsGroups = $dm->getGroupings("customer", "groups");
		$groupsGrp = $groupingsGroups->create("Unit Test Group");
		$groupingsGroups->add($groupsGrp);
		$dm->saveGroupings($groupingsGroups);

		// Create an entity and initialize values
		$customer = $this->createCustomer();
		$customer->addMultiValue("groups", $groupsGrp->id, $groupsGrp->name);
		// Cache returned time
		$cid = $dm->save($customer, $this->user);
		$this->assertNotEquals(false, $cid);

		// Now clear multi-vals
		$customer->clearMultiValues("groups");
		$cid = $dm->save($customer, $this->user);

		// Create new entity
		$ent = $this->account->getServiceManager()->get("EntityFactory")->create("customer");

		// Load the object through the loader which should cache it
		$ret = $dm->getById($ent, $cid);
		$this->assertTrue($ret);
		$this->assertEquals(array(), $ent->getValue("groups"));
		$this->assertEquals(array(), $ent->getValueNames("groups"));
		$this->assertEquals('', $ent->getValueName("groups"));

		// Cleanup
		$groupingsStat->delete($statGrp->id);
		$dm->saveGroupings($groupingsStat);
		$groupingsGroups->delete($groupsGrp->id);
		$dm->saveGroupings($groupingsGroups);
		$dm->delete($ent, true);
	}

	/**
	 * Test delete
	 */
	public function testDelete()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
			return;

		// First test a custom table object
		// ------------------------------------------------------------------------
		
		// Create a test customer to delete
		$customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$customer->setValue("name", "Entity_DataMapperTests");
		$cid = $dm->save($customer, $this->user);
		$this->assertNotEquals(false, $cid);

		// Test soft delete first
		$ret = $dm->delete($customer);
		$this->assertTrue($ret);

		// Reload and test if flagged but still in database
		$customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$ret = $dm->getById($customer, $cid);
		$this->assertTrue($ret);
		$this->assertEquals(true, $customer->isDeleted());

		// Now delete and make sure the object cannot be reloaded
		$ret = $dm->delete($customer);
		$this->assertTrue($ret);
		$customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$ret = $dm->getById($customer, $cid);
		$this->assertFalse($ret); // Not found

		// Test a dynamic table object
		// ------------------------------------------------------------------------
		
		// Create a test customer to delete
		$story = $this->account->getServiceManager()->get("EntityLoader")->create("project_story");
		$story->setValue("name", "Entity_DataMapperTests");
		$cid = $dm->save($story, $this->user);
		$this->assertNotEquals(false, $cid);

		// Test soft delete first
		$ret = $dm->delete($story);
		$this->assertTrue($ret);

		// Reload and test if flagged but still in database
		$story = $this->account->getServiceManager()->get("EntityLoader")->create("project_story");
		$ret = $dm->getById($story, $cid);
		$this->assertTrue($ret);
		$this->assertEquals(true, $story->isDeleted());

		// Now delete and make sure the object cannot be reloaded
		$ret = $dm->delete($story);
		$this->assertTrue($ret);
		$story = $this->account->getServiceManager()->get("EntityLoader")->create("project_story");
		$ret = $dm->getById($story, $cid);
		$this->assertFalse($ret); // Not found
	}

	/**
	 * Test entity has moved functionalty
	 */
	public function testSetEntityMovedTo()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
			return;

		// Create first entity
		$customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$customer->setValue("name", "testSetEntityMovedTo");
		$oid1 = $dm->save($customer, $this->user);

		// Create second entity
		$customer2 = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$customer2->setValue("name", "testSetEntityMovedTo");
		$oid2 = $dm->save($customer2, $this->user);

		// Set moved to
        $def = $customer->getDefinition();
		$ret = $dm->setEntityMovedTo($def, $oid1, $oid2);
		$this->assertTrue($ret);

		// Cleanup
		$dm->delete($customer, true);
		$dm->delete($customer2, true);
	}

	/**
	 * Test entity has moved functionalty
	 */
	public function testEntityHasMoved()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
			return;

		// Create first entity
		$customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$customer->setValue("name", "testSetEntityMovedTo");
		$oid1 = $dm->save($customer, $this->user);

		// Create second entity
		$customer2 = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$customer2->setValue("name", "testSetEntityMovedTo");
		$oid2 = $dm->save($customer2, $this->user);

		// Set moved to
        $def = $customer->getDefinition();
		$ret = $dm->setEntityMovedTo($def, $oid1, $oid2);

		// Get access to protected entityHasMoved with reflection object
		$refIm = new \ReflectionObject($dm);
		$entityHasMoved = $refIm->getMethod("entityHasMoved");
		$entityHasMoved->setAccessible(true);
		$movedTo = $entityHasMoved->invoke($dm, $customer->getDefinition(), $oid1);

		// Now make sure the movedTo works
		$this->assertEquals($oid2, $movedTo);

		// Cleanup
		$dm->delete($customer, true);
		$dm->delete($customer2, true);
	}
	
	/**
	 * Test revisions
	 */
	public function testGetRevisions()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
			return;

		// Save first time
		$customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$customer->setValue("name", "First");
		$cid = $dm->save($customer, $this->user);
		$this->assertEquals(1, $customer->getValue("revision"));

		// Change value and set again
		$customer->setValue("name", "Second");
		$dm->save($customer, $this->user);
		$rev1 = $customer->getValue("revision");
		$this->assertEquals(2, $customer->getValue("revision"));

		// Get the revisions and make sure old value is stored
		$revisions = $dm->getRevisions("customer", $cid);
		$this->assertEquals("First", $revisions[1]->getValue("name"));
		$this->assertEquals("Second", $revisions[2]->getValue("name"));

		// Cleanup
		$dm->delete($customer, true);

		// Make sure revisions got deleted
		$this->assertEquals(0, count($dm->getRevisions("customer", $cid)));
	}

	/**
	 * Test skip revisions if the definition has saveRevisions set to false
	 */
	public function testSaveRevisionsSetting()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
			return;

		// Save first time
		$customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		// Set saveRevisions to false
		$customer->getDefinition()->storeRevisions = false;
		$customer->setValue("name", "First");
		$cid = $dm->save($customer, $this->user);
		$this->assertEquals(1, $customer->getValue("revision"));

		// Make sure revisions got deleted
		$this->assertEquals(0, count($dm->getRevisions("customer", $cid)));

		// Turn back on and save changes
		$customer->getDefinition()->storeRevisions = true;
		$customer->setValue("name", "Second");
		$dm->save($customer, $this->user);

		// Get the revisions and make sure old value is stored
		$revisions = $dm->getRevisions("customer", $cid);
		$this->assertEquals("Second", $revisions[2]->getValue("name"));

		// Cleanup
		$dm->delete($customer, true);

	}

    // Test saving and deleting groupings
    public function testSaveGroupings()
    {
        $dm = $this->getDataMapper();
		if (!$dm)
			return;
        
        $groupings = $dm->getGroupings("customer", "groups");
        
        // Save new
        $newGroup = $groupings->create();
        $newGroup->name = "UTTEST DM::testSaveGroupings";
        $groupings->add($newGroup);
        $dm->saveGroupings($groupings);
        $group = $groupings->getByName($newGroup->name);
        $this->assertNotEquals($group->id, "");
        
        // Save existing
        $name2 = "UTTEST DM::testSaveGroupings::edited";
        $group = $groupings->getByName($newGroup->name);
        $group->name = $name2;
        $group->setDirty(true);
        $dm->saveGroupings($groupings);
        $gid = $group->id;
        unset($groupings);
        $groupings = $dm->getGroupings("customer", "groups");
        $group = $groupings->getById($gid);
        $this->assertEquals($name2, $group->name);
        
        // Test delete
        $groupings->delete($gid);
        $dm->saveGroupings($groupings);
        unset($groupings);
        $groupings = $dm->getGroupings("customer", "groups");
        $this->assertFalse($groupings->getById($gid));
    }
    
	/**
	 * TODO: Test getGroupings
	 */
	public function testGetGroupings()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
			return;
        
        // No filter
        $groupings = $dm->getGroupings("customer", "groups");
        
        // Delete just in case
        if ($groupings->getByName("UTEST.DM.testGetGroupings"))
        {
            $groupings->delete($groupings->getByName("UTEST.DM.testGetGroupings")->id);
            $dm->saveGroupings($groupings);
        }
        
        // Save new
        $newGroup = $groupings->create();
        $newGroup->name = "UTEST.DM.testGetGroupings";
        $groupings->add($newGroup);
        $dm->saveGroupings($groupings);
        $groupings = $dm->getGroupings("customer", "groups");
        $group1 = $groupings->getByName($newGroup->name);
        $this->assertEquals($newGroup->name, $group1->name);
        
        // Add a subgroup
        $newGroup2 = $groupings->create();
        $newGroup2->name = "UTEST.DM.testGetGroupings2";
        $newGroup2->parentId = $group1->id;
        $groupings->add($newGroup2);
        $dm->saveGroupings($groupings);
        unset($groupings);
        $groupings = $dm->getGroupings("customer", "groups");
        $group2 = $groupings->getByPath($newGroup->name . "/" . $newGroup2->name);
        $this->assertEquals($newGroup2->name, $group2->name);
        
        // Cleanup
        $groupings->delete($group1->id);
        $groupings->delete($group2->id);
        $dm->saveGroupings($groupings);
	}

	/**
	 * Test entity has moved functionalty
	 */
	public function testCommitImcrement()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
			return;
        
        // No filter grouping
        $groupings = $dm->getGroupings("customer", "groups");
        
        // Save new
        $newGroup = $groupings->create();
        $newGroup->name = "UTEST.DM.testGetGroupings";
        $groupings->add($newGroup);
        $dm->saveGroupings($groupings);
        $oldCommitId = $groupings->getByName($newGroup->name)->commitId;
        $this->assertNotEquals(0, $oldCommitId);

		// Add another to increment commit id
		$newGroup2 = $groupings->create();
        $newGroup2->name = "UTEST.DM.testGetGroupings2";
        $groupings->add($newGroup2);
        $dm->saveGroupings($groupings);
        $newCommitId = $groupings->getByName($newGroup2->name)->commitId;
        $this->assertNotEquals($oldCommitId, $newCommitId);

        // Reload and double check commitIDs
		$groupings = $dm->getGroupings("customer", "groups");
		$oldCommitId = $groupings->getByName($newGroup->name)->commitId;
		$newCommitId = $groupings->getByName($newGroup2->name)->commitId;
		$this->assertNotEquals($oldCommitId, $newCommitId);

		// Cleanup
        $groupings->delete($newGroup->id);
        $groupings->delete($newGroup2->id);
        $dm->saveGroupings($groupings);
	}

	/**
	 * Make sure that after saving the isDirty flag is unset
	 */
	public function testDirtyFlagUnsetOnSave()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
			return;

		// Create first entity
		$customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$customer->setValue("name", "testNotDirty");
		$dm->save($customer, $this->user);

		$this->assertFalse($customer->isDirty());

		// Cleanup
		$dm->delete($customer, true);
	}

	/**
	 * Make sure that after saving the isDirty flag is unset
	 */
	public function testDirtyFlagUnsetOnLoad()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
			return;

		// Create first entity
		$customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$customer->setValue("name", "testNotDirty");
		$oid = $dm->save($customer, $this->user);

		// Load into a new entity
		$ent = $this->account->getServiceManager()->get("EntityFactory")->create("customer");
		$ret = $dm->getById($ent, $oid);

		// Even though we just loaded all the data into the entity, it should not be marked as dirty
		$this->assertFalse($ent->isDirty());

		// Cleanup
		$dm->delete($ent, true);
	}

	/**
	 * Test to make sure that saving an entity with recurrence works in the datamapper
	 */
	public function testSaveAndLoadRecurrence()
	{
		$dm = $this->getDataMapper();

		// Create a simple recurrence pattern
		$recurrencePattern = new RecurrencePattern();
		$recurrencePattern->setRecurType(RecurrencePattern::RECUR_DAILY);
		$recurrencePattern->setDateStart(new \DateTime("2015-12-01"));
		$recurrencePattern->setDateEnd(new \DateTime("2015-12-02"));

		// Now save a task with this pattern and make sure it is given an id
		$task = $this->account->getServiceManager()->get("EntityLoader")->create("task");
		$task->setValue("name", "A test task");
		$task->setValue("start_date", date("Y-m-d", strtotime("2015-12-01")));
		$task->setRecurrencePattern($recurrencePattern);
		$tid = $dm->save($task, $this->user);
		$this->assertNotNull($recurrencePattern->getId());

		// Now close the task and reload it to make sure recurrence is still set
		$task2 = $this->account->getServiceManager()->get("EntityLoader")->get("task", $tid);
		$this->assertNotNull($task2->getRecurrencePattern());

		// Cleanup
		$dm->delete($task2, true);
	}

    /**
     * Make sure that when we delete the parent object it deletes its recurrence pattern
     */
    public function testDeleteRecurrence()
    {
        $dm = $this->getDataMapper();

        // Create a simple recurrence pattern
        $recurrencePattern = new RecurrencePattern();
        $recurrencePattern->setRecurType(RecurrencePattern::RECUR_DAILY);
        $recurrencePattern->setDateStart(new \DateTime("2015-12-01"));
        $recurrencePattern->setDateEnd(new \DateTime("2015-12-02"));

        // Now save a task with this pattern
        $task = $this->account->getServiceManager()->get("EntityLoader")->create("task");
        $task->setValue("name", "A test task");
        $task->setValue("start_date", date("Y-m-d", strtotime("2015-12-01")));
        $task->setRecurrencePattern($recurrencePattern);
        $tid = $dm->save($task, $this->user);

        $recurId = $recurrencePattern->getId();
        $this->assertTrue($recurId > 0);

        // Delete the object and make sure the pattern cannot be loaded
        $dm->delete($task, true);

        // Try to load recurId which should result in null
        $recurDm = $this->account->getServiceManager()->get("RecurrenceDataMapper");
        $loadedPattern = $recurDm->load($recurId);
        $this->assertNull($loadedPattern);
    }

	/**
	 * Make sure that if we save an entity without fvals for fkey and object references
	 * the datamapper will set them.
	 */
	public function testUpdateForeignKeyNames()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
		{
			// Do not run if we don't have a datamapper to work with
			$this->assertTrue(true);
			return;
		}

		// Create a few test groups
		$groupingsStat = $dm->getGroupings("customer", "status_id");
		$statGrp = $groupingsStat->create("Unit Test Status");
		$groupingsStat->add($statGrp);
		$dm->saveGroupings($groupingsStat);

		$groupingsGroups = $dm->getGroupings("customer", "groups");
		$groupsGrp = $groupingsGroups->create("Unit Test Group");
		$groupingsGroups->add($groupsGrp);
		$dm->saveGroupings($groupingsGroups);

		// Create an entity and initialize values
		$customer = $this->createCustomer();
		// fkey with no label (third param)
		$customer->setValue("status_id", $statGrp->id);
		// fkey_multi with no label (third param)
		$customer->addMultiValue("groups", $groupsGrp->id);
		// object with no label (third param)
		$customer->setValue("owner_id", $this->user->getId());

		// Save should call private updateForeignKeyNames in the DataMapperAbstract
		$cid = $dm->save($customer, $this->user);

		// Load the entity from the datamapper
		$ent = $this->account->getServiceManager()->get("EntityFactory")->create("customer");
		$ret = $dm->getById($ent, $cid);

		// Make sure the fvals for references are updated
		$this->assertEquals($ent->getValueName("status_id", $statGrp->id), $statGrp->name);
		$this->assertEquals($ent->getValueName("groups", $groupsGrp->id), $groupsGrp->name);
		$this->assertEquals($ent->getValueName("owner_id", $this->user->getId()), $this->user->getName());

		// Cleanup
		$groupingsStat->delete($statGrp->id);
		$dm->saveGroupings($groupingsStat);
		$groupingsGroups->delete($groupsGrp->id);
		$dm->saveGroupings($groupingsGroups);
		$dm->delete($ent, true);
	}

	/**
	 * TODO: Test verifyUniqueName
	 */
	public function testVerifyUniqueName()
	{
	}

	/**
	 * Test the public function for entityHasMoved
	 */
	public function testCheckEntityHasMoved()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
			return;

		// Create first entity
		$customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$customer->setValue("name", "testSetEntityMovedTo");
		$oid1 = $dm->save($customer, $this->user);

		// Create second entity
		$customer2 = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$customer2->setValue("name", "testSetEntityMovedTo");
		$oid2 = $dm->save($customer2, $this->user);

		// Set moved to
		$def = $customer->getDefinition();
		$ret = $dm->setEntityMovedTo($def, $oid1, $oid2);

		$movedTo = $dm->checkEntityHasMoved($customer->getDefinition(), $oid1);

		// Now make sure the movedTo works
		$this->assertEquals($oid2, $movedTo);

		// Cleanup
		$dm->delete($customer, true);
		$dm->delete($customer2, true);
	}
}
