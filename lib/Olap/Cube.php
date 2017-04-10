<?php
/**
 * Abstract class for managing cubes
 *
 * This class will be used to store, query, and manage OLAP cubes
 *
 * @category  Olap
 * @package   Cube
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/Olap/Cube/Query.php");
require_once("lib/Olap/Cube/Dimension.php");
require_once("lib/Olap/Cube/Measure.php");
require_once("lib/Olap/Cube/Adhock.php");
require_once("lib/Olap/Cube/Dataware.php");
require_once("lib/Olap/Cube/Custom.php");

/**
 * Main Olap Cube class that must be implemented with interface subclass
 */
abstract class Olap_Cube
{
	/**
     * Handle to account database
     *
     * @var CDatabase
	 */
	protected $dbh = null;

	/**
     * Array of dimensions
     *
     * @var Olap_Cube_Dimension
	 */
	protected $dimensions = array();

	/**
     * Array of measures
     *
     * @var Olap_Cube_Measure
	 */
	protected $measures = array();

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
	 * Create and return instance of Olap_Cube_Query
	 *
	 * @return Olap_Cube_Query
	 */
	public function getQuery()
	{
		$query = new Olap_Cube_Query();
		return $query;
	}

	/**
	 * Get list of available dimensions
	 *
	 * @return Olap_Cube_Dimension[] Array of dimension objects
	 */
	public function getDimensions()
	{
		return $this->dimensions;
	}

	/**
	 * Get list of operators available for a dimension type
	 *
	 * @return string[] Array of available conditions for a field type
	 */
	public function getDimFilterOperators($dname)
	{
		$dim = null;

		foreach ($this->dimensions as $dimen)
		{
			if ($dimen->name == $dname)
				$dim = $dimen;
		}

		if (!$dim)
			return null;

		switch ($dim->type)
		{
		case 'time':
		case 'date':
			return array(
				"is_equal", "is_not_equal", "is_greater", "is_less", "is_greater_or_equal", "is_less_or_equal",
				"day_is_equal", "year_is_equal", "last_x_days", "last_x_weeks", "last_x_months", "last_x_years",
				"next_x_days", "next_x_weeks", "next_x_months", "next_x_years",
			);
		case 'numeric':
			return array("is_equal", "is_not_equal", "is_greater", "is_less", "is_greater_or_equal", "is_less_or_equal");
		case 'string':
			return array("is_equal", "is_not_equal", "begins_with", "contains");
		}

		return null;
	}

	/**
	 * Get list of available measures
	 *
	 * @return Olap_Cube_Measure[] Array of measure objects
	 */
	public function getMeasures()
	{
		return $this->measures;
	}

	/**
	 * If implemented this supports deleting a cube and all its data
	 *
	 * @return bool True on successful delete, false on failure
	 */
	public function remove()
	{
		return false;
	}

	/**
	 * If implemented this supports writeback then write data to the cube.
	 *
	 * If the cube does not support writeback, then this function MUST be implemented and return false
	 *
	 * Example code:
	 * <code>
	 * 	$measures = array("visits"=>100);
	 * 	$dimensions = array("page"=>"/index.php", "country"=>array(1=>"us")); // 1 = "us"
	 * 	$cube->wireData($measures, $dimensions);
	 * </code>
	 * 
	 * @param array $measures Associative array of measures 'name'=>value
	 * @param array $data Associateive array of dimension data. ('dimensionanme'=>'value'). Value may be key/value array like 'id'=>'label'
	 * @param bool $increment If set to true then do not overwrite matching record, but increment it. Default = false.
	 */
	public function writeData($measures, $data, $increment=false)
	{
		return false;
	}

	/**
	 * Query data
	 *
	 * @param Olap_Cube_Query $ocqQuery instance of olap cube query object
	 *
	 * @return array Muti-dimensional array in the following format: ["dim1_value"]["meas_name"] or ["dim1_value"]["dim2_value"]["meas_name"]
	 */
	abstract public function getData($ocqQuery);

	/**
	 * Get data in a tabular two dimensional format
	 *
	 * @param Olap_Cube_Query $ocqQuery instance of olap cube query object
	 *
     * @return array sing-dimensional array in the following format: array("colname"=>"colval)
     */
	abstract public function getTabularData($ocqQuery);

	/**
	 * If implementaion supports writeback then increment data in this cube with matching data
	 *
	 * Example code:
	 * <code>
	 * 	$measures = array("visits"=>100);
	 * 	$dimensions = array("page"=>"/index.php", "country"=>array(1=>"us")); // 1 = "us"
	 * 	$cube->incrementData($measures, $dimensions);
	 * </code>
	 * 
	 * @param array $measures Associative array of measures 'name'=>value
	 * @param array $data Associateive array of dimension data. ('dimensionanme'=>'value'). Value may be key/value array like 'id'=>'label'
	 */
	public function incrementData($measures, $data)
	{
		$this->writeData($measures, $data, true);
	}
    
    /**
     * Aggregate a value
     *
     * We may need to move this to the base class
     *
     * @param number $newval The added value
     * @param number $curval The current value of the dimension
     * @param string $agg Aggregate function to use
     * @return number result of aggregate
     */
    public function aggregateValue($newval, $curval, $agg)
    {
        $ret = 0;

        switch ($agg)
        {
        case 'min':
            $ret = ($newval < $curval) ? $newval : $curval;
            break;
        case 'max':
            $ret = ($newval > $curval) ? $newval : $curval;
            break;
        case 'avg':
            // In order for avg to work correctly, we have to wait until all have been processed
            // So sum add data, then later we'll divide it by count
        case 'sum':
        default:
            $ret = $newval + $curval;
            break;
        }

        return $ret;
    }

}
