<?php
/**
 * Aereus Object Report
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * function like saving report dimensions, measures, and filters
 *
 * @category  CAntObject
 * @package   CAntObject_Report
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */  
 
 /**
 * Object extensions for managing reports
 */
class CAntObject_Report extends CAntObject
{
    public $tableType;
    public $id = null;
    private $reportFilter = array();
    private $reportDim = array();
    private $reportMeasure = array();
    
    /**
     * Initialize CAntObject with correct type
     *
     * @param CDatabase $dbh        An active handle to a database connection
     * @param int $rid              The report id we are editing - this is optional
     * @param AntUser $user         Optional current user
     */
    function __construct($dbh, $rid=null, $user=null)
    {
        parent::__construct($dbh, "report", $rid, $user);
    }
    
    /**
     * Function used for derrived classes to hook save event
     *
     * This is called after CAntObject base saves all properties
     */
    protected function saved()
    {
        if(empty($this->tableType))
            $this->tableType = $this->getValue("table_type");
        
        $this->saveReportFilter();
        $this->saveReportTableDims();
        $this->saveReportTableMeasures();
    }
    
    /**
     * Get the report details
     *      
     */
    public function getDetails()
    {
        $dbh = $this->dbh;
        $ret = array();
        if($this->id)
        {
            $query = "select * from reports where id = " . $dbh->EscapeNumber($this->id);
            $result = $dbh->Query($query);
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                
                // This will fix the data of old reports
                if(empty($row['custom_report']))
                {
                    if(empty($row['chart_dim1']))
                        $row['chart_dim1'] = $row['dim_one_fld'];                    
                    if(empty($row['chart_dim1_grp']))
                        $row['chart_dim1_grp'] = $row['dim_one_grp'];                    
                    if(empty($row['chart_dim2']))
                        $row['chart_dim2'] = $row['dim_two_fld'];                    
                    if(empty($row['chart_dim2_grp']))
                        $row['chart_dim2_grp'] = $row['dim_two_grp'];                    
                    if(empty($row['chart_measure']))
                        $row['chart_measure'] = $row['measure_one_fld'];                    
                    if(empty($row['chart_measure_agg']))
                        $row['chart_measure_agg'] = $row['measure_one_agg'];
                }
                
                $ret = $row;
            }                
            
            $dbh->FreeResults($result);
        }
        
