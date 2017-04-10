<?php
/**
 * Template CustCube that determines if tasks are on track or off track using combination of due date and status
 *
 * @category  CustCube
 * @package   Project_TaskTrack
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/Olap/Cube/CustomInterface.php");
require_once("lib/CAntObject.php");

class CustCube_Project_TaskTrack extends Olap_Cube_CustomInterface
{
	/**
	 * Object definition
	 * 
	 * @var CAntObject
	 */
	private $objDef = null;

	/**
	 * Flag used to determine if we set on track or off track
	 *
	 * $var bool
	 */
	private $onTrack = false;

	/**
	 * Setup class variables
	 */
	public function setup()
	{
		$this->objDef = new CAntObject($this->dbh, "task");
	}

	/**
	 * Set the available measures for the custom cube
	 *
	 * @return bool true on success, false if failure
	 */
	public function setMeasures()
	{
		// Add special calculated measures
		$this->cube->addMeasure("count");
	}

	/**
	 * Set the available dimensions for the custom cube
	 *
	 * @return bool true on success, false if failure
	 */
	public function setDimensions()
	{
		$fields = $this->objDef->fields->getFields();
		foreach($fields as $fname=>$fdef)
        {
            $this->cube->addDimension($fname, $fdef['type']);
		}

		// Add special 'track' dimension
        $this->cube->addDimension("track", "string");
	}

	/**
	 * Retrieve data and pass through to cube
	 *
	 * @param Olap_Cube_Query $ocqQuery instance of olap cube query object
	 * @return array Muti-dimensional array in the following format: ["dim1_value"]["meas_name"] or ["dim1_value"]["dim2_value"]["meas_name"]
	 */
	public function getData($ocqQuery)
	{
		$data = array();

		// Pull number of tasks that are on track
		$olist = new CAntObjectList($this->dbh, "task");
		$olist->addCondition("and", "done", "is_equal", "t");
		$olist->addCondition("or", "deadline", "is_greater_or_equal", "now");
		$olist->addCondition("or", "deadline", "is_equal", "");
		for ($i = 0; $i < count($ocqQuery->filters); $i++)
		{
			$olist->addCondition($ocqQuery->filters[$i]['blogic'], $ocqQuery->filters[$i]['field'], 
									$ocqQuery->filters[$i]['operator'], $ocqQuery->filters[$i]['condition']);
		}
		$olist->getObjects();
		if (count($ocqQuery->dimensions))
		{
			$this->onTrack = true;
			$this->setDimData($ocqQuery->measures, $ocqQuery->dimensions, $olist, $data);
		}
		else
		{
			$data['count'] = (int) $olist->getTotalNumObjects();
		}

		// Pull number of tasks that are off track
		$olist = new CAntObjectList($this->dbh, "task");
		$olist->addCondition("and", "done", "is_not_equal", "t");
		$olist->addCondition("and", "deadline", "is_not_equal", "");
		$olist->addCondition("and", "deadline", "is_less", "now");
		for ($i = 0; $i < count($ocqQuery->filters); $i++)
		{
			$olist->addCondition($ocqQuery->filters[$i]['blogic'], $ocqQuery->filters[$i]['field'], 
									$ocqQuery->filters[$i]['operator'], $ocqQuery->filters[$i]['condition']);
		}
		$olist->getObjects();
		if (count($ocqQuery->dimensions))
		{
			$this->onTrack = false;
			$this->setDimData($ocqQuery->measures, $ocqQuery->dimensions, $olist, $data);
		}
		else
		{
			$data['count'] = (int) $olist->getTotalNumObjects();
		}


		return $data;
	}

	/**
	 * Get data in a tabular two dimensional format
	 *
	 * @param Olap_Cube_Query $ocqQuery instance of olap cube query object
     * @return array sing-dimensional array in the following format: array("colname"=>"colval)
	 */
	public function getTabularData($ocqQuery)
	{
		// TODO: this needs a little work...
		$data = array();

		return $data;
	}

	/**
	 * Set dimension data with object list results
	 *
	 * @param array $measures List of measures to report on
	 * @param array $dimensions List of dimensions to query by
	 * @param &CAntObjectList $objList Reference to object list with results
	 * @param &array $retData Reference to data array to be returned to cube after processing
	 */
	private function setDimData($measures, $dimensions, &$objList, &$retData)
	{
		// Get counters and stats
        $num = $objList->getNumObjects();
        $total = $objList->getTotalNumObjects();                
        $offset = 0;

		for ($i = 0; $i < $num; $i++)
		{
			$obj = $objList->getObject($i);

			$currEl = &$retData;

			foreach ($dimensions as $dim)
			{
				if ($dim['name'] == 'track')
				{
					$dimValue = ($this->onTrack) ? "On Track" : "Off Track";
				}
				else
				{
					$dimValue = $obj->getValue($dim['name'], true);
				}

				if (!isset($currEl["$dimValue"]))
					$currEl["$dimValue"] = array();

				// Move up the tree with currEl
				$currEl = &$currEl["$dimValue"];
			}

			// Set measures in current element
			foreach ($measures as $mname=>$magg)
			{
				// Get value if field, otherwise treat like count and increment 1
				if ($obj->fields->getField($mname))
				{
                	$measValue = $obj->getValue($mname);
				}
				else
				{
					$measValue = 1;
				}

                $currVal = null;                
                if(isset($currEl[$mname]))
                    $currVal = $currEl[$mname];
                    
				$currEl[$mname] = Olap_Cube::aggregateValue($measValue, $currVal, $magg);
			}

			// If result set is larger than 1000
            $offset++;
            if ($i == $num && ($num+$offset) < $total)
            {
                $objList->getObjects($offset, 1000); // Get next page
                $num = $objList->getNumObjects();
                $total = $objList->getTotalNumObjects();
            }
		}

	}
}
