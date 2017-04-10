<?php
/**
 * Define common tests that will need to be run with all data mappers.
 *
 * In order to implement the unit tests, a datamapper test case just needs
 * to extend this class and create a getDataMapper class that returns the
 * datamapper to be tested
 */
namespace NetricTest\EntityQuery\Index\Aggregation;

use Netric\EntityQuery\Aggregation;
use PHPUnit_Framework_TestCase;

abstract class AggregateTestsAbstract extends PHPUnit_Framework_TestCase 
{
    /**
     * Tennant account
     * 
     * @var \Netric\Account\Account
     */
    protected $account = null;
    
    /**
     * Campaign id used for filter
     * 
     * @var int
     */
    protected $campaignId = null;

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->createTestData();
	}
    
    /**
     * Cleanup test objects
     */
    protected function tearDown()
    {
        $this->deleteTestData();
    }
    
    /**
     * Required by all derrieved classes
     * 
     * @return \Netric\EnittyQuery\Index\IndexInterface The setup index to query
     */
    abstract protected function getIndex();
    
    /**
     * Create a few test objects
     */
    protected function createTestData()
    {
        // Cleanup any old objects
        $this->deleteTestData();
        
        // Get datamapper
        $dm = $this->account->getServiceManager()->get("Entity_DataMapper");
        
        // Create a campaign for filtering
        $obj = $this->account->getServiceManager()->get("EntityLoader")->create("marketing_campaign");
        $obj->setValue("name", "Unit Test Aggregates");
        $this->campaignId = $dm->save($obj);
        if (!$this->campaignId)
            throw new \Exception("Could not create campaign");

        // Create first opportunity
        $obj = $this->account->getServiceManager()->get("EntityLoader")->create("opportunity");
        $obj->setValue("name", "Website");
        $obj->setValue("f_won", false);
        $obj->setValue("probability_per", 50);
        $obj->setValue("campaign_id", $this->campaignId);
        $obj->setValue("amount", 100);
        $oid = $dm->save($obj);
        
        // Create first opportunity
        $obj = $this->account->getServiceManager()->get("EntityLoader")->create("opportunity");
        $obj->setValue("name", "Application");
        $obj->setValue("f_won", true);
        $obj->setValue("probability_per", 75);
        $obj->setValue("campaign_id", $this->campaignId);
        $obj->setValue("amount", 50);
        $oid = $dm->save($obj);
    }  
    
    
    /**
     * Create a few test objects
     */
    protected function deleteTestData()
    {
        // Get index and fail if not setup
        $index = $this->getIndex();
        if (!$index)
            return;
        
        $dm = $this->account->getServiceManager()->get("Entity_DataMapper");
        
        // Find campaign id if not set
        if (!$this->campaignId)
        {
            $query = new \Netric\EntityQuery("marketing_campaign");
            $query->where('name')->equals("Unit Test Aggregates");
            $res = $index->executeQuery($query);
            if ($res->getTotalNum() > 0)
                $this->campaignId = $res->getEntity(0)->getId();
        }
        
        // Nothing to delete yet
        if (!$this->campaignId)
            return;
        
        
        $query = new \Netric\EntityQuery("opportunity");
        $query->where('campaign_id')->equals($this->campaignId);
        $res = $index->executeQuery($query);
        for ($i = 0; $i < $res->getTotalNum(); $i++)
        {
            $ent = $res->getEntity(0);
            $dm->delete($ent, true); // delete hard
        }
        
        // Delete the campaign
        $ent = $this->account->getServiceManager()->get("EntityLoader")->get("marketing_campaign", $this->campaignId);
        $dm->delete($ent, true);
    }
    
    /**
     * Make sure the getTypeName for the abstract class works
     */
    public function testGetTypeName()
    {
        $agg = new \Netric\EntityQuery\Aggregation\Terms("test");
        $this->assertEquals("terms", $agg->getTypeName());
    }
    
    /**
     * Test terms aggregate
     */
    public function testTerms()
    {
        // Get index and fail if not setup
        $index = $this->getIndex();
        if (!$index)
            return;

        $query = new \Netric\EntityQuery("opportunity");
        $query->where('campaign_id')->equals($this->campaignId);
        
        $agg = new \Netric\EntityQuery\Aggregation\Terms("test");
        $agg->setField("name");
        $query->addAggregation($agg);
        $agg = $index->executeQuery($query)->getAggregation("test");
        $appInd = (strtolower($agg[0]["term"]) == "application") ? 0 : 1;
        $webInd = (strtolower($agg[0]["term"]) == "website") ? 0 : 1;
        
        $this->assertEquals(1, $agg[$appInd]["count"]);
        $this->assertEquals("application", strtolower($agg[$appInd]["term"]));
        $this->assertEquals(1, $agg[$webInd]["count"]);
        $this->assertEquals("website", strtolower($agg[$webInd]["term"]));
    }
    
    /**
     * Test sum aggregate
     */
    public function testSum()
    {
        // Get index and fail if not setup
        $index = $this->getIndex();
        if (!$index)
            return;

        $query = new \Netric\EntityQuery("opportunity");
        $query->where('campaign_id')->equals($this->campaignId);
        
        $agg = new \Netric\EntityQuery\Aggregation\Sum("test");
        $agg->setField("amount");
        $query->addAggregation($agg);
        $agg = $index->executeQuery($query)->getAggregation("test");
        $this->assertEquals(150, $agg); // 2 opps one with 50 and one with 100
    }
    
    /**
     * Test stats aggregate
     */
    public function testStats()
    {
        // Get index and fail if not setup
        $index = $this->getIndex();
        if (!$index)
            return;

        $query = new \Netric\EntityQuery("opportunity");
        $query->where('campaign_id')->equals($this->campaignId);
        
        $agg = new \Netric\EntityQuery\Aggregation\Stats("test");
        $agg->setField("amount");
        $query->addAggregation($agg);
        $agg = $index->executeQuery($query)->getAggregation("test");
        $this->assertEquals(2, $agg["count"]); // 2 opps one with 50 and one with 100
        $this->assertEquals(50, $agg["min"]); // 2 opps one with 50 and one with 100
        $this->assertEquals(100, $agg["max"]); // 2 opps one with 50 and one with 100
        $this->assertEquals(((100 + 50)/2), $agg["avg"]); // 2 opps one with 50 and one with 100
        $this->assertEquals(150, $agg["sum"]); // 2 opps one with 50 and one with 100
    }
    
    /**
     * Test agv aggregate
     */
    public function testAvg()
    {
        // Get index and fail if not setup
        $index = $this->getIndex();
        if (!$index)
            return;

        $query = new \Netric\EntityQuery("opportunity");
        $query->where('campaign_id')->equals($this->campaignId);
        
        $agg = new \Netric\EntityQuery\Aggregation\Avg("test");
        $agg->setField("amount");
        $query->addAggregation($agg);
        $agg = $index->executeQuery($query)->getAggregation("test");
        $this->assertEquals(((100 + 50)/2), $agg); // 2 opps one with 50 and one with 100
    }
    
    /**
     * Test min aggregate
     */
    public function testMin()
    {
        // Get index and fail if not setup
        $index = $this->getIndex();
        if (!$index)
            return;

        $query = new \Netric\EntityQuery("opportunity");
        $query->where('campaign_id')->equals($this->campaignId);
        
        $agg = new \Netric\EntityQuery\Aggregation\Min("test");
        $agg->setField("amount");
        $query->addAggregation($agg);
        $agg = $index->executeQuery($query)->getAggregation("test");
        $this->assertEquals(50, $agg); // 2 opps one with 50 and one with 100
    }
}