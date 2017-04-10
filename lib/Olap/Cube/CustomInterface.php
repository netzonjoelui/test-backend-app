<?php
/**
 * Custom cube interface definition
 *
 * This abstract interface class is implemented by all custom cubes
 *
 * @category  Olap_Cube
 * @package   CustomInterface
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

abstract class Olap_Cube_CustomInterface
{
	/**
     * Handle to account database
     *
     * @var CDatabase
	 */
	protected $dbh = null;

	/**
     * Reference to custom cube instance we are interfacing with
     *
     * @var Olap_Cube_Custom
	 */
	protected $cube = null;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param Olap_Cube_Custom $cube Reference to custom cube instance
	 */
	public function __construct($dbh, $cube)
	{
		$this->dbh = $dbh;
		$this->cube = $cube;

		$this->setup();
	}

	/**
	 * Setup function can optionally be overridden in inherited classes
	 */
	public function setup()
	{
	}

	/**
	 * Set the available measures for the custom cube
	 *
	 * @return bool true on success, false if failure
	 */
    abstract public function setMeasures();

	/**
	 * Set the available dimensions for the custom cube
	 *
	 * @return bool true on success, false if failure
	 */
    abstract public function setDimensions();

	/**
	 * Retrieve data and pass through to cube
	 *
	 * @param Olap_Cube_Query $ocqQuery instance of olap cube query object
	 * @return array Muti-dimensional array in the following format: ["dim1_value"]["meas_name"] or ["dim1_value"]["dim2_value"]["meas_name"]
	 */
    abstract public function getData($ocqQuery);

	/**
	 * Get data in a tabular two dimensional format
	 *
	 * @param Olap_Cube_Query $ocqQuery instance of olap cube query object
     * @return array sing-dimensional array in the following format: array("colname"=>"colval)
	 */
    abstract public function getTabularData($ocqQuery);
}
