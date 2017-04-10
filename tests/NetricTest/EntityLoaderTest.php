<?php
/**
 * Test entity  loader class that is responsible for creating and initializing exisiting objects
 */
namespace NetricTest;

use Netric;
use PHPUnit_Framework_TestCase;

class EntityLoaderTest extends PHPUnit_Framework_TestCase 
{
	/**
     * Tennant account
     * 
     * @var \Netric\Account\Account
     */
    private $account = null;

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
        $this->account = \NetricTest\Bootstrap::getAccount();
	}

	/**
	 * Test loading an object definition
	 */
	public function testGet()
	{
		$loader = $this->account->getServiceManager()->get("EntityLoader");

		// Create a test object
		$dm = $this->account->getServiceManager()->get("Entity_DataMapper");
		$cust = $loader->create("customer");
		$cust->setValue("name", "EntityLoaderTest:testGet");
		$cid = $dm->save($cust);
		
		// Use the laoder to get the object
		$ent = $loader->get("customer", $cid);
		$this->assertEquals($cust->getId(), $ent->getId());

		// Test to see if the isLoaded function indicates the entity has been loaded and cached locally
		$refIm = new \ReflectionObject($loader);
        $isLoaded = $refIm->getMethod("isLoaded");
		$isLoaded->setAccessible(true);
		$this->assertTrue($isLoaded->invoke($loader, "customer", $cid));

		// Test to see if it is cached
		$refIm = new \ReflectionObject($loader);
        $getCached = $refIm->getMethod("getCached");
		$getCached->setAccessible(true);
		$this->assertTrue(is_array($getCached->invoke($loader, "customer", $cid)));

		// Cleanup
		$dm->delete($cust, true);
	}
}
