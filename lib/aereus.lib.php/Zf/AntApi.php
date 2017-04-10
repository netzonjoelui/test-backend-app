<?php
/**
 * Make loading the AntApi easier in the zend framework
 *
 * This class basically loads the AntApi object using the config values
 * found in application.ini
 *
 * @category  Aereus_Zf
 * @package   Aereus_Zf_AntApi
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("Aereus/elastic.php");

/**
* Extend AntApi and set all variables through applicaiton configuration
*/
class Aereus_Zf_AntApi extends AntApi
{
	/**
	 * Store the single instance of Database 
	 */
    private static $m_pInstance;

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		// Get configuration
        $config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);


		// Now set the api store source if setting has been set
		if ($config->antapi->store)
		{
			global $ANTAPI_STORE, $ANTAPI_STORE_ELASTIC_HOST, $ANTAPI_STORE_ELASTIC_IDX,
				   $ANTAPI_STORE_PGSQL_HOST, $ANTAPI_STORE_PGSQL_DBNAME, $ANTAPI_STORE_PGSQL_USER, $ANTAPI_STORE_PGSQL_PASSWORD;

			$ANTAPI_STORE = $config->antapi->store;

			// Set global variables
			switch ($config->antapi->store)
			{
			case 'elastic':
				$ANTAPI_STORE_ELASTIC_HOST = ($config->antapi->storehost) ? $config->antapi->storehost : "localhost"; 
				$ANTAPI_STORE_ELASTIC_IDX = ($config->antapi->storeName) ? $config->antapi->storeName : str_replace(".", "_", $config->antapi->server); 
				break;
			case 'pgsql':
				$ANTAPI_STORE_PGSQL_HOST = $config->antapi->storehost; 
				$ANTAPI_STORE_PGSQL_DBNAME = $config->antapi->storeName; 
				$ANTAPI_STORE_PGSQL_USER = $config->antapi->storeUser; 
				$ANTAPI_STORE_PGSQL_PASSWORD = $config->antapi->storePass; 
				break;
			}
		}

		// Call antapi with the correct settings
		parent::__construct($config->antapi->server, $config->antapi->username, $config->antapi->password);
	}

	/**
	 * Factory
	 */
	public static function getInstance($server=null, $username=null, $password=null) 
	{ 
		if (!self::$m_pInstance) 
		{
			self::$m_pInstance = new Aereus_Zf_AntApi(); 
		}

		return self::$m_pInstance; 
	}
}
