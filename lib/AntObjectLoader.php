<?php
/**
 * This is the object loader/identity mapper that is used to make sure we don't load the same object twice
 *
 * @depricated We now use EntityLoader which this is now just a proxy for
 * @category	Ant
 * @package		Object
 * @subpackage	Loader
 * @copyright	Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/CAntObject.php");

/**
 * Identity mapping class
 */
class AntObjectLoader
{
	/**
	 * Handle to account database
	 *
	 * @var CDatabase
	 */
	private $dbh = null;

	/**
	 * Store the single instance of Database 
	 */
	private static $m_pInstance;
	
	/**
	 * Multi-dimensional array of loaded objects ['obj_type']['oid']
	 *
	 * @type array
	 */
	private $objects = array();

	/**
	 * Make the constructor private for singleton pattern
	 *
	 * @param CDatabase $dbh Handle to account database
	 */
	private function __construct($dbh)
	{
		$this->dbh = $dbh;
	}

	/**
	 * Factory
	 *
	 * @param CDatabase $dbh Handle to account database
	 */
	public static function getInstance($dbh) 
	{ 
		if (!self::$m_pInstance) 
			self::$m_pInstance = new AntObjectLoader($dbh); 

		// If we have switched accounts then reload the cache
		if ($dbh->dbname != self::$m_pInstance->dbh->dbname)
		{
			self::$m_pInstance->objects = array(); 
			self::$m_pInstance->dbh = $dbh; 
		}

		return self::$m_pInstance; 
	}

	/**
	 * Get an object
	 *
	 * If it is cached then return cached version, otherwise instantiate, cache, and return
	 *
	 * @param string $objType The name of the object type
	 * @param string $oid The unique id of the object to get
	 * @param AntUser $user Recommended user for permissions, otherwise system is used
	 * @return CAntObject
	 */
	public function byId($objType, $oid, $user=null)
	{
		/**
		 * joe: we are moving to EntityLoader which is called from within CAntObject::loadFromDb
		 * Just use the factory because data will be loaded from EntityLoader in the CAntObject class
		 */
		/*
		if ($this->isCached($objType, $oid))
			return $this->objects[$objType][$oid];

		// Instantiate a new object, cache it, and return a reference
		$obj = CAntObject::factory($this->dbh, $objType, $oid, $user);
		$this->cacheObject($obj);
		return $obj;
		 */

		return CAntObject::factory($this->dbh, $objType, $oid, $user);
	}

	/**
	 * Check if object is in local cache
	 *
	 * @param string $objType The name of the object type
	 * @param string $oid The unique id of the object to get
	 * @return bool true if object was previously loaded, false otherwise
	 */
	private function isCached($objType, $oid)
	{
		if (isset($this->objects[$objType]))
			return isset($this->objects[$objType][$oid]);

		return false;
	}

	/**
	 * Cache a local object that has been created
	 *
	 * @param CAntObject $obj The object to cache
	 */
	private function cacheObject($obj)
	{
		if (!isset($this->objects[$obj->object_type]) || !is_array($this->objects[$obj->object_type]))
			$this->objects[$obj->object_type] = array();

		$this->objects[$obj->object_type][$obj->id] = $obj;
	}
}
