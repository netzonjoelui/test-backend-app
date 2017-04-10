<?php
/**
 * Main abstract class for controllers in netric
 *
 * netric uses a custom controller class to expose actions to ajax requests. This base class is essentially used
 * to define how basic controllers should function.
 *
 * @copyright Copyright (c) 2003-2014 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Mvc;

use Netric\Account\Account;
use Netric\Application\Application;
use Netric\Permissions\Dacl;
use Netric\Entity\ObjType\UserEntity;

/**
 * Main abstract class for controllers in netric
 */
abstract class AbstractController
{
	/**
	 * Application instance for the request
	 *
	 * @var Application
	 */
	protected $application = null;

	/**
     * Reference to current netric account
     *
     * @var \Netric\Account\Account
	 */
	public $account = null;

	/**
	 * Get request interface
	 *
	 * @var \Netric\Request\RequestInterface
	 */
	protected $request = null;

	/**
     * If set to true then all 'echo' statements should be ignored for unit tests
     *
     * @var bool
	 */
	public $testMode = false;

	/**
     * If we are running in debug or testing mode, this variable can be used to test output
     *
     * @var string
	 */
	public $debugOutputBuf = "";

	/**
	 * Output format will default to raw which allows the action to encode
	 *
	 * @var string
	 */
	public $output = "json";

	/**
	 * class constructor. All calls to a controller class require a reference to $ant and $user classes
	 *
	 * @param Application $application The current application instance
	 * @param Account $account The tenant we are running under
	 */
	function __construct(Application $application, Account $account = null)
	{
        $this->application = $application;
		$this->account = $account;
        $this->request = $application->getServiceManager()->get("/Netric/Request/Request");
		$this->init();
	}

	/**
	 * Empty method to be optionally overridden by controller implementations
	 */
	protected function init() {}

	/**
	 * Get the request object
	 *
	 * @return \Netric\Request\RequestInterface
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Get application instance this is working under
	 *
	 * @return \Netric\Application\Application
	 */
	protected function getApplication()
	{
		return $this->application;
	}

	/**
	 * Determine what users can access actions in the concrete controller
	 *
	 * This can easily be overridden in derrived controllers to allow custom access per controller
	 * or each action can handle its own access controll list if desired.
	 *
	 * @return Dacl
	 */
	public function getAccessControlList()
	{
		$dacl = new Dacl();

		// By default allow authenticated users to access a controller
		$dacl->allowGroup(UserEntity::GROUP_USERS);

		return $dacl;
	}

	/**
	 * Print data to the browser. If debug, just cache data
	 *
	 * @param string $data The data to data to the browser or store in buffer
     * @return string
	 */
	protected function sendOutput($data)
	{
		$data = $this->utf8Converter($data);

        switch ($this->output)
        {
        case 'xml':
            return $this->sendOutputXml($data);
            break;
        case 'json':
            return $this->sendOutputJson($data);
            break;
        case 'raw':
            return $this->sendOutputRaw($data);
            break;
        }

		return $data;
	}

	/**
	 * Send raw output
	 *
	 * @param string $data
     * @return array
	 */
	protected function sendOutputRaw($data)
	{
        if (!$this->testMode)
            echo $data;
        
		return $data;
	}

	/**
	 * Print data to the browser. If debug, just cache data
	 *
	 * @param array $data The data to output to the browser or store in buffer
     * @return string JSON encoded string
	 */
	protected function sendOutputJson($data)
	{
		$this->setContentType("json");
		$enc = json_encode($data);

		switch (json_last_error()) 
		{
		case JSON_ERROR_DEPTH:
			$enc = json_encode(array("error"=>"Maximum stack depth exceeded"));
			break;
		case JSON_ERROR_STATE_MISMATCH:
			$enc = json_encode(array("error"=>"Underflow or the modes mismatch"));
			break;
		case JSON_ERROR_CTRL_CHAR:
			$enc = json_encode(array("error"=>"Unexpected control character found"));
			break;
		case JSON_ERROR_SYNTAX:
			$enc = json_encode(array("error"=>"Syntax error, malformed JSON"));
			break;
		case JSON_ERROR_UTF8:
			// Try to fix encoding
			foreach ($data as $vname=>$vval)
			{
				if (is_string($vval))
					$data[$vname] = utf8_encode($vval);
			}
			$enc = json_encode($data);
			break;
		case JSON_ERROR_NONE:
		default:
			// ALl is good
			break;
		}
		

		if (!$this->testMode)
			echo $enc;

		return $data;
	}

	/**
	 * Print data to the browser in xml format
	 *
	 * @param array $data The data to output to the browsr
	 */
	protected function sendOutputXml($data)
	{
		$this->setContentType("xml");
		$enc = json_encode($data);

		$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
		$xml .= "<response>";
		if (is_array($data))
			$xml .= $this->makeXmlFromArray($data);
		else
		{
			if ($data === true)
				$data = "1";
			else if ($data === false)
				$data = "0";

			$xml .= $this->escapeXml($data);
		}
		$xml .= "</response>";

		if (!$this->testMode)
			echo $xml;

		return $xml;
	}

	/**
	 * Set headers for this response so the data type is correct
	 *
	 * @param string $output The data to output to the browser or store in buffer
	 */
	protected function setContentType($type="html")
	{
		// If in debug mode then we are not sending any output to the browser
		if ($this->testMode)
			return;

		switch ($type)
		{
		case 'xml':
			header('Cache-Control: no-cache, must-revalidate');
			header("Content-type: text/xml");
			break;
		case 'json':
			header('Cache-Control: no-cache, must-revalidate');
			//header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Content-type: application/json');
			break;

		default:
			// Use the php defaults if no type or html is set
		}
	}

	/**
	 * Recurrsively convert array to xml
	 *
	 * @param array $data The data to convert to xml
     * @return string
	 */
	private function makeXmlFromArray($data)
	{
		if (!is_array($data)) {
			if ($data === true) {
                return "1";
            } else if ($data === false) {
                return '0';
            }

			// Return the string
			return $this->escapeXml($data);
		}

		$ret = "";

		foreach ($data as $key=>$val) {
			if (is_numeric($key)) {
                $key = "item";
            }

			$ret .= "<" . $key . ">";
			if (is_array($val)) {
				$ret .= $this->makeXmlFromArray($val);
			} else {
				// Escape
				$val = $this->escapeXml($val);
				$ret .= $val;
			}

			$ret .= "</" . $key . ">";
		}

		return $ret;
	}

	/**
	 * Escape XML
	 *
	 * @param string $string The string to escape for xml
	 * @return string The escaped string
	 */
	private function escapeXml($string)
	{
		return str_replace(
            array("&", "<", ">", "\"", "'"),
			array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;"),
            $string
        );
	}

	/**
	 * Recursively convert strings in array to UTF-8
	 *
	 * @param array $array
	 * @return array
	 */
	private function utf8Converter($array)
	{
        if (!is_array($array))
            return $array;

		array_walk_recursive($array, function(&$item, $key){
			if(is_string($item) && !mb_detect_encoding($item, 'utf-8', true)){
				$item = utf8_encode($item);
			}
		});

		return $array;
	}
}
