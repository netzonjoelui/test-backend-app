<?php
/**
 * Implementation of OLAP cube that gets data by querying AntObjects
 *
 * This class is an olap cube interface that queries objects in ANT in real time
 *
 * @category  Olap_Cube
 * @package   Adhock
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Implementation of OLAP cube that gets data by querying AntObjects
 */
class Olap_Cube_Adhock extends Olap_Cube
{
	/**
     * The object type we are querying
     *
     * @var string
	 */
    protected $objType = null;
    protected $dbh;
    protected $user;
    
	var $mainObject;
    
	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param string $objType Unique name of the object type we are reporting on
	 */
	public function __construct($dbh, $objType, $user)
	{
		$this->objType = $objType;
        
		parent::__construct($dbh);        
        $this->dbh = $dbh;
        $this->user = $user;
        $this->objType = $objType;
        $this->mainObject = new CAntObject($this->dbh, $this->objType);        
        $this->getObjectFields(); // loop thru valid object fields and put in measures.
	}
    
    /**
     * Get the type of the field
     *
     * @param string $fieldName Field name of the object
     * @return Field Type
     */
    private function getFieldType($fieldName)
    {
        $ret = false;
        if(!empty($fieldName))
        {
            $fieldParts = $this->mainObject->getFieldType($fieldName);            
            $ret = $fieldParts['type'];
        }
        
        return $ret;
    }
    
    /**
     * Formats the dimension key
     *
     * @param array $dim Dimention array
     * @param string $key The current key stored to get the value for
     * @param string $format Usually used by time dimensions to group parts
     * @return string The string of the orignial named value for the key
     */
    private function formatDimensionKey($dim, $key, $format=null)
    {
        if (!$dim)
            return false;
        
        if ($format!=null)
        {
            switch($dim->type)
            {
                case "time":
                case "timestamp":
                case "date":
                    $time = @strtotime($key);

                    if ($time != -1)
                    {
                        // Utilize PHP date formats
                        $key = date($format, $time);

                        // Calculate quarter
                        $key = str_replace("Q", "Q".(floor((date("m", $time) - 1) / 3) + 1), $key);
                    }
                    break;
                default:
                    break;                    
            }
        }
        
        $ret = $key;
        return $ret;
    }
    
    /**
     * Check to see if a measure already exists, and optionally add it
     *
     * @param string $name Then unique name of this measure
     * @param bool $createIfMissing If set to true (default) then create the measure if missing
     * @return Olap_Cube_Measure
     */
    public function getDimension($name, $type="", $createIfMissing=true)
    {        
        for ($i = 0; $i < count($this->dimensions); $i++)
        {
            if ($this->dimensions[$i]->name == $name)
                return $this->dimensions[$i];
        }

        // Dimension was not found, let's create it
        if ($createIfMissing)
            return $this->setDimension($name, $type);
        else
            return null;
    }
    
    /**
     * Add a new dimension to this cube
     *
     * @param string $name Then unique name of this dimension
     * @return Olap_Cube_Measure on success, false on failure
     */
    private function setDimension($name, $type="")
    {
        $ret = false;
        
        // Try to get field type in main object if type is not set
        if(empty($type))
            $type = $this->getFieldType($name);
        
        // Try to determine type if not set
        if (empty($type))
        {
            if ($name == "time" || substr($name, -3)=="_ts")
                $type = "time";
            else if (substr($name, -2)=="_i" || substr($name, -4)=="_num")
                $type = "numeric";
            else
                $type = "string";
        }
        
        // Set the column type
        switch ($type)
        {
            case 'numeric':
                $coltype = "numeric";
                break;
            case 'timestamp':
            case 'time':
                $coltype = "timestamp without time zone";
                break;
            case 'string':
            default:
                $coltype = "integer";
                break;
        }
        
        $dim = new Olap_Cube_Dimension();
        $dim->id = sizeof($this->dimensions)+1;
        $dim->name = $name;
        $dim->type = $type;

        $this->dimensions[] = $dim;
        $ret = $dim;        
        return $ret;
    }
    
