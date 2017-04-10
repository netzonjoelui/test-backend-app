<?php
/**
 * Template CustCube can be used as an exaple for creating customized cubes in ANT
 *
 * This class is also used fro unit testing
 *
 * @category  CustCube
 * @package   Template
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/Olap/Cube/CustomInterface.php");

class CustCube_Template extends Olap_Cube_CustomInterface
{
	/**
	 * Test data
	 *
	 * @var array
	 */
	private $data = array(
		array("page"=>"index.php", "country"=>"USA", "visits"=>100, "hits"=>500),
		array("page"=>"index.php", "country"=>"RUSSIA", "visits"=>75, "hits"=>250),
	);

	/**
	 * Set the available measures for the custom cube
	 *
	 * @return bool true on success, false if failure
	 */
	public function setMeasures()
	{
		$this->cube->addMeasure("hits");
		$this->cube->addMeasure("visits");
	}

	/**
	 * Set the available dimensions for the custom cube
	 *
	 * @return bool true on success, false if failure
	 */
	public function setDimensions()
	{
		$this->cube->addDimension("page", "string");
		$this->cube->addDimension("country", "string");
	}

	/**
	 * Retrieve data and pass through to cube
	 *
	 * @param Olap_Cube_Query $ocqQuery instance of olap cube query object
	 * @return array Muti-dimensional array in the following format: ["dim1_value"]["meas_name"] or ["dim1_value"]["dim2_value"]["meas_name"]
	 */
	public function getData($ocqQuery)
	{
		// TODO: can optionally filter the results with $ocqQuery->getFilters();
		
		$ret = array();

		$ret['index.php']['USA']['visits'] = 100;
		$ret['index.php']['RUSSIA']['visits'] = 75;

		$ret['index.php']['USA']['hits'] = 500;
		$ret['index.php']['RUSSIA']['hits'] = 250;

		return $ret;
	}

	/**
	 * Get data in a tabular two dimensional format
	 *
	 * @param Olap_Cube_Query $ocqQuery instance of olap cube query object
     * @return array sing-dimensional array in the following format: array("colname"=>"colval)
	 */
	public function getTabularData($ocqQuery)
	{
		// TODO: can optionally filter the results with $ocqQuery->getFilters();
		return $this->data;
	}
}
