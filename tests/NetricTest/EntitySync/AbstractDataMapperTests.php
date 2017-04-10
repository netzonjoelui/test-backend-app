<?php
/**
 * Define common tests that will need to be run with all data mappers.
 *
 * In order to implement the unit tests, a datamapper test case just needs
 * to extend this class and create a getDataMapper class that returns the
 * datamapper to be tested
 */
namespace NetricTest\EntitySync;

use Netric\EntitySync;
use PHPUnit_Framework_TestCase;

/**
 * @group integration
 */
abstract class AbstractDataMapperTests extends PHPUnit_Framework_TestCase 
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
     * Test saving and loading partners
     */
    public function testSaveAndLoadPartner()
    {
        $partnerId = "UTEST-DEVICE-SAVEANDLOAD";
        $dm = $this->getDataMapper();

        // Save the partner
        $partner = new EntitySync\Partner($dm);
        $partner->setPartnerId($partnerId);
        $partner->setOwnerId($this->user->getId());
        $ret = $dm->savePartner($partner);
        $this->assertTrue($ret, $dm->getLastError());
        $this->assertNotNull($partner->getId());

        // Load the partner in another object and test
        $partner2 = $dm->getPartnerById($partner->getId());
        $this->assertEquals($partner->getId(), $partner2->getId());
        $this->assertEquals($partner->getPartnerId(), $partner2->getPartnerId());

        // Cleanup by partner id (second param)
        $dm->deletePartner($partner, true);
    }

    public function testDeletePartner()
    {
        $partnerId = "UTEST-DEVICE-SAVEANDLOAD";
        $dm = $this->getDataMapper();

        // Save the partner
        $partner = new EntitySync\Partner($dm);
        $partner->setPartnerId($partnerId);
        $partner->setOwnerId($this->user->getId());
        $ret = $dm->savePartner($partner);

        // Now delete it
        $dm->deletePartner($partner, true);

        // Try to load the partner and verify it was not found
        $partner2 = $dm->getPartnerById($partner->getId());
        $this->assertNull($partner2);
    }

    /**
     * Now test saving and loading an entity collection through the partner
     */
    public function testSaveAndLoadPartnerEntityCollection()
    {
        $partnerId = "UTEST-DEVICE-SAVEANDLOADPARTNERENTITYCOLLECITON";
        $dm = $this->getDataMapper();
        $testConditions = array(
            array("blogic"=>"and", "field"=>"name", "operator"=>"is_equal", "condValue"=>"test")
        );

        // Create a partner
        $partner = new EntitySync\Partner($dm);
        $partner->setPartnerId($partnerId);
        $partner->setOwnerId($this->user->getId());

        // Add a collection
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $commitManager = $this->account->getServiceManager()->get("EntitySyncCommitManager");
        $collection = new EntitySync\Collection\EntityCollection($dm, $commitManager, $index);
        $collection->setPartnerId($partner->getId());
        $collection->setObjType("customer");
        $collection->setConditions($testConditions);
        $partner->addCollection($collection);

        // Save the partner
        $dm->savePartner($partner);

        // Now load the parter fresh and check the collection
        $partner2 = $dm->getPartnerById($partner->getId());
        $collections = $partner2->getCollections();
        $this->assertEquals(1, count($collections));
        $this->assertEquals($testConditions, $collections[0]->getConditions());
        $this->assertEquals("customer", $collections[0]->getObjType());

        // Cleanup by partner id (second param)
        $dm->deletePartner($partner, true);
    }

    /*
     * Make sure we can make changes to a collection inside a partner and save
     */
    public function testUpdatePartnerCollection()
    {
        $partnerId = "UTEST-DEVICE-SAVEAUPLOADPARTNERENTITYCOLLECITON";
        $dm = $this->getDataMapper();
        $testConditions = array(
            array("blogic"=>"and", "field"=>"name", "operator"=>"is_equal", "condValue"=>"test")
        );

        // Create a partner
        $partner = new EntitySync\Partner($dm);
        $partner->setPartnerId($partnerId);
        $partner->setOwnerId($this->user->getId());

        // Add a collection
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $commitManager = $this->account->getServiceManager()->get("EntitySyncCommitManager");
        $collection = new EntitySync\Collection\EntityCollection($dm, $commitManager, $index);
        $collection->setPartnerId($partner->getId());
        $collection->setObjType("customer");
        $partner->addCollection($collection);

        // Save the partner which should save the collection
        $dm->savePartner($partner);

        // Reload the parter fresh and update it
        $partner2 = $dm->getPartnerById($partner->getId());
        $collections = $partner2->getCollections();
        $collections[0]->setFieldName("categories");
        $collections[0]->setConditions($testConditions);
        $dm->savePartner($partner2);

        // Reload the parter fresh and update it
        $partner3 = $dm->getPartnerById($partner->getId());
        $collections = $partner3->getCollections();
        $this->assertEquals(1, count($collections));
        $this->assertEquals($testConditions, $collections[0]->getConditions());
        $this->assertEquals("customer", $collections[0]->getObjType());
        $this->assertEquals("categories", $collections[0]->getFieldName());

        // Cleanup by partner id (second param)
        $dm->deletePartner($partner, true);
    }

    /**
     * Test deleting a collection from the partner
     */
    public function testDeletePartnerCollection()
    {
        $partnerId = "UTEST-DEVICE-SAVEANDLOADPARTNERENTITYCOLLECITON";
        $dm = $this->getDataMapper();

        // Create a partner
        $partner = new EntitySync\Partner($dm);
        $partner->setPartnerId($partnerId);
        $partner->setOwnerId($this->user->getId());

        // Add a collection and save
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $commitManager = $this->account->getServiceManager()->get("EntitySyncCommitManager");
        $collection = new EntitySync\Collection\EntityCollection($dm, $commitManager, $index);
        $collection->setPartnerId($partner->getId());
        $collection->setObjType("customer");
        $partner->addCollection($collection);
        $dm->savePartner($partner);

        // Now load the parter and delete the collection
        $partner2 = $dm->getPartnerById($partner->getId());
        $collections = $partner2->getCollections();
        $partner2->removeCollection($collections[0]->getId());
        $dm->savePartner($partner2);

        // Load it once more and make sure there are no collections
        $partner3 = $dm->getPartnerById($partner->getId());
        $this->assertEquals(0, count($partner3->getCOllections()));

        // Cleanup by partner id (second param)
        $dm->deletePartner($partner, true);
    }

    public function testLogExportedCommit()
    {
        $partnerId = "UTEST-DEVICE-SAVEANDLOADPARTNERENTITYCOLLECITON";
        $uniqueId = 1234;
        $commitId1 = 1;

        $dm = $this->getDataMapper();

        // Create a partner
        $partner = new EntitySync\Partner($dm);
        $partner->setPartnerId($partnerId);
        $partner->setOwnerId($this->user->getId());

        // Add a collection and save
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $commitManager = $this->account->getServiceManager()->get("EntitySyncCommitManager");
        $collection = new EntitySync\Collection\EntityCollection($dm, $commitManager, $index);
        $collection->setPartnerId($partner->getId());
        $collection->setObjType("customer");
        $partner->addCollection($collection);
        $dm->savePartner($partner);

        // Add new exported entry
        $ret = $dm->logExported($collection->getType(), $collection->getId(), $uniqueId, $commitId1);
        $this->assertTrue($ret);

        // Cleanup by partner id (second param)
        $dm->deletePartner($partner, true);
    }

    public function testSetAndGetExportedCommitStale()
    {
        $partnerId = "UTEST-DEVICE-SAVEANDLOADPARTNERENTITYCOLLECITON";
        $uniqueId = 1234;
        $commitId1 = 1;
        $commitId2 = 2;

        $dm = $this->getDataMapper();

        // Create a partner
        $partner = new EntitySync\Partner($dm);
        $partner->setPartnerId($partnerId);
        $partner->setOwnerId($this->user->getId());

        // Add a collection and save
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $commitManager = $this->account->getServiceManager()->get("EntitySyncCommitManager");
        $collection = new EntitySync\Collection\EntityCollection($dm, $commitManager, $index);
        $collection->setPartnerId($partner->getId());
        $collection->setObjType("customer");
        $partner->addCollection($collection);
        $dm->savePartner($partner);

        // Add new exported entry then mark it as stale
        $dm->logExported($collection->getType(), $collection->getId(), $uniqueId, $commitId1);
        $dm->setExportedStale($collection->getType(), $commitId1, $commitId2);

        // Make sure the stale stat is returned when called
        $staleStats = $dm->getExportedStale($collection->getId());
        $this->assertEquals(1, count($staleStats));
        $this->assertEquals($uniqueId, $staleStats[0]);

        // Cleanup by partner id (second param)
        $dm->deletePartner($partner, true);
    }

}