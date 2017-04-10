<?php
/**
 * Define common tests that will need to be run with all data mappers.
 *
 * In order to implement the unit tests, a datamapper test case just needs
 * to extend this class and create a getDataMapper class that returns the
 * datamapper to be tested
 *
 * All generic tests should go here, and implementation specific tests 
 * (like querying a database to verify data) should go in the derrived
 * unit tests.
 */
namespace NetricTest\EntitySync\Commit\DataMapper;

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
	 * Setup each test
	 */
	protected function setUp() 
	{
        $this->account = \NetricTest\Bootstrap::getAccount();
	}

	/**
	 * Use this funciton in all the datamappers to construct the datamapper
	 *
	 * @return \Netric\Entity\Commit\DataMaper\DataMapperInterface
	 */
	abstract protected function getDataMapper();

	public function testGetNextCommitId()
	{
		$dm = $this->getDataMapper();
		$nextCid = $dm->getNextCommitId("test_dm");
		$this->assertTrue($nextCid > 0);
	}

	public function testSaveHead()
	{
		$dm = $this->getDataMapper();

		// Increment head
		$nextCid = $dm->getNextCommitId("test_dm");
		$dm->saveHead("test_dm", $nextCid);

		// Test saved value
		$this->assertEquals($nextCid, $dm->getHead("test_dm"));
	}

	public function testGetHead()
	{
		$dm = $this->getDataMapper();

		$currCid = $dm->getHead("test_dm");

		// Increment head if new object
		if (0 == $currCid)
		{
			$nextCid = $dm->getNextCommitId("test_dm");
			$dm->saveHead("test_dm", $nextCid);
		}

		$currCid = $dm->getHead("test_dm");
		$this->assertTrue($currCid > 0);
	}
}