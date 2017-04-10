<?php
/**
 * Test entity/object class
 */
namespace NetricTest\EntitySync\Collection;

use Netric\EntitySync;
use Netric\EntitySync\Collection;
use PHPUnit_Framework_TestCase;

class GroupingCollectionTest extends AbstractCollectionTests 
{
    /**
     * Entity DataMapper
     *
     * @var \Netric\Entity\DataMapperInterface
     */
    private $entityDataMapper = null;

    /**
     * Groupings object
     *
     * @var \Netric\EntityGrouping
     */
    private $groupings = null;

    /**
     * Setup datamapper
     */
    protected function setUp()
    {
        // Make sure we don't override parent tearDown
        parent::setUp();

        $this->entityDataMapper = $this->account->getServiceManager()->get("Entity_DataMapper");
    }

    /**
     * Cleanup
     */
    protected function tearDown()
    {
        // Make sure we don't override parent tearDown
        parent::tearDown();
    }
    
    /**
     * Required by AbstractCollectionTests
     */
	protected function getCollection()
	{

        $collection = new Collection\GroupingCollection($this->esDataMapper, $this->commitManager, $this->entityDataMapper);
        $collection->setObjType("customer");
        $collection->setFieldName("groups");
		return $collection;
	}

    protected function createLocal()
    {
        // Create the grouping below
        $this->groupings = $this->entityDataMapper->getGroupings("customer", "groups");
        $newGroup = $this->groupings->create();
        $newGroup->name = "UTEST CS::testGetExportChanged" . rand();
        $this->groupings->add($newGroup);
        $this->entityDataMapper->saveGroupings($this->groupings);
        $group = $this->groupings->getByName($newGroup->name);
        return array("id"=>$group->id, "revision"=>$group->commitId);
    }

    protected function changeLocal($id)
    {
        $group = $this->groupings->getById($id);
        // Record a change to the grouping
        $group->name = "UTEST CS::testGetExportChanged" . rand();
        $group->setDirty(true);
        $this->entityDataMapper->saveGroupings($this->groupings);
    }

    protected function deleteLocal($id=null)
    {
        if ($this->groupings)
        {
            if ($id)
            {
                $this->groupings->delete($id);
            }

            $this->entityDataMapper->saveGroupings($this->groupings);
        }
    }

	/**
     * Test getting changed objects for this collection
     *
    public function testGetExportChanged() 
    {
        // Setup collection
        $collection = $this->getCollection();

		// Create and save partner with one collection watching customers
		$this->partner = new EntitySync\Partner($this->esDataMapper);
        $this->partner->setPartnerId("GroupingCollectionTest::testGetExportChanged");
        $this->partner->setOwnerId($this->user->getId());
        $this->esDataMapper->savePartner($this->partner);

        $id = $this->createLocal();

        // Fast forward past all previous groupings
        $collection->fastForwardToHead();

        // Should be no changes now, we have to loop over to check
        $stats = $collection->getExportChanged();
        $this->assertEquals(0, count($stats));

        $this->changeLocal();

		// Make sure the one change is now returned
        $stats = $collection->getExportChanged();
        $found = false;
        foreach ($stats as $stat)
        {
            if ($stat["id"] == $id)
                $found = true;
        }
        $this->assertTrue($found);
    }*/

    /**
     * Make sure we can detect when an entity has been deleted
     *
    public function testGetExportChanged_Deleted() 
    {
        // Setup collection 
        $collection = $this->getCollection();
        $collection->setObjType("customer");
        $collection->setFieldName("groups");

        // Create and save partner with one collection watching customers
        $this->partner = new EntitySync\Partner($this->esDataMapper);
        $this->partner->setPartnerId("GroupingCollectionTest::testGetExportChanged_Deleted");
        $this->partner->setOwnerId($this->user->getId());
        $this->partner->addCollection($collection);
        $this->esDataMapper->savePartner($this->partner);

        $dm = $this->entityDataMapper;

        // Create the grouping below
        $groupings = $dm->getGroupings("customer", "groups");
        $newGroup = $groupings->create();
        $newGroup->name = "UTTEST CS::testGetExportChanged_Deleted";
        $groupings->add($newGroup);
        $dm->saveGroupings($groupings);
        $group1 = $groupings->getByName("UTTEST CS::testGetExportChanged_Deleted");

        // Get exported which will cause the customer to be logged
        $stats = $collection->getExportChanged();

        // Fast forward past all previous groupings
        $collection->fastForwardToHead();

        // Should be no changes now, we have to loop over to check
        $stats = $collection->getExportChanged();
        $this->assertEquals(0, count($stats));

        // Delete the grouping
        $groupings->delete($group1->id);
        $dm->saveGroupings($groupings);

        // Make sure the one change is now returned
        $stats = $collection->getExportChanged();
        $foundStat = null;
        foreach ($stats as $stat)
        {
            if ($stat["id"] == $group1->id)
                $foundStat = $stat;
        }
        $this->assertNotNull($foundStat);
        $this->assertEquals("delete", $foundStat['action']);

        // Make sure a second call does not get the same stale id
        $stats = $collection->getExportChanged();
        $foundStat = null;
        foreach ($stats as $stat)
        {
            if ($stat["id"] == $group1->id)
                $foundStat = $stat;
        }
        $this->assertNull($foundStat);
    }*/
}