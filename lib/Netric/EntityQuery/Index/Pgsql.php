<?php
/**
 * PostgreSQL implementation of indexer for querying objects
 *
 * @author		joe, sky.stebnicki@aereus.com
 * @copyright	Copyright (c) 2003-2014 Aereus (http://www.aereus.com)
 */
namespace Netric\EntityQuery\Index;

use Netric\EntityQuery;
use Netric\EntityQuery\Results;
use Netric\EntityQuery\Aggregation;

class Pgsql extends IndexAbstract implements IndexInterface
{
    /**
     * Handle to PostgreSQL database
     * 
     * @var \Netric\Db\Pgsql
     */
    public $dbh = null;
    
    /**
     * Setup this index for the given account
     * 
     * @param \Netric\Account\Account $account
     */
    protected function setUp(\Netric\Account\Account $account)
    {
        $this->dbh = $account->getServiceManager()->get("Db");
    }
    
    /**
	 * Save an object to the index
	 *
     * @param \Netric\Entity\Entity $entity Entity to save
	 * @return bool true on success, false on failure
	 */
	public function save(\Netric\Entity\Entity $entity)
    {
        $def = $entity->getDefinition();

        // Update the full text index if we are not using a custom table
        if ($def->isCustomTable())
            return true;

        $tableName = $def->getTable();
        $tableName .= ($entity->isDeleted()) ? "_del" : "_act";

        // Get indexed text
        $fields = $def->getFields();
        $buf = "";
        foreach ($fields as $field) {
            if ($field->type != "fkey_multi" && $field->type != "object_multi") {
                $val = $entity->getValue($field->name);
                $buf .= strtolower($val . " ");
            }
        }

        $sql = "UPDATE " . $tableName . " SET tsv_fulltext=";
        $sql .= "to_tsvector('english', '".$this->dbh->escape(strip_tags($buf))."') ";
        $sql .= "WHERE id='" . $this->dbh->escape($entity->getId()) . "'";
        return ($this->dbh->query($sql)) ? true : false;
    }
    
    /**
	 * Delete an object from the index
	 *
     * @param string $id Unique id of object to delete
	 * @return bool true on success, false on failure
	 */
	public function delete($id)
    {
        // Nothing need be done because we are currently storing data in pgsql
        return true;
    }
    
