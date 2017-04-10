<?php
/**
 * Handle loading and caching loaded access control lists
 *
 * @category  Dacl
 * @package   DaclLoader
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/Dacl.php");

/**
 * Class to handle to loading of DACLs
 */
class DaclLoader
{
	/**
	 * Handle to account database
	 *
	 * @var CDatabase
	 */
	private $dbh;

	/**
	 * Store the single instance of Database 
	 */
    private static $m_pInstance;

	/**
	 * Array holding already loaded lists
	 *
	 * @var array
	 */
	private $dacls = array();

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
			self::$m_pInstance = new DaclLoader($dbh); 

		// If we have switched accounts then reload the definition
		if ($dbh->dbname != self::$m_pInstance->dbh->dbname)
		{
			self::$m_pInstance->dacls = array(); 
			self::$m_pInstance->dbh = $dbh; 
		}


		return self::$m_pInstance; 
	}

	/**
	 * Get an access controll list by name
	 * 
	 * @param string $key The name of the list to pull
	 * @return Dacl
	 */
	public function byName($key, $cache=true)
	{
		$key = $this->dbh->dbname . "/" . $key;

		if (isset($this->dacls[$key]) && $cache)
			return $this->dacls[$key];

		// Not yet loaded, create then store
		if ($cache)
		{
			$this->dacls[$key] = new Dacl($this->dbh, $key);
			return $this->dacls[$key];
		}
		else
		{
			$dacl = new Dacl($this->dbh, $key);
			return $dacl;
		}
	}

	/**
	 * Initialize a DACL by dada array
	 * 
	 * @param string $key The name of the list to pull
	 * @param array $data Data array to be passed to Dacl::loadByData
	 * @return Dacl
	 */
	public function byData($key, $data)
	{
		$dacl = new Dacl($key);
		$dacl->loadByData($data);
		return $dacl;
	}

	/**
	 * Clear object definition cache by name
	 * 
	 * @param string $key The name of the list to pull
	 * @return CAntObjectFields
	 */
	public function clearDacl($key)
	{
		$this->dacls[$key] = null;
	}
}
