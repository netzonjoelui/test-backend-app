<?php
/**
 * Console Request
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Request;

use Zend\Console\Getopt;

/*
 * A tick is an event that occurs for every N low-level tickable
 * statements executed by the parser within the declare block.
 * We are basically telling the pcntl_signal to check after every
 * single operation to see if we should exit.
 */
declare(ticks = 1);

class ConsoleRequest implements RequestInterface
{
	/**
     * @var array
     */
    private $params = array();

    /**
     * @var array
     */
    private $envParams = array();

    /**
     * @var string
     */
    private $scriptName = null;

    /**
     * The path to the controller and action
     *
     * @var sring
     */
    private $path = null;

	/**
     * Contains the request input data
     *
     * @var string
     */
    private $rawBody = null;

    /**
     * Flag if the request (process) is being canceled or stopped
     *
     * @var bool
     */
    private $stopping = false;

	/**
	 * Initialize request object variables
	 */
	public function __construct(array $args = null, array $env = null)
	{
		if ($args === null) 
		{
            if (!isset($_SERVER['argv'])) 
            {
                $errorDescription = (ini_get('register_argc_argv') == false)
                    ? "Cannot create Console\\Request because PHP ini option 'register_argc_argv' is set Off"
                    : 'Cannot create Console\\Request because $_SERVER["argv"] is not set for unknown reason.';
                throw new \RuntimeException($errorDescription);
            }
            $args = $_SERVER['argv'];
        }

        if ($env === null) {
            $env = $_ENV;
        }

        /*
         * Extract first param assuming it is the script name
         */
        if (count($args) > 0) {
            $this->setScriptName(array_shift($args));
        }

        /*
         * Extract the second parameter which is the path
         */
        if (count($args) >= 1) {
            $this->path = array_shift($args);
        }

        /*
         * Store runtime params
         */
        $this->params = $this->parseArgs($args);

        /*
         * Store environment data
         */
        $this->envParams = $env;

        // Setup signal handlers to actually catch and direct the signals
        if (function_exists("pcntl_signal")) {
            pcntl_signal(SIGTERM, array($this, "singHandleStop"));
            pcntl_signal(SIGHUP, array($this, "singHandleStop"));
            pcntl_signal(SIGINT, array($this, "singHandleStop"));
        }
	}

	/**
	 * Get a request param by name
	 *
	 * @param string $name The name of the param to get
	 */
	public function getParam($name)
	{
		if (isset($this->params[$name]))
			return $this->params[$name];

		// Not found
		return null;
	}

	/**
	 * Get all params in an associative array
	 *
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

    /**
     * Set/override a param
     *
     * @param string $name
     * @param string $value
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Get an environment variable
     *
     * @param string    $name       Parameter name
     * @param string    $default    (optional) default value in case the parameter does not exist
     * @return \Zend\Stdlib\Parameters
     */
    public function getEnv($name, $default = null)
    {
    	if (!isset($this->envParams[$name]))
    		return $default;

        return $this->envParams[$name];
    }

	/**
	 * Get the raw body of the request
	 *
	 * @return string
	 */
	public function getBody()
	{
        // If $rawBody is set then we will return it instead of php://input
        $data = ($this->rawBody) ? $this->rawBody : file_get_contents("php://input");

        return $data;
	}

	/**
     * @param string $scriptName
     */
    public function setScriptName($scriptName)
    {
        $this->scriptName = $scriptName;
    }
    /**
     * @return string
     */
    public function getScriptName()
    {
        return $this->scriptName;
    }

    /**
     * Convert arguments into named params
     *
     * @param array $args The arguments to parse into params
     * @return array An associative array of params for each arg
     */
    private function parseArgs(array $args)
    {
        $options = $this->getOptionsFromArgs($args);

        $getOpt = new Getopt($options, $args);

        return $getOpt->getArguments();
    }

    /**
     * Loop through all args and extract options that start with - or -- for getopts
     *
     * @param array $args The arguments to parse
     * @return \array
     */
    private function getOptionsFromArgs(array $args)
    {
        $options = [];

        foreach ($args as $arg) {

            // Skip malformed arguments
            if (strlen($arg) < 2)
                continue;

            // Extract all options from the arguments
            if ($arg[0] == '-') {
                // If -- then jump 2, otherwise 1
                $start = ($arg[1] == '-') ? 2 : 1;

                $paramName = "";
                $end = strlen($arg);
                for ($i = $start; $i < $end; $i++) {

                    // Finish when we see the delimiter
                    if ($arg[$i] == '=' || $arg[$i] == ' ')
                        break;

                    $paramName .= $arg[$i];
                }

                if ($paramName && $start == 2) {
                    // Add long option
                    $options[$paramName . "=s"] = $paramName;
                } else if ($paramName && $start == 1) {
                    // Add short options
                    $options[$paramName . "-s"] = $paramName;
                }
            }
        }

        return $options;
    }

    /**
     * Get the path taht was requested after the server name
     *
     * For example, www.mysite.com/my/path would return
     * 'my/path'.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Manual path override
     *
     * @param string $path The path to set
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Get the method/verb of the request type
     *
     * @return string Always returns 'CONSOLE'
     */
    public function getMethod()
    {
        return 'CONSOLE';
    }

    /**
     * Set the raw body with the request data
     *
     * @param {array} Request data that will be set as raw body
     */
    public function setBody($data)
    {
        $this->rawBody = $data;
    }

    /**
     * Handle stop signals if we are working in the console
     *
     * @param int $signo
     */
    public function singHandleStop($signo)
    {
        $this->stopping = true;
    }

    /**
     * Check if the request was canceled and we should exist gracefully
     *
     * @return bool true if the process is being killed
     */
    public function isStopping()
    {
        return $this->stopping;
    }
}