    /**
	 * Execute a query and return the results
	 *
     * @param EntityQuery &$query The query to execute
     * @param Results $results Optional results set to use. Otherwise create new.
	 * @return \Netric\EntityQuery\Results
	 */
	protected function queryIndex(EntityQuery $query, Results $results = null)
    {
        $condition_query = "";
        $def = $this->getDefinition($query->getObjType());
        
		// Set default f_deleted condition
		if ($def->getField("f_deleted"))
		{
            $conditions = $query->getWheres();
			
            $fDeletedCondSet = false;
			if (count($conditions))
			{
				foreach ($conditions as $cond)
				{
					if ($cond->fieldName == "f_deleted")
                    {
						$fDeletedCondSet = true;
                    }
				}
			}

			if (!$fDeletedCondSet)
            {
                $query->andWhere("f_deleted")->equals(false);
            }
		}

        // Get table to query
        $objectTable = $def->getTable();
        
        /*
		if (!$def->isCustomTable() && $query->isDeletedQuery())
            $objectTable .= "_del";
		else if (!$def->isCustomTable())
			$objectTable .= "_act";
         * 
         */
        
		// Start constructing query
		$sql = "SET constraint_exclusion = on;";
        // Removed the window "count(*) OVER() because it was insanely slow - joe
        //$sql .= "SELECT *, count(*) OVER() AS full_count FROM ".$objectTable." ";
        $sql .= "SELECT * FROM ".$objectTable." ";

		// Build condition string
        $conditionQuery = "";
		if (count($query->getWheres()))
		{
			$conditionQuery = $this->buildConditionString($query, $def);
			if ($conditionQuery)
				$sql .= "WHERE $conditionQuery";
		}

		// Add order by
		$order_cnd = "";
		if (count($query->getOrderBy()))
		{
            $orderBy = $query->getOrderBy();
			foreach ($orderBy as $sort)
			{
				if ($order_cnd) $order_cnd .= ", ";

                // TODO: check this
				// Replace name field to order by full name with path
				//if ($def->parentField && $def->getField("path"))
					//$order_fld = str_replace($this->obj->fields->listTitle, $this->obj->fields->listTitle."_full", $sortObj->fieldName);

				$order_cnd .= $sort->fieldName;
				$order_cnd .= " " . $sort->direction;
			}
		}
		if ($order_cnd)
			$sql .= " ORDER BY $order_cnd ";

		$sql .= " OFFSET " . $query->getOffset();
		if ($query->getLimit())
			$sql .= " LIMIT " . $query->getLimit();

		// Get fields for this object type (used in decoding multi-valued fields)
		$ofields = $def->getFields();

        // Create results object
        if ($results == null)
            $results = new EntityQuery\Results($query, $this);
        else 
            $results->clearEntities();

        $sqlRes = $this->dbh->query($sql);
        for ($i = 0; $i < $this->dbh->getNumRows($sqlRes); $i++)
        {
            $row = $this->dbh->getRow($sqlRes, $i);
            $id = $row["id"];

            // Window function no longer used to get count because of performance issues - joe
            // 
            // Set total num of returned objects from the window function count(*) OVER() above in the
            // query. Not sure if this more performant than running two separate queries or not.
            //if ($i == 0)
                //$results->setTotalNum($row['full_count']);

            // Decode multival fields into arrays of values
            foreach ($ofields as $fname=>$fdef)
            {
                if ($fdef->type == "fkey_multi" || $fdef->type == "object_multi")
                {
                    if (isset($row[$fname]))
                    {
                        $dec = json_decode($row[$fname]);
                        if ($dec !== false)
                            $row[$fname] = $dec;
                    }
                }
                
                if ($fdef->type == "fkey" || $fdef->type == "object" 
                        || $fdef->type == "fkey_multi" || $fdef->type == "object_multi")
                {
                    if (isset($row[$fname . "_fval"]))
                    {
                        $dec = json_decode($row[$fname . "_fval"], true);
                        if ($dec !== false)
                            $row[$fname . "_fval"] = $dec;
                    }
                }
            }

            // Set and add entity
            $ent = $this->entityFactory->create($def->getObjType());
            $ent->fromArray($row);
			$ent->resetIsDirty();
            $results->addEntity($ent);
        }

        // Log error
        if ($sqlRes === false)
        {
            $log = $this->account->getServiceManager()->get("Log");
            $log->error("Failed EntityQuery: " . $this->dbh->getLastError() . " | $sql");
        }
        
        // Get total num
        // ----------------------------------------
        $sqlCnt = "SET constraint_exclusion = on;SELECT count(*) as cnt FROM ".$objectTable." ";
        if ($conditionQuery)
            $sqlCnt .= "WHERE " . $conditionQuery;
        $sqlRes = $this->dbh->query($sqlCnt);
        if ($sqlRes)
            $results->setTotalNum($this->dbh->getValue($sqlRes, 0, "cnt"));
           
        // Get aggregations
        // ----------------------------------------
        if ($query->hasAggregations())
        {
            $aggregations = $query->getAggregations();
            foreach ($aggregations as $name=>$agg)
            {
                $this->queryAggregation($agg, $results, $objectTable, $conditionQuery);
            }
        }
        
        // Get facets
        // ----------------------------------------
        /*
        if (is_array($this->facetFields) && count($this->facetFields))
        {
            foreach ($this->facetFields as $fldname=>$fldcnt)
            {
                $query = "select distinct($fldname), count($fldname) as cnt from ".$objectTable." where id is not null ";
                if ($condition_query)
                    $query .= " and ($condition_query) ";
                $query .= " GROUP BY $fldname";
                $result = $this->dbh->query($query);
                $num = $this->dbh->getNumRows($result);
                for ($j = 0; $j < $num; $j++)
                {
                    $row = $this->dbh->getRow($result, $j);

                    if(!isset($this->objList->facetCounts[$fldname]))
                        $this->objList->facetCounts[$fldname] = array();
                    else if(!is_array($this->objList->facetCounts[$fldname]))
                        $this->objList->facetCounts[$fldname] = array();

                    $this->objList->facetCounts[$fldname][$row[$fldname]] = $row['cnt'];
                }
            }
        }
         */

        // Get aggregates
        // ----------------------------------------
        /*
        if (is_array($this->aggregateFields) && count($this->aggregateFields))
        {
            foreach ($this->aggregateFields as $fldname=>$type)
            {
                switch ($type)
                {
                case 'avg':
                    $aggfunct = "avg";
                    break;
                case 'sum':
                default:
                    $aggfunct = "sum";
                }

                $query = "select $aggfunct($fldname) as cnt from ".$objectTable." where id is not null ";
                if ($condition_query)
                    $query .= " and ($condition_query) ";
                $result = $this->dbh->query($query);
                $num = $this->dbh->getNumRows($result);
                for ($j = 0; $j < $num; $j++)
                {
                    $row = $this->dbh->getRow($result, $j);

                    if(!isset($this->objList->aggregateCounts[$fldname]))
                        $this->objList->aggregateCounts[$fldname] = array();
                    else if(!is_array($this->objList->aggregateCounts[$fldname]))
                        $this->objList->aggregateCounts[$fldname] = array();

                    $this->objList->aggregateCounts[$fldname][$type] = $row['cnt'];
                }
            }
        }
         */	
                
        return $results;
    }
    
    
    /**
     * Create a condition sql query string based on the query object
     * 
     * @param \Netric\EntityQuery $query
     * @param \Netric\EntityDefinition $def
     * @return string
     */
	private function buildConditionString(\Netric\EntityQuery &$query, \Netric\EntityDefinition &$def)
	{
		$dbh = $this->dbh;
		$cond_str = "";
		$ofields = $def->getFields();

        // Check for full text
        $fullText = "";
        $wheres = $query->getWheres();
        foreach ($wheres as $where)
        {
            if ("*" == $where->fieldName)
                $fullText = $where->value;
        }
        
		// General Search
		// -------------------------------------------------------------
		if ($fullText && $def->isCustomTable())
		{
			// First add text fields
			// ------------------------------------------------
			// ------------------------------------------------
			$part_buf = "";
			foreach ($ofields as $fname=>$field)
			{
				$buf = "";

				if ($field->type == 'text' && $field->subtype)
					$buf = "lower($fname) like lower('%".$dbh->escape(str_replace(" ", "%", str_replace("*", "%", $fullText)))."%') ";
				else if ($field->type == 'text')
					$buf = " to_tsvector($fname) @@ plainto_tsquery('".$dbh->escape($fullText)."') ";

				if ($buf)
				{
					if ($part_buf)
						$part_buf .= " OR ";

					$part_buf .= $buf;
				}
			}

			// Apply full text to the condition string if set
			if ($cond_str && $part_buf) $cond_str .= " AND ";
			if ($part_buf) $cond_str .= "($part_buf) ";
			
			// Now add all other fields
			// ------------------------------------------------
			if (strpos($fullText, " ") != false)
				$parts = explode(" ", $fullText);
			else
				$parts = array($fullText);
			foreach ($parts as $part)
			{
				$part_buf = "";

				if (is_numeric($part))
				{
					foreach ($ofields as $fname=>$field)
					{
						$buf = "";
						switch ($field->type)
						{
						case 'number':
						case 'real':
						case 'integer':
						case 'bigint':
						case 'int8':
							if (is_numeric($fullText))
								$buf .= "$fname='".$dbh->escape($part)."'";
							break;
						default:
							// No conditions
							break;
						}

						if ($buf)
						{
							if ($part_buf)
								$part_buf .= " OR ";

							$part_buf .= $buf;
						}
					}
				}

				if ($cond_str && $part_buf) $cond_str .= " AND ";
				if ($part_buf) $cond_str .= "($part_buf) ";
			}
		}
		else if ($fullText && !$def->isCustomTable())
		{
			$cond_str = "tsv_fulltext @@ plainto_tsquery('".$dbh->escape($fullText)."')";
		}

		if ($cond_str)
			$cond_str = " ($cond_str) ";

		// Filtered search
		// -------------------------------------------------------------
		$adv_cond = $this->buildAdvancedConditionString($query, $def);
		if ($cond_str && $adv_cond)
			$cond_str .= " and ($adv_cond) ";
		else if (!$cond_str && $adv_cond)
			$cond_str = " ($adv_cond) ";

		return $cond_str;
	}


