<?php
/**
 * ANT application log used to log all events
 *
 * Example:
 * <code>
 * 		AntLog::getInstance()->info("There was a problem doing something");
 * 		AntLog::getInstance()->warning("There was a problem doing something");
 * 		AntLog::getInstance()->error("There was a problem doing something");
 * 		AntLog::getInstance()->debug("There was a problem doing something");
 * </code>
 *
 * Configuration Constants:
 * ANTLOG_LEVEL can be any of the defined constants below like LOG_DEBUG or LOG_ERR
 *
 * @category ANT
 * @package AntLog
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/ant_error_handler.php");

/**
 * Set log level constants if not already set by the system
 */
if (!defined("LOG_EMERG"))
	define("LOG_EMERG", 1); // system is unusable

if (!defined("LOG_ALERT"))
	define("LOG_ALERT", 2); // action must be taken immediately

if (!defined("LOG_CRIT"))
	define("LOG_CRIT", 3); // critical issues

if (!defined("LOG_ERR"))
	define("LOG_ERR", 4); // error conditions

if (!defined("LOG_WARNING"))
	define("LOG_WARNING", 5); // warning conditions

if (!defined("LOG_NOTICE"))
	define("LOG_NOTICE", 6); // normal, but significant, condition

if (!defined("LOG_INFO"))
	define("LOG_INFO", 7); // informational message

if (!defined("LOG_DEBUG"))
	define("LOG_DEBUG", 8); // debug-level message

/**
 * Define log csv definition - what columns store what
 */
define("LOGDEF_LEVEL", 0);
define("LOGDEF_TIME", 1);
define("LOGDEF_DETAILS", 2);
define("LOGDEF_SOURCE", 3);
define("LOGDEF_SERVER", 4);
define("LOGDEF_ACCOUNT", 5);
define("LOGDEF_USER", 6);


/**
 * Logging class
 */
class AntLog
{
	/**
	 * Store the single instance of class for singleton pattern
	 */
    private static $m_pInstance;

	/**
	 * Current log level
	 *
	 * @var int
	 */
	private $level = LOG_ERR;

	/**
	 * Path to the log file
	 *
	 * @var string
	 */
	private $logPath = "";

	/**
	 * Maximum size in MB for this log file
	 *
	 * @param int
	 */
	public $maxSize = 500;

	/**
	 * Log file handle
	 *
	 * @var int File handle
	 */
	private $logFile = null;

	/**
	 * Constructor
	 *
	 * This is not set at private but usually this class will be called using
	 * the singleton $this->getInstance() function.
	 */
	public function __construct()
	{
		// Make sure the local data path exists
		if (AntConfig::getInstance()->log)
		{
			$this->logPath = AntConfig::getInstance()->log;

			// Now make sure we have not exceeded the maxiumu size for this log file
			if (file_exists($this->logPath))
			{
				if (filesize($this->logPath) >= ($this->maxSize * 1024))
					unlink($this->logPath);
			}

			// Check to see if log file exists and create it if it does not
			if (!file_exists($this->logPath))
			{
				if (touch($this->logPath))
					chmod($this->logPath, 0777);
				else
					$this->logPath = ""; // clear the path which will raise exception on write
			}

			// Now open the file
			$this->logFile = fopen($this->logPath, 'a');
		}

		// Set current logging level if defined
		if (defined("ANTLOG_LEVEL"))
			$this->level = ANTLOG_LEVEL;
	}

	/**
	 * Destructor - cleanup file handles
	 */
	public function __destruct()
	{
		if ($this->logFile != null)
			@fclose($this->logFile);
	}

	/**
	 * Factory for returing a singleton reference to this class
	 */
	public static function getInstance() 
	{ 
		if (!self::$m_pInstance) 
		{
			self::$m_pInstance = new AntLog(); 
		}

		return self::$m_pInstance; 
	}

	/**
	 * Put a new entry into the log
	 *
	 * This is usually called by one of the aliased methods like info, error, warning
	 * which in turn just sets the level and writes to this method.
	 *
	 * @param int $lvl The level of the event being logged
	 * @param string $message The message to log
	 */
	public function writeLog($lvl, $message)
	{
		// Only log events below the current logging level set
		if ($lvl > $this->level)
			return false;

		if ($this->logPath == "")
			return false;
			//throw new Exception('AntLog: Data path "' . $this->logPath . '" does not exist or is not writable');

		global $_SERVER;

		$source = "ANT";
		if (isset($_SERVER['REQUEST_URI']))
			$source = $_SERVER['REQUEST_URI'];
		else if (isset($_SERVER['PHP_SELF']))
			$source = $_SERVER['PHP_SELF'];

		$server = "";
		if (isset($_SERVER['SERVER_NAME']))
			$server = $_SERVER['SERVER_NAME'];

		$eventData = array();
		$eventData[LOGDEF_LEVEL] = $this->getLevelName($lvl);
		$eventData[LOGDEF_TIME] = date('c');
		$eventData[LOGDEF_DETAILS] = $message;
		$eventData[LOGDEF_SOURCE] = $source;
		$eventData[LOGDEF_SERVER] = $server;
		$eventData[LOGDEF_ACCOUNT] = "";
		$eventData[LOGDEF_USER] = "";

		//file_put_contents($this->logPath, $message, FILE_APPEND);
		return fputcsv($this->logFile, $eventData);
	}

	/**
	 * Log an informational message
	 * 
	 * @param string $message The message to insert into the log
	 */
	public function info($message)
	{
		return $this->writeLog(LOG_INFO, $message);
	}

	/**
	 * Log a warning message
	 * 
	 * @param string $message The message to insert into the log
	 */
	public function warning($message)
	{
		return $this->writeLog(LOG_WARNING, $message);
	}

	/**
	 * Log an error message
	 * 
	 * @param string $message The message to insert into the log
	 */
	public function error($message)
	{
		return $this->writeLog(LOG_ERR, $message);
	}

	/**
	 * Log a debug message
	 * 
	 * @param string $message The message to insert into the log
	 */
	public function debug($message)
	{
		return $this->writeLog(LOG_DEBUG, $message);
	}

	/**
	 * Get textual representation of the level
	 *
	 * @param int $lvl The level to convert
	 * @return string Textual representation of level
	 */
	public function getLevelName($lvl)
	{
		switch ($lvl)
		{
		case LOG_EMERG:
		case LOG_ALERT:
		case LOG_CRIT:
		case LOG_ERR:
			return 'ERROR';
		case LOG_WARNING:
			return 'WARNING';
		case LOG_DEBUG:
			return 'DEBUG';
		case LOG_NOTICE:
		case LOG_INFO:
		default:
			return 'INFO';
		}
	}
}
