<?php
/**
 * This is a temporary loader to be used by classes until everything uses proper dependency injection
 *
 * The design of this is sloppy so it should only be used if absolutely necessary.
 */
require_once("lib/ServiceLocator.php");
require_once("lib/AntSystem.php");
require_once("lib/Ant.php");

class ServiceLocatorLoader
{
	/**
	 * Store the single instance of the loader 
	 */
    private static $m_pInstance;

	/**
	 * Handle to Ant account object
	 *
	 * @var Ant
	 */
	private $ant = null;

	/**
	 * Class constructor
	 *
	 * @param Ant $ant Reference to ANT account object
	 */
	private function __construct($ant=null)
	{
		$this->ant = $ant;
		return $this;
	}

	/**
	 * Factory
	 *
	 * @param Ant $ant Reference to ANT account object
	 */
	public static function init($ant) 
	{ 
		if (!self::$m_pInstance) 
			self::$m_pInstance = new ServiceLocatorLoader($ant); 

		// If we have switched accounts then reload the cache
		if ($ant->id != self::$m_pInstance->ant->id)
		{
			// Switch accounts which will also force the service locator singleton 
			// pattern to refresh on next getInstance call
			self::$m_pInstance->ant = $ant; 
		}
	}

	/**
	 * Factory
	 *
	 * @param CDatabase $dbh Database to use to reverse lookup ant account
	 */
	public static function getInstance($dbh) 
	{ 
		if (!self::$m_pInstance) 
		{
            if (!$dbh->accountId)
                throw new Exception("Cannot get account id from the database");
                
			$ant = new Ant($dbh->accountId);
			self::$m_pInstance = new ServiceLocatorLoader($ant); 
		}

		return self::$m_pInstance; 
	}

	/**
	 * Get service locator
	 *
	 * @return ServiceLocator
	 */
	public function getServiceLocator()
	{
		return $this->ant->getServiceLocator();
	}
    
    /**
	 * Get service locator
	 *
	 * @return \Netric\ServiceManager\ServiceLocatorInterface
	 */
	public function getServiceManager()
	{
		return $this->ant->getNetricAccount()->getServiceManager();
	}
}
