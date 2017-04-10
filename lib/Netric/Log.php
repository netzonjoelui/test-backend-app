<?php
/**
 * Netric logger class
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */

namespace Netric;

use Netric\Config\Config;

/**
 * Description of Log
 */
class Log 
{
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
     * Define log csv definition - what columns store what
     * 
     * @var array
     */
    private $logDef = array(
        "LEVEL"=>0,
        "TIME"=>1,
        "DETAILS"=>2,
        "SOURCE"=>3,
        "SERVER"=>4,
        "ACCOUNT"=>5,
        "USER"=>6,
    );

	/**
	 * Log levels
	 */
	const LOG_EMERG = 0;
	const LOG_ALERT = 1;
	const LOG_CRIT = 2;
	const LOG_ERR = 3;
	const LOG_WARNING = 4;
	const LOG_NOTICE = 5;
	const LOG_INFO = 6;
	const LOG_DEBUG = 7;

	/**
	 * Current log level
	 *
	 * @var int
	 */
	private $level = self::LOG_ERR;

    /**
     * Current application release
     *
     * @var string
     */
    private $appBranch = "release";

    /**
     * Flag to print logs to the console
     *
     * @var bool
     */
    private $printToConsole = false;

	/**
	 * Constructor
	 *
	 * @param Config $config
	 */
	public function __construct(Config $config)
	{
        // Set the path to log to
        if (!empty($config->log))
            $this->setLogPath($config->log);


		// Set current logging level if defined
		if ($config->log_level)
			$this->level = $config->log_level;

        // Set the current version/branch we are running
        if ($config->version)
            $this->appBranch = $config->version;

		// Open a connection to the syslog
		//$opt = ($config->log_stderr) ? LOG_PID | LOG_PERROR : LOG_PID;
		openlog("netric", LOG_PID, LOG_LOCAL5);
	}

    /**
     * Set the path to use for logging
     *
     * @param string $logPath
     */
    public function setLogPath($logPath)
    {
        // Make sure the local data path exists if we are logging to a file
        $this->logPath = $logPath;

        // If we are not working with php streams, then handle creating the file and rotating it
        if ("php:" != substr($this->logPath, 0, 4)) {

            // Make sure we have not exceeded the maximum size for this log file
            if (file_exists($this->logPath)) {
                if (filesize($this->logPath) >= ($this->maxSize * 1024))
                    unlink($this->logPath);
            }

            // Check to see if log file exists and create it if it does not
            if (!file_exists($this->logPath)) {
                if (!touch($this->logPath)) {
                    throw new \RuntimeException("Could not create log file: " . $this->logPath);
                }
            }
        }

        // Now open the file
        $this->logFile = fopen($this->logPath, 'a');
    }

