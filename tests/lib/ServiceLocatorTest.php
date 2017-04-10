<?php
/**
 * Test entity definition loader class that is responsible for creating and initializing exisiting definitions
 */
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');

class ServiceLocatorTest extends PHPUnit_Framework_TestCase 
{
	var $dbh = null;
	var $user = null;

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
	}

	/**
	 * Check if we can get the config
	 */
	public function testGet()
	{
		$sl = $this->ant->getServiceLocator();

		// Get config service
		$config = $sl->get("Config");
		$this->assertInstanceOf("AntConfig", $config);

		// Test to see if the isLoaded function indicates the service has been loaded
		$refIm = new \ReflectionObject($sl);
        $isLoaded = $refIm->getMethod("isLoaded");
		$isLoaded->setAccessible(true);
		$this->assertTrue($isLoaded->invoke($sl, "Config"));

		// Now that we know it is cached, lets make sure the returned object is correct
		$config = $sl->get("Config");
		$this->assertInstanceOf("AntConfig", $config);
	}

	/**
	 * Test getting entity datamapper
	 */
	public function testFactoryEntity_DataMapper()
	{
		$sl = $this->ant->getServiceLocator();

		// Get config service
		$config = $sl->get("Entity_DataMapper");
		$this->assertInstanceOf("Netric\Entity\DataMapper\Pgsql", $config);
	}

	/**
	 * Test getting entity datamapper
	 */
	public function testFactoryEntityDefinition_DataMapper()
	{
		$sl = $this->ant->getServiceLocator();

		// Get config service
		$config = $sl->get("EntityDefinition_DataMapper");
		$this->assertInstanceOf("Netric\EntityDefinition\DataMapper\Pgsql", $config);
	}
}
