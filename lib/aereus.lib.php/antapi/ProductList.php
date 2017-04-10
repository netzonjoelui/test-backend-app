<?php
/**
 * Aereus API Library
 *
 * Product class is basically just an alias for AntApi_ObjectList but returns type AntApi_Product
 *
 * @category  AntApi
 * @package   AntApi_ProductList
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class representing an ANT object list of type=product
 */
class AntApi_ProductList extends AntApi_ObjectList
{
	/**
     * Class constructor just calls base class constructor with appropriate object type
	 *
	 * @param string $server	ANT server name
	 * @param string $username	A Valid ANT user name with appropriate permissions
	 * @param string $password	ANT user password
     */
	function __construct($server, $username, $password) 
	{
		parent::__construct($server, $username, $password, "product");
	}

	/**
	 * Execute a query
	 *
	 * @param int $offset	Start offset
	 * @param int $limit	The maximum number of objects to return in this set
	 */
	function getProducts($offset=0, $limit=null)
	{
		return $this->getObjects($offset, $limit);
	}

	/**
	 * Get the number of products/objects returned in this result set
	 */
	function getNumProducts()
	{
		return $this->getNumObjects();
	}

	/**
	 * Get the number of products/objects in all result sets
	 */
	function getTotalNumProducts()
	{
		return $this->getTotalNumObjects();
	}
	
	/**
	 * Get an object at the specificied index
	 *
	 * @param int $idx	Index of object to retrieve
	 */
	function getProduct($idx)
	{
		if ($idx >= $this->getNumProducts())
			return null;

		if (!$this->m_objectList[$idx]["id"])
			return null;

		$obja = new AntApi_Product($this->m_server, $this->m_user, $this->m_pass);
		$obja->open($this->m_objectList[$idx]["id"]);
		return $obja;
	}

	/**
	 * Just get the data array for this object, but do not create a class
	 *
	 * @param int $idx	Index of object to retrieve
	 */
	function getProductMin($idx)
	{
		return $this->getObjectMin($idx);
	}
}
