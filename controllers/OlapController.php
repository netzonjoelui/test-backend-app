<?php
/**
* OLAP Actions
*/
require_once(dirname(__FILE__).'/../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../lib/Controller.php');
require_once(dirname(__FILE__).'/../lib/Olap.php');
require_once(dirname(__FILE__).'/../lib/aereus.lib.php/CChart.php');

/**
* Actions for interacting with Application Controler
*/

class OlapController extends Controller
{
    public function __construct($ant, $user)
    {
        $this->ant = $ant;
        $this->user = $user;
    }
    
    /**
     * Get the current cube (dataware or adhock)
     */
    public function getCube($params)
    {
        $dbh = $this->ant->dbh;
        $olap = new Olap($dbh);
        
        if (isset($params['customReport']) && $params['customReport'])
        {
            $cube = $olap->getCustomCube($params['customReport'], $this->user);
        }
        else if (isset($params['datawareCube']) && $params['datawareCube'])
        {
            $cube = $olap->getCube($params['datawareCube']);
        }
		else if (isset($params['obj_type']) && $params['obj_type'])
        {
            $cube = $olap->getAdhockCube($params['obj_type'], $this->user);
        }
        else
        {
            $cube = false;
        }
        
        if(isset($params['api']))
            $this->sendOutputJson($cube);
        
        return $cube;
    }
    
	/**
	 * Query an olap cube and return results in json format
	 */
    public function queryCubeJson($params)
    {        
		$ret = array();
        
        // Initialize arrays to avoid warning messages
        if (!is_array($params['measures'])) $params['measures'] = array();
        if (!is_array($params['dimensions'])) $params['dimensions'] = array();
        if (!isset($params['filters'])) $params['filters'] = array();
        if (!is_array($params['filters'])) $params['filters'] = array();
        
		// Get cube
		$cube = $this->getCube($params);
        if(!$cube)
            return $this->sendOutputJson(array("error"=>"Cube type must be set"));

		$query = new Olap_Cube_Query();
        
		// Add measures
		foreach ($params['measures'] as $mid)
		{
			$query->addMeasure($params['measure_name_'.$mid], $params['measure_aggregate_'.$mid]);
		}

		// Add dimensions
		foreach ($params['dimensions'] as $did)
		{
            $funct = null;
            if(isset($params['dimension_fun_'.$did]))
                $funct = $params['dimension_fun_'.$did];
                
			$query->addDimension($params['dimension_name_'.$did], $params['dimension_sort_'.$did], $funct);
		}

		// Add filters
		foreach ($params['filters'] as $fid)
		{
			$query->addFilter($params['filter_blogic_'.$fid], $params['filter_field_'.$fid], 
							  $params['filter_operator_'.$fid], $params['filter_condition_'.$fid]);
		}
        
        $format = null;
        if(isset($params['format']))
            $format = $params['format'];
        
        switch($format)
        {
            case "tabular":
                $ret = $cube->getTabularData($query);
                break;
            default:
                $ret = $cube->getData($query);
                break;
        }

        if(!isset($params['display_graph']))
		    $this->sendOutputJson($ret);
            
		return $ret;
	}
    
    /**
     * Get the data in cube (e.g. Dimensions, Etc)
     */
    public function getCubeData($params)
    {
        $ret = array();
        
        // Get cube
        $cube = $this->getCube($params);
        if(!$cube)
            return $this->sendOutputJson(array("error"=>"Cube type must be set"));

        $dimensions = $cube->getDimensions();
        $measures = $cube->getMeasures();
        
        // Dimensions
        foreach($dimensions as $dim)
            $ret['dimensions'][] = array("id" => $dim->id, "name" => $dim->name, "type" => $dim->type);
            
        foreach($measures as $meas)
            $ret['measures'][] = array("id" => $meas->id, "name" => $meas->name);
        
        //$ret['dimensions'][]
        return $this->sendOutputJson($ret);
    }
    
