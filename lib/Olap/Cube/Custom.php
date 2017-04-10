<?php
/**
 * Implementation of OLAP cube that for custom reporting
 *
 * @category  Olap_Cube
 * @package   Custom
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Implementation of OLAP cube that gets cube data from custom report classes
 */
class Olap_Cube_Custom extends Olap_Cube
{
	/**
     * Unique id of this cube
     *
     * @var integer
	 */
	public $id = null;

	/**
     * Name/path of the custom cube
     *
     * @var string
	 */
	protected $cubeName = null;

	/**
     * Reference to the custom report object
     *
     * @var Olap_Cube_CustomInterface
	 */
	public $interface = null;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param string $cubeName Unique name of the custom cube to load
	 * @param bool $createIfMissing If true then create the cube on insert if it does not exist. Default = true
	 */
	public function __construct($dbh, $cubeName)
	{
		$this->cubeName = $cubeName;
		
		parent::__construct($dbh);

		if ($this->cubeName)
			$this->load();
	}

	/**
	 * Load custom cube data through instance of Olap_Cube_CustomInterface
	 */
	private function load()
	{
		include_once("reports/cubes/".str_replace("_", "/", $this->cubeName).".php");

		$classname = "CustCube_".$this->cubeName;
		
		if (class_exists($classname))
		{
			$this->interface = new $classname($this->dbh, $this);
			$this->interface->setMeasures();
			$this->interface->setDimensions();
		}
	}

	/**
	 * Add a dimension to the custom cube
	 *
	 * @param string $name A unique name for this dimension
	 * @param string $type The dimension type can be numeric, time or string
	 */
	public function addDimension($name, $type="string")
	{
		// numeric, time, string are the available types

		$dim = new Olap_Cube_Dimension();
		$dim->name = $name;
		$dim->type = $type;

		$this->dimensions[] = $dim;
	}

	/**
	 * Add a measure to the custom cube
	 *
	 * @param string $name A unique name for this measure
	 */
	public function addMeasure($name)
	{
		$meas = new Olap_Cube_Measure();
		$meas->name = $name;

		$this->measures[] = $meas;
	}

	/**
	 * Query data
	 *
	 * @param Olap_Cube_Query $ocqQuery instance of olap cube query object
	 *
	 * @return array Muti-dimensional array in the following format: ["dim1_value"]["meas_name"] or ["dim1_value"]["dim2_value"]["meas_name"]
	 */
	public function getData($ocqQuery)
	{
		if (!$this->interface)
			return false;

		return $this->interface->getData($ocqQuery);
	}

	/**
	 * Get data in a tabular two dimensional format
	 *
	 * @param Olap_Cube_Query $ocqQuery instance of olap cube query object
	 *
     * @return array sing-dimensional array in the following format: array("colname"=>"colval)
     */
	public function getTabularData($ocqQuery)
	{
		if (!$this->interface)
			return false;

		return $this->interface->getTabularData($ocqQuery);
	}
}
