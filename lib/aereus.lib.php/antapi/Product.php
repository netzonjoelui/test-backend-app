<?php
/**
 * Aereus API Library
 *
 * Product class is basically just an alias for AntApi_Object
 *
 * @category  AntApi
 * @package   AntApi_Product
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class representing an ANT object of type=product
 */
class AntApi_Product extends AntApi_Object
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
}
