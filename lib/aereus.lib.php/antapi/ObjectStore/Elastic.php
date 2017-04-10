<?php
/**
 * Main abstract class for localized stores of ANT objects in Elasic index
 *
 * Class for storing and querying object data in elastic search server.
 * Elastic returns all document data so while it is mostly designed to be a
 * full-text index, it also serves well as a chached local store.
 *
 * Global Variables:
 * $ANTAPI_STORE_ELASTIC_HOST = "localhost" - host of elastic server def to "localhost"
 * $ANTAPI_STORE_ELASTIC_IDX = "aereus.com" - index name, usualy website domain or app name
 *
 * @category  AntApi
 * @package   ObjectStore_Elastic
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

class AntApi_ObjectStore_Elastic extends AntApi_ObjectStore
{
	var $indexName = null;
	var $elasticHost = null;
	var $Client = null;

	/**
	 * Handle to ES index
	 *
	 * @var Elastica_Index
	 */
	private $eIndex = null;

	/**
	 * Handle to document type
	 *
	 * @var Elastica_Index_Type
	 */
	private $eType = null;
    
    /**
     * String value to search
     *
     * @var String
     */
    var $fullTextSearch = null;

	/**
	 * Open a connection to the elastic server
	 *
	 * @return false on fail, elastic index object on success
	 */
	public function connect()
	{
		global $ANTAPI_STORE_ELASTIC_HOST, $ANTAPI_STORE_ELASTIC_IDX;

        if(defined("ANTAPI_STORE_ELASTIC_HOST"))
            $elasticHost = ANTAPI_STORE_ELASTIC_HOST;
        else if(isset($ANTAPI_STORE_ELASTIC_HOST))
            $elasticHost = $ANTAPI_STORE_ELASTIC_HOST;
            
        if(defined("ANTAPI_STORE_ELASTIC_IDX"))
            $elasticIdx = ANTAPI_STORE_ELASTIC_IDX;
        else if(isset($ANTAPI_STORE_ELASTIC_IDX))
            $elasticIdx = $ANTAPI_STORE_ELASTIC_IDX;
        
		if (!$elasticIdx|| !$elasticHost)
			return false;

		$this->eClient = new Elastica_Client(array("host"=>$elasticHost));
		$this->indexName = $elasticIdx;
	}

	/**
	 * Delete the index
	 */
	public function deleteIndex()
	{
		$ret = $this->getIndex()->delete();
	}

	/**
	 * Get index type from elastic
	 *
	 * @param string $type The type name (we map each object to a type like 'customer' is a type)
	 * @return Elastica_Index_Type
	 */
	private function getType($type)
	{
		$this->eType = $this->getIndex()->getType($type);
		return $this->eType;
	}

	/**
	 * Open an object from the local store. Return fail if not present
	 *
	 * @param string $obj_type The name of the object type to open
	 * @param string $oid The uniq id of the object to open
	 * @return false if no object found, and array of property values if found
	 */
	public function openObject($obj_type, $oid)
	{
		$indType = $this->getType($obj_type);

		$ret = false;

		// Check for uname
		$pos = strpos($oid, "uname:");
		if ($pos !== false)
		{
            $parts = explode(":", $oid);
			$oid = $parts[1];
			$resultSet = $indType->search("uname_s:$oid");
		}
		else
		{
			$resultSet = $indType->search("_id:$oid");
		}
		$num = $resultSet->getTotalHits();
		if ($num)
		{
			$results = $resultSet->getResults();
			if (count($results))
			{
				$ret = array();

				$data= $results[0]->getData();
				foreach ($data as $fname=>$fval)
					$ret[$this->unescapeField($fname)] = $fval;
			}
			
			/*
			for ($i = 0; $i < count($results); $i++)
			{
				$doc = $results[$i];
				$data = $doc->getData();
			}
			*/
		}

		return $ret;
	}

	/**
	 * Store object data into local database
	 *
	 * @param AntAPi_Object $obj Object instance to store locally
	 * @return bool true on success, false on failure
	 */
	public function storeObject($obj)
	{
		if (!$obj->id)
			return false;

		$indType = $this->getType($obj->obj_type);

		$ret = true;
		$buf = "";
		$snippet = "";

		$obj_data = array();
		$buf = "";
		$snippet = "";
		$fields = $obj->getFields();
		if (is_array($fields) && count($fields))
		{
			foreach ($fields as $field=>$fdef)
			{
				switch ($fdef['type'])
				{
				case 'fkey_multi':
					$vals = $obj->getValue($field);
					if (is_array($vals) && count($vals))
					{
						$obj_data[$this->escapeField($obj, $field)] = $vals;
						$obj_data[$field."_tsort"] = $val;
					}
					break;
				case 'object_multi':
					$vals = $obj->getValue($field);
					if (is_array($vals) && count($vals))
					{
						$obj_data[$this->escapeField($obj, $field)] = $vals;
					}
					break;
				case 'date':
				case 'timestamp':
					$val = $obj->getValue($field);
					
					if ($val)
					{
						// Convert to UTC
						if ($val == "now")
						{
							$val = gmdate("Ymd\\TG:i:s", time());
							$val_s = gmdate("Y-m-d\\TG:i:s\\Z", time());
						}
						else
						{
							$time = strtotime($val);
							$val = gmdate("Ymd\\TG:i:s", $time);
							$val_s = gmdate("Y-m-d\\TG:i:s\\Z", $time);
						}

						$obj_data[$this->escapeField($obj, $field)] = $val;
						// Add string version of field for wildcard queries
						$obj_data[$this->escapeField($obj, $field)."_s"] = $val_s;
					}
					break;
				case 'fkey':
				case 'number':
					$val = $obj->getValue($field);
					if (is_numeric($val))
						$obj_data[$this->escapeField($obj, $field)] = $val;
					break;
				case 'object':
					$val = $obj->getValue($field);
					if ($val)
					{
						$obj_data[$this->escapeField($obj, $field)] = $val;
						$obj_data[$field."_tsort"] = $val;
					}

					break;
				case 'boolean':
				case 'bool':
					$val = $obj->getValue($field);
					if ($val=='t')
						$val = 'true';
					else
						$val = 'false';

					$obj_data[$this->escapeField($obj, $field)] = $val;
					break;
				case 'text':
				default:
					$val = $obj->getValue($field);
					if ($val)
					{
						$obj_data[$this->escapeField($obj, $field)] = $val;
						$obj_data[$field."_tsort"] = $val;
						if ($fdef['subtype']) // save original for facets
							$obj_data[$field."_s"] = $val;
					}
					break;
				}

				// Now set foreign values
				switch ($fdef['type'])
				{
				case 'fkey_multi':
				case 'fkey':
				case 'object':
				case 'object_multi':
				case 'obj_reference':
				case 'alias':
					$fval = $obj->getForeignValue($field);
					if ($fval)
						$obj_data[$field."_fval_s"] = $fval;
					break;
				}
			}

			try
			{
				$doc = new Elastica_Document($obj->id, $obj_data);
				$resp = $indType->addDocument($doc);
				$this->getIndex()->refresh();
				if ($resp->hasError())
					$ret = false;
			}
			catch (Elastica_Exception_Response $ex)
			{
				$ret = false;
			}
		}

		return $ret;
	}

	/**
	 * Delete an object from the local store
	 *
	 * @param string $obj_type The name of the object type we are storing
	 * @param integer $oid Unique id of the object being deleted
	 * @return bool true on success, false on failure
	 */
	public function removeObject($obj_type, $oid)
	{
		if (!$this->eClient || !$this->indexName || !$obj_type) // Must connect first
			return false;

		try 
		{
			$this->eClient->deleteIds(array($oid), $this->indexName, $obj_type);
			$this->getIndex()->refresh();
			//$index->optimize();
		}
		catch (Elastica_Exception_Response $ex)
		{
			//echo "<pre>removeObject failed: ".$ex->getMessage()."</pre>";
			return false;
		}

		return true;
	}

	/**
	 * Query the local store for a matching list of objects
	 *
	 * @param AntAPi_Object $objDef Object instance used for definition
	 * @param int $start The starting offset
	 * @param int $limit The maximum number of objects to return with each set
	 * @return array Associative array of data for the selected objects, -1 on failure
	 */
	public function queryObjects($objDef, $start=0, $limit=500)
	{
		if (!$this->eClient || !$this->indexName || !$objDef) // Must connect first
			return -1;

		$conditionText = $this->conditionText;
		$conditions = $this->conditions;
		$orderBy = $this->orderBy;
		$arrObjects = array();
		$ret = 0;

		$indType = $this->getType($objDef->obj_type);

		// Build condition string
		$query = "";
		if (count($conditions) || $conditionText!="")
		{
			$query = $this->buildConditionString($objDef, $conditionText, $conditions);
		}

		// Add order by
		$order_cnd = array();
		if (is_array($orderBy) && count($orderBy))
		{
			foreach ($orderBy as $fname=>$direction)
			{
				$fld = $objDef->getField($fname);
				if ($fld)
				{
					// Use non-analyzed field for sorting
					if ($fld['type'] == "text" || $fld['type'] == "object" || $fld['type'] == "object_multi")
						$fld_name = $fname."_idxsort";
					else	
						$fld_name = $fname;

					$order_cnd[$this->escapeField($objDef, $fld_name, true)] = strtolower($direction);
				}
			}
		}
		
		// Build query
		$qbuf = array();
		if ($query)
		{
			/*
			$qbuf["filtered"] = array();
			$qbuf["filtered"]['query'] = array("query_string"=>array("query"=>$query));
			$qbuf["filtered"]['filter'] = array("term"=>array("f_deleted"=>"false"));
			 */

			// Moved everything to an fquery so we can cache if not full-text query is defined
			$qbuf['constant_score'] = array();
			$qbuf['constant_score']['filter'] = array();
			$qbuf['constant_score']['filter']["fquery"] = array();
			$qbuf['constant_score']['filter']["fquery"]['query'] = array();
			$qbuf['constant_score']['filter']["fquery"]['query']["query_string"] = array("query"=>$query);
			$qbuf['constant_score']['filter']["fquery"]['_cache'] = "true";
		}
		else if (!$query && $this->objList->hideDeleted && $this->obj->fields->getField("f_deleted"))
		{
			$qbuf['constant_score'] = array();
			$qbuf['constant_score']['filter'] = array("term"=>array("f_deleted"=>"false"));
		}
		else
		{
			$qbuf = array("query_string"=>array("query"=>$query));
		}
		$arrQuery = array();
		$arrQuery['query'] = $qbuf;


		//$arrQuery = array("query_string"=>array("query"=>$query));
		//$queryString = new Elastica_Query_QueryString($query);

		//echo "<pre>Query: ";
		//echo $query;
		//echo var_export($queryString, true);
		//echo "</pre>";
		$this->lastQuery = var_export($arrQuery, true);
        
		if (!$fPullCached) // query index if not already cached
		{
			$queryObject = new Elastica_Query();
			$queryObject->setRawQuery($arrQuery);
			if (count($order_cnd))
				$queryObject->setSort($order_cnd);
			if ($limit)
				$queryObject->setLimit($limit);
			if ($start)
				$queryObject->setFrom($start);
			//$queryObject->setRawQuery($arrQuery);
			//$queryObject->setQuery($queryString);

			// Add facet
			if (is_array($this->facetFields) && count($this->facetFields))
			{
				foreach ($this->facetFields as $fldname=>$fldcnt)
				{
					//echo "<pre>Adding: ".$this->escapeField($objDef, $fldname, true)."</pre>";
					$facet = new Elastica_Facet_Terms($fldname."_term");
					$facet->setField($this->escapeField($objDef, $fldname, true));
					$queryObject->addFacet($facet);
				}
			}

			try
			{
				$resultSet = $indType->search($queryObject);
				//echo "<pre>".var_export($resultSet, true)."</pre>";
			}
			catch (Elastica_Exception_Response $ex)
			{
				if ($ex->getCode() == 0) // type missing
				{
					//$obj_typeateObjectTypeIndexElastic();
					$resultSet = $indType->search($queryObject);
				}
				else
				{
					//echo "<pre>".$ex->getCode()." - ".$ex->getMessage()."</pre>";
					return -1;
				}
			}

			$ret = $resultSet->getTotalHits();
			$results = $resultSet->getResults();

			//echo "<pre>Ran Query: \"";
			//echo $query;
			//echo "\" and found $ret items </pre>";

			for ($i = 0; $i < count($results); $i++)
			{
				$doc = $results[$i];
				$id = $doc->getId();

				$buf = array();
				$data = $doc->getData();
				foreach ($data as $fn=>$fv)
				{
					$buf[$this->unescapeField($fn)] = $fv;
				}
				$arrObjects[] = $buf;
			}

			// Set facet counts
			$facets = $resultSet->getFacets();
			if (is_array($facets) && count($facets))
			{
				foreach($facets as $facetname=>$fres) 
				{	
					// Set terms
					if ($fres['_type'] == "terms")
					{
						$fldname = substr($facetname, 0, -5); // remove _term from the name
						$this->objList->facetCounts[$fldname] = array();
						foreach ($fres['terms'] as $termres)
						{
							$this->facetCounts[$fldname][$termres['term']] = $termres['count'];
						}

						if (count($this->facetCounts[$fldname])>=1)
							ksort($this->facetCounts[$fldname]);
					}

					// Set aggregates
					if ($fres['_type'] == "statistical")
					{
						$fldname = substr($facetname, 0, -5); // Remove _stat from the name
						$this->aggregateCounts[$fldname] = array();
						$this->aggregateCounts[$fldname]["sum"] = $fres['total'];
						$this->aggregateCounts[$fldname]["count"] = $fres['count'];
						$this->aggregateCounts[$fldname]["avg"] = $fres['mean'];
						$this->aggregateCounts[$fldname]["min"] = $fres['min'];
						$this->aggregateCounts[$fldname]["max"] = $fres['max'];
					}
				}
				//echo "<pre>".var_export($this->facetCounts, true)."</pre>";
			}

		}

		return $arrObjects;
	}

	/**
	 * Create query condition string
	 *
	 * @param AntApi_Object $objDef The object definition to work with
	 * @param string $conditionText Plain text search (general - no specific fields)
	 * @param array $conditions Array of arrays('blogic', 'field', 'operator', 'value)
	 * @return string The prepared condition string to use with this query
	 */
	private function buildConditionString($objDef, $conditionText, $conditions)
	{
		$cond_str = "";

		// Advanced Search
		// -------------------------------------------------------------
		$adv_cond = $this->buildAdvancedConditionString($objDef, $conditions);

		if ($adv_cond)
			$cond_str = " ($adv_cond) ";

		// General text Search
		// -------------------------------------------------------------
		$txt_cond = "";
		if ($conditionText)
		{
			$txt_cond = $conditionText;
		}

		if ($cond_str && $txt_cond)
			$cond_str .= " AND ($txt_cond) ";
		else if (!$cond_str && $txt_cond)
			$cond_str = $txt_cond;

		return $cond_str;
	}

	/**
	 * Build advanced query
	 *
	 * Function is used in conjunction with buildConditionString to handle advanced filters
	 *
	 * @param AntApi_Object $objDef The object definition to work with
	 * @param array $conditions Array of arrays('blogic', 'field', 'operator', 'value)
	 * @return string The prepared condition string to use with this query
	 */
	private function buildAdvancedConditionString($objDef, $conditions)
	{
		$cond_str = "";
		$inOrGroup = false;

		if (count($conditions))
		{
			for ($i = 0; $i < count($conditions); $i++)
			{
				$cond = $conditions[$i];
				$blogic = $cond['blogic'];
				// If next condition is available, pull the boolean logic for query grouping at the end
				if ($i+1 < count($conditions))
					$next_blogic = $conditions[$i+1]['blogic'];
				else
					$next_blogic = "";
				$fieldName = $cond['field'];
				$fieldNameEs = $this->escapeField($objDef, $fieldName);
				$operator = $cond['operator'];
				$condValue = $cond['value'];
				$buf = "";

				//echo "<pre>Adding condtions $blogic - $fieldName - $operator - $condValue</pre>";

				// Look for associated object conditions
				if (strpos($fieldName, '.'))
				{
					$parts = explode(".", $fieldName);
				}
				else
				{
					$parts[0] = $fieldName;
				}

				$field = $objDef->getField($parts[0]);
				if (count($parts) > 1)
				{
					$fieldName = $parts[0];
					$ref_field = $parts[1];
					$field['type'] = "object_reference";
				}
				else
				{
					$ref_field = "";
				}


				if (!$field)
					continue; // Skip non-existant field

				// Build Query String
				// -----------------------------------------------------
				
				switch ($operator)
				{
				case 'is_equal':
					switch ($field["type"])
					{
					case 'object_reference':
						/*
						// TODO: review
						$tmp_cond_str = "";
						if ($field['subtype'] && $ref_field)
						{
							$tmpobj = new CAntObject($this->dbh, $field['subtype']);
							$ol = new CAntObjectList($this->dbh, $field['subtype'], $this->objList->user, $conds);
							$ol->addCondition("and", $ref_field, $operator, $condValue);
							$tmp_obj_cnd_str = $ol->buildConditionString($conds);

							if ($condValue == "" || $condValue == "NULL")
							{
								$buf .= " ".$this->obj->object_table.".$fieldNameEs not in (select id from ".$tmpobj->object_table."
																							where $tmp_obj_cnd_str) ";
							}
							else
							{
								$buf .= " ".$this->obj->object_table.".$fieldNameEs in (select id from ".$tmpobj->object_table."
																							where $tmp_obj_cnd_str) ";
							}
						}
						*/
						break;
					case 'object_multi':
					case 'object':
						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= "-$fieldNameEs:[* TO *] ";
						}
						else
						{
							if ($field['subtype'])
							{
									$buf .= "$fieldNameEs:\"$condValue\" "; // Numeric id
							}
							else
							{
								$parts = explode(":", $condValue); // obj_type:object_id

								if (count($parts)==2)
								{
									$buf .= "$fieldNameEs:\"".$parts[0].":".$parts[1]."\" ";
								}
								else if (count($parts)==1) // only query assocaited type
								{
									$buf .= "$fieldNameEs:".$condValue."\:* ";
								}
							}
						}
						break;
					case 'fkey_multi':
					case 'fkey':
						$tmp_cond_str = "";
						/*
						if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
						{
						*/
							$children = $this->getHeiarchyDown($objDef, $fieldName, $condValue);
							$tmp_cond_str = "";
							foreach ($children as $child)
							{
								if ($tmp_cond_str) $tmp_cond_str .= " OR ";
								$tmp_cond_str .= "$fieldNameEs:\"$child\" ";
							}
							if ($tmp_cond_str)
								$tmp_cond_str = "($tmp_cond_str)";
						/*
						}
						else if ($condValue && $condValue!="NULL")
						{
							$tmp_cond_str = "$fieldNameEs:\"$condValue\"";
						}
						*/

						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= "-$fieldNameEs:[* TO *] ";
						}
						else if ($tmp_cond_str)
						{
							$buf .= "$tmp_cond_str ";
						}
						break;

					case 'bool':
						if ($condValue == "" || $condValue == "f" || $condValue == "NULL")
						{
							$buf .= "$fieldNameEs:false ";
						}
						else
						{
							$buf .= "$fieldNameEs:true ";
						}
						break;

					case 'date':
					case 'timestamp':
						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= "-$fieldNameEs:[* TO *] ";
						}
						else
						{
							$time = @strtotime($condValue);
							if ($time !== false)
								$buf .= "$fieldNameEs:".gmdate("Ymd\\TG:i:s", $time)." ";
						}
						break;

					case 'number':
					case 'text':
					default:
						if ($condValue == "" || $condValue == "NULL")
							$buf .= "-$fieldNameEs:[* TO *] ";
						else
							$buf .= "$fieldNameEs:".$this->escapeValue($condValue);
						break;
					}
					break;

				case 'is_not_equal':
					switch ($field["type"])
					{
					case 'object_reference':
						/*
						// TODO: review
						$tmp_cond_str = "";
						if ($field['subtype'] && $ref_field)
						{
							$tmpobj = new CAntObject($this->dbh, $field['subtype']);
							$ol = new CAntObjectList($this->dbh, $field['subtype'], $this->objList->user, $conds);
							$ol->addCondition("and", $ref_field, $operator, $condValue);
							$tmp_obj_cnd_str = $ol->buildConditionString($conds);

							if ($condValue == "" || $condValue == "NULL")
							{
								$buf .= " ".$this->obj->object_table.".$fieldNameEs not in (select id from ".$tmpobj->object_table."
																							where $tmp_obj_cnd_str) ";
							}
							else
							{
								$buf .= " ".$this->obj->object_table.".$fieldNameEs in (select id from ".$tmpobj->object_table."
																							where $tmp_obj_cnd_str) ";
							}
						}
						*/
						break;
					case 'object_multi':
					case 'object':
						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= "$fieldNameEs:[* TO *] ";
						}
						else
						{
							if ($field['subtype'])
							{
									$buf .= "-$fieldNameEs:\"$condValue\" "; // Numeric id
							}
							else
							{
								$parts = explode(":", $condValue); // obj_type:object_id

								if (count($parts)==2)
								{
									$buf .= "-$fieldNameEs:\"".$parts[0].":".$parts[1]."\" ";
								}
								else if (count($parts)==1) // only query assocaited type
								{
									$buf .= "-$fieldNameEs:".$objDef->obj_type."\:* ";
								}
							}
						}
						break;
					case 'fkey_multi':
					case 'fkey':
						$tmp_cond_str = "";
						/*
						if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
						{
							$children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
							$tmp_cond_str = "";
							foreach ($children as $child)
							{
								if ($tmp_cond_str) $tmp_cond_str .= " OR ";
								$tmp_cond_str .= "$fieldNameEs:\"$child\"";
							}
							if ($tmp_cond_str)
								$tmp_cond_str = "($tmp_cond_str)";
						}
						else */
						if ($condValue && $condValue!="NULL")
						{
							$tmp_cond_str = "$fieldNameEs:\"$condValue\"";
						}

						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= "$fieldNameEs:[* TO *] ";
						}
						else if ($tmp_cond_str)
						{
							$buf .= "-$tmp_cond_str ";
						}
						break;

					case 'bool':
						if ($condValue == "" || $condValue == "f" || $condValue == "NULL")
						{
							$buf .= "$fieldNameEs:true ";
						}
						else
						{
							$buf .= "$fieldNameEs:false ";
						}
						break;

					case 'number':
					case 'text':
					default:
						if ($condValue == "" || $condValue == "NULL")
							$buf .= "$fieldNameEs:[* TO *] ";
						else
							$buf .= "-$fieldNameEs:".$this->escapeValue($condValue);
						break;
					}
					break;

				case 'is_greater':
					switch ($field["type"])
					{
					case 'object_multi':
					case 'object':
					case 'fkey_multi':
						break;
					case 'text':
						break;
					case 'number':
						$buf .= "$fieldNameEs:{".$condValue." TO *} ";
						break;
					case 'date':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:{".gmdate("Ymd\\TG:i:s", $time)." TO *} ";
						break;
					case 'timestamp':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:{".gmdate("Ymd\\TG:i:s", $time)." TO *} ";
						break;
					default:
						break;
					}
					break;
				case 'is_less':
					switch ($field["type"])
					{
					case 'object_multi':
					case 'object':
					case 'fkey_multi':
						break;
					case 'text':
						break;
					case 'number':
						$buf .= "$fieldNameEs:{* TO $condValue} ";
						break;
					case 'date':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:{* TO ".gmdate("Ymd\\TG:i:s", $time)."} ";
						break;
					case 'timestamp':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:{* TO ".gmdate("Ymd\\TG:i:s", $time)."} ";
						break;
					default:
						break;
					}
					break;
				case 'is_greater_or_equal':
					switch ($field["type"])
					{
					case 'object_multi':
					case 'object':
					case 'fkey_multi':
						break;
					case 'text':
						break;
					case 'number':
						$buf .= "$fieldNameEs:[$condValue TO *] "; // square brackets are inclusive
						break;
					case 'date':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s", $time)." TO *] ";
						break;
					case 'timestamp':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s", $time)." TO *] ";
						break;
					default:
						break;
					}
					break;
				case 'is_less_or_equal':
					switch ($field["type"])
					{
					case 'object_multi':
					case 'object':
					case 'fkey_multi':
						break;
					case 'text':
						break;
					case 'number':
						$buf .= "$fieldNameEs:[* TO $condValue] "; // square brackets are inclusive
						break;
					case 'date':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:[* TO ".gmdate("Ymd\\TG:i:s", $time)."] ";
						break;
					case 'timestamp':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:[* TO ".gmdate("Ymd\\TG:i:s", $time)."] ";
						break;
					default:
						break;
					}
					break;
				case 'begins':
				case 'begins_with':
					switch ($field["type"])
					{
					case 'text':
						$buf .= "$fieldNameEs:".strtolower($this->escapeValue($condValue))."* ";
						break;
					default:
						break;
					}
					break;
				case 'contains':
					switch ($field["type"])
					{
					case 'text':
						$buf .= "$fieldNameEs:*".strtolower($this->escapeValue($condValue))."* ";
						break;
					default:
						break;
					}
					break;
				}
				
				// New system to added to group "or" statements
				if ($blogic == "and")
				{
					if ($buf)
					{
						if ($cond_str) 
							$cond_str .= ") ".strtoupper($blogic)." (";
						else
							$cond_str .= " ( ";
						$inOrGroup = true;
					}

					// Alternative approach to reduce unneeded parentheses ()
					/*
					if ($buf)
					{
						if ($next_blogic == "or")
						{
							if ($cond_str && $inOrGroup)
								$cond_str .= ") ".strtoupper($blogic)." (";
							else if ($cond_str && !$inOrGroup)
								$cond_str .= strtoupper($blogic)." ( ";
							else
								$cond_str .= " ( ";
							$inOrGroup = true;
						}
						else if ($inOrGroup)
						{
							$cond_str .= ") ".strtoupper($blogic)." ";
							$inOrGroup = false;
						}
					}
					*/
				}
				else if ($cond_str && $buf) // or
					$cond_str .= " ".strtoupper($blogic)." ";

				// Fix problem with lucene not being able to interpret pure negative queries - change (-field) to (*:* -field)
				if (substr($buf, 0, 1) == "-")
					$buf = "*:* ".$buf;
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
	 * Private function used to retrieve the document/type used for storing settings
	 *
	 * Get settings document - everything is stored as properties in doc 1
	 *
	 * @return array Associative array of settings
	 */
	private function settingsGetDoc()
	{
		try
		{
			$type = $this->getIndex()->getType("keyval_data");
			$resultSet = $type->search("_id:1"); // For some reason id is not indexing right so use internal _id
			//echo "<pre>settingsGetDoc: ".var_export($resultSet, true)."</pre>";
			$num = $resultSet->getTotalHits();
			if ($num)
			{
				$results = $resultSet->getResults();
				if (count($results))
					$ret = $results[0]->getData();
			}
		}
		catch (Elastica_Exception_Response $ex)
		{
			// Settings type probably does not exist
			$ret = false;
		}

		return $ret;
	}

	/**
	 * Place a value in the key-value store
	 *
	 * @param string $key The unique key used to access this value
	 * @param string $value The value to store under the given key
	 * @param mixed false on failure, value on success
	 */
	public function putValue($key, $value)
	{
		$ret = true;

		try
		{
			$obj_data = $this->settingsGetDoc();
			if (!is_array($obj_data))
				$obj_data = array("id"=>"1");
			$obj_data[$key] = $value;

			$type = $this->getType("keyval_data");
			$doc = new Elastica_Document(1, $obj_data);
			$resp = $type->addDocument($doc);
			//echo "<pre>settingsSet ".var_export($resp, true)."</pre>";
			$this->getIndex()->refresh();
			if ($resp->hasError())
				$ret = false;
		}
		catch (Elastica_Exception_Response $ex)
		{
			$ret = false;
		}

		return $ret;
	}

	/**
	 * Delete a key/value entry
	 *
	 * @param string $key The unique key used to access this value
	 * @return true on success, false on failure
	 */
	public function deleteValue($key)
	{
		$ret = true;

		try
		{
			$obj_data = $this->settingsGetDoc();
			if (!is_array($obj_data))
				$obj_data = array("id"=>"1");
			$obj_data[$key] = "";

			$type = $this->getType("keyval_data");
			$doc = new Elastica_Document(1, $obj_data);
			$resp = $type->addDocument($doc);
			//echo "<pre>settingsSet ".var_export($resp, true)."</pre>";
			$this->getIndex()->refresh();
			if ($resp->hasError())
				$ret = false;
		}
		catch (Elastica_Exception_Response $ex)
		{
			$ret = false;
		}

		return $ret;
	}

	/**
	 * Get a value from the key/value store
	 *
	 * @param string $key The unique key used to access this value
	 * @return mixed value on success, false on failure
	 */
	public function getValue($key)
	{
		$data = $this->settingsGetDoc();

		if (!is_array($data))
			return false;

		if (!isset($data[$key]))
			return false;

		return $data[$key];
	}

	/**
	 * Unescape a name pulled from the index
	 *
	 * @param string $fname The field name to escape
	 * @param bool $sortable If set to true, then created a sortable version of this field name (string - not tokenized)
	 */
	private function unescapeField($fname, $sortable=false)
	{
		$ret = $fname;

		if ($fname == "oid")
			$ret = "id";

		// Return raw system or reserved fields
		if ($fname == "database" || $fname == "type" || $fname == "f_deleted" || $fname == "revision" || $fname == "idx_private_owner_id")
		{
			return $fname;
		}

		$pos = strrpos($fname, "_");
		if ($pos)
		{
			$ret = substr($fname, 0, $pos);
		}

		return $ret;
	}

	/**
	 * Escape field name because we used field name postfix to determine the type of field being stored
	 *
	 * @param AntApi_Object $objDef Instance of object for definition
	 * @param string $fname The field name to escape
	 * @param bool $sortable If set to true, then created a sortable version of this field name (string - not tokenized)
	 */
	private function escapeField($objDef, $fname, $sortable=false, $facet=false)
	{
		$ret = "";

		$field = $objDef->getField($fname);
		if (!$field)
			return $ret;

		if ($fname == "id")
			return "oid";

		// Return raw system or reserved fields
		if ($fname == "database" ||  $fname == "f_deleted" || $fname == "revision" || $fname == "idx_private_owner_id") // $fname == "type" ||
			return $fname;

		switch ($field['type'])
		{
		case 'integer':
		case 'fkey':
			$ret = $fname."_i";
			break;
		case 'fkey_multi':
			$ret = $fname."_imv";
			break;
		case 'date':
		case 'timestamp':
			$ret = $fname."_dt";
			break;
		case 'number':
			switch ($field['subtype'])
			{
			case 'double':
			case 'double precision':
			case 'float':
			case 'real':
				$ret = $fname."_d";
				break;
			case 'integer':
				$ret = $fname."_i";
				break;
			default: // long
				$ret = $fname."_l";
				break;
			}
			break;
		case 'boolean':
		case 'bool':
			$ret = $fname."_b";
			break;
		case 'object_multi':
			$ret = $fname."_smv";
			break;
		case 'object':
			$ret = $fname."_s";
			break;
		case 'text':
		default:
			//if ($field['subtype']) // limited size
				//$ret = $fname."_s";
			//else
			if ($sortable) // looking for a sortable version
				$ret = $fname."_tsort";
			else if  ($facet && $field['subtype'])
				$ret = $fname."_s";
			else
				$ret = $fname."_t";
			break;
		}

		return $ret;
	}

	/**
	 * Escape query values (for dynamic type saving in elastic)
	 *
	 * @param string $string The value to escape
	 * @return string The excaped version of the string
	 */
	private function escapeValue($string)
    {
        $match = array('\\', '+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '"', ';', ' ');
        $replace = array('\\\\', '\\+', '\\-', '\\&', '\\|', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\*', '\\?', '\\:', '\\"', '\\;', '\\ ');
        $string = str_replace($match, $replace, $string);
 
        return $string;
    }

	/**
	 * Get index - and verify schema
	 */
	public function getIndex()
	{
		if (!$this->eIndex)
		{
			$this->eIndex = $this->eClient->getIndex($this->indexName);

			// Check to see if the index exists by getting the schema
			try
			{
				$type = $this->eIndex->getType("keyval_data");
				$resultSet = $type->search("_id:1"); // For some reason id is not indexing right so use internal _id
			}
			catch (Exception $ex)
			{
				if (!$this->eIndex->exists())
				{
					$this->createIndex($this->indexName);
				}
			}
		}

		return $this->eIndex;
	}

	/**
	 * Create index and set mapping
	 *
	 * @param Elastica_Index $index A newly created index to set mapping for
	 */
	public function createIndex($indexname)
	{
		$mapping = array(
			"mappings" => array(
				"_default_" => array(
					"dynamic_templates" => array(
						array(
							"ant_int"	=> array(
								"match" => "*_i",
								"mapping" => array(
									"type" => "integer",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_dbl"	=> array(
								"match" => "*_d",
								"mapping" => array(
									"type" => "double",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_imv"	=> array(
								"match" => "*_imv",
								"mapping" => array(
									"type" => "integer",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_long"	=> array(
								"match" => "*_l",
								"mapping" => array(
									"type" => "long",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_string"	=> array(
								"match" => "*_s",
								"mapping" => array(
									"type" => "string",
									"analyzer" => "string_lowercase",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_t"	=> array(
								"match" => "*_t",
								"mapping" => array(
									"type" => "string",
									"index" => "analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_tsort"	=> array(
								"match" => "*_tsort",
								"mapping" => array(
									"type" => "string",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_smv"	=> array(
								"match" => "*_smv",
								"mapping" => array(
									"type" => "string",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_date"	=> array(
								"match" => "*_dt",
								"mapping" => array(
									"type" => "date",
									"index" => "analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_b"	=> array(
								"match" => "*_b",
								"mapping" => array(
									"type" => "boolean",
									"index" => "analyzed",
									"store" => "yes",
								),
							),
						),
					),
				),
			),
		);

		try 
		{
			$ret = $this->eClient->request($indexname, Elastica_Request::POST, $mapping);
		} 
		catch(Elastica_Exception_Response $e) 
		{
			AntLog::getInstance()->error("ERROR CREATING INDEX $indexname: " . $e->getMessage());
		}
	}
}
