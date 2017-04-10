<?php
/**
 * Test entity definition loader class that is responsible for creating and initializing exisiting definitions
 *
 * Most tests are inherited from DmTestsAbstract.php.
 * Only define pgsql specific tests here and try to avoid name collision with the tests
 * in the parent class. For the most part, the parent class tests all public functions
 * so private functions should be tested below.
 */
namespace NetricTest\EntitySync\Commit\DataMapper;

use PHPUnit_Framework_TestCase;

class PgsqlTest extends DmTestsAbstract 
{
    /**
     * Handle to pgsql database
     * 
     * @var Db\Pgsql
     */
    private $dbh = null;

    /**
	 * Use this funciton in all the datamappers to construct the datamapper
	 *
	 * @return \Netric\Entity\Commit\DataMaper\DataMapperInterface
	 */
	protected function getDataMapper()
	{      
        $sm = $this->account->getServiceManager();
        $dbh = $sm->get("Db");
        $this->dbh = $dbh;

		return new \Netric\EntitySync\Commit\DataMapper\Pgsql($this->account);
	}

	public function testCreateNewSequenceIfMissing()
	{
		$dm = $this->getDataMapper();

		$reflector = new \ReflectionClass(get_class($dm));
        $property = $reflector->getProperty("sSequenceName");
        $property->setAccessible(true);
        $property->setValue($dm, "test_create_new_for_commit");

        $nextCid = $dm->getNextCommitId('customer');
        $this->assertTrue($nextCid > 0); // make sure the sequence gets created

        $this->dbh->query("DROP SEQUENCE test_create_new_for_commit;");

	}
}