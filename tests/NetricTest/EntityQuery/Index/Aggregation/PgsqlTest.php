<?php
/**
 * Test aggregates against pgsql index
 *
 * Most tests are inherited from AggregateTestsAbstract.php.
 * Only define index specific tests here and try to avoid name collision with the tests
 * in the parent class. For the most part, the parent class tests all public functions
 * so private functions should be tested below.
 */
namespace NetricTest\EntityQuery\Index\Aggregation;

use Netric;
use PHPUnit_Framework_TestCase;

class PgsqlTest extends AggregateTestsAbstract 
{
    /**
     * Handle to pgsql database
     * 
     * @var Db\Pgsql
     */
    private $dbh = null;
    
	/**
	 * Use this funciton in all the indexes to construct the datamapper
	 *
	 * @return EntityDefinition_DataMapperInterface
	 */
	protected function getIndex()
	{      
        /*
        $sm = $this->account->getServiceManager();
        $dbh = $sm->get("Db");
        $this->dbh = $dbh;
		return new \Netric\EntityQuery\Index\Pgsql($this->account, $dbh);
         * 
         */
        $this->dbh = $this->account->getServiceManager()->get("Db");
        return new \Netric\EntityQuery\Index\Pgsql($this->account);
	}
    
    /**
     * Dummy test
     */
    public function testDummy()
    {
        $this->assertTrue(true);
    }
}