    /**
     * Get the graph view of olap
     */
    public function processGraphDisplay($params)
    {        
              
        $measure = $params['measure_name_0'];        
        
        // Get cube
        $cubeData = $this->queryCubeJson($params);
        
        if(sizeof($cubeData)==0)
            $ret = array("message" => "No data is available");
        else
        {
            // Process chart
            $chart = new CChart($params['chart_type']);
            $checkDir = is_dir(dirname(__FILE__).'/..' . $chart->basePath);            
            if(!$checkDir);
                $chart->basePath = "/lib/aereus.lib.php/fcharts";
            
            $cdata = $chart->creatXmlData("", "", "", NULL, NULL, "0");
            $cdata->setAttribute("showValues", "0");
            $cdata->setAttribute("showNames", "1");
            $cdata->setAttribute("showAnchors", "1");
            $cdata->setAttribute("numberPrefix", "");
            $cdata->setAttribute("decimalPrecision", "2");
            $cdata->setAttribute("divLineDecimalPrecision", "2");
            $cdata->setAttribute("limitsDecimalPrecision", "2");
            $cdata->setAttribute("bgAlpha", "0");
            
            if(sizeof($params['dimensions']) == 2) // With Groupings
            {
                foreach($cubeData as $category=>$categoryData)
                {
                    $dimCat[] = $category; // Build Categories (Y-Axis)
                    foreach($categoryData as $set=>$setData)
                    {
                        $setValue = $setData[$measure];                        
                        $dimSet[$set][$category][] = $setValue; // setData [Groupings][Y-Axis][] = X-Axis
                    }
                }
                
                foreach($dimCat as $key=>$category) 
                    $cdata->addCategory($category); // (Y-Axis)
                
                foreach($dimSet as $set=>$catData)
                {
                    $sdata = $cdata->addSet($set); // Groupings
                    
                    // Loop thru categories to get the value of datasets
                    // and also we can set a blank value on Y-Axis that dont have data
                    foreach($dimCat as $key=>$category)
                    {
                        $setData = $dimSet[$set][$category];
                        $setCount = sizeof($setData);
                        
                        // if count is 0, it means the data for Y-Axis is not available
                        // it is important to set blank entry, 
                        // so the next addAntry (X-Axis) will be set to the next Y-Axis
                        if($setCount == 0)
                            $sdata->addEntry("");
                        else
                        {
                            foreach($setData as $setValue)
                                $sdata->addEntry($setValue); // X-Axis
                        }
                    }
                }
            }
            else if(sizeof($params['dimensions']) == 1)
            {
                foreach($cubeData as $category=>$categoryData)
                {
					$color = ($params['chart_type'] == "Area2D") ? "79A29A" : null;
                    $cdata->addEntry($categoryData[$measure], $category, $color);
                    $cdata->addCategory($category);
                }
            }
            else
            {                
                $cdata->addEntry($cubeData[$measure], $measure);
                $cdata->addCategory($measure);
            }
            
            $ret = array("chart" => $chart->getChart($params['chart_width'], $params['chart_height']));
        }
            
        return $this->sendOutputJson($ret);
    }
    
    /**
     * Writes data in olap dataware
     */
    public function writeData($params)
    {
        // Prepare Measure
        $measParts = explode(" ", $params["measures"]);
        foreach($measParts as $measParams)
        {
            $mParts = explode("=", $measParams);
            $measures[$mParts[0]] = $mParts[1];
        }
        
        // Prepare Data
        $dataParts = explode(" ", $params["data"]);
        foreach($dataParts as $dataParams)
        {
            $dParts = explode("=", $dataParams);
            $data[$dParts[0]] = $dParts[1];
        }
        
        // Prepare increment
        if($params["increment"])
            $increment = true;
        else
            $increment = false;
        
        // Get cube
        $cube = $this->getCube($params);
        $cube->writeData($measures, $data, $increment);
        
        $ret = $cube->id;
        return $this->sendOutputJson($ret);
    }
}
