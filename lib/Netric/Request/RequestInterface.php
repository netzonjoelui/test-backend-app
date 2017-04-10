<?php
/**
 * Request interface
 */
namespace Netric\Request;

interface RequestInterface
{
	/**
	 * Get a request param by name
	 *
	 * @param string $name The name of the param to get
	 */
	public function getParam($name);

	/**
	 * Set/override a param
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setParam($name, $value);

	/**
	 * Get all params in an associative array
	 *
	 * @return array
	 */
	public function getParams();

	/**
	 * Get the raw body of the request
	 *
	 * @return string
	 */
	public function getBody();

	/**
	 * Get the path taht was requested after the server name
	 *
	 * For example, www.mysite.com/my/path would return
	 * 'my/path'.
	 *
	 * @return string
	 */
	public function getPath();

	/**
	 * Manual path override
	 *
	 * @param string $path The path to set
	 */
	public function setPath($path);

    /**
     * Get the method/verb of the request type
     *
     * @return string Usually a HTTP verb or console
     */
    public function getMethod();
}