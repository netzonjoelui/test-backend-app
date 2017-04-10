<?php
/**
 * Class handled settings and configuration data
 *
 * This class must be loaded before any other libraries or classes in ANT!
 *
 * Example of usage
 * <code>
 * 	echo "Application path : " AntConfig::getInstance()->application_path;
 * </code>
 *
 * @category  Ant
 * @package   Config
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("Netric/Config/Config.php");
require_once("Netric/Config/ConfigLoader.php");

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/..'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Add application path to path
ini_set('include_path', APPLICATION_PATH . "/" . PATH_SEPARATOR . ini_get('include_path'));


// Globals used for ALIB
$ALIBPATH = "";
$ALIB_ANS_SERVER = "";
$ALIB_ANS_ACCOUNT = "";
$ALIB_ANS_PASS = "";
$ALIB_CACHE_DIR = "";
$ALIB_USEMEMCACHED = false;
$ALIB_MEMCACHED_SVR = "";

/**
 * Main Ant Filesystem class
 *
 * Because properties are accessed though overriding the get '->' operator
 * it is important that all locally declared properties start with $m_
 * to prevent name collision with the config files.
 */
class AntConfig
{
    /**
     * Netric config object which is replacing AntConfig
     * 
     * @var \Netric\Config
     */
    private $nconfig = null;

	/**
	 * Netric config loader object which gets the config file from /config folder
	 *
	 * @var \Netric\Config\ConfigLoader
	 */
	private $nconfigLoader = null;
    
	/**
	 * Store the single instance of class for singleton pattern
	 *
	 * @var $this
	 */
	private static $m_pInstance;

	/**
	 * Current environment
	 *
	 * @var string
	 */
	public $m_env = "production";

	/**
	 * Base path where config files can be found
	 *
	 * This can be overridden for alternate config path through the AntConfig::setPath function
	 *
	 * @var string
	 */
	public $m_basePath = null;

	/**
	 * Settings array
	 *
	 * @var array
	 */
	private $m_settings = array();

	/**
	 * Class constructor
	 *
	 * @param string $appEnv Optional application envirionment to load. If not set the 'APPLICATION_ENV' is used.
	 * @param string $path Optional alternate base path
	 */
	function __construct($appEnv=null, $path=null)
	{
        if (!$appEnv)
            $appEnv = APPLICATION_ENV;


		// Create the instance of config loader
		$this->nconfigLoader = new \Netric\Config\ConfigLoader();
		$applicationEnvironment = (getenv('APPLICATION_ENV')) ? getenv('APPLICATION_ENV') : "production";

		// Setup the new config
		$this->nconfig = $this->nconfigLoader->fromFolder(__DIR__ . "/../config", $applicationEnvironment);

        //$this->nconfig = new \Netric\Config\Config($configData);
        
        /**
         * joe: We have moved the core ini reading to Netric\Config
		$this->m_env = ($appEnv) ? $appEnv : APPLICATION_ENV;

		// Set initial config values
		$this->m_settings['application_env'] = $this->m_env;

		// Initialize the default base path
		$this->setPath($path);

		// Load configuration files
		$this->readConfigs();

		// Set dynamic config vars based on operating environment (load third level domains)
		global $_SERVER;
		if ($_SERVER['HTTP_HOST'])
			$this->m_settings['localhost'] = $_SERVER['HTTP_HOST'];
        */
        
		// Now define constants from settings
		$this->defineConstants();
	}

	/**
	 * Destructor - cleanup file handles
	 */
	public function __destruct()
	{
	}

	/**
	 * Factory for returing a singleton reference to this class
	 */
	public static function getInstance() 
	{ 
		if (!self::$m_pInstance) 
		{
			self::$m_pInstance = new AntConfig(); 
		}

		return self::$m_pInstance; 
	}

	/**
	 * Overload the set '->' operator
	 *
	 * @param string $name The name of the propery to set
	 * @param mixed $value The value of the named property
	 */
 	public function __set($name, $value)
    {
        //$this->nconfig->set($name, $value);

		// The new config does not allow this after construction

        // joe: pass through to netric config
        //$this->m_settings[$name] = $value;
    }

	/**
	 * Overload the get '->' operator
	 *
	 * @param string $name The name of the propery to get
	 */
    public function __get($name)
    {
        return $this->nconfig->get($name);
        
        // joe: pass through to netric config
        /*
        if (array_key_exists($name, $this->m_settings))
            return $this->m_settings[$name];

		return null;
         */
	}