	/**
	 * Destructor - cleanup file handles
	 */
	public function __destruct()
	{
		// This will be deprecated when we move it all to syslog
		if ($this->logFile != null)
			@fclose($this->logFile);

		// Close connection to the system log
		closelog();
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

		//if ($this->logPath == "")
		//	throw new \Exception('AntLog: Data path "' . $this->logPath . '" does not exist or is not writable');

		global $_SERVER;

		$source = "ANT";
		if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'])
			$source = $_SERVER['REQUEST_URI'];
		else if (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF'])
			$source = $_SERVER['PHP_SELF'];

		$server = "";
		if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'])
			$server = $_SERVER['SERVER_NAME'];

		$eventData = array();
		$eventData[$this->logDef["LEVEL"]] = "<22.$lvl>"; //$this->getLevelName($lvl);
		$eventData[$this->logDef["TIME"]] = date('M d H:i:s');
		$eventData[$this->logDef["DETAILS"]] = $message;
		$eventData[$this->logDef["SOURCE"]] = $source;
		$eventData[$this->logDef["SERVER"]] = $server;
		$eventData[$this->logDef["ACCOUNT"]] = "";
		$eventData[$this->logDef["USER"]] = "";

        // If flag is set to print to the console, then do it now
        if ($this->printToConsole) {
            echo "[" . $eventData['TIME'] . "] [:$lvl] " .
                 "[pid "  . getmypid() . "] netric " . $message . "\n";
        }

        // If we are logging to a file, then write it here
		if ($this->logFile) {
            /*
             [Mon Jul 18 13:19:31.260660 2016] [:error] [pid 86] [client 172.18.0.1:33174] PHP   8. Zen
             */
            /*
            $logLine = date('M m d H:i:s') . " " .
            (($server) ? $server : ' - ') . " " .
            " netric: " . $message;
            return fwrite($this->logFile, $logLine);
            */
            return fputcsv($this->logFile, $eventData);
        } else {
            // Otherwise just log to syslog
            return syslog($lvl, "branch={$this->appBranch}, page=$source, message=$message");
        }

	}

	/**
	 * Log an informational message
	 * 
	 * @param string $message The message to insert into the log
	 */
	public function info($message)
	{
		return $this->writeLog(self::LOG_INFO, $message);
	}

	/**
	 * Log a warning message
	 * 
	 * @param string $message The message to insert into the log
	 */
	public function warning($message)
	{
		return $this->writeLog(self::LOG_WARNING, $message);
	}

	/**
	 * Log an error message
	 * 
	 * @param string $message The message to insert into the log
	 */
	public function error($message)
	{
		return $this->writeLog(self::LOG_ERR, $message);
	}

	/**
	 * Log a debug message
	 * 
	 * @param string $message The message to insert into the log
	 */
	public function debug($message)
	{
		return $this->writeLog(self::LOG_DEBUG, $message);
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
		case self::LOG_EMERG:
		case self::LOG_ALERT:
		case self::LOG_CRIT:
		case self::LOG_ERR:
			return 'ERROR';
		case self::LOG_WARNING:
			return 'WARNING';
		case self::LOG_DEBUG:
			return 'DEBUG';
		case self::LOG_NOTICE:
		case self::LOG_INFO:
		default:
			return 'INFO';
		}
	}

	/**
	 * PHP error handler function is called with set_error_handler early in execution
	 *
	 * @param int $errorno The error code
	 * @param string $errstr The error message
	 * @param string $errfile The file originating the error
	 * @param int $errline The line that triggered the error
	 * @param array $errcontext Every variable that existed in the scope the error was triggered in
	 */
	public function phpErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
	{
		// if error has been supressed with an @
		if (error_reporting() == 0) {
			return;
		}

		// check if function has been called by an exception
		if(func_num_args() == 5) 
		{
			// called by trigger_error()
			$exception = null;
			list($errno, $errstr, $errfile, $errline) = func_get_args();
			$backtrace = array_reverse(debug_backtrace());
		}
		else 
		{
			// called by unhandled exception
			$exc = func_get_arg(0);
			$errno = $exc->getCode();
			$errstr = $exc->getMessage();
			$errfile = $exc->getFile();
			$errline = $exc->getLine();
			$backtrace = $exc->getTrace();
		}

		$errorType = array (
			E_ERROR          => 'ERROR',
			E_WARNING        => 'WARNING',
			E_PARSE          => 'PARSING ERROR',
			E_NOTICE         => 'NOTICE',
			E_CORE_ERROR     => 'CORE ERROR',
			E_CORE_WARNING   => 'CORE WARNING',
			E_COMPILE_ERROR  => 'COMPILE ERROR',
			E_COMPILE_WARNING => 'COMPILE WARNING',
			E_USER_ERROR     => 'USER ERROR',
			E_USER_WARNING   => 'USER WARNING',
			E_USER_NOTICE    => 'USER NOTICE',
			E_STRICT         => 'STRICT NOTICE',
			E_RECOVERABLE_ERROR  => 'RECOVERABLE ERROR',
		);

		// create error message
		if (array_key_exists($errno, $errorType)) 
		{
			$err = $errorType[$errno];
		} 
		else 
		{
			$err = 'UNHANDLED ERROR';
		}

		$errMsg = "$err: $errstr in $errfile on line $errline";

		// start backtrace
		foreach ($backtrace as $v) 
		{
			if (isset($v['class'])) 
			{
				$trace = 'in class '.$v['class'].'::'.$v['function'].'(';

				if (isset($v['args'])) 
				{
					$separator = '';

					foreach($v['args'] as $arg ) 
					{
						$trace .= "$separator".$this->getPhpErrorArgumentStr($arg);
						$separator = ', ';
					}
				}
				$trace .= ')';
			}
			elseif (isset($v['function']) && empty($trace)) 
			{
				$trace = 'in function '.$v['function'].'(';
				if (!empty($v['args'])) 
				{
					$separator = '';

					foreach($v['args'] as $arg ) 
					{
						$trace .= "$separator".$this->getPhpErrorArgumentStr($arg);
						$separator = ', ';
					}
				}
				$trace .= ')';
			}
		}

		// what to do
		switch ($errno) 
		{
		case E_NOTICE:
		case E_USER_NOTICE:
		case E_STRICT:
		case E_DEPRECATED:
			return;
			break;

		default:

			$body = "";
			if (isset($_COOKIE['uname']))
				$body .= "USER_NAME: ".$_COOKIE['uname']."\n";
			$body .= "Type: System\n";
			if (isset($_COOKIE['db']))
				$body .= "DATABASE: ".$_COOKIE['db']."\n";
			if (isset($_COOKIE['dbs']))
				$body .= "DATABASE_SERVER: ".$_COOKIE['dbs']."\n";
			if (isset($_COOKIE['aname']))
				$body .= "ACCOUNT_NAME: ".$_COOKIE['aname']."\n";

			$body .= "When: ".date('Y-m-d H:i:s')."\n";
			$body .= "URL: ".$_SERVER['REQUEST_URI']."\n";
			$body .= "PAGE: ".$_SERVER['PHP_SELF']."\n";
			$body .= "----------------------------------------------\n".nl2br($errMsg)."\nTrace: ".nl2br($trace);
			$body .= "\n----------------------------------------------\n";

			// Log the error
			$this->error($body);

			break;
		}
	}

