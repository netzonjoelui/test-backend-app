<?php
/**
 * Test PHP
 */
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');

class AntObjectLoaderTest extends PHPUnit_Framework_TestCase 
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
	 * Test loading an object by id and putting it into cache
	 */
	public function testById()
	{
		// Create test object
		$obj = CAntObject::factory($this->dbh, "task", null, $this->user);
		$obj->setValue("name", "testCacheObject");
		$obj->save();

		// Load the object through the loader which should cache it
		$loader = AntObjectLoader::getInstance($this->dbh);
		$objLoaded = $loader->byId("task", $obj->id);
		$this->assertEquals($objLoaded->id, $obj->id);

		/**
		 * Caching is now handled by the EntityLoader from within CAntObject::loadFromDb function
		// Test to see if the isCached function indicates theobject has been loaded
		$refIm = new \ReflectionObject($loader);
        $mthIsCached = $refIm->getMethod("isCached");
		$mthIsCached->setAccessible(true);
		$this->assertTrue($mthIsCached->invoke($loader, $obj->object_type, $obj->id));
		 */

		// Cleanup
		$obj->removeHard();
	}

	/**
	 * Test private isCached function
	public function testIsCached()
	{
		// Create test object
		$obj = CAntObject::factory($this->dbh, "task", null, $this->user);
		$obj->setValue("name", "testCacheObject");
		$obj->save();

		$loader = AntObjectLoader::getInstance($this->dbh);

		// Set the cache
		$refIm = new \ReflectionObject($loader);
        $mthCacheObject = $refIm->getMethod("cacheObject");
		$mthCacheObject->setAccessible(true);
        $mthCacheObject->invoke($loader, $obj);

		// Test to see if the isCached function indicates theobject has been loaded
		$refIm = new \ReflectionObject($loader);
        $mthIsCached = $refIm->getMethod("isCached");
		$mthIsCached->setAccessible(true);
		$this->assertTrue($mthIsCached->invoke($loader, $obj->object_type, $obj->id));

		// Cleanup
		$obj->removeHard();
	}
	 */

	/**
	 * Test private cache object function
	public function testCacheObject()
	{
		// Create test object
		$obj = CAntObject::factory($this->dbh, "task", null, $this->user);
		$obj->setValue("name", "testCacheObject");
		$obj->save();

		$loader = AntObjectLoader::getInstance($this->dbh);

		// Set the cache
		$refIm = new \ReflectionObject($loader);
        $mthCacheObject = $refIm->getMethod("cacheObject");
		$mthCacheObject->setAccessible(true);
        $mthCacheObject->invoke($loader, $obj);

		// Get the protected and private values
		$refColl = new \ReflectionObject($loader);
		$objProp = $refColl->getProperty('objects');
		$objProp->setAccessible(true);
        $objects = $objProp->getValue($loader);

		$this->assertTrue(isset($objects[$obj->object_type][$obj->id]));

		// Cleanup
		$obj->removeHard();
	}
	 */
}
