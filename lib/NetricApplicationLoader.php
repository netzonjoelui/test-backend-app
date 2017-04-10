<?php
/**
 * This is a temporary loader to make sure we don't have too many instances of antsystem db connection opening
 *
 * The design of this is sloppy so it should only be used when loading the application from the Ant class
 */
require_once("lib/ServiceLocator.php");
require_once("lib/AntSystem.php");
require_once("lib/Ant.php");

class NetricApplicationLoader
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
	private $application = null;
    
    private function __construct($config) {
        $this->application = new Netric\Application\Application($config);
    }
    /**
	 * Factory
	 *
	 * @param CDatabase $dbh Database to use to reverse lookup ant account
	 */
	public static function getInstance($config) 
	{ 
		if (!self::$m_pInstance) 
		{
            self::$m_pInstance = new NetricApplicationLoader($config);
		}

		return self::$m_pInstance; 
	}
    
    public function getApplication()
    {
        return $this->application;
    }
}