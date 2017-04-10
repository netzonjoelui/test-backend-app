<?php
/**
 * Test entity definition loader class that is responsible for creating and initializing exisiting definitions
 */
namespace NetricTest;

use Netric;
use PHPUnit_Framework_TestCase;

class EntityDefinitionLoaderTest extends PHPUnit_Framework_TestCase 
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
		// Use pgsql datamapper for testing
		$dm = $this->account->getServiceManager()->get("EntityDefinition_DataMapper");
		$def = $dm->fetchByName("task");

		// Load the object through the loader which should cache it
		$loader = $this->account->getServiceManager()->get("EntityDefinitionLoader");
		$loadedDef = $loader->get("task");
		$this->assertEquals($loadedDef->getId(), $def->getId());

		// Test to see if the isLoaded function indicates the definition has been loaded
		$refIm = new \ReflectionObject($loader);
        $isLoaded = $refIm->getMethod("isLoaded");
		$isLoaded->setAccessible(true);
		$this->assertTrue($isLoaded->invoke($loader, "task"));

		// Check to make sure we have views loaded from the system
		$views = $loadedDef->getViews();
		$this->assertTrue(count($views) > 0);

		// Check to make sure we have forms
		$xmlForm = $loadedDef->getForm("default");
		$this->assertFalse(empty($xmlForm));
	}

	/**
	 * Test if object is being loaded from cache
	 */
	public function testGetCached()
	{
		// Load the object through the loader which should cache it
		$loader = $this->account->getServiceManager()->get("EntityDefinitionLoader");
		$def = $loader->get("task");

		// Test to see if the isCached function indicates theo bject has been loaded
		$refIm = new \ReflectionObject($loader);
        $getCached = $refIm->getMethod("getCached");
		$getCached->setAccessible(true);
		$this->assertNotEquals(false, $getCached->invoke($loader, "task"));
	}

	/**
	 * Test loading all entity definitions
	 */
	public function testGetAll()
	{
		// Use pgsql datamapper for testing
		$dm = $this->account->getServiceManager()->get("EntityDefinition_DataMapper");
		$allObjectTypes = $dm->getAllObjectTypes();
		$this->assertTrue(sizeOf($allObjectTypes) > 0);

		// Load all the definitions through the loader which should cache it
		$loader = $this->account->getServiceManager()->get("EntityDefinitionLoader");
		$loadedDefinitions = $loader->getAll();
		$this->assertTrue($loadedDefinitions[0]->getId() > 0);
		$this->assertTrue(sizeOf($loadedDefinitions) > 0);
	}
}
