<?php
/**
 * Main abstract class for controllers in ANT
 *
 * ANT uses a custom controller class to expose actions to ajax requests. This base class is essentially used
 * to define how basic controllers should function.
 *
 * @category  Contoller
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

abstract class Controller
{
	/**
     * Reference to current ANT account object
     *
     * @var CAnt
	 */
	public $ant = null;

	/**
     * Reference to current user object
     *
     * @var AntUser
	 */
	protected $user = null;

	/**
     * If set to true then all 'echo' statements should be ignored for unit tests
     *
     * @var bool
	 */
	public $debug = false;

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
	 * @param CAnt $ant	An active reference to the current ANT account object
	 * @param AntUser $user The current user object
	 */
	function __construct($ant, $user)
	{
		$this->ant = $ant;
		$this->user = $user;
		$this->init();
	}

	/**
	 * Empty method to be optionally overridden by controller implementations
	 */
	public function init() {}

	/**
	 * Print data to the browser. If debug, just cache data
	 *
	 * @param string $data The data to data to the browser or store in buffer
	 */
	protected function sendOutput($data)
	{
		$data = $this->utf8Converter($data);

		if (!$this->debug)
		{
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
		}
		else
		{
			$this->debugOutputBuf .= $data;
		}

		return $data;
	}

	/**
	 * Send raw output
	 *
	 * @param string $data
	 */
	protected function sendOutputRaw($data)
	{
		echo $data;
		return $data;
	}

	/**
	 * Print data to the browser. If debug, just cache data
	 *
	 * @param string $output The data to output to the browser or store in buffer
	 */
	protected function sendOutputJson($data)
	{
		$this->setContentType("json");
		$enc = json_encode($this->utf8Converter($data));

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
		

		if (!$this->debug)
		{
			echo $enc;
		}
		else
		{
			$this->debugOutputBuf = $enc;
		}

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

		if (!$this->debug)
		{
			echo $xml;
		}
		else
		{
			$this->debugOutputBuf = $xml;
		}

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
		if ($this->debug)
			return;

		switch ($type)
		{
		case 'xml':
			header('Cache-Control: no-cache, must-revalidate');
			header("Content-type: text/xml");			// Returns XML document
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
	 */
	private function makeXmlFromArray($data)
	{
		if (!is_array($data))
		{
			if ($data === true)
				return "1";
			else if ($data === false)
				return '0';

			// Return the string
			return $this->escapeXml($data);
		}

		$ret = "";

		foreach ($data as $key=>$val)
		{
			if (is_numeric($key))
				$key = "item";

			$ret .= "<" . $key . ">";
			if (is_array($val))
			{
				$ret .= $this->makeXmlFromArray($val);
			}
			else
			{
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
		return str_replace(array("&", "<", ">", "\"", "'"),
						   array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;"), $string);
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
			if(!mb_detect_encoding($item, 'utf-8', true)){
				$item = utf8_encode($item);
			}
		});

		return $array;
	}
}