    /**
     * Check to see if a measure already exists, and optionally add it
     *
     * @param string $name Then unique name of this measure
     * @param bool $createIfMissing If set to true (default) then create the measure if missing
     * @return Olap_Cube_Measure
     */
    public function getMeasure($name, $createIfMissing=true)
    {
        for ($i = 0; $i < count($this->measures); $i++)
        {
            if ($this->measures[$i]->name == $name)
                return $this->measures[$i];
        }

        // Measure was not found, let's create it
        if ($createIfMissing)
            return $this->setMeasure($name);
        else
            return null;
    }
    
    /**
     * Add a new measure to this cube
     *
     * @param string $name Then unique name of this measure
     * @return Olap_Cube_Measure on success, false on failure
     */
    private function setMeasure($name)
    {        
        $ret = false;
        
        $meas = new Olap_Cube_Measure();
        $meas->id = sizeof($this->measures)+1;
        $meas->name = $name;

        $this->measures[] = $meas;
        $ret = $meas;
        return $ret;
    }
     
     /**
     * Scans the object fields for 'number', 'integer' or 'real' to be added as additional measure
     */
     public function getObjectFields()
     {         
        foreach($this->mainObject->fields->fields as $key=>$fields)
        {
            $keyExist = false;
            $type = $fields['type'];
            $this->getDimension($key, $type);
            //echo "$key => $type <hr />";            
            
            switch($type)
            {
                case "number":
                case "integer":
                case "real":
                    $this->getMeasure($key);                    
                    break;
            }
        }
     }

     /**
     * Checks the dimension type     
     * 
     * @param string $dimType Dimension Type
     */
    public function checkDimType($dimType)
    {
        switch($dimType)
        {
            case "fkey":
            case "multi_fkey":
            case "object":
                return true;
            default:
                return false;
                break;
        }
    }
    
    /**
     * Checks/Gets the Object Value
     * or Gets the Foreign Key Value
     * 
     * @param array $foreignData    Contains Foreign Key Values
     * @param string $field         Field name of dimension
     * @param string $value         Value of dimension
     * 
     * @return string   Object Value
     */
    public function getObjectValue($foreignData, $field, $value)
    {
        if(isset($foreignData[$field]) && $foreignData[$field] && !empty($value))
            $ret = $foreignData[$field][$value];
        else
            $ret = $value;
        
        return $ret;
    }
    