        return $ret;
    }
    
    /**
     * Get the report filters
     *      
     */
    public function getFilters()
    {
        $dbh = $this->dbh;
        $ret = array();
        if($this->id)
        {
            // Get Report Filters
            $query = "select * from report_filters where report_id = " . $dbh->EscapeNumber($this->id) . " order by id asc";
            $result = $dbh->Query($query);
            
            $num = $dbh->GetNumberRows($result);            
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetNextRow($result, $i);
                $ret[] = array("id" => $row['id'], "blogic" => $row['blogic'], "fieldName" => $row['field_name'], "operator" => $row['operator'], "condValue" => $row['value']);
            }
            
            $dbh->FreeResults($result);
        }
        
        return $ret;
    }
    
    /**
     * Get the report dimensions
     *      
     */
    public function getDimensions()
    {
        $dbh = $this->dbh;
        $ret = array();
        if($this->id)
        {
            $query = "select * from report_table_dims where report_id = " . $dbh->EscapeNumber($this->id) . " order by id asc";
            $result = $dbh->Query($query);
            
            $num = $dbh->GetNumberRows($result);            
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetNextRow($result, $i);
                $ret[] = array("id" => $row['id'], "table_type" => $row['table_type'], "name" => $row['name'], "sort" => $row['sort'], "format" => $row['format'], "f_column" => $row['f_column'], "f_row" => $row['f_row']);
            }
            
            $dbh->FreeResults($result);
        }
        
        return $ret;
    }
    
    public function getMeasures()
    {
        $dbh = $this->dbh;
        $ret = array();
        if($this->id > 0)
        {
            $query = "select * from report_table_measures where report_id = " . $dbh->EscapeNumber($this->id) . " order by id asc";
            $result = $dbh->Query($query);
            
            $num = $dbh->GetNumberRows($result);            
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetNextRow($result, $i);
                $ret[] = array("id" => $row['id'], "table_type" => $row['table_type'], "name" => $row['name'], "aggregate" => $row['aggregate']);
            }
            
            $dbh->FreeResults($result);
        }
        
        return $ret;
    }
    
    /**
    * Updates the Report Filters Table
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function saveReportFilter()
    {
        $dbh = $this->dbh;
        $filterId = array();
        $deleteFilter = null;
        
        if(sizeof($this->reportFilter) > 0)
        {
            foreach ($this->reportFilter as $filter)
            {
                if($filter->id)
                    $filterId[] = $filter->id;
            }
        }
        
        if(sizeof($filterId) > 0)
            $deleteFilter = "and id not in (" . implode(",", $filterId) . ")";
        
        $query = "delete from report_filters where report_id = '" . $this->id . "' $deleteFilter;";
        $dbh->Query($query);
        
        if(sizeof($this->reportFilter) == 0)
            return;
        
        foreach ($this->reportFilter as $filter)
        {            
            if($filter->id)
            {
                $query = "update report_filters set
                            blogic = '" . $dbh->Escape($filter->blogic) . "',
                            field_name = '" . $dbh->Escape($filter->fieldName) . "',
                            operator = '" . $dbh->Escape($filter->operator) . "',
                            value = '" . $dbh->Escape($filter->condValue) . "'
                            where id = '" . $filter->id . "' and report_id = " . $dbh->EscapeNumber($this->id);
            }
            else
            {                
                $query = "insert into report_filters (report_id, blogic, field_name, operator, value)
                            values (" . $dbh->EscapeNumber($this->id) . ",
                            '" . $dbh->Escape($filter->blogic) . "',
                            '" . $dbh->Escape($filter->fieldName) . "',
                            '" . $dbh->Escape($filter->operator) . "',
                            '" . $dbh->Escape($filter->condValue) . "');";
            }            
            $dbh->Query($query);
        }
    }
    
    /**
    * Updates the Report Table Dims
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function saveReportTableDims()
    {
        $dbh = $this->dbh;
        $dimensionId = array();
        $deleteDimension = null;
        
        if(sizeof($this->reportDim) > 0)
        {
            foreach ($this->reportDim as $dimension)
            {
                if($dimension->id)
                    $dimensionId[] = $dimension->id;
            }
            
            if(sizeof($dimensionId) > 0)
            {
                $deleteDimension = "and id not in (" . implode(",", $dimensionId) . ")";
            }
        }
        
        $query = "delete from report_table_dims where report_id = '" . $this->id . "' $deleteDimension;";
        $dbh->Query($query);
        
        if(sizeof($this->reportDim) == 0)
            return;
        
        foreach ($this->reportDim as $dimension)
        {
            
            if(empty($dimension->f_column))
                $dimension->f_column = "f";
            
            if(empty($dimension->f_row))
                $dimension->f_row = "f";
            
            if($dimension->id)
            {
                $query = "update report_table_dims set
                            table_type = '" . $dbh->Escape($this->tableType) . "',
                            name = '" . $dbh->Escape($dimension->name) . "',
                            sort = '" . $dbh->Escape($dimension->sort) . "',
                            format = '" . $dbh->Escape($dimension->format) . "',
                            f_column = " . $dbh->Escape($dimension->f_column) . ",
                            f_row = " . $dbh->Escape($dimension->f_row) . "
                            where id = '" . $dimension->id . "' and report_id = " . $dbh->EscapeNumber($this->id);
            }
            else
            {
                $query = "insert into report_table_dims (report_id, table_type, name, sort, format, f_column, f_row)
                            values (" . $dbh->EscapeNumber($this->id) . ",
                            '" . $dbh->Escape($this->tableType) . "',
                            '" . $dbh->Escape($dimension->name) . "',
                            '" . $dbh->Escape($dimension->sort) . "',
                            '" . $dbh->Escape($dimension->format) . "',
                            '" . $dbh->Escape($dimension->f_column) . "',
                            '" . $dbh->Escape($dimension->f_row) . "');";
            }
            $dbh->Query($query);
        }
    }
    
    /**
    * Updates the Report Table Measures
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function saveReportTableMeasures()
    {
        $dbh = $this->dbh;
        $measureId = array();
        $deleteMeasure = null;
        
        if(sizeof($this->reportMeasure) > 0)
        {
            foreach ($this->reportMeasure as $measure)
            {
                if($measure->id)
                    $measureId[] = $measure->id;
            }
            
            if(sizeof($measureId) > 0)
                $deleteMeasure = "and id not in (" . implode(",", $measureId) . ")";
        }
        
        
        $query = "delete from report_table_measures where report_id = '" . $this->id . "' $deleteMeasure;";
        $dbh->Query($query);
        
        if(sizeof($this->reportMeasure) == 0)
            return;
        
        foreach ($this->reportMeasure as $measure)
        {
            if($measure->id)
            {
                $query = "update report_table_measures set
                            table_type = '" . $this->tableType . "',
                            name = '" . $dbh->Escape($measure->name) . "',
                            aggregate = '" . $dbh->Escape($measure->aggregate) . "'
                            where id = '" . $measure->id . "' and report_id = " . $dbh->EscapeNumber($this->id);
            }
            else
            {
                $query = "insert into report_table_measures (report_id, table_type, name, aggregate)
                            values (" . $dbh->EscapeNumber($this->id) . ",
                            '" . $dbh->Escape($this->tableType) . "',
                            '" . $dbh->Escape($measure->name) . "',
                            '" . $dbh->Escape($measure->aggregate) . "');";
            }
            $dbh->Query($query);
        }
    }
    
    /**
    * Updates the Report Filters
    */
    public function addReportFilter($blogic, $fieldName, $operator, $condValue, $id=null)
    {
        $filter = new stdClass();
        $filter->id = $id;
        $filter->blogic = $blogic;
        $filter->operator = $operator;
        $filter->fieldName = $fieldName;
        $filter->condValue = $condValue;

        $this->reportFilter[] = $filter;
    }
    
    /**
    * Updates the Report Table Dimensions
    */
    public function addReportDim($name, $sort, $format=null, $f_column=null, $f_row=null, $id=null)
    {
        $dimension = new stdClass();
        $dimension->id = $id;
        $dimension->name = $name;
        $dimension->sort = $sort;
        $dimension->format = $format;        
        $dimension->f_column = $f_column;
        $dimension->f_row = $f_row;        

        $this->reportDim[] = $dimension;
    }
    
    /**
    * Updates the Report Table Dimensions
    */
    public function addReportMeasure($name, $aggregate=null, $id=null)
    {
        $measure = new stdClass();
        $measure->id = $id;
        $measure->name = $name;
        $measure->aggregate = $aggregate;        

        $this->reportMeasure[] = $measure;
    }
}
?>