	/**
	 * Log an unhandled exception
	 *
	 * @param \ExceptionInterface $exception
	 */
	public function phpUnhandledExceptionHandler($exception)
	{
		$errno = $exception->getCode();
		$errstr = $exception->getMessage();
		$errfile = $exception->getFile();
		$errline = $exception->getLine();
		$backtrace = $exception->getTraceAsString();

		$body = "$errMsg = \"$errno: $errstr in $errfile on line $errline\";\n";
		if (isset($_COOKIE['uname']))
			$body .= "USER_NAME: ".$_COOKIE['uname']."\n";
		$body .= "Type: System\n";
		if (isset($_COOKIE['db']))
			$body .= "DATABASE: ".$_COOKIE['db']."\n";
		if (isset($_COOKIE['dbs']))
			$body .= "DATABASE_SERVER: ".$_COOKIE['dbs']."\n";
		if (isset($_COOKIE['aname']))
			$body .= "ACCOUNT_NAME: ".$_COOKIE['aname']."\n";

		$body .= "When: ".date('Y-m-d H:i:s')."\n";
		$body .= "URL: ".$_SERVER['REQUEST_URI']."\n";
		$body .= "PAGE: ".$_SERVER['PHP_SELF']."\n";
		$body .= "----------------------------------------------\n";
		$body .= $errMsg."\nTrace: $backtrace";
		$body .= "\n----------------------------------------------\n";

		// Log the error
		$this->error($body);
	}

	/**
	 * Capture PHP shutdown event to look for a fatal error
	 */
	public function phpShutdownErrorChecker()
	{
		// Check for a fatal error that would halted execution
		$error = error_get_last();
		if (null != $error)
		{
			if ($error['type'] <= E_ERROR)
			{
				$this->phpErrorHandler($error['type'], 
					$error['message'], 
					$error['file'], 
					$error['line'], array()
				);
			}
		}
	}

    /**
     * Set or unset a flag that will print all logs to the console
     *
     * @param bool $print
     */
    public function setPrintToConsole($print = false) {
        $this->printToConsole = $print;
    }

	/**
	 * Convert an error argument or backtrace to a string for logging
	 */
	private function getPhpErrorArgumentStr($arg)
	{
		switch (strtolower(gettype($arg))) 
		{
		case 'string':
			return( '"'.str_replace( array("\n"), array(''), $arg ).'"' );

		case 'boolean':
			return (bool)$arg;

		case 'object':
			return 'object('.get_class($arg).')';

		case 'array':
			$ret = 'array(';
			$separtor = '';

			foreach ($arg as $k => $v) 
			{
				//$ret .= $separtor.$this->getPhpErrorArgumentStr).' => '.$this->getPhpErrorArgumentStr);
				$separtor = ', ';
			}
			$ret .= ')';

			return $ret;

		case 'resource':
			return 'resource('.get_resource_type($arg).')';

		default:
			return var_export($arg, true);
		}
	}
}