    /**
     * Query data
     *
     * $data = $cube->getData(array("count"), array("time.year.quarter"), array("measures.visits"));
     *
     * // IDEA: Blow is a possible replacement idea for a Olap_Cube_Dataset object that has dimensions & measures properties
     * and the dimensions will in turn reference a Dataset
     * ->getDimByName('/index.php')->getDimByName('us')->getMeasure('hits'); 
     *
     * For now we will just have it return data
     *
     * @param Olap_Cube_Query $ocqQuery Query object used for pulling data
     *
     * @return array Muti-dimensional array in the following format: ["dim1_value"]["meas_name"] or ["dim1_value"]["dim2_value"]["meas_name"]
     */
    public function getData($ocqQuery)
    {
        $measures = $ocqQuery->measures;
        $dimensions = $ocqQuery->dimensions;
        $filters = $ocqQuery->filters;
        $pullMinFields = $ocqQuery->pullMinFields;
        
        $ret = array();
        $objList = new CAntObjectList($this->dbh, $this->objType, $this->user);
        $objList->setIndex("db");
        $obj = $objList->obj;
        
        // Set pullMinFields and field sort
        foreach($pullMinFields as $key=>$fields)
        {
            // Do not process if field is count since its not part of the table field
            if($key==0 && $fields["name"]=="count")
                continue;
                
            $objList->pullMinFields[] = $fields["name"];
            
            // Facet Field for faster query
            $objList->addFacetField($fields["name"]);
            
            if(!empty($fields["sort"]))
                $objList->addOrderBy($obj->object_table . "." . $fields["name"], $fields["sort"]);
        }
        
        // add filters to object list
        foreach ($filters as $condition)
            $objList->addCondition($condition['blogic'], $condition['field'], $condition['operator'], $condition['condition']);
        
        $foreignData = $objList->getMinFieldsForeignValue();
        
        // Pull objects and set values
        $this->values = array();
        $numRecords = 1000;
        $offset = 0;
        $objList->getObjects($offset, $numRecords);
        $num = $objList->getNumObjects();
        $total = $objList->getTotalNumObjects();
        
        // Loop through the results and summarize them
        // Should we be doing this in the database instead?
        // Right now I'd rather put the load on the client than the database, that may change
        // as the dataset grows though        
        
        for ($i = 0; $i < $num; $i++)
        {
            //$row = $objList->getObject($i);
            $row = $objList->getObjectMin($i);
            
            foreach ($measures as $mname=>$magg)
            {
                $meas = $this->getMeasure($mname);

                // If no dimensions then just populate measures
                if (!count($dimensions) && $meas)
                {
                    //$measValue = $row->getValue($mname);
                    $measValue = $this->getObjectValue($foreignData, $mname, $row[$mname]);
                    
                    if(!isset($ret[$mname]))
                        $ret[$mname] = 0;
                        
                    $ret[$mname] = $this->aggregateValue($measValue, $ret[$mname], $magg);
                }
                else if ($meas)
                {
                    // dimension 1
                    $dimName1 = $dimensions[0]['name'];                    
                    $dim1 = $this->getDimension($dimName1);
                    
                    // Check for dim type before getting the row value                    
                    /*$dimType = $row->getFieldType($dimName1);
                    $getForeign = $this->checkDimType($dimType['type']);                    
                    $dimValue1 = $row->getValue($dimName1, $getForeign);*/                    
                    $dimValue1 =  $this->getObjectValue($foreignData, $dimName1, $row[$dimName1]);
                    
                    $d1val = $this->formatDimensionKey($dim1, $dimValue1, $dimensions[0]['fun']);
                    
                    if(!isset($ret[$d1val]) || !is_array($ret[$d1val]))
                    {
                        $ret[$d1val] = array();
                        $ret[$d1val]['count'] = 0;
                    }                        
                        
                    if (count($dimensions) == 2)
                    {
                        // dimension 2
                        $dimName2 = $dimensions[1]['name'];
                        $dim2 = $this->getDimension($dimName2);
                        
                        // Check for dim type before getting the row value
                        /*$dimType = $row->getFieldType($dimName2);
                        $getForeign = $this->checkDimType($dimType['type']);
                        $dimValue2 = $row->getValue($dimName2, $getForeign);*/
                        
                        $dimValue2 = $this->getObjectValue($foreignData, $dimName2, $row[$dimName2]);
                        
                        $d2val = $this->formatDimensionKey($dim2, $dimValue2, $dimensions[1]['fun']);
                        
                        if(!isset($ret[$d1val][$d2val]) || !is_array($ret[$d1val][$d2val]))
                        {
                            $ret[$d1val][$d2val] = array();
                            $ret[$d1val][$d2val]['count'] = 0;
                            unset($ret[$d1val]['count']);
                        }
                        
                        $ret[$d1val][$d2val]['count']++;
                        
                        if($mname=="id" || $mname=="revision")
                            $measValue = $row[$mname];
                        else
                        {
                            $measValue = $this->getObjectValue($foreignData, $mname, $row[$mname]);
                            $this->aggregateValue($measValue, $ret[$d1val][$d2val][$mname], $magg);
                        }
                            
                        $ret[$d1val][$d2val][$mname] = $measValue;
                    }
                    else if (count($dimensions) == 1)
                    {                        
                        $ret[$d1val]['count']++;
                        //$measValue = $row->getValue($mname);
                        $measValue = $this->getObjectValue($foreignData, $mname, $row[$mname]);
                        $ret[$d1val][$mname] = $this->aggregateValue($measValue, $ret[$d1val][$mname], $magg);
                    }                        
                }
            }
            
            // If result set is larger than 1000
            $offset++;
            //echo "$i == $num && $offset < $total<hr/>";
            
            if ($i == ($num-1) && $offset < $total)
            {                
                $objList->getObjects($offset, $numRecords); // Get next page
                $num = $objList->getNumObjects();
                $i = -1;
            }
        }
        
        // Now handle avg
        foreach ($measures as $mname=>$magg)
        {            
            if ($magg != "avg")
                continue;  // skip

            $meas = $this->getMeasure($mname);
            
            // If no dimensions then just populate measures
            if (!count($dimensions) && $meas)
            {
                $ret["count"] = $i;
                $ret[$mname] = $ret[$mname] / $ret["count"];
            }
            else if ($meas)
            {
                if (count($dimensions) == 2)
                {
                    foreach ($ret as $dim1name=>$dim1)
                    {
                        foreach ($dim1 as $dim2name=>$dim2)
                        {
                            $ret[$dim1name][$dim2name][$mname] = $dim2[$mname] / $dim2["count"];
                        }
                    }
                }
                else if (count($dimensions) == 1)
                {
                    foreach ($ret as $dim1name=>$dim1)
                    {                        
                        $ret[$dim1name][$mname] = $dim1[$mname] / $dim1["count"];
                    }
                }
            }
        }

        return $ret;
        
    }