	/**
	 * Used to manually set values at runtime
	 *
	 * @param string $name The name of the propery to set
	 * @param string $subName The name of the sub-propery to set
	 * @param mixed $value The value of the named property
	 */
 	public function setValue($name, $subname=null, $value)
    {
        $this->nconfig->setValue($name, $subname, $value);
        /*
		if ($name && $subname)
        	$this->m_settings[$name][$subname] = $value;
		else
        	$this->m_settings[$name] = $value;
         */
    }

	/**
     * @depricated Using \Netric\Config::setPath to read files
     * 
	 * Set the base path for the configuration files
	 * 
	 * @param string $path Option manual path, otherwise defaults to APPLICATION_PATH/config
	 */
	public function setPath($path="")
	{
        $this->nconfig->setpath($path);
		//$this->m_basePath = ($path) ? $path : APPLICATION_PATH . "/config";
	}

	/**
     * @depricated Using \Netric\Config::setPath to readConfigs
	 * Read the configuration data
	 *
	 * This function will iterate through configuration files and load settings
	 * according to $this->m_env and available config files.
	 *
	 * /config/ant.ini will always be loaded no matter what. Then it will look for
	 * the (all lowercase) ant.[$this->envname].ini file like 'ant.testing.ini' and
	 * load variables over any settings already defined. This allows for environmental
	 * overrides of each config value.
	 *
	 * Finally the script will check for the existence of a *.local.ini file like
	 * ant.testing.local.ini which should never be included in the repo but will
	 * be used for local only variable overrides and must be manually set.
	 */
	public function readConfigs()
	{
        $this->nconfig->readConfigs();
        /*
		// Load the default/base config file
		$this->loadConfigFile("ant.ini");

		// Load local values if they exist
		$this->loadConfigFile("ant.local.ini");

		// Load environment specific values
		$this->loadConfigFile("ant." . $this->m_env . ".ini");

		// Load local values if they exist
		$this->loadConfigFile("ant." . $this->m_env . ".local.ini");
         */
	}

	/**
	 * Load a config file into the settings array
	 *
	 * This function may be called if we are not sure if a config file exists
	 * because it will verify it exists before trying to load any values.
	 *
	 * @param string $name The name of the file to load
	 * @return bool true on success, false on failure
	 */
	private function loadConfigFile($name)
	{
        //$this->nconfig->loadConfigFile($name);

		$configData = $this->nconfigLoader->importFileArray($name);

		$this->nconfig->setValues($configData);
        
        /*
		$path = $this->m_basePath . "/" . $name;

		if (!file_exists($path))
			return false;

		// Currently we assume all files are ini but this may change later
		$values = parse_ini_file($path, true); // make sure we process sections

		if (is_array($values))
			$this->setValues($values);
         */
	}

	/**
	 * Set configuration values
	 *
	 * We traverse through the values and set them in order
	 *
	 * @param array $values The values to set
	 */
	private function setValues($values)
	{
		$this->nconfig->setValues($values);
        /*
		foreach ($values as $name=>$val)
		{
			if (is_array($val))
			{
                if(!isset($this->m_settings[$name]))
                    $this->m_settings[$name] = array();
				else if(!is_array($this->m_settings[$name]))
					$this->m_settings[$name] = array();

				foreach ($val as $subname=>$subval)
					$this->m_settings[$name][$subname] = $subval;
			}
			else
			{
				$this->m_settings[$name] = $val;
			}
		}
         */
	}

