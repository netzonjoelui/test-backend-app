<?php
/**
 * Main OLAP class for ANT data
 *
 * This class will be used to store, query, and manage OLAP cubes
 *
 * @category  Ant
 * @package   Olap
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/Olap/Cube.php");

/**
 * Main Olap class
 */
class Olap
{
	/**
     * Handle to account database
     *
     * @var CDatabase
	 */
	private $dbh = null;

	/**
     * Store last error generated
     *
     * @var string
	 */
	public $lastError = "";

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 */
	public function __construct($dbh)
	{
		$this->dbh = $dbh;
	}

	/**
	 * Delete a cube and all it's data
	 *
	 * @param string $name The unique name of the cube to retrieve
	 * @return bool True on success, false on failure
	 */
	public function deleteCube($name)
	{
		$cube = new Olap_Cube_Dataware($this->dbh, $name);
		return $cube->remove();
	}

	/**
	 * Get an instance of a data-warehouse cube by name
	 *
	 * @param string $name The unique name of the cube to retrieve
	 * @return Olap_Cube_Dataware
	 */
	public function getCube($name)
	{
		$cube = new Olap_Cube_Dataware($this->dbh, $name);
		return $cube;
	}

	/**
	 * Get an instance of a adhock query cube
	 *
	 * Adhock cubes query data from objects in real time
	 *
     * @param string $obj_type The unique name of the object we will be querying
	 * @param AntUser $user The user object
	 * @return Olap_Cube_Object
	 */
	public function getAdhockCube($obj_type, $user)
	{        
		$cube = new Olap_Cube_Adhock($this->dbh, $obj_type, $user);        
		return $cube;
	}

	/**
	 * Get an instance of a custom report cube
	 *
	 * Custom report cubes are created manually in /reports/cubes
	 *
     * @param string $name The unique name of the custom cube
	 * @param AntUser $user The user object
	 * @return Olap_Cube_Object
	 */
	public function getCustomCube($name, $user)
	{        
		$cube = new Olap_Cube_Custom($this->dbh, $name);        
		return $cube;
	}
}
