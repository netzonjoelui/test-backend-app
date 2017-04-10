<?php
/**
 * Test entity definition loader class that is responsible for creating and initializing exisiting definitions
 */
namespace NetricTest\EntitySync;

use Netric\EntitySync;

/**
 * @group integration
 * @group integration-pgsql
 */
class DataMapperPgsqlTest extends AbstractDataMapperTests 
{
	/**
	 * Setup datamapper
	 *
	 * @return DataMapperInterface
	 */
	protected function getDataMapper()
	{
		$dbh = $this->account->getServiceManager()->get("Db");
		return new EntitySync\DataMapperPgsql($this->account, $dbh);
	}

	/**
	 * Test construction
	 */
	public function testConstruct()
	{
		$dm = $this->getDataMapper();
		$this->assertInstanceOf('\Netric\EntitySync\DataMapperPgsql', $dm);
	}

	/**
	 * In the pgsql datamapper save and delete individual collections
	 * are handled as private helper functions to saving partners.
	 *
	 * Other datamappers will probably implement this differently depending
	 * on how they manage relationships. For example, a document store will
	 * probably just embed the collections into the partner object.
	 */
	public function testSaveAndDeleteCollection()
    {
        $dm = $this->getDataMapper();

        // Setup save colleciton reflection object
        $refIm = new \ReflectionObject($dm);
        $saveCollection = $refIm->getMethod("saveCollection");
        $saveCollection->setAccessible(true);

        // Save a the partner because it is required for saving a colleciton
        $partner = new EntitySync\Partner($dm);
        $partner->setPartnerId("UTEST-DEVICE-SAVEANDLOAD");
        $partner->setOwnerId($this->user->getId());
        $ret = $dm->savePartner($partner);

        // Create a new collection and save it
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $commitManager = $this->account->getServiceManager()->get("EntitySyncCommitManager");
        $collection = new EntitySync\Collection\EntityCollection($dm, $commitManager, $index);
        $collection->setPartnerId($partner->getId());
        $collection->setObjType("customer");

        $ret = $saveCollection->invoke($dm, $collection);
        $this->assertTrue($ret, $dm->getLastError());
        $this->assertNotNull($collection->getId());
        $this->assertEquals("customer", $collection->getObjType());

        // Save changes to a collection
        $collection->setObjType("task");
        $ret = $saveCollection->invoke($dm, $collection);
        $this->assertTrue($ret, $dm->getLastError());
        $this->assertNotNull($collection->getId());
        $this->assertEquals("task", $collection->getObjType());

        // Cleanup by partner id (second param)
        $dm->deletePartner($partner, true);

        $deleteCollection = $refIm->getMethod("deleteCollection");
        $deleteCollection->setAccessible(true);
        $ret = $deleteCollection->invoke($dm, $collection->getId());
        $this->assertTrue($ret, $dm->getLastError());
    }
	
}