	/**
	 * Process filter conditions
	 *
	 * @param \Netric\EntityQuery $query
     * @param \Netric\EntityDefinition $def
     * @return string
     * @throws \RuntimeException If a problem is encountered with the query
	 */
	public function buildAdvancedConditionString(\Netric\EntityQuery &$query, \Netric\EntityDefinition &$def=null)
	{
		$dbh = $this->dbh;
		$cond_str = "";
		$inOrGroup = false;
        $conditions = $query->getWheres();
        
        if ($def == null)
            $def = $this->getDefinition($query->getObjType());

        // Get table to query
        $objectTable = $def->getTable();
        // No need to query partion because constraints should take care of that
        /*
		if (!$def->isCustomTable() && $query->isDeletedQuery())
            $objectTable .= "_del";
		else if (!$def->isCustomTable())
			$objectTable .= "_act";
         */

		if (count($conditions))
		{
			foreach ($conditions as $cond)
			{
				$blogic = $cond->bLogic;
				$fieldName = $cond->fieldName;
				$operator = $cond->operator;
				$condValue = $cond->value;

                // Should never happen, but just in case if operator is missing throw an exception
                if (!$operator)
                    throw new \RuntimeException("No operator provided for " . var_export($cond, true));

				$buf = "";
                
                // Skip full text
				if ($fieldName == "*")
					continue;
                
				// Look for associated object conditions
				$parts = array($fieldName);
				if (strpos($fieldName, '.'))
					$parts = explode(".", $fieldName);

                // Get field
				$origField = $def->getField($parts[0]);
                if (!$origField)
                    throw new \RuntimeException("Could not get field " . $query->getObjType() . ":" . $parts[0]);
                
                // Make a copy in case we need change the type to object_dereference
                $field = clone $origField;
                
                // Skip non-existant field or full text
				if (!$field)
					continue;
                
				if (count($parts) > 1)
				{
					$fieldName = $parts[0];
					$ref_field = $parts[1];
					$field->type = "object_dereference";
				}
				else
				{
					$ref_field = "";
				}

				// Sanitize and replace environment variables like 'current_user' to concrete vals
				$condValue = $this->sanitizeWhereCondition($field, $condValue);

				// Convert PHP bool to textual true or false
				if ($field->type == "bool")
					$condValue = ($condValue === true) ? 't' : 'f';

				if ($condValue !== "" && $condValue !== null)
				{
					switch ($operator)
					{
					case 'is_equal':
                        $buf .= $this->buildIsEqual($field, $fieldName, $condValue, $objectTable, $def);

                        break;
					case 'is_not_equal':
                        $buf .= $this->buildIsNotEqual($field, $fieldName, $condValue, $objectTable, $def);
						break;
					case 'is_greater':
						switch ($field->type)
						{
						case 'object_multi':
						case 'object':
						case 'fkey_multi':
						case 'text':
							break;
						default:
                            if ($field->type == "timestamp")
                                $condValue = (is_numeric($condValue)) ? date("Y-m-d H:i:s T", $condValue) : $condValue;
                            else if ($field->type == "date")
                                $condValue = (is_numeric($condValue)) ? date("Y-m-d", $condValue) : $condValue;
                            
							$buf .= " $fieldName>'".$dbh->escape($condValue)."' ";
							break;
						}
						break;
					case 'is_less':
						switch ($field->type)
						{
						case 'object_multi':
						case 'object':
						case 'fkey_multi':
							break;
						case 'text':
							break;
						default:
                            if ($field->type == "timestamp")
                                $condValue = (is_numeric($condValue)) ? date("Y-m-d H:i:s T", $condValue) : $condValue;
                            else if ($field->type == "date")
                                $condValue = (is_numeric($condValue)) ? date("Y-m-d", $condValue) : $condValue;
                            
							$buf .= " $fieldName<'".$dbh->escape($condValue)."' ";
							break;
						}
						break;
					case 'is_greater_or_equal':
						switch ($field->type)
						{
						case 'object':
							if ($field->subtype)
							{
                                $children = $this->getHeiarchyDownObj($field->subtype, $condValue);
                                $tmp_cond_str = "";
                                foreach ($children as $child)
                                {
                                    if ($tmp_cond_str) $tmp_cond_str .= " or ";
                                    $tmp_cond_str .= " $fieldName='".$dbh->escape($child)."' ";
                                }
                                $buf .= "($tmp_cond_str) ";
								
								break;
							}
							break;
						case 'object_multi':
						case 'fkey_multi':
							break;
						case 'text':
							break;
						default:
                            if ($field->type == "timestamp")
                                $condValue = (is_numeric($condValue)) ? date("Y-m-d H:i:s T", $condValue) : $condValue;
                            else if ($field->type == "date")
                                $condValue = (is_numeric($condValue)) ? date("Y-m-d", $condValue) : $condValue;
                            
							$buf .= " $fieldName>='".$dbh->escape($condValue)."' ";
							break;
						}
						break;
					case 'is_less_or_equal':
						switch ($field->type)
						{
						case 'object':
							if (isset($field->subtype) && $def->parentField == $fieldName && is_numeric($condValue))
							{
                                $defDef = $this->getDefinition($field->subtype);

								if ($defDef->parentField)
								{
									$buf .= " $fieldName in (WITH RECURSIVE children AS
												(
													-- non-recursive term
													SELECT id FROM " . $defDef->getTable(true) . " WHERE id = '$condValue'
													UNION ALL
													-- recursive term
													SELECT " . $defDef->getTable(true) . ".id
													FROM
														" . $defDef->getTable(true) . "
													JOIN
														children AS chld
														ON (" . $defDef->getTable(true) . "." . $defDef->parentField . " = chld.id)
												)
												SELECT id
												FROM children)";
								}
							}
							break;
						case 'object_multi':
						case 'fkey_multi':
							break;
						case 'text':
							break;
						default:
                            if ($field->type == "timestamp")
                                $condValue = (is_numeric($condValue)) ? date("Y-m-d H:i:s T", $condValue) : $condValue;
                            else if ($field->type == "date")
                                $condValue = (is_numeric($condValue)) ? date("Y-m-d", $condValue) : $condValue;
                            
							$buf .= " $fieldName<='".$dbh->escape($condValue)."' ";
							break;
						}
						break;
					case 'begins':
					case 'begins_with':
						switch ($field->type)
						{
						case 'text':
							if ($field->subtype)
								$buf .= " lower($fieldName) like lower('".$dbh->escape($condValue)."%') ";
							else
								$buf .= " to_tsvector($fieldName) @@ plainto_tsquery('".$dbh->escape($condValue)."*') ";
							break;
						default:
							break;
						}
						break;
					case 'contains':
						switch ($field->type)
						{
						case 'text':
							if ($field->subtype)
								$buf .= " lower($fieldName) like lower('%".$dbh->escape($condValue)."%') ";
							else
								$buf .= " to_tsvector($fieldName) @@ plainto_tsquery('".$dbh->escape($condValue)."') ";

							break;
						default:
							break;
						}
						break;
					case 'day_is_equal':
						if ($field->type == "date" || $field->type == "timestamp")
						{
							switch ($condValue)
							{
							case '<%current_day%>':
								$tmpcond = "extract('day' from now())";
								break;
							default:
								$tmpcond = "'".$dbh->escape($condValue)."'";
								break;
							}

							$buf .= " extract(day from $fieldName)=$tmpcond ";
						}
						break;
					case 'month_is_equal':
						if ($field->type == "date" || $field->type == "timestamp")
						{
							switch ($condValue)
							{
							case '<%current_month%>':
								$tmpcond = "extract('month' from now())";
								break;
							default:
								$tmpcond = "'".$dbh->escape($condValue)."'";
								break;
							}

							$buf .= " extract(month from $fieldName)=$tmpcond ";
						}
						break;
					case 'year_is_equal':
						if ($field->type == "date" || $field->type == "timestamp")
						{
							switch ($condValue)
							{
							case '<%current_year%>':
								$tmpcond = "extract('year' from now())";
								break;
							default:
								$tmpcond = "'".$dbh->escape($condValue)."'";
								break;
							}

							$buf .= " extract(year from $fieldName)=$tmpcond ";
						}
						break;
					case 'last_x_days':
						if ($field->type == "date" || $field->type == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=(now()-INTERVAL '".$dbh->escape($condValue)." days')";
						}
						break;
					case 'last_x_weeks':
						if ($field->type == "date" || $field->type == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=(now()-INTERVAL '".$dbh->escape($condValue)." weeks')";
						}
						break;
					case 'last_x_months':
						if ($field->type == "date" || $field->type == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=(now()-INTERVAL '".$dbh->escape($condValue)." months')";
						}
						break;
					case 'last_x_years':
						if ($field->type == "date" || $field->type == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=(now()-INTERVAL '".$dbh->escape($condValue)." years')";
						}
						break;
					case 'next_x_days':
						if ($field->type == "date" || $field->type == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=now() and $fieldName<=(now()+INTERVAL '".$dbh->escape($condValue)." days')";
						}
						break;
					case 'next_x_weeks':
						if ($field->type == "date" || $field->type == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=now() and $fieldName<=(now()+INTERVAL '".$dbh->escape($condValue)." weeks')";
						}
						break;
					case 'next_x_months':
						if ($field->type == "date" || $field->type == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=now() and $fieldName<=(now()+INTERVAL '".$dbh->escape($condValue)." months')";
						}
						break;
					case 'next_x_years':
						if ($field->type == "date" || $field->type == "timestamp" && is_numeric($condValue))
						{
							$buf .= " $fieldName>=now() and $fieldName<=(now()+INTERVAL '".$dbh->escape($condValue)." years')";
						}
						break;
					}
				}
				else // handle null
				{
					switch ($operator)
					{
					case 'is_equal':
						// Deal with "isnull"
                        $buf .= $this->buildIsEqual($field, $fieldName, $condValue, $objectTable, $def);
                        /*
						switch ($field->type)
						{
                        case 'object_multi':
							$buf .= " ".$objectTable.".id not in (select object_id from object_associations
																				where type_id='" . $def->getId() . "'
																				and field_id='" . $field->id . "') ";
                            break;
						case 'fkey_multi':
							$buf .= " ".$objectTable.".id not in (select ".$field->fkeyTable['ref_table']["this"]."
																		from ".$field->fkeyTable['ref_table']['table'].") ";
                            
							break;
						case 'text':
							$buf .= " ($fieldName='' or $fieldName is null) ";
							break;
                        case 'object':
						default:
							$buf .= " $fieldName is null ";
							break;
						}
                         
                         */
						break;
					case 'is_not_equal':
						// Deal with "isnull"
                        $buf .= $this->buildIsNotEqual($field, $fieldName, $condValue, $objectTable, $def);
                        /*
						switch ($field->type)
						{
						case 'object_multi':
							$buf .= " ".$objectTable.".id in (select object_id from object_associations
																				where type_id='" . $def->getId() . "'
																				and field_id='" . $field->id . "') ";
							break;
						case 'fkey_multi':
							$buf .= " ".$objectTable.".id in (select ".$field->fkeyTable['ref_table']["this"]."
																		from ".$field->fkeyTable['ref_table']['table'].") ";
							break;
						case 'text':
							$buf .= " ($fieldName!='' and $fieldName is not null) ";
							break;
						case 'object':
						default:
							$buf .= " $fieldName is not null ";
							break;
						}
                         * 
                         */
						break;
					}
				}

				// New system to added to group "or" statements
				if ($blogic == "and")
				{
					if ($buf)
					{
						if ($cond_str) 
							$cond_str .= ") $blogic (";
						else
							$cond_str .= " ( ";
						$inOrGroup = true;
					}
				}
				else if ($cond_str && $buf) // or
					$cond_str .= " $blogic ";

				$cond_str .= $buf;
			}

			// Close condtion grouping
			if ($inOrGroup)
				$cond_str .= ")";
		}

