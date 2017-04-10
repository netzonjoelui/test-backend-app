<?php
/**
 * This class will be used to create queries
 *
 * @category  Olap
 * @package   Cube
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Main Olap Cube Query class used to contain query params
 */
class Olap_Cube_Query
{
	/**
	 * Associative array of dimensions [['name'=>'dimname', 'sort'=>'asc'|'desc', 'fun'=>'fmtfunname']]
     *
     * @var array
	 */
	public $dimensions = array();

	/**
	 * Associative array of measures [['name'=>'measurename', 'aggregate'=>'aggregatefun']]
     *
     * @var array
	 */
	public $measures = array();

	/**
	 * Associative array of conditions [['blogic'=>'and'|'or', 'field'=>'fieldtoquery', 'operator'='is_equal', 'condition'=>'yes']]
     *
     * @var array
	 */
	public $filters = array();
    
    /**
     * Simple array of fields to be retrieved from Ant Object List ['field1', 'field2', 'field3']
     *
     * @var array
     */    
    public $pullMinFields = array();

	/**
	 * Add dimension
	 *
	 * @param string $dname The dimension/field name
	 * @param string $sort The direction the dimension should be sorted in. Can either be 'asc' or 'desc'
	 * @param string $fun An optional formatting function
	 */
	public function addDimension($dname, $sort="asc", $function="")
	{        
        if(!$this->checkDimension($dname) && !empty($dname))
        {
            $this->dimensions[] = array("name"=>$dname, "sort"=>$sort, "fun"=>$function);
            $this->pullMinFields[] = array("name"=>$dname, "sort"=>$sort);
        }		
	}

	/**
	 * Add measure
	 *
	 * @param string $mname The unique name of the measure to pull
	 * @param string $agg The aggregate to use with this measure. Defaults to sum(marize)
	 */
	public function addMeasure($mname, $agg="sum")
	{
		//$this->measures[] = array("name"=>$mname, "aggregate"=>$agg);
		$this->measures[$mname] = $agg;
        $this->pullMinFields[] = array("name"=>$mname);
	}

	/**
	 * Add filter
	 *
	 * @param string $blogic Can be "and" or "or"
	 * @param string $fieldname The field name to filter by
	 * @param string $operator Can be a number of predefined operators
	 * @param string $condition The condition to query to fieldname by
	 */
	public function addFilter($blogic, $fieldname, $operator, $condition)
	{
		$filter = array(
			"blogic" => $blogic,
			"field" => $fieldname,
			"operator" => $operator,
			"condition" => $condition,
		);

		$this->filters[] = $filter;
	}
    
    /**
     * Check dimension if already exists
     *
     * @param string $name Dimension name
     */
    private function checkDimension($name)
    {        
        for ($i = 0; $i < count($this->dimensions); $i++)
        {            
            if ($this->dimensions[$i]["name"] == $name)
                return true;
        }
        
        return false;
    }
}
