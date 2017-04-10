<?php
/**
 * Hanlde loading object definitions
 *
 * @category  AntObject
 * @package   AntObjectDefLoader
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/CAntObjectFields.php");

/**
 * Class to handle to loading of object definitions
 */
class AntObjectDefLoader
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
	 * Array holding already loaded object definitions
	 *
	 * @var array
	 */
	private $defs = array();

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
			self::$m_pInstance = new AntObjectDefLoader($dbh); 

		// If we have switched accounts then reload the definition
		if ($dbh->dbname != self::$m_pInstance->dbh->dbname)
		{
			self::$m_pInstance->defs = array(); 
			self::$m_pInstance->dbh = $dbh; 
		}

		return self::$m_pInstance; 
	}

	/**
	 * Get an object definition for an object by name
	 * 
	 * @param string $oname The name of the object to pull
	 * @return CAntObjectFields
	 */
	public function getDef($oname)
	{
		if(isset($this->defs[$oname]))
			return $this->defs[$oname];

		// Not yet loaded, create then store
		$this->defs[$oname] = new CAntObjectFields($this->dbh, $oname);

		return $this->defs[$oname];
	}

	/**
	 * Clear object definition cache by name
	 * 
	 * @param string $oname The name of the object to clear
	 * @return CAntObjectFields
	 */
	public function clearDef($oname)
	{
		$this->defs[$oname] = null;
	}
}
