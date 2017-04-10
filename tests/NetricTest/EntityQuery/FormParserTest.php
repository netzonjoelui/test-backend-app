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

class FormParserTest extends PHPUnit_Framework_TestCase 
{   
    public function testBuildQueryWheres()
    {
        $query = new Netric\EntityQuery("customer");
        $params = array(
            "where" => array(
                "and,first_name,is_equal,Sky",
                "or,first_name,contains,Aerin",
            ),
            "q" => "full text search",
        );
        Netric\EntityQuery\FormParser::buildQuery($query, $params);
                
        // Test wheres
        $wheres = $query->getWheres();
        $this->assertEquals(3, count($wheres));
        
        $this->assertEquals("and", $wheres[0]->bLogic);
        $this->assertEquals("first_name", $wheres[0]->fieldName);
        $this->assertEquals("is_equal", $wheres[0]->operator);
        $this->assertEquals("Sky", $wheres[0]->value);
        
        $this->assertEquals("or", $wheres[1]->bLogic);
        $this->assertEquals("first_name", $wheres[1]->fieldName);
        $this->assertEquals("contains", $wheres[1]->operator);
        $this->assertEquals("Aerin", $wheres[1]->value);
        
        $this->assertEquals("and", $wheres[2]->bLogic);
        $this->assertEquals("*", $wheres[2]->fieldName);
        $this->assertEquals("is_equal", $wheres[2]->operator);
        $this->assertEquals($params["q"], $wheres[2]->value);
    }
    
    public function testBuildQueryOrderBy()
    {
        $query = new Netric\EntityQuery("customer");
        $params = array(
            "order_by" => array(
                "last_name",
                "first_name,DESC",
            ),
        );
        Netric\EntityQuery\FormParser::buildQuery($query, $params);
                
        // Test wheres
        $orders = $query->getOrderBy();
        $this->assertEquals(2, count($orders));
        
        $this->assertEquals("last_name", $orders[0]->fieldName);
        $this->assertEquals("ASC", $orders[0]->direction);
        
        $this->assertEquals("first_name", $orders[1]->fieldName);
        $this->assertEquals("DESC", $orders[1]->direction);
        
    }
    
    public function testBuildQueryOffsetLimit()
    {
        $query = new Netric\EntityQuery("customer");
        $params = array(
            "offset" => 100,
            "limit" => 73,
        );
        Netric\EntityQuery\FormParser::buildQuery($query, $params);
                
        $this->assertEquals($params["offset"], $query->getOffset());
        $this->assertEquals($params['limit'], $query->getLimit());
    }
}