		//echo "COND STR: ".$cond_str;
		return $cond_str;
	}
    
    /**
     * Add conditions for "is_eqaul" operator
     * 
     * @param type $field
     * @param type $condValue
     * @return string
     */
    private function buildIsEqual($field, $fieldName, $condValue, $objectTable, $def)
    {
        $buf = "";
        
        switch ($field->type)
        {
        case 'object':
            if ($field->subtype)
            {
            	/*
                if (isset($field->fkeyTable["parent"]) && is_numeric($condValue))
                {
                    $children = $this->getHeiarchyDownObj($field->subtype, $condValue);
                    $tmp_cond_str = "";
                    foreach ($children as $child)
                    {
                        if ($tmp_cond_str) $tmp_cond_str .= " or ";
                        $tmp_cond_str .= " $fieldName='".$this->dbh->escape($child)."' ";
                    }
                    $buf .= "($tmp_cond_str) ";
                }
                else */if ($condValue)
                {
                    $buf .= " $fieldName='".$this->dbh->escape($condValue)."' ";
                }
                else
                {
                    $buf .= " $fieldName is null ";
                }
                break;
            }
        case 'object_multi':
            $tmp_cond_str = "";
            if ($condValue == "" || $condValue == "NULL" || $condValue==null)
            {
                $buf .= " not EXISTS (select 1 from object_associations
                                        where object_associations.object_id=".$objectTable.".id
                                        and type_id='" . $def->getId() . "'
                                        and field_id='" . $field->id . "') ";
            }
            else
            {
                $objRef = \Netric\Entity\Entity::decodeObjRef($condValue);
                if ($objRef)
                {
                    $refDef = $this->getDefinition($objRef['obj_type']);
                    if ($refDef && $refDef->getId() && $objRef['id'])
                    {
                        $buf .= " EXISTS (select 1 from object_associations 
                                    where object_associations.object_id=".$objectTable.".id
                                    and type_id='" . $def->getId() . "' and field_id='".$field->id."'
                                    and assoc_type_id='" . $refDef->getId() . "' 
                                    and assoc_object_id='" . $objRef['id'] . "') ";
                    }
                }
                else if ($condValue) // only query assocaited type
                {
                    $refDef = $this->getDefinition($condValue);
                    if ($refDef && $refDef->getId())
                    {
                        $buf .= " EXISTS (select 1 from object_associations 
                                    where object_associations.object_id=".$objectTable.".id and
                                    type_id='" . $def->getId() . "' and field_id='".$field->id."'
                                    and assoc_type_id='" . $refDef->getId() . "') ";
                    }
                }
            }
            break;
        case 'object_dereference':
            if ($field->subtype && isset($ref_field))
            {
                // Create subquery
                $subQuery = new \Netric\EntityQuery($field->subtype);
                $subQuery->where($ref_field, $operator, $condValue);
                $subIndex = new \Netric\EntityQuery\Index\Pgsql($this->account);
                $tmp_obj_cnd_str = $subIndex->buildAdvancedConditionString($subQuery);
                $refDef = $this->getDefinition($field->subtype);

                if ($condValue == "" || $condValue == "NULL")
                {
                    $buf .= " ".$objectTable.".$fieldName not in (select id from " . $refDef->getTable() . "
                                                                                where $tmp_obj_cnd_str) ";
                }
                else
                {
                    $buf .= " ".$objectTable.".$fieldName in (select id from " . $refDef->getTable() . "
                                                                                where $tmp_obj_cnd_str) ";
                }
            }
            break;
        case 'fkey_multi':
            $tmp_cond_str = "";
            if (isset($field->fkeyTable["parent"]) && is_numeric($condValue))
            {
                $children = $this->getHeiarchyDownGrp($field, $condValue);
                $tmp_cond_str = "";
                foreach ($children as $child)
                {
                    if ($tmp_cond_str) $tmp_cond_str .= " or ";
                    $tmp_cond_str .= $field->fkeyTable['ref_table']['ref']."='$child'";
                }
            }
            else if ($condValue && $condValue!="NULL" && $condValue!=null)
            {
                $tmp_cond_str = $field->fkeyTable['ref_table']['ref']."='$condValue'";
            }

            $thisfld = $field->fkeyTable['ref_table']["this"];
            $reftbl = $field->fkeyTable['ref_table']['table'];
            if ($condValue == "" || $condValue == "NULL" || $condValue == null)
            {
                $buf .= " NOT EXISTS (select 1 from  ".$reftbl." where 
                                  ".$reftbl.".".$thisfld."=".$objectTable.".id) ";
            }
            else
            {
                $buf .= " EXISTS (select 1 from  ".$reftbl." where 
                                  ".$reftbl.".".$thisfld."=".$objectTable.".id and ($tmp_cond_str)) ";
            }
            break;
        case 'fkey':
            $tmp_cond_str = "";
            if ($condValue == "" || $condValue == "NULL" || $condValue == null)
            {
                $tmp_cond_str .= " $fieldName is null ";
            }
            else
            {
                if (isset($field->fkeyTable["parent"]) && is_numeric($condValue))
                {
                    $children = $this->getHeiarchyDownGrp($field, $condValue);
                    $tmp_cond_str = "";
                    foreach ($children as $child)
                    {
                        if ($tmp_cond_str) $tmp_cond_str .= " or ";
                        $tmp_cond_str .= " $fieldName='".$this->dbh->escape($child)."' ";
                    }
                }
                else
                {
                    $tmp_cond_str = " $fieldName='".$this->dbh->escape($condValue)."' ";
                }
            }

            $buf .= "($tmp_cond_str) ";
            break;
        case 'text':
            if ($condValue == "" || $condValue == "NULL" || $condValue == null)
            {
                $buf .= " ($fieldName is null OR $fieldName='')";
            }
            else
            {
                if ($field->subtype)
                    $buf .= " lower($fieldName)=lower('".$this->dbh->escape($condValue)."') ";
                else
                    $buf .= " to_tsvector($fieldName) @@ plainto_tsquery('".$this->dbh->escape($condValue)."') ";
            }

            break;
        case 'date':
        case 'timestamp':
            if ($field->type == "timestamp")
                $condValue = (is_numeric($condValue)) ? date("Y-m-d H:i:s T", $condValue) : $condValue;
            else if ($field->type == "date")
                $condValue = (is_numeric($condValue)) ? date("Y-m-d", $condValue) : $condValue;
        default:
            if ($condValue === "" || $condValue === "NULL" || $condValue === null)
                $buf .= " $fieldName is null";
            else
                $buf .= " $fieldName='".$this->dbh->escape($condValue)."' ";
            break;
        }
        
        return $buf;
    }
    
    /**
     * Add conditions for "is_not_eqaul" operator
     * 
     * @param type $field
     * @param type $condValue
     * @return string
     */
    private function buildIsNotEqual($field, $fieldName, $condValue, $objectTable, $def)
    {
        $buf = "";
        
        switch ($field->type)
        {
        case 'object':
            // Check if we are querying table directly, otherwise fall through to object_multi code
            if ($field->subtype)
            {
                if ($condValue == "" || $condValue == "NULL")
                {
                    $buf .= " $fieldName is not null";
                }
                else if (isset($field->subtype) && $def->parentField == $fieldName && $condValue)
                {
                    $refDef = $this->getDefinition($field->subtype);

                    if ($refDef->parentField)
                    {
                        $buf .= " $fieldName not in (WITH RECURSIVE children AS
                                    (
                                        -- non-recursive term
                                        SELECT id FROM " . $refDef->getTable(true) . " WHERE id = '$condValue'
                                        UNION ALL
                                        -- recursive term
                                        SELECT " . $refDef->getTable(true) . ".id
                                        FROM
                                            " . $refDef->getTable(true) . "
                                        JOIN
                                            children AS chld
                                            ON (" . $refDef->getTable(true) . "." . $refDef->parentField . " = chld.id)
                                    )
                                    SELECT id
                                    FROM children)";
                    }
                }
                else
                {
                    $buf .= " $fieldName!='".$this->dbh->escape($condValue)."' ";
                }

                break;
            }
        case 'object_multi':
            if ($condValue == "" || $condValue == "NULL")
            {
                $buf .= " ".$objectTable.".id in (select object_id from object_associations
                                                                where type_id='" . $def->getId() . "'
                                                                and field_id='" . $field->id . "') ";
            }
            else
            {
                $objRef = \Netric\Entity\Entity::decodeObjRef($condValue);
                if ($objRef)
                {
                    $refDef = $this->getDefinition($objRef['obj_type']);
                    if ($refDef && $refDef->getId() && $objRef['id'])
                    {
                        $buf .= " ".$objectTable.".id not in (select object_id from object_associations 
                            where type_id='" . $def->getId() . "' and field_id='" . $field->id . "'
                            and assoc_type_id='" . $refDef->getId() . "' and assoc_object_id='" . $objRef['id'] . "') ";
                    }
                }
            }
            break;
        case 'object_dereference':
            $tmp_cond_str = "";
            if ($field->subtype && $ref_field)
            {
                // Create subquery
                $subQuery = new \Netric\EntityQuery($field->subtype);
                $subQuery->where($ref_field, $operator, $condValue);
                $subIndex = new \Netric\EntityQuery\Index\Pgsql($this->account);
                $tmp_obj_cnd_str = $subIndex->buildAdvancedConditionString($subQuery);
                $refDef = $this->getDefinition($field->subtype);

                if ($condValue == "" || $condValue == "NULL")
                {
                    $buf .= " ".$objectTable.".$fieldName is not null ";
                }
                else
                {
                    $buf .= " ".$objectTable.".$fieldName not in (select id from ".$refDef->getTable(true)."
                                                                                where $tmp_obj_cnd_str) ";
                }
            }
            break;
        case 'fkey_multi':
         
            if ($condValue == "" || $condValue == "NULL" || $condValue == null)
            {
                $buf .= " ".$objectTable.".id in (select ".$field->fkeyTable['ref_table']["this"]."
																		from ".$field->fkeyTable['ref_table']['table'].") ";
            }
            else
            {
                if (isset($field->fkeyTable["parent"]) && is_numeric($condValue))
                {
                    $children = $this->getHeiarchyDownGrp($field, $condValue);
                    $tmp_cond_str = "";
                    foreach ($children as $child)
                    {
                        if ($tmp_cond_str) $tmp_cond_str .= " or ";
                        $tmp_cond_str .= $field->fkeyTable['ref_table']['ref']."='$child'";
                    }
                }
                else
                {
                    $tmp_cond_str = $field->fkeyTable['ref_table']['ref']."='$condValue'";
                }
            
                $buf .= " ".$objectTable.".id not in (select ".$field->fkeyTable['ref_table']["this"]."
                                                                  from ".$field->fkeyTable['ref_table']['table']." 
                                                                  where $tmp_cond_str) ";
            }
            
            break;
        case 'fkey':
            if ($condValue == "" || $condValue == "NULL" || $condValue == null)
            {
                $buf .= " $fieldName is not null";
            }
            else
            {
                if (isset($field->fkeyTable["parent"]) && is_numeric($condValue))
                {
                    $children = $this->getHeiarchyDownGrp($field, $condValue);
                    $tmp_cond_str = "";
                    foreach ($children as $child)
                    {
                        if ($tmp_cond_str) $tmp_cond_str .= " and ";
                        $tmp_cond_str .= " $fieldName!='".$this->dbh->escape($child)."' ";
                    }
                }
                else
                {
                    $tmp_cond_str = " $fieldName!='".$this->dbh->escape($condValue)."' ";
                }

                $buf .= "(($tmp_cond_str)  or $fieldName is null) ";
            }
            
            break;
        case 'text':
            if ($condValue == "" || $condValue == "NULL" || $condValue == null)
            {
                $buf .= " ($fieldName!='' AND $fieldName is not NULL) ";
            }
            else
            {
                if ($field->subtype)
                    $buf .= " lower($fieldName)!=lower('".$this->dbh->escape($condValue)."') ";
                else
                    $buf .= " (to_tsvector($fieldName) @@ plainto_tsquery('".$this->dbh->escape($condValue)."'))='f' ";
            }
            
            break;
        case 'date':
        case 'timestamp':
            if ($field->type == "timestamp")
                $condValue = (is_numeric($condValue)) ? date("Y-m-d H:i:s T", $condValue) : $condValue;
            else if ($field->type == "date")
                $condValue = (is_numeric($condValue)) ? date("Y-m-d", $condValue) : $condValue;
        default:
            if ($condValue == "" || $condValue == "NULL" || $condValue == null)
                $buf .= " $fieldName is not null ";
            else 
                $buf .= " ($fieldName!='".$this->dbh->escape($condValue)."' or $fieldName is null) ";
            break;
        }
        
        return $buf;
    }
    
    /**
	 * Get ids of all child entries in a parent-child relationship
     * 
     * This function may be over-ridden in specific indexes for performance reasons
	 *
	 * @param string $table The table to query
	 * @param string $parent_field The field containing the id of the parent entry
	 * @param int $this_id The id of the child element
	 */
	public function getHeiarchyDownGrp(\Netric\EntityDefinition\Field $field, $this_id)
	{
		$dbh = $this->dbh;
		$ret = array();
        
        // If not heiarchy then just return this
        if (!isset($field->fkeyTable["parent"]) || !$field->fkeyTable["parent"])
            return array($this_id);
        
        $sql = "WITH RECURSIVE children AS
                (
                    -- non-recursive term
                    SELECT id FROM " . $field->subtype . " WHERE id = '$this_id'
                    UNION ALL
                    -- recursive term
                    SELECT " . $field->subtype . ".id
                    FROM
                        " . $field->subtype . "
                    JOIN
                        children AS chld
                        ON (" . $field->subtype . "." . $field->fkeyTable["parent"] . " = chld.id)
                )
                SELECT id
                FROM children";
        $result = $dbh->query($sql);
        for ($i = 0; $i < $this->dbh->getNumRows($result); $i++)
        {
            $ret[] = $dbh->getValue($result, $i, "id");
        }
        
		return $ret;
	}
    
    /**
     * Set aggregation data
     * 
     * @param \Netric\EntityQuery\Aggregation\AggregationInterface $agg
     * @param \Netric\EntityQuery\Results $res
     * @param string $objectTable The actual table we are querying
     * @param string $conditionQuery The query condition for filtering
     */
    private function queryAggregation(\Netric\EntityQuery\Aggregation\AggregationInterface $agg, EntityQuery\Results &$res, $objectTable, $conditionQuery)
    {
        $data = null;
        
        switch ($agg->getTypeName())
        {
        case 'terms':
            $data = $this->queryAggTerms($agg, $objectTable, $conditionQuery);
            break;
        case 'sum':
            $data = $this->queryAggSum($agg, $objectTable, $conditionQuery);
            break;
        case 'avg':
            $data = $this->queryAggAvg($agg, $objectTable, $conditionQuery);
            break;
        case 'min':
            $data = $this->queryAggMin($agg, $objectTable, $conditionQuery);
            break;
        case 'stats':
            $data = $this->queryAggStats($agg, $objectTable, $conditionQuery);
            if ($data)
                $data['count'] = $res->getTotalNum ();
            break;
        case 'count':
            $data = $res->getTotalNum();
            break;
        }
        
        if ($data)
            $res->setAggregation($agg->getName(), $data);
    }
    
    /**
     * Set terms aggregation - basically a select distinct
     * 
     * @param \Netric\EntityQuery\Aggregation\AggregationInterface $agg
     * @param string $objectTable The actual table we are querying
     * @param string $conditionQuery The query condition for filtering
     */
    private function queryAggTerms(\Netric\EntityQuery\Aggregation\AggregationInterface $agg, $objectTable, $conditionQuery)
    {
        $fieldName = $agg->getField();
        
        if (!$fieldName)
            return false;
        
        $retData = array();
        
        $query = "select distinct($fieldName), count($fieldName) as cnt from " . $objectTable . " where id is not null ";
        if ($conditionQuery)
            $query .= " and ($conditionQuery) ";
        $query .= " GROUP BY $fieldName";
        $result = $this->dbh->query($query);
        $num = $this->dbh->getNumRows($result);
        for ($j = 0; $j < $num; $j++)
        {
            $row = $this->dbh->getRow($result, $j);

            $retData[] = array(
                "count" => $row["cnt"],
                "term" => $row[$fieldName],
            );
        }
        
        return $retData;
    }
    
    /**
     * Set sum aggregation
     * 
     * @param \Netric\EntityQuery\Aggregation\AggregationInterface $agg
     * @param string $objectTable The actual table we are querying
     * @param string $conditionQuery The query condition for filtering
     */
    private function queryAggSum(\Netric\EntityQuery\Aggregation\AggregationInterface $agg, $objectTable, $conditionQuery)
    {
        $fieldName = $agg->getField();
        
        if (!$fieldName)
            return false;
        
        $query = "select sum($fieldName) as amount from " . $objectTable . " where id is not null ";
        if ($conditionQuery)
            $query .= " and ($conditionQuery) ";
        $result = $this->dbh->query($query);
        if ($this->dbh->getNumRows($result))
            return $this->dbh->getValue($result, 0, "amount");
        
        return false;
    }
    
    /**
     * Set sum aggregation
     * 
     * @param \Netric\EntityQuery\Aggregation\AggregationInterface $agg
     * @param string $objectTable The actual table we are querying
     * @param string $conditionQuery The query condition for filtering
     */
    private function queryAggAvg(\Netric\EntityQuery\Aggregation\AggregationInterface $agg, $objectTable, $conditionQuery)
    {
        $fieldName = $agg->getField();
        
        if (!$fieldName)
            return false;
        
        $query = "select avg($fieldName) as amount from " . $objectTable . " where id is not null ";
        if ($conditionQuery)
            $query .= " and ($conditionQuery) ";
        $result = $this->dbh->query($query);
        if ($this->dbh->getNumRows($result))
            return $this->dbh->getValue($result, 0, "amount");
        
        return false;
    }
    
    /**
     * Set sum aggregation
     * 
     * @param \Netric\EntityQuery\Aggregation\AggregationInterface $agg
     * @param string $objectTable The actual table we are querying
     * @param string $conditionQuery The query condition for filtering
     */
    private function queryAggMin(\Netric\EntityQuery\Aggregation\AggregationInterface $agg, $objectTable, $conditionQuery)
    {
        $fieldName = $agg->getField();
        
        if (!$fieldName)
            return false;
        
        $query = "select min($fieldName) as amount from " . $objectTable . " where id is not null ";
        if ($conditionQuery)
            $query .= " and ($conditionQuery) ";
        $result = $this->dbh->query($query);
        if ($this->dbh->getNumRows($result))
            return $this->dbh->getValue($result, 0, "amount");
        
        return false;
    }
    
    /**
     * Set sum aggregation
     * 
     * @param \Netric\EntityQuery\Aggregation\AggregationInterface $agg
     * @param string $objectTable The actual table we are querying
     * @param string $conditionQuery The query condition for filtering
     */
    private function queryAggStats(\Netric\EntityQuery\Aggregation\AggregationInterface $agg, $objectTable, $conditionQuery)
    {
        $fieldName = $agg->getField();
        
        if (!$fieldName)
            return false;
        
        $query = "select "
                . "min($fieldName) as mi, "
                . "max($fieldName) as ma, "
                . "avg($fieldName) as av, "
                . "sum($fieldName) as su "
                . "FROM " . $objectTable . " where id is not null ";
        if ($conditionQuery)
            $query .= " and ($conditionQuery) ";
        
        $result = $this->dbh->query($query);
        if ($this->dbh->getNumRows($result))
        {
            return array(
                "min" => $this->dbh->getValue($result, 0, "mi"),
                "max" => $this->dbh->getValue($result, 0, "ma"),
                "avg" => $this->dbh->getValue($result, 0, "av"),
                "sum" => $this->dbh->getValue($result, 0, "su"),
                "count" => "" // set in calling class
            );
            
        }
        
        return false;
    }
}