    /**
     * Query data and send results in a tabular format
     *
     * @return array sing-dimensional array in the following format: array("colname"=>"colval)
     */
    public function getTabularData($ocqQuery)
    {        
        $dimensions = $ocqQuery->dimensions;
        $filters = $ocqQuery->filters;
        $pullMinFields = $ocqQuery->pullMinFields;
        
        // count is always included
        if (!isset($measures['count']))
            $measures['count'] = "sum";

        $ret = array();
        $objList = new CAntObjectList($this->dbh, $this->objType, $this->user);        
        $obj = $objList->obj;
        
        // Set pullMinFields and field sort
        foreach($pullMinFields as $fields)
        {
            $objList->pullMinFields[] = $fields["name"];
            
            if(!empty($fields["sort"]))
                $objList->addOrderBy($obj->object_table . "." . $fields["name"], $fields["sort"]);
        }
        
        // add filters to object list
        foreach ($filters as $condition)
            $objList->addCondition($condition['blogic'], $condition['field'], $condition['operator'], $condition['condition']);
            
        // Pull objects and set values
        $this->values = array();
        $objList->getObjects(0, 1000);
        $num = $objList->getNumObjects();
        $total = $objList->getTotalNumObjects();                
        $offset = 0;
        
        // Loop through the results and summarize them
        // Should we be doing this in the database instead?
        // Right now I'd rather put the load on the client than the database, that may change
        // as the dataset grows though
        for ($i = 0; $i < $num; $i++)
        {
            $row = $objList->getObjectMin($i);
            $retRow = array();

            foreach ($dimensions as $dim)
            {
                // name, sort, fun
                $dimName = $dim['name'];
                $dimData = $this->getDimension($dimName);                
                $dval = $this->formatDimensionKey($dimData, $row[$dimName], $dim['fun']);
                $retRow[$dimData->name] = $dval;
            }

            $ret[] = $retRow;
            
            // If result set is larger than 1000
            $offset++;
            if ($i == $num && ($num+$offset) < $total)
            {
                $objList->getObjects($offset, 1000); // Get next page
                $num = $objList->getNumObjects();
                $total = $objList->getTotalNumObjects();
            }
        }

        return $ret;
	}
}
