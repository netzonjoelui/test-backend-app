<?php
/**
 * Test entity/object class
 */
namespace NetricTest\EntitySync\Collection;

use Netric\EntitySync;
use Netric\EntitySync\Collection;
use PHPUnit_Framework_TestCase;

class EntityCollectionTest extends AbstractCollectionTests 
{
    /**
     * New objects created
     *
     * @var \Netric\Entity[]
     */
    private $newCreated = array();

    /**
     * @return Collection\EntityCollection
     */
	protected function getCollection()
	{
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $collection = new Collection\EntityCollection($this->esDataMapper, $this->commitManager, $index);
        $collection->setObjType("customer");
        return $collection;
	}

    protected function createLocal()
    {
        // Create new customer with the passed data
        $newEnt = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
        $newEnt->setValue("name", "EntityEyncTests");
        $this->account->getServiceManager()->get("Entity_DataMapper")->save($newEnt);
        $this->newCreated[] = $newEnt;
        return array("id"=>$newEnt->getId(), "revision"=>$newEnt->getValue("commit_id"));
    }

    protected function changeLocal($id)
    {
        foreach ($this->newCreated as $createdEnt)
        {
            if ($createdEnt->getId() == $id)
            {
                // Record object change
                $createdEnt->setValue("name", "EntityEyncTests_2");
                $this->account->getServiceManager()->get("Entity_DataMapper")->save($createdEnt);
            }
        }
    }

    protected function deleteLocal($id=null)
    {
        foreach ($this->newCreated as $createdEnt)
        {
            if ($id == $createdEnt->getId() || $id==null)
                $this->account->getServiceManager()->get("Entity_DataMapper")->delete($createdEnt, true);
        }
    }


	/**
     * Test getting changed objects for this collection
     *
    public function testGetExportChanged()
    {
        $pid = "AntObjectSync_CollectionTest::testGetChangedObjects";

        $localId = $this->createLocal();

		// Create and save partner with one collection watching customers
		$partner = new EntitySync\Partner($this->esDataMapper);
        $partner->setPartnerId($pid);
        $partner->setOwnerId($this->user->getId());
        $collection = $this->getCollection();
        $this->esDataMapper->savePartner($partner);

        // Initial pull should start with all objects
        $stats = $collection->getExportChanged();
        $this->assertTrue(count($stats) >= 1);
        $collection->fastForwardToHead();

        // Should be no changes now
        $stats = $collection->getExportChanged();
        $this->assertEquals(0, count($stats));

        $this->changeLocal();

		// Make sure the one change is now returned
        $stats = $collection->getExportChanged();
        $this->assertTrue(count($stats) >= 1);
        $this->assertEquals($stats[0]['id'], $localId);

        // Cleanup
        $this->esDataMapper->deletePartner($partner, true);
    }
     */

    /**
     * Test exporting imported
     */
    public function testGetExportChangedImported()
    {
        $pid = "AntObjectSync_CollectionTest::testGetChangedObjects";

        // Create customer just in case there are none already in the database
        $customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
        $customer->setValue("name", "EntityEyncTests");
        $this->account->getServiceManager()->get("Entity_DataMapper")->save($customer);

        // Create and save partner with one collection watching customers
        $partner = new EntitySync\Partner($this->esDataMapper);
        $partner->setPartnerId($pid);
        $partner->setOwnerId($this->user->getId());
        $collection = $this->getCollection();
        $collection->setObjType("customer");
        $this->esDataMapper->savePartner($partner);

        // Initial pull should start with all objects
        $stats = $collection->getExportChanged();
        $this->assertTrue(count($stats) >= 1);
        $collection->fastForwardToHead();

        // Should be no changes now
        $stats = $collection->getExportChanged();
        $this->assertEquals(0, count($stats));

        // Record object change
        $customer->setValue("name", "EntityEyncTests_2");
        $this->account->getServiceManager()->get("Entity_DataMapper")->save($customer);

        // Make sure the one change is now returned
        $stats = $collection->getExportChanged();
        $this->assertTrue(count($stats) >= 1);
        $this->assertEquals($stats[0]['id'], $customer->getId());

        // Cleanup
        $this->esDataMapper->deletePartner($partner, true);
        $this->account->getServiceManager()->get("Entity_DataMapper")->delete($customer, true);
    }

    /**
     * Make sure we can detect when an entity has been deleted
     */
    public function testGetExportChanged_Deleted() 
    {
        $pid = "AntObjectSync_CollectionTest::testGetChangedObjects";

        // Create customer just in case there are none already in the database
        $customer = $this->account->getServiceManager()->get("EntityLoader")->create("customer");
		$customer->setValue("name", "EntityEyncTests");
		$this->account->getServiceManager()->get("Entity_DataMapper")->save($customer);
        $customerId = $customer->getId();
		
		// Create and save partner with one collection watching customers
		$partner = new EntitySync\Partner($this->esDataMapper);
        $partner->setPartnerId($pid);
        $partner->setOwnerId($this->user->getId());
        $collection = $this->getCollection();
        $collection->setObjType("customer");
        $partner->addCollection($collection);
        $this->esDataMapper->savePartner($partner);

        // Get all exported which will cause the customer to be logged
        while (count($stats = $collection->getExportChanged())) {}

        // Fast-forward past the created customer
        $collection->fastForwardToHead();
        $stats = $collection->getExportChanged();
        $this->assertEquals(0, count($stats));

        // Soft delete the customer
        $this->account->getServiceManager()->get("Entity_DataMapper")->delete($customer);

		// Make sure the one change is now returned for the deleted item
        $stats = $collection->getExportChanged();
        $this->assertEquals(1, count($stats));
        $this->assertEquals($customerId, $stats[0]['id']);
        $this->assertEquals("delete", $stats[0]['action']);

        // Make sure a next call does not return the stale item again
        $stats = $collection->getExportChanged();
        $this->assertEquals(0, count($stats));

        // Cleanup
        $this->esDataMapper->deletePartner($partner, true);
        $this->account->getServiceManager()->get("Entity_DataMapper")->delete($customer, true);
    }

}
