<?php
/**
 * Use PGSQL as the local query cache for storing ANT objects
 *
 * Global Variables:
 * $ANTAPI_STORE_PGSQL_HOST = "localhost" - host of pgsql server def to "localhost"
 * $ANTAPI_STORE_PGSQL_DBNAME = "aereus_com" - db name, usualy website domain or app name
 * $ANTAPI_STORE_PGSQL_USER = user used to connect to database
 * $ANTAPI_STORE_PGSQL_PASSWORD = password used to connect to database
 *
 * @category  AntApi
 * @package   ObjectStore_Elastic
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
class AntApi_ObjectStore_Pgsql extends AntApi_ObjectStore
{
    /**
     * Instance of CDatabase
     *
     * @var Object
     */
    var $dbh = NULL;
    
    /**
     * Determines wheter the instance is used for testing
     *
     * @var Boolean
     */
    var $debug = false;
    
    /**
     * Containes the syncec objects
     *
     * @var Array
     */
    var $syncObjects;
    
    /**
     * The last query executed
     *
     * @var String
     */
    var $lastQuery;
    
    /**
     * Database encoding type. default; pgsql
     *
     * @var String
     */
    var $encoding = "pgsql";
    
    /**
     * Full text search condition
     *
     * @var String
     */
    var $conditionText = "";
    
    /**
     * Array of conditions
     *
     * @var String
     */
    var $conditions = array();
    
    /**
     * Array of sort order
     *
     * @var String
     */
    var $orderBy = array();
    
    /**
     * String value to search the tsv_fulltext field
     *
     * @var String
     */
    var $fullTextSearch = null;

    /**
     * Class constructor
     *     
     */                
    function __construct($obj=null) 
    {
        parent::__construct($obj);
        
        $this->createDatabase(); // Check if database name is set or needs to be created
    }
    
    /**
     * Formats the string
     *
     * @param string $text      String to be formatted
     */
    private function Escape($text)
    {
        if ($this->encoding == "UNICODE")
            $text = iconv('utf-8',"utf-8//IGNORE", $text);

        return pg_escape_string($text);
    }
    
    /**
     * Formats the number
     *
     * @param string $numbervalue      Numberic value to be formatted
     */
    private function EscapeNumber($numbervalue)
    {
        if (is_numeric($numbervalue))
            return "'$numbervalue'";
        else
            return 'NULL';
    }
    
    /**
     * Executes a query string
     *
     * @param string $query      Query string to be executed
     */
    private function Query($query)
    {
        if(!$this->dbh)
            return false;
        
        $this->lastQuery = $query;
        pg_set_client_encoding ($this->dbh, $this->encoding);
        $result = pg_query($this->dbh, $query);        
        if(!$result)
        {
            if($this->debug)
            {
                global $ANTAPI_STORE_PGSQL_DBNAME, $ANTAPI_STORE_PGSQL_HOST, $ANTAPI_STORE_PGSQL_USER, $ANTAPI_STORE_PGSQL_PASSWORD;
                echo "<hr/>";
                echo "DbHost: $ANTAPI_STORE_PGSQL_HOST<br />";
                echo "DbName: $ANTAPI_STORE_PGSQL_DBNAME<br />";
                echo "DbUser: $ANTAPI_STORE_PGSQL_USER<br />";
                echo "Query Error: $query";
            }
            return false;
        }
    
        return $result;
    }
    
    /**
     * Gets the number of rows
     *
     * @param Object $result    Result of the query
     */
    private function GetNumberRows($result)
    {
        if ($result)
            return pg_num_rows($result);
    }
    
    /**
     * Gets the result row
     *
     * @param Object $result    Result of the query
     * @param Integer $num      The index of result to be returned
     * 
     * @return Array
     */
    private function GetRow($result, $num = 0)
    {
        $retval = pg_fetch_array($result, $num);
        return $retval;
    }
    
    /**
     * Gets the result row
     *
     * @param Object $result    Result of the query
     * @param Integer $num      The index of result to be returned
     * 
     * @return Associated Array
     */
    private function GetNextRow($result, $num = 0)
    {
        $retval = pg_fetch_assoc($result, $num);
        return $retval;
    }

    /**
     * Gets the result row value
     *
     * @param Object $result    Result of the query
     * @param Integer $num      The index of result to be returned
     * @param String $name      Name of the column
     */
    private function GetRowValue($result, $num = 0, $name=0)
    {
        $row = pg_fetch_array($result, $num);
        return $row[$name];
    }
    
    /**
     * Clears the result
     *
     * @param Object $result    Result of the query
     */
    private function FreeResults($result)
    {
        if ($result)
            pg_free_result($result);
    }
    
    /**
     * Checks if the database is existing
     *
     * @param String $databaseName      Database name to be checked
     */
    private function DatabaseExists($databaseName)
    {
        $query = "select * from pg_database where datname='$databaseName'";
        $result = $this->Query($query);
        if ($this->GetNumberRows($result))
            return true;
        else
            return false;
    }
    
    /**
     * Checks if the table is existing
     *
     * @param String $table      Table name to be checked
     * @param String $schema     Database Schema
     */
    private function TableExists($tbl, $schema=null)
    {
        $query = "SELECT tablename FROM pg_tables where tablename='$tbl'";
        if ($schema) $query .= " and schemaname='$schema'";

        if ($this->GetNumberRows($this->Query($query)))
            return true;
        else
            return false;
    }
    
    /**
     * Checks if the Column is existing
     *
     * @param String $tableName      Table name where column name will be checked
     * @param String $columnName     Column name to be checked
     */
    private function ColumnExists($tableName, $columnName)
    {
        $query = "SELECT attname FROM pg_attribute WHERE attrelid = ( SELECT oid FROM pg_class WHERE relname = '$tableName') AND attname = '$columnName'";
        $result = $this->Query($query);
        return $this->GetNumberRows($result);
    }
    
    /**
     * Adds column in the table
     *
     * @param String $tableName      Table name where column name will be added
     * @param String $columnName     Column name to be added
     * @param String $type           Data Type of the column
     */
    private function buildFieldQuery($tableName, $columnName, $type)
    {
        $query = "ALTER TABLE \"$tableName\" ADD COLUMN $columnName $type";
        return $query;
    }
    
    /**
     * Gets the localstore table schema
     *
     * @param String $type      Table name
     */
    private function getObjDef($type)
    {
        $definition = array();
        $definition["fields"] = array();
        $definition["objType"] = $type;
        $query = "select * from information_schema.columns where table_name = '$type'";
        $result = $this->Query($query);
        $num = $this->GetNumberRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $this->GetNextRow($result, $i);
            $columnName = $row["column_name"];
            $subType = null;
            
            if(isset($row["length"]))
                $subType = $row["length"];
            
            $definition["fields"][$columnName] = array("type" => $row["data_type"], "sub_type" => $subType);
        }
        
        return $definition;
    }
    
    /**
     * Open an object from the local store
     *
     * @param string $type The object type name to open
     * @param int $oid The unique id of the object to open
     * @return false if the object was not found and AntApi_Object if found
     */
    public function openObject($type, $oid)
    {
        if(!$this->dbh)
            $this->connect();
            
        $objDef = $this->getObjDef($type);
        $this->clearCondition(); // Make sure to clean the conditions
        
        if($oid > 0 && is_numeric($oid)) // Get object using object id
        {
            $this->addCondition("and", "id", "is_equal", $oid);
            $result = $this->queryObjects($objDef);            
            if(sizeof($result) > 0)
                return $result[0];
        }
        else if($this->ColumnExists($type, "uname")) // check if uname field does exist in table
        {
            $pos = strpos($oid, "uname:");
            if ($pos !== false)
            {
                $parts = explode(":", $oid);
                $this->addCondition("and", "uname", "is_equal", $parts[1]);                
                $result = $this->queryObjects($objDef);
                if(sizeof($result) > 0)
                    return $result[0];
            }
        }
        
        // Return false if the result is empty
        return false;
    }

    /**
     * Store object values in the local store (including an index if needed)
     *
     * @param AntApi_Object The object to be store locally
     * @return bool true on success and false on failure
     */
    public function storeObject($obj)
    {
        $hasId = false;
        $tableName = $obj->obj_type;
        $id = $obj->id;
        $insertField = array();
        $insertValue = array();
        
        $fields = $obj->getFields();

        // Check if table already exists. If not then lets create the table
        $this->createTable($tableName, $fields);
        
        // Delete Existing Row and Create New Row
        $this->removeObject($tableName, $id);
 
        if (is_array($fields) && count($fields))
        {
            foreach ($fields as $field=>$fdef)
            {
                $val = "";

                // always have an id field and value
                if(strtolower($field) == "id")
                    $hasId = true;
                
                switch ($fdef['type'])
                {
                    case 'fkey_multi':
                    case 'object_multi':
                        $multiValue = array();
                        $mVal = $obj->getValue($field);
                        if (is_array($mVal) && count($mVal))
                        {
                            foreach ($mVal as $value)
                                $multiValue[] = $value;
                                
                            $val = implode(", ", $multiValue);
                            $val = "{" . $val . "}";
                            
                            // create additional field entry for *_fval
                            $multiFvalue = array();
                            $mFval = null;
                            
                            if(isset($obj->m_attribFVals[$field]))
                                $mFval = $obj->m_attribFVals[$field];
                            
                            if(is_array($mFval) && count($mVal))
                            {
                                foreach ($mFval as $value)
                                    $multiFvalue[] = $value;
                                    
                                $fval = implode(", ", $multiFvalue);
                                
                                if(is_array($fval))
                                    $fval = implode(", ", $fval);
                                
                                $insertField[] = "{$field}_fval";
                                $insertValue[] = "'{" . $fval . "}'";
                            }
                        }
                        break;
                    case 'date':
                    case 'timestamp':
                        $val = $obj->getValue($field);
                        
                        if ($val)
                        {
                            // Convert to UTC
                            if ($val == "now")
                                $val = gmdate("Ymd\\TG:i:s", time());
                            else
                                $val = gmdate("Ymd\\TG:i:s", strtotime($val));
                        }
                        break;
                    case 'boolean':
                    case 'bool':
                        $val = $obj->getValue($field);
                        if ($val=='t')
                            $val = 'true';
                        else
                            $val = 'false';
                        break;
                    case "fkey":
                    case "object":
                        $val = $obj->getValue($field);
                        $mVal = null;
                        
                        if(isset($obj->m_attribFVals[$field]))
                            $mVal = $obj->m_attribFVals[$field];
                            
                        // create additional field entry for *_fval
                        $insertValue[] = "'$mVal'";
                        $insertField[] = "{$field}_fval";
                        break;
					case "text":
                        $val = $obj->getValue($field);
						if (is_numeric($fdef['subtype']))
						{
							if (strlen($val) > $fdef['subtype'])
								$val = substr($val, 0, $fdef['subtype']);
						}
						break;
                    default:                        
                        $val = $obj->getValue($field);
                        break;
                }
                
                if(!empty($val))
                {
                    $tsvValue[] = $val;
                    $insertField[] = $field;
                    if(!is_numeric($val))
                        $val = "'" . $this->Escape($val) . "'";
                        
                    $insertValue[] = $val;
                }
            }
        }        
        
        if(sizeof($insertField) > 0 && sizeof($insertValue) > 0)
        {
            if(!$hasId)
            {
                $insertField[] = "id";
                $insertValue[] = $id;
            }
            
            if(!empty($id))
            {
                $strField = implode(", ", $insertField);
                $strValue = implode(", ", $insertValue);
                $strTsv = implode(" ", $tsvValue);
                
				$query = "insert into \"$tableName\" ($strField, tsv_fulltext) 
							values($strValue, to_tsvector('english', '" . $this->Escape(strip_tags($strTsv)) ."'))";                
                $res = $this->Query($query);

                
                $this->syncObjects[] = array("status" => 1, "tableName" => $tableName, "objectId" => $id, "object" => $obj->m_values);
                
                return true;
            }
        }
        else
            return false;
    }

    /**
     * Remove an object from the local store
     *
     * @param string $type The object type name to open
     * @param int $oid The unique id of the object to remove
     * @return bool true on success and false on failure
     */
    public function removeObject($type, $oid)
    {
        if(!empty($type) && $oid > 0)
        {
            $query = "delete from \"$type\" where id = '$oid'";
            $result = $this->Query($query);
            return true;
        }
        else
            return false;
    }

    /**
     * Retrieve objects list from the local store/index
     *
     * @param AntApi_Object $objDef The object type object being queried
     * @param int $start The starting offset, defaults to 0
     * @param int $limit The maximum number of objects to retrieve per page/set
     * @return int The number of objects found
     */
    public function queryObjects($objDef, $start=0, $limit=500)
    {        
        $ret = array();
        $sortOrder = null;
        $whereClause = null;
        
        if(is_array($objDef))
            $tableName = $objDef["objType"];
            
        if(is_object($objDef))
            $tableName = $objDef->obj_type;
        
        $queryCondition = $this->processCondition($this->conditions, $objDef);
        
        if(!empty($this->fullTextSearch))
        {
            if(!empty($queryCondition))            
                $queryCondition .= " and ";
                
            $queryCondition .= "tsv_fulltext @@ plainto_tsquery('{$this->fullTextSearch}')";
        }
        
        if(!empty($queryCondition))
            $whereClause = "where $queryCondition";

        if(count($this->orderBy) > 0)
		{
			$orderStr = "";
			foreach ($this->orderBy as $fname=>$dir)
			{
				$orderStr .= ($orderStr) ? "," :  "";
				$orderStr .= $fname . " " . $dir;
			}

			if ($orderStr)
				$sortOrder = " ORDER BY " . $orderStr;
		}
        
        if(!is_numeric($limit) || empty($limit))
            $limit = 500;
            
        if(!is_numeric($start) || empty($start))
            $start = 0;
        
        $query = "select * from \"$tableName\" $whereClause $sortOrder limit $limit offset $start";
        $result = $this->Query($query);
        $numRow = $this->GetNumberRows($result);
        
        for ($i = 0; $i < $numRow; $i++)
        {
            $row = $this->GetRow($result, $i);
            $ret[] = $row;
            
            // if facet array is set, lets add facetcounts
            foreach ($this->facetFields as $name=>$count)
            {
                //echo "<pre>Adding: ".$fldname."</pre>";
                $fieldValue = $row[$name];
                $facetCount = null;
                
                if(isset($this->facetCounts[$name][$fieldValue]))
                    $facetCount = $this->facetCounts[$name][$fieldValue];
                
                if(empty($facetCount))
                    $facetCount = $count;
                else
                    $facetCount++;
                    
                $this->facetCounts[$name][$fieldValue] = $facetCount;
            }
        }
        
        return $ret;
    }
    
    /**
     * Adds condition for query object
     *
     * $param string $logic         "and" "or"
     * $param string $name          filed name
     * $param string $operator      operator
     * $param string $value     value to test for     
     */
    public function addCondition($blogic, $fieldName, $operator, $value)
    {
        $this->conditions[] = array("blogic"=>$blogic, "field"=>$fieldName, "operator"=>$operator, "value"=>$value);
    }
    
    /**
     * Clears the current condition set
     *
     */
    public function clearCondition()
    {
        $this->conditions = array();
    }
    
    /**
     * Process the object conditions
     *
     * $param array  $conditions    Array of conditions to be set
     * $param object $objDef        The object to be used
     * $param bool   $inOrGroup     Determine whether the or blogic will be grouped
     */
    private function processCondition($conditions, $objDef, $inOrGroup=false)
    {
        $condStr = "";
        
        // Define if we will pull from deleted or non-deleted
        if (count($conditions))
        {
            foreach ($conditions as $cond)
            {
                $blogic = $cond['blogic'];
                $fieldName = $cond['field'];
                $operator = $cond['operator'];
                $condValue = $cond['value'];

                if ($fieldName == "f_deleted" && $operator == "is_equal" && $condValue == "t")
                {                    
                }
            }
        }

        if (count($conditions))
        {
            foreach ($conditions as $cond)
            {
                $blogic = $cond['blogic'];
                $fieldName = $cond['field'];
                $operator = $cond['operator'];
                $value = $cond['value'];
                $query = "";
                
                // check if field is valid
                if(is_array($objDef))
                    $field = $objDef["fields"][$fieldName];
                
                if(is_object($objDef))
                    $field = $objDef->getField($fieldName);
                
                if (!$field)
                {
                    // sometimes objects cant get the id field
                    // but id field should always be created in table
                    switch($fieldName)
                    {
                        case "id":
                            $field = array("type" => "number");
                            break;
                        case "uname":
                            $field = array("type" => "string");
                            break;
                        default:
                            continue 2; // Skip non-existant field
                            break;
                    }
                }
                    
                // check if field is an array                
                $fieldType = $field["type"];
                switch ($operator)
                {
                    case 'is_equal':
                        switch ($fieldType)
                        {
                            case 'object_multi':
                            case 'fkey_multi':
								$children = $this->getHeiarchyDown($objDef, $fieldName, $value);
								$tmp_cond_str = "";
								foreach ($children as $child)
								{
									if ($tmp_cond_str) $tmp_cond_str .= " OR ";
									$tmp_cond_str .= " $child = ANY ($fieldName) ";
								}
								if ($tmp_cond_str)
									$query .= "($tmp_cond_str)";
                                break;
                            case 'boolean':
                            case 'bool':
                                if (empty($value) || $value == "f" || $value == "NULL")
                                    $query .= " ($fieldName = 'f' or $fieldName is null)";
                                else
                                    $query .= " $fieldName = 't' ";
                                break;
                            case "smallint":
                            case "integer":
                            case "bigint":
                            case "decimal":
                            case "numeric":
                            case "real":
                            case "object":
                            case "fkey":
                            case "number":
                                if(empty($value))
                                    $query .= "$fieldName IS NULL";
                                else
                                    $query .= "$fieldName = " . $this->EscapeNumber($value);
                                break;
                            default:
                                $query .= "$fieldName = ";
                                if(is_numeric($value))
                                    $query .= $this->EscapeNumber($value);
                                else if(empty($value))
                                    $query = "$fieldName IS NULL";
                                else
                                    $query .= "'" . $this->Escape($value) . "'";
                            break;
                        }
                        break;
                    case 'is_not_equal':
                        switch ($fieldType)
                        {
                            case 'bool':
                            case 'boolean':
                                if (empty($value) || $value == "f" || $value == "NULL")
                                    $query .= " $fieldName = 't' ";
                                else
                                    $query .= " ($fieldName = 'f' or $fieldName is null)";
                                break;
                            case "smallint":
                            case "integer":
                            case "bigint":
                            case "decimal":
                            case "numeric":
                            case "real":
                            case "object":
                            case "fkey":
                            case "number":
                                if(empty($value))
                                    $query = "$fieldName IS NOT NULL";
                                else
                                    $query = "$fieldName <> " . $this->EscapeNumber($value);
                                break;
                            default:
                                $query .= "$fieldName <> ";
                                if(is_numeric($value))
                                    $query .= $this->EscapeNumber($value);
                                else
                                    $query .= "'" . $this->Escape($value) . "'";
                            break;
                        }
                        break;
                    case 'is_greater':
                        switch ($fieldType)
                        {
                            case "smallint":
                            case "integer":
                            case "bigint":
                            case "decimal":
                            case "numeric":
                            case "real":
                            case "object":
                            case "fkey":
                            case "number":
                                $query .= " $fieldName > " . $this->EscapeNumber($value);
                                break;
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                $query .= "  $fieldName > '$value'";
                                break;
                            default:                                
                                break;
                        }
                        break;
                    case 'is_less':
                        switch ($fieldType)
                        {
                            case "smallint":
                            case "integer":
                            case "bigint":
                            case "decimal":
                            case "numeric":
                            case "real":
                            case "object":
                            case "fkey":
                            case "number":
                                $query .= " $fieldName < " . $this->EscapeNumber($value);
                                break;
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                $query .= "  $fieldName < '$value'";
                                break;
                            default:                                
                                break;
                        }
                        break;
                    case 'is_greater_or_equal':
                        switch ($fieldType)
                        {
                            case "smallint":
                            case "integer":
                            case "bigint":
                            case "decimal":
                            case "numeric":
                            case "real":
                            case "object":
                            case "fkey":
                            case "number":
                                $query .= " $fieldName >= " . $this->EscapeNumber($value);
                                break;
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                $query .= "  $fieldName >= '$value'";
                                break;
                            default:                                
                                break;
                        }
                        break;
                    case 'is_less_or_equal':
                        switch ($fieldType)
                        {
                            case "smallint":
                            case "integer":
                            case "bigint":
                            case "decimal":
                            case "numeric":
                            case "real":
                            case "object":
                            case "fkey":
                            case "number":
                                $query .= " $fieldName <= " . $this->EscapeNumber($value);
                                break;
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                $query .= "  $fieldName <= '$value'";
                                break;
                            default:                                
                                break;
                        }
                        break;
                    case 'begins':
                    case 'begins_with':
                        switch ($fieldType)
                        {
                            case 'text':
                            case 'character varying':
                            case 'varchar':
                            case 'character':
                            case 'char':
                                $query .= " lower($fieldName) like '" . strtolower($this->Escape($value)) . "%'";
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'contains':
                        switch ($fieldType)
                        {
                            case 'text':
                            case 'character varying':
                            case 'varchar':
                            case 'character':
                            case 'char':
                                $query .= " lower($fieldName) like '%" . strtolower($this->Escape($value)) . "%'";
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'day_is_equal':
                        switch ($fieldType)
                        {
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                $query .= " extract('day' from $fieldName) = " . $this->EscapeNumber($value);
                                break;
                            default:
                                break;
                        }                        
                        break;
                    case 'month_is_equal':
                        switch ($fieldType)
                        {
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                $query .= " extract('month' from $fieldName) = " . $this->EscapeNumber($value);
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'year_is_equal':
                        switch ($fieldType)
                        {
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                $query .= " extract('year' from $fieldName) = " . $this->EscapeNumber($value);
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'last_x_days':
                        switch ($fieldType)
                        {
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                if (is_numeric($value))
                                    $query .= " $fieldName >= (now() - INTERVAL '" . $this->EscapeNumber($value) . " days')";
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'last_x_weeks':
                        switch ($fieldType)
                        {
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                if (is_numeric($value))
                                    $query .= " $fieldName >= (now() - INTERVAL '" . $this->EscapeNumber($value) . " weeks')";
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'last_x_months':
                        switch ($fieldType)
                        {
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                if (is_numeric($value))
                                    $query .= " $fieldName >= (now() - INTERVAL '" . $this->EscapeNumber($value) . " months')";
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'last_x_years':
                        switch ($fieldType)
                        {
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                if (is_numeric($value))
                                    $query .= " $fieldName >= (now() - INTERVAL '" . $this->EscapeNumber($value) . " years')";
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'next_x_days':
                        switch ($fieldType)
                        {
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                if (is_numeric($value))
                                    $query .= " $fieldName >= now() and $fieldName <= (now() - INTERVAL '" . $this->EscapeNumber($value) . " days')";
                                break;
                            default:
                                break;
                        }
                    case 'next_x_weeks':
                        switch ($fieldType)
                        {
                            case 'date':
                            case 'timestamp':
                                if (is_numeric($value))
                                    $query .= " $fieldName >= now() and $fieldName <= (now() - INTERVAL '" . $this->EscapeNumber($value) . " weeks')";
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'next_x_months':                       
                        switch ($fieldType)
                        {
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                if (is_numeric($value))
                                    $query .= " $fieldName >= now() and $fieldName <= (now() - INTERVAL '" . $this->EscapeNumber($value) . " months')";
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'next_x_years':
                        switch ($fieldType)
                        {
                            case 'timestamp without time zone':
                            case 'timestamp':
                            case 'date':
                                if (is_numeric($value))
                                    $query .= " $fieldName >= now() and $fieldName <= (now() - INTERVAL '" . $this->EscapeNumber($value) . " years')";
                                break;
                            default:
                                break;
                        }
                        break;
                }
                
                // New system to added to group "or" statements
                if ($blogic == "and")
                {
                    if ($query)
                    {
                        if ($condStr) 
                            $condStr .= " ) $blogic ( ";
                        else
                            $condStr .= " ( ";
                        $inOrGroup = true;
                    }
                }
                else if ($condStr && $query) // or
                    $condStr .= " $blogic ";

                $condStr .= $query;
            }

            // Close condtion grouping
            if ($inOrGroup)
                $condStr .= " )";
        }
        return $condStr;
    }

    /**
     * Save a key/value settings pair in local store
     *
     * @param string $key The unique key/name of this value
     * @param string $value The value to store for the given key
     */
    public function putValue($key, $value)
    {
        if(!$this->dbh)
            $this->connect();

        $this->deleteValue($key);        
        
        $query = "insert into system_registry(key_name, key_val) values('$key', '" . $this->Escape($value) . "');";
        $this->Query($query);

        return true;
    }

    /**
     * Get a name/values setting from the local store
     *
     * @param string $key The unique key/name of the value to retrieve
     * @return string The value store for the key/name or false on failure
     */
    public function getValue($key)
    {
        $ret = null;
        
        if(!$this->dbh)
            $this->connect();
            
        $query = "select key_val from system_registry where key_name='$key' AND user_id is NULL";        
        $result = $this->Query($query);
        if ($this->GetNumberRows($result))
            $ret = $this->GetRowValue($result, 0, "key_val");

        return $ret;
    }

    /**
     * Delete a key/value settings pair in local store
     *
     * @param string $key The unique key
     * @return bool false on failure, true on success
     */
    public function deleteValue($key)
    {
        if(!$this->dbh)
            $this->connect();
            
        $query = "delete from system_registry where key_name = '$key'";
        $this->Query($query);
        
        return true;
    }
    
    /**
     * Use PGSQL as a local store for ANT objects - like a searchable cache
     * 
     * @param string $databaseName      The databaseName to be used/connected
     *
     * @return bool true on success, false on failure
     */
    public function connect($databaseName=null)
    {
        global $ANTAPI_STORE_PGSQL_DBNAME, $ANTAPI_STORE_PGSQL_HOST, $ANTAPI_STORE_PGSQL_USER, $ANTAPI_STORE_PGSQL_PASSWORD;
        
        if(defined("ANTAPI_STORE_PGSQL_DBNAME"))
            $pgSqlDbName = ANTAPI_STORE_PGSQL_DBNAME;
        else if(isset($ANTAPI_STORE_PGSQL_DBNAME))
            $pgSqlDbName = $ANTAPI_STORE_PGSQL_DBNAME;
            
        if(defined("ANTAPI_STORE_PGSQL_HOST"))
            $pgSqlHost = ANTAPI_STORE_PGSQL_HOST;
        else if(isset($ANTAPI_STORE_PGSQL_HOST))
            $pgSqlHost = $ANTAPI_STORE_PGSQL_HOST;
            
        if(defined("ANTAPI_STORE_PGSQL_USER"))
            $pgSqlUser = ANTAPI_STORE_PGSQL_USER;
        else if(isset($ANTAPI_STORE_PGSQL_USER))
            $pgSqlUser = $ANTAPI_STORE_PGSQL_USER;
            
        if(defined("ANTAPI_STORE_PGSQL_PASSWORD"))
            $pgSqlPass = ANTAPI_STORE_PGSQL_PASSWORD;
        else if(isset($ANTAPI_STORE_PGSQL_PASSWORD))
            $pgSqlPass = $ANTAPI_STORE_PGSQL_PASSWORD;
        
        if ( !$pgSqlDbName || !$pgSqlHost || !$pgSqlUser || !$pgSqlPass)
        {
            $ret = array("status" => -1, "message" => "Database info is not complete.<br /> 
                                        DB Host: $pgSqlHost<br /> 
                                        DB Name: $pgSqlDbName<br /> 
                                        DB User: $pgSqlUser<br /> 
                                        DB Password Length: " . strlen($pgSqlPass));
            return $ret;
        }
        
        if($this->dbh)
        {
            $ret = array("status" => 1, "message" => "pgSql Connected.");
            return $ret;
        }
            
        
        if(empty($databaseName))
            $databaseName = $pgSqlDbName;
        
        $this->dbh = @pg_connect("host=".$pgSqlHost." 
                                      dbname=".$pgSqlDbName." 
                                      user=".$pgSqlUser." 
                                      port=5432 
                                      password=".$pgSqlPass);
                                     
        if($this->dbh)
            $ret = array("status" => 1, "message" => "pgSql Connected. ");
        else
        {
            if($this->DatabaseExists($databaseName))
                $ret = array("status" => -2, "message" => "Error while trying to connect to the database. ");
            else
                $ret = array("status" => -3, "message" => "Database $databaseName is missing. ");
        }            
        
        return $ret;
    }
    
    /**
     * Disconnects the current PG SQL Connection
     *
     * @return bool true on success, false on failure
    */
    public function disconnect()
    {
        pg_close($this->dbh);
    }
    
    /**
     * Creates the database using $ANTAPI_STORE_PGSQL_DBNAME as database name
     *
     * @return bool true on success, false on failure
    */
    public function createDatabase()
    {
        global $ANTAPI_STORE_PGSQL_DBNAME;
        
        if(defined("ANTAPI_STORE_PGSQL_DBNAME"))
            $pgSqlDbName = ANTAPI_STORE_PGSQL_DBNAME;
        else if(isset($ANTAPI_STORE_PGSQL_DBNAME))
            $pgSqlDbName = $ANTAPI_STORE_PGSQL_DBNAME;
        
        $result = $this->connect("template1");
        
        if($result['status']==1)
        {
            if(empty($pgSqlDbName))
                $ret = array("status" => -4, "message" => "Database name is empty! ");            
            else if($this->DatabaseExists($pgSqlDbName))
            {
                $this->connect();
                $ret = array("status" => 2, "message" => "Database already exist! ");
            }
            else
            {
                $query = "CREATE DATABASE $pgSqlDbName;";                
                $result = $this->Query($query);
                
                if($result)
                {
                    $this->connect();
                    
                    $ret = array("status" => 1, "message" => "Database $pgSqlDbName created! ");
                }
                else
                    $ret = array("status" => -1, "message" => "Error while creating $pgSqlDbName database! ");
            }
            
            // Always make sure system registry table is created/checked along with its fields
            if($this->dbh)
            {
                $customFields[] = array("name" => "key_name", "type" => "text", "subtype" => 256);
                $customFields[] = array("name" => "key_val", "type" => "text");
                $customFields[] = array("name" => "user_id", "type" => "number");
                $this->createTable("system_registry", $customFields);
            }
        }
        else
            $ret = array("status" => -3, "message" => "Error while trying to connect to pgSql! ");
            
        return $ret;
    }
    
    /**
     * Creates database table and fields
     * 
     * @param string $type          Object Type
     * @param array $customFields   The custom fields that will be saved in the table
     *
     * @return bool true on success, false on failure
    */
    public function createTable($tableName, $objectFields=array())
    {
        $ret = array();
        if ($this->TableExists($tableName))
		{
            $ret["table"] = array("status" => 2, "tableName" => $tableName, "message" => "$tableName already exist! ");
		}
        else
        {
            $query = "CREATE TABLE \"$tableName\"();";
            $result = $this->Query($query);
            if($result)
                $ret["table"] = array("status" => 1, "tableName" => $tableName, "message" => "$tableName successfully created! ");
        }
        
        // Always try to create id, uname, tsv_fulltext field
        $ret["column"][] = $this->createColumn($tableName, "id", "bigint");
        $ret["column"][] = $this->createColumn($tableName, "uname", "character varying(256)");
        $ret["column"][] = $this->createColumn($tableName, "tsv_fulltext", "tsvector");
        
        foreach($objectFields as $field)
        {
            $fieldsQuery = array();
            $fieldIndex = array();
            $fieldBracket = "";
            $subtype = null;
            
            $columnName = $field['name'];
            $type = $field['type'];
            
            if(isset($field['subtype']))
                $subtype = $field['subtype'];
            
            switch($type)
            {
                case "fkey_multi":
                case "object_multi":
                    $type = "integer[]";
                    $fieldsQuery[] = $this->buildFieldQuery($tableName, "{$columnName}_fval", "text[]");
                    break;
                case "fkey":
                case "object":                    
                    $type = "integer";
                    $fieldsQuery[] = $this->buildFieldQuery($tableName, "{$columnName}_fval", "text");
                    break;
                case "integer":
                    $type = "integer";
                    break;
                case "real":
                case "double precision":
                case "number":
                case "numeric":
                    $type = "numeric";
                    break;
                case "alias":
                    $type = "character varying(16)";
                    break;
                case "text":
                    if(is_numeric($subtype))
                        $type = "character varying($subtype)";
                    break;
                default:
                    break;
            }
            
            $ret["column"][] = $this->createColumn($tableName, $columnName, $type, $fieldsQuery);
        }
        
        return $ret;
    }
    
    /**
     * Creates table column
     *
     * @param string $tableName     The name of the table
     * @param string $columnName    The name of the column
     * @param string $type          The column type
     * @param array $fieldsQuery    Predefiend field sql query
     * 
     * @return bool true on success, false on failure
    */
    public function createColumn($tableName, $columnName, $type, $fieldsQuery=array())
    {
        $ret = array();
        if(!empty($type))
        {
            if($this->ColumnExists($tableName, $columnName))
			{
				$ret = array(
					"status" => 2, 
					"columnName" => $columnName, 
					"message" => "$columnName already exist! "
				);
			}
            else
            {
                if($columnName == "id")
                    $type = "bigint";
                    
                // build the query for the current field
                $fieldsQuery[] = $this->buildFieldQuery($tableName, $columnName, "$type");
                
				// Combine, in some cases a field will require two columns in the db for fkey and fkey_val storage
                $query = implode("; ", $fieldsQuery);
                $result = $this->Query($query);
                if($result)
                {
					$ret = array(
						"status" => 1, 
						"columnName" => $columnName, 
						"message" => "$columnName $type successfully created! "
					);
                    
                    if($columnName == "tsv_fulltext") // Create a tsv index
                    {
						$queryIndex = "CREATE INDEX {$tableName}_tsv_fulltext_idx ON $tableName USING gin (tsv_fulltext) ";
						$queryIndex .= "WHERE tsv_fulltext is not null;";
                        $this->Query($queryIndex);
                    }                    
                }
                else
				{
					$ret = array(
						"status" => -1, 
						"columnName" => $columnName, 
						"message" => "Error when creating $columnName $type! "
					);
				}
            }
        }
		else
		{
			$ret = array(
				"status" => -1, 
				"columnName" => $columnName, 
				"message" => "$columnName type is missing! "
			);
		}
        return $ret;
    }
}