	/**
	 * Define constants used throughout the application
	 *
	 * This function should be called only after the config files have loaded
	 */
	private function defineConstants()
	{
        $logLevel = null;
        if($this->nconfig->log_level)
            $logLevel = $this->nconfig->log_level;

        if(!defined("ANTLOG_LEVEL"))
		    define("ANTLOG_LEVEL", $logLevel);

        $objectIndexType = null;
        if($this->nconfig->object_index->type)
            $objectIndexType = $this->nconfig->object_index['type'];
            
        $objectIndexHost = null;
        if($this->nconfig->object_index->host)
            $objectIndexHost = $this->nconfig->object_index['host'];
            
		// Object index
        if(!defined("ANT_INDEX_TYPE"))
		    define("ANT_INDEX_TYPE", $objectIndexType);
            
        if(!defined("ANT_INDEX_ELASTIC_HOST"))
		    define("ANT_INDEX_ELASTIC_HOST", $objectIndexHost);

		$configStats = $this->nconfig->stats;

        $statsEngine = null;
        if($configStats->engine)
            $statsEngine = $configStats->engine;
            
        $statsHost = null;
        if($configStats->host)
            $statsHost = $configStats->host;
        
        $statsPort = null;
        if($configStats->port)
            $statsPort = $configStats->port;
            
        $statsPrefix = null;
        if($configStats->prefix)
            $statsPrefix = $configStats->prefix;
        
		// Stats
        if(!defined("STATS_ENABLE"))
		    define("STATS_ENABLE", ($configStats->enabled) ? true : false);
        
        if(!defined("STATS_ENGINE"))
		    define("STATS_ENGINE", $statsEngine);
            
        if(!defined("STATS_DHOST"))
		    define("STATS_DHOST", $statsHost);
        
        if(!defined("STATS_DPORT"))
		    define("STATS_DPORT", $statsPort);
        
        if(!defined("STATS_PREFIX"))
		    define("STATS_PREFIX", $statsPrefix);

		// ANTAPI
        if(!defined("ANTAPI_NOHTTPS"))
		    define("ANTAPI_NOHTTPS", ($this->force_https) ? false : true);

		// Set default timezone
		if(($this->nconfig->default_timezone) && $this->nconfig->default_timezone)
			date_default_timezone_set($this->nconfig->default_timezone);

		//ALIB global settings
		//--------------------------------------------------------
		global $ALIBPATH, $ALIB_ANS_SERVER, $ALIB_ANS_ACCOUNT, $ALIB_ANS_PASS, $ALIB_CACHE_DIR,
				$ALIB_USEMEMCACHED, $ALIB_MEMCACHED_SVR, $ALIB_SESS_USEDB,
				$ALIB_SESS_DB_SERVER, $ALIB_SESS_DB_NAME, $ALIB_SESS_DB_USER, $ALIB_SESS_DB_PASS;

        if(($this->nconfig->alib->path))
		    $ALIBPATH = $this->nconfig->alib->path;		// Path to js library
        
		// Aereus Network Storage
        if($this->nconfig->alib->ans_server)
		    $ALIB_ANS_SERVER = $this->nconfig->alib->ans_server;
            
        if($this->nconfig->alib->ans_account)
		    $ALIB_ANS_ACCOUNT = $this->nconfig->alib->ans_account;
            
        if($this->nconfig->alib->ans_password)
		    $ALIB_ANS_PASS = $this->nconfig->alib->ans_password;

        $dataPath = "";
        if($this->nconfig->data_path)
            $dataPath = $this->nconfig->data_path;
		$ALIB_CACHE_DIR = $dataPath . "/cache";
		$ALIB_USEMEMCACHED = ($this->nconfig->alib->memcached) ? true : false;
 
        if($this->nconfig->alib->memcache_host)
		    $ALIB_MEMCACHED_SVR = $this->nconfig->alib->memcache_host;
        
        // Define global variables as constant
        if(!defined("ALIBPATH"))
            define("ALIBPATH", $ALIBPATH);
        
        if(!defined("ALIB_ANS_SERVER"))
            define("ALIB_ANS_SERVER", $ALIB_ANS_SERVER);
            
        if(!defined("ALIB_ANS_ACCOUNT"))
            define("ALIB_ANS_ACCOUNT", $ALIB_ANS_ACCOUNT);
            
        if(!defined("ALIB_ANS_PASS"))
            define("ALIB_ANS_PASS", $ALIB_ANS_PASS);
            
        if(!defined("ALIB_CACHE_DIR"))
            define("ALIB_CACHE_DIR", $ALIB_CACHE_DIR);
            
        if(!defined("ALIB_USEMEMCACHED"))
            define("ALIB_USEMEMCACHED", $ALIB_USEMEMCACHED);
            
        if(!defined("ALIB_MEMCACHED_SVR"))
            define("ALIB_MEMCACHED_SVR", $ALIB_MEMCACHED_SVR);
            
        if(!defined("ALIB_SESS_USEDB"))
            define("ALIB_SESS_USEDB", $ALIB_SESS_USEDB);
            
        if(!defined("ALIB_SESS_DB_SERVER"))
            define("ALIB_SESS_DB_SERVER", $ALIB_SESS_DB_SERVER);
            
        if(!defined("ALIB_SESS_DB_NAME"))
            define("ALIB_SESS_DB_NAME", $ALIB_SESS_DB_NAME);
            
        if(!defined("ALIB_SESS_DB_USER"))
            define("ALIB_SESS_DB_USER", $ALIB_SESS_DB_USER);
            
        if(!defined("ALIB_SESS_DB_PASS"))
            define("ALIB_SESS_DB_PASS", $ALIB_SESS_DB_PASS);
        
	}

	/**
	 * Dump all vars for debugging
	 */
	public function dumpVars()
	{
		return var_export($this->m_settings, true);
	}
}

// Initiaize variables
$cfg = AntConfig::getInstance();
