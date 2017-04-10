<?php
/**
 * Test querying ElasticSearch server
 *
 * Most tests are inherited from IndexTestsAbstract.php.
 * Only define index specific tests here and try to avoid name collision with the tests
 * in the parent class. For the most part, the parent class tests all public functions
 * so private functions should be tested below.
 */
namespace NetricTest\EntityQuery;

use Netric;
use PHPUnit_Framework_TestCase;

class ResultsTest extends PHPUnit_Framework_TestCase 
{   
    /**
     * Test automatic pagination
     */
    public function testPagination()
    {
        $query = new Netric\EntityQuery("customer");
        $query->setOffset(0);
        $query->setLimit(2);
        
        $results = new Netric\EntityQuery\Results($query);
        $results->setTotalNum(5);
        
        $ent = $results->getEntity(3); // Should push us to the next page
        $this->assertEquals(2, $results->getOffset());
        
        // Do it again but skip a bunch of pages this time
        $query->setOffset(0);
        $query->setLimit(50);
        $results = new Netric\EntityQuery\Results($query);
        $results->setTotalNum(150);
        
        $ent = $results->getEntity(149); // Should push us to the next page
        $this->assertEquals(100, $results->getOffset());
        
        $ent = $results->getEntity(75); // Should push us to the next page
        $this->assertEquals(50, $results->getOffset());
        
        // Now test less than
        $ent = $results->getEntity(5); // Should push us to the next page
        $this->assertEquals(0, $results->getOffset());
    }
}