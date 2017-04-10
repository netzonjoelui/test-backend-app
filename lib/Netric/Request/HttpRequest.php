<?php
/**
 * Http Request
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Request;

class HttpRequest implements RequestInterface
{
	/**
	 * Array of stores to get params from
	 *
	 * @var array
	 */
	private $httpStores = null;

	/**
	 * Params array
	 *
	 * @var array
	 */
	private $params = array();

	/**
	 * Request method
	 *
	 * @var string
	 */
	private $method = self::METHOD_GET;
    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    /**
     * Path to requested page/controller/action
     *
     * @var string
     */
    private $path = null;

	/**
	 * Contains the request input data
	 *
	 * @var string
	 */
	private $rawBody = null;

	/**
	 * Initialize request object variables
	 */
	public function __construct()
	{
		$headers = (function_exists("apache_request_headers"))
			 ? \apache_request_headers()
			 : $headers = array();

		// Add uploaded files
		$this->params['files'] = (isset($_FILES) && count($_FILES)) ? $_FILES : array();

		// Combine all sources of request data
		$this->httpStores = array(
			$headers, $_COOKIE, $_POST, $_GET, $this->params,
		);

		$this->method = (isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : null;
        $this->path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : null;
	}

	/**
	 * Get a request param by name
	 *
	 * @param string $name The name of the param to get
	 * @return string|array The value of the named param
	 */
	public function getParam($name) {
		// Check through any http request objects
		foreach ($this->httpStores as $httpStore) {

			// Make query case insensitive
			$paramName = isset($httpStore[$name]) ? $name : strtolower($name);

			// Return the first match
			if (isset($httpStore[$paramName]) && $httpStore[$paramName]) {
				return $httpStore[$paramName];
			}
		}

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
		$ret = array();

		// Check through any http request objects
		foreach ($this->httpStores as $httpStore)
		{
			foreach ($httpStore as $pname=>$pval)
			{
				// Over-write duplicates
				$ret[$pname] = $pval;
			}
		}

		return $ret;
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
     * @return string The HTTP verb used for this request: POST, GET, PUT, DELETE
     */
    public function getMethod()
    {
        return $this->method;
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
}
