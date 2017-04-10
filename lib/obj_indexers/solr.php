<?php
class CAntObjectIndexSolr extends CAntObjectIndex
{
	var $solrClient = null;

	public function __construct($dbh, $obj) 
	{ 
        parent::__construct($dbh, $obj); 

		// Increment this if major changes are made to the way objects are indexed
		// causing them to be reindexed
		$this->engineRev = 2; 

		$options = array
		(
			'hostname' => ANT_INDEX_SOLR_HOST,
			'login'    => "admin",
			'password' => "admin",
			'port'     => 8983,
			'timeout'  => 30
		);

		$this->solrClient = new SolrClient($options);

		// fields that will be indexed differently
		$this->specialFields = array("database", "type", "f_deleted", "revision", "idx_private_owner_id"); 

		// This index returns full dataset in result set so objects do not have to be reloaded
		$this->cachable = true;
    }

	/*************************************************************************************
	*	Function:		indexObject	
	*
	*	Purpose:		Index an object
	*
	*	Arguments:		obj - CAntObject
	**************************************************************************************/
	function indexObject($obj, $commit=true)
	{
		global $G_OBJ_IND_EXISTS;

		if (!$obj)
			return false;

		if (!$obj->id || !$obj->object_type_id)
			return false;

		$ret = true;
		$dbh = $this->dbh;
		$doc = new SolrInputDocument();

		$doc->addField('id', $this->dbh->dbname.'.'.$obj->object_type.'.'.$obj->id);
		$doc->addField('oid', $obj->id);
		$doc->addField('database', $this->dbh->dbname);
		$doc->addField('type', $obj->object_type);

		$noIndex = array("database", "type", "oid", "id"); 

		$buf = "";
		$snippet = "";
		$fields = $obj->fields->getFields();
		if (is_array($fields) && count($fields))
		{
			foreach ($fields as $field=>$fdef)
			{
				if (in_array($field, $noIndex))
					continue; // Skip

				switch ($fdef['type'])
				{
				case 'fkey_multi':
					$vals = $obj->getValue($field);
					if (is_array($vals) && count($vals))
					{
						foreach ($vals as $val)
						{
							$doc->addField($this->escapeField($field), $val);
						}
					}
					break;
				case 'object_multi':
					$vals = $obj->getValue($field);
					if (is_array($vals) && count($vals))
					{
						foreach ($vals as $val)
						{
							$doc->addField($this->escapeField($field), $val);
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
							$val = gmdate("Y-m-d\\TG:i:s\\Z", time());
						else
							$val = gmdate("Y-m-d\\TG:i:s\\Z", strtotime($val));

						$doc->addField($this->escapeField($field), $val);
						// Add string version of field for wildcard queries
						$doc->addField($this->escapeField($field)."_s", $val);
					}
					break;
				case 'fkey':
				case 'number':
					$val = $obj->getValue($field);
					if (is_numeric($val))
						$doc->addField($this->escapeField($field), $val);
					break;
				case 'object':
					$val = $obj->getValue($field);
					if ($val)
						$doc->addField($this->escapeField($field), $val);

					break;
				case 'boolean':
				case 'bool':
					$val = $obj->getValue($field);
					if ($val=='t')
						$val = 'true';
					else
						$val = 'false';

					$doc->addField($this->escapeField($field), $val);
					break;
				case 'text':
				default:
					$val = $obj->getValue($field);
					if ($val)
					{
						//if ($fdef['subtype']) // strings will only be indexed in lower case
							//$val = strtolower($val);

						$doc->addField($this->escapeField($field), $val);
						//if (!$fdef['subtype']) // text field needs a sort
							$doc->addField($field."_tsort", $val);
						if ($fdef['subtype']) // save original for facets
							$doc->addField($field."_s", $val);
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
					$fval = $obj->getFVals($field);
					if ($fval)
						$obj_data[$field."_fval_s"] = json_encode($fval);
					break;
				}
			}

			if ($obj->isPrivate() && $obj->owner_id)
			{
				$doc->addField("idx_private_owner_id", $obj->owner_id);
			}

			try
			{
				$this->solrClient->addDocument($doc);
				//if ($commit)
					//$ret = $this->commit();
			}
			catch (SolrClientException $ex)
			{
				//echo $ex->getMessage()."\n";
				$this->lastError = $ex->getMessage();
				$ret = false;
			}
		}

		return $ret;
	}

	/*************************************************************************************
	*	Function:		removeObject
	*
	*	Purpose:		Remove an object from the index
	*
	*	Arguments:		obj - CAntObject
	**************************************************************************************/
	function removeObject($obj)
	{
		$ret = $this->solrClient->deleteById($this->dbh->dbname.'.'.$obj->object_type.'.'.$obj->id);
		$this->solrClient->commit(); // not sure if this is needed or not
		return true;
	}

	/*************************************************************************************
	*	Function:		commit
	*
	*	Purpose:		Commit changes to index
	**************************************************************************************/
	function commit()
	{
		try
		{
			$resp = $this->solrClient->commit();
			$ret = true;
		}
		catch (SolrException $ex)
		{
			//echo "Commit failed: ".$ex->getMessage();
			//sleep(5); // sleep for 5 seconds waiting for the server to finish the commit if large
			$ret = false;
		}

		return $ret;
	}

	/*************************************************************************************
	*	Function:		optimize
	*
	*	Purpose:		Defrag index
	**************************************************************************************/
	function optimize()
	{
		try
		{
			$this->solrClient->optimize();
		}
		catch (SolrException $ex)
		{
		}

		return true;
	}

    function queryObjects($objList, $conditionText="", $conditions=array(), $orderBy=array(), $START=0, $LIMIT=1000)
	{
        parent::queryObjects($objList, $conditionText, $conditions, $orderBy, $START, $LIMIT); 

		$query = "";
		$queryObject = new SolrQuery();

		// Build condition string
		if (count($conditions) || $conditionText!="")
		{
			$query = $this->buildConditionString($conditionText, $conditions);
		}

		// Add order by
		$order_cnd_str = ""; // used for caching uname below
		if (count($orderBy))
		{
			foreach ($orderBy as $sortObj)
			{
				// Replace name field to order by full name with path
				if ($this->objList->obj->fields->parentField && $this->objList->obj->fields->getField("path"))
					$order_fld = str_replace($this->obj->fields->listTitle, $this->obj->fields->listTitle."_full", $sortObj->fieldName);

				$fld = $this->obj->fields->getField($sortObj->fieldName);
				if ($fld)
				{
					// Use non-analyzed field for sorting
					$fld_name = $this->escapeField($sortObj->fieldName, true);

					$order_cnd_str .= $fld_name." ".$sortObj->direction;

					if (strtolower($sortObj->direction)=="asc")
						$order = SolrQuery::ORDER_ASC;
					else
						$order = SolrQuery::ORDER_DESC;
					$queryObject->addSortField($fld_name, $order);
				}
			}
		}

		// Set query and fitler
		if ($query && $this->objList->hideDeleted)
		{
			$queryObject->setQuery($query);
			$queryObject->addFilterQuery("database:".$this->dbh->dbname." AND type:".$this->obj->object_type." AND f_deleted:false");
			$query_cache_str = $this->obj->object_type.":".$query . ":f_deleted:false";
		}
		else if (!$query && $this->objList->hideDeleted && $this->obj->fields->getField("f_deleted"))
		{
			$queryObject->setQuery("id:[* TO *]");
			$queryObject->addFilterQuery("database:".$this->dbh->dbname." AND type:".$this->obj->object_type." AND f_deleted:false");
			$query_cache_str = $this->obj->object_type."::f_deleted:false";
		}
		else
		{
			if ($query)
				$queryObject->setQuery($query);
			else
				$queryObject->setQuery("id:[* TO *]");
			$queryObject->addFilterQuery("database:".$this->dbh->dbname." AND type:".$this->obj->object_type."");
			$query_cache_str = $this->obj->object_type.":$query";
		}

		// Add facet
		if (is_array($this->facetFields) && count($this->facetFields))
		{
			$queryObject->setFacet(true);
			foreach ($this->facetFields as $fldname=>$fldcnt)
			{
				$queryObject->addFacetField($this->escapeField($fldname, false, true));
				$queryObject->setFacetMinCount($fldcnt, $this->escapeField($fldname, false, true));
			}
		}

		// Add aggregates
		if (is_array($this->aggregateFields) && count($this->aggregateFields))
		{
			$queryObject->setStats(true);
			foreach ($this->aggregateFields as $fldname=>$type)
			{
				$queryObject->addStatsField($this->escapeField($fldname, false, true));
			}
		}

		$this->objList->lastQuery = $query_cache_str;

		// Check for query cache
		if ($order_cnd_str)
			$query_cache_str .= ":$order_cnd_str";
		if ($START)
			$query_cache_str .= ":start=$START";
		if ($LIMIT)
			$query_cache_str .= ":limit=$LIMIT";
		$fPullCached = $this->objList->getListCache($query_cache_str);

		if (!$fPullCached) // query index if not already cached
		{
			if ($START)
				$queryObject->setStart($START);
			if ($LIMIT)
				$queryObject->setRows($LIMIT);

			try
			{
				$query_response = $this->solrClient->query($queryObject);
				$response = $query_response->getResponse();
			}
			catch (SolrClientException $ex)
			{
				$this->lastError = $ex->getMessage();
				return -1;
			}

			$this->objList->total_num = $response->response->numFound;

			// Set facet counts
			if ($response->facet_counts && $response->facet_counts->facet_fields)
			{
				foreach($response->facet_counts->facet_fields as $fname=>$terms) 
				{
					$fname = $this->unescapeField($fname);
					if (is_array($this->objList->facetCounts[$fname]))
						$this->objList->facetCounts[$fname] = array();

					$this->objList->facetCounts[$fname] = $this->slrObjToArray($terms);
				}
			}

			// Set stats counts
			if ($response->stats && $response->stats->stats_fields)
			{
				foreach($response->stats->stats_fields as $fname=>$stats) 
				{
					$fname = $this->unescapeField($fname);
					if (is_array($this->objList->aggregateCounts[$fname]))
						$this->objList->aggregateCounts[$fname] = array();

					// Conversion for aggregate avg
					$stats->avg = $stats->mean;

					$this->objList->aggregateCounts[$fname] = $this->slrObjToArray($stats);
				}
			}

			// Set returned objects/documents
			$results = $response->response->docs;
			for ($i = 0; $i < count($results); $i++)
			{
				$id = $results[$i]->oid;
				$data = $this->slrObjToArray($results[$i]);

				if (isset($data['owner_id']))
					$is_owner = ($this->objList->user->id==$data['owner_id'])?true:false;
				else if (isset($data['user_id']))
					$is_owner = ($this->objList->user->id==$data['user_id'])?true:false;
				else
					$is_owner = false;

				if ($id)
				{
					$ind = count($this->objList->objects);
					$this->objList->objects[$ind] = array();
					$this->objList->objects[$ind]['id'] = $id;
					$this->objList->objects[$ind]['obj'] = null;
					$this->objList->objects[$ind]['revision'] = ($data['revision']) ? $data['revision'] : 1;
					$this->objList->objects[$ind]['owner_id'] = (isset($data['owner_id'])) ? $data['owner_id'] : null;
					$this->objList->objects[$ind]['data_min'] = $data;
					$this->objList->objects[$ind]['data'] = $data; //$data;
				}
			}

			$this->objList->cacheList($query_cache_str);
		}
	}

	function buildConditionString($conditionText, $conditions)
	{
		$dbh = $this->dbh;
		$cond_str = "";

		// Advanced Search
		// -------------------------------------------------------------
		$adv_cond = $this->buildAdvancedConditionString($conditions);

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

	function buildAdvancedConditionString($conditions)
	{
		if ($this->objList->user)
			$userid = $this->objList->user->id;
		$dbh = $this->dbh;
		$cond_str = "";
		$inOrGroup = false;

		// Define if we will pull from deleted, non-deleted, or both indexes
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
					$this->objList->hideDeleted = false;
				}
			}
		}

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
				$fieldNameEs = $this->escapeField($fieldName);
				$operator = $cond['operator'];
				$condValue = $cond['value'];
				$buf = "";

				// Look for associated object conditions
				if (strpos($fieldName, '.'))
				{
					$parts = explode(".", $fieldName);
				}
				else
				{
					$parts[0] = $fieldName;
				}

				$field = $this->obj->fields->getField($parts[0]);
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


				// Replace variables - DEPRICATED - now handled in CAntObjectList
				// -----------------------------------------------------

				/*
				// Replace current user
				if (((($field['type'] == "fkey" || $field['type'] == "fkey_multi") 
						&& $field['subtype'] == "users") || ($field['type'] == "object" && $field['subtype'] == "user")) 
						&& $condValue=='-3' && $userid)
				{
					$condValue = $userid;
				}

				// Replace current user team
				if ($field['type'] == "fkey" && $field['subtype'] == "user_teams" && $condValue=='-3' && $USERTEAM)
					$condValue = $USERTEAM;

				// Replace time/date
				*/

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
						$tmp_cond_str = "";
						if ($field['subtype'] && $ref_field)
						{
							$tmp_cond_str = "";
							$ol = new CAntObjectList($this->dbh, $field['subtype'], $this->objList->user);
							$ol->addCondition("and", $ref_field, $operator, $condValue);
							$num = $ol->getObjects();
							for ($i = 0; $i < $num; $i++)
							{
								$omin = $ol->getObjectMin($i);
								if ($tmp_cond_str) $tmp_cond_str .= " OR ";
								$tmp_cond_str .= "$fieldNameEs:\"".$omin['id']."\"";

							}

							if ($tmp_cond_str)
								$buf .= $tmp_cond_str;
						}
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
						if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
						{
							$children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
							$tmp_cond_str = "";
							foreach ($children as $child)
							{
								if ($tmp_cond_str) $tmp_cond_str .= " OR ";
								$tmp_cond_str .= "$fieldNameEs:\"$child\" ";
							}
							if ($tmp_cond_str)
								$tmp_cond_str = "($tmp_cond_str)";
						}
						else if ($condValue && $condValue!="NULL")
						{
							$tmp_cond_str = "$fieldNameEs:\"$condValue\"";
						}

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
								$buf .= "$fieldNameEs:".gmdate("Y-m-d\\TG:i:s\\Z", $time)." ";
						}
						break;

					case 'number':
					case 'text':
					default:
						if ($condValue == "" || $condValue == "NULL")
							$buf .= "-$fieldNameEs:[* TO *] ";
						else
							$buf .= "$fieldNameEs:".$this->escapeSolrValue($condValue);
						break;
					}
					break;

				case 'is_not_equal':
					switch ($field["type"])
					{
					case 'object_reference':
						$tmp_cond_str = "";
						if ($field['subtype'] && $ref_field)
						{
							$tmp_cond_str = "";
							$ol = new CAntObjectList($this->dbh, $field['subtype'], $this->objList->user);
							$ol->addCondition("and", $ref_field, $operator, $condValue);
							$num = $ol->getObjects();
							for ($i = 0; $i < $num; $i++)
							{
								$omin = $ol->getObjectMin($i);
								if ($tmp_cond_str) $tmp_cond_str .= " AND ";
								$tmp_cond_str .= "-$fieldNameEs:\"".$omin['id']."\"";

							}

							if ($tmp_cond_str)
								$buf .= $tmp_cond_str;
						}
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
									$buf .= "-$fieldNameEs:".$this->obj->object_type."\:* ";
								}
							}
						}
						break;
					case 'fkey_multi':
					case 'fkey':
						$tmp_cond_str = "";
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
						else if ($condValue && $condValue!="NULL")
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
							$buf .= "-$fieldNameEs:".$this->escapeSolrValue($condValue);
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
							$buf .= "$fieldNameEs:{".gmdate("Y-m-d\\TG:i:s\\Z", $time)." TO *} ";
						break;
					case 'timestamp':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:{".gmdate("Y-m-d\\TG:i:s\\Z", $time)." TO *} ";
						break;
					default:
						//$buf .= " $fieldNameEs>'".$dbh->Escape($condValue)."' ";
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
							$buf .= "$fieldNameEs:{* TO ".gmdate("Y-m-d\\TG:i:s\\Z", $time)."} ";
						break;
					case 'timestamp':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:{* TO ".gmdate("Y-m-d\\TG:i:s\\Z", $time)."} ";
						break;
					default:
						//$buf .= " $fieldName<'".$dbh->Escape($condValue)."' ";
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
							$buf .= "$fieldNameEs:[".gmdate("Y-m-d\\TG:i:s\\Z", $time)." TO *] ";
						break;
					case 'timestamp':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:[".gmdate("Y-m-d\\TG:i:s\\Z", $time)." TO *] ";
						break;
					default:
						//$buf .= " $fieldName>='".$dbh->Escape($condValue)."' ";
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
							$buf .= "$fieldNameEs:[* TO ".gmdate("Y-m-d\\TG:i:s\\Z", $time)."] ";
						break;
					case 'timestamp':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:[* TO ".gmdate("Y-m-d\\TG:i:s\\Z", $time)."] ";
						break;
					default:
						//$buf .= " $fieldName<='".$dbh->Escape($condValue)."' ";
						break;
					}
					break;
				case 'begins':
				case 'begins_with':
					switch ($field["type"])
					{
					case 'text':
						$buf .= "$fieldNameEs:".strtolower($this->escapeSolrValue($condValue))."* ";
						break;
					default:
						break;
					}
					break;
				case 'contains':
					switch ($field["type"])
					{
					case 'text':
						$buf .= "$fieldNameEs:*".strtolower($this->escapeSolrValue($condValue))."* ";
						break;
					default:
						break;
					}
					break;
				case 'day_is_equal':
					if ($field["type"] == "date" || $field["type"] == "timestamp")
					{
						switch ($condValue)
						{
						case '<%current_day%>':
							$tmpcond = date("d");
							break;
						default:
							$tmpcond = $condValue;
							break;
						}

						$buf .= $fieldNameEs."_s:*".$tmpcond."T* ";
					}
					break;
				case 'month_is_equal':
					if ($field["type"] == "date" || $field["type"] == "timestamp")
					{
						switch ($condValue)
						{
						case '<%current_month%>':
							$tmpcond = date("m");
							break;
						default:
							$tmpcond = $condValue;
							break;
						}
						$buf .= $fieldNameEs."_s:*-$tmpcond-* ";
					}
					break;
				case 'year_is_equal':
					if ($field["type"] == "date" || $field["type"] == "timestamp")
					{
						switch ($condValue)
						{
						case '<%current_year%>':
							$tmpcond = date("Y");
							break;
						default:
							$tmpcond = $condValue;
							break;
						}

						$buf .= "$fieldNameEs:[".gmdate("Y-m-d\\TG:i:s\\Z", strtotime("$tmpcond-01-01"))." TO ";
						$buf .= gmdate("Y-m-d\\TG:i:s\\Z", strtotime("$tmpcond-12-31"))."] ";
					}
					break;
				case 'last_x_days':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[NOW-".$condValue."DAYS TO NOW] ";
					}
					break;
				case 'last_x_weeks':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[NOW-".($condValue*7)."DAYS TO NOW] ";
					}
					break;
				case 'last_x_months':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[NOW-".$condValue."MONTHS TO NOW] ";
					}
					break;
				case 'last_x_years':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[NOW-".$condValue."YEARS TO NOW] ";
					}
					break;
				case 'next_x_days':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[NOW TO NOW+".$condValue."DAYS] ";
					}
					break;
				case 'next_x_weeks':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[NOW TO NOW+".($condValue*7)."DAYS] ";
					}
					break;
				case 'next_x_months':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[NOW TO NOW+".$condValue."MONTHS] ";
					}
					break;
				case 'next_x_years':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[NOW TO NOW+".$condValue."YEARS] ";
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

	/*************************************************************************************
	*	Function:		escapeField	
	*
	*	Purpose:		Add a postfix to a feild depending on type. This is how solr knows
	*					how to add and index a field.
	*
	*	Arguments:		fname - string: field name
	**************************************************************************************/
	function escapeField($fname, $sortable=false, $facet=false)
	{
		$ret = "";

		$field = $this->obj->fields->getField($fname);
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

	/*************************************************************************************
	*	Function:		unescapeField	
	*
	*	Purpose:		Remove postfix from an escaped field. Example: myint_i will ret myint
	*
	*	Arguments:		fname - string: escaped field name. 
	**************************************************************************************/
	function unescapeField($fname)
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

	/*************************************************************************************
	*	Function:		slrObjToArray	
	*
	*	Purpose:		Conver a solrObject (usualy a document response) to an array and
	*					unescape all the variable names.
	*
	*	Arguments:		slrObj - SolrObject: valid object to convert to an assoc array
	**************************************************************************************/
	function slrObjToArray($slrObj)
	{
		if (!$slrObj)
			return array();

		$ret = array();
		foreach($slrObj as $var=>$value) 
		{
			$fname = $this->unescapeField($var);
			$fdef = $this->obj->fields->getField($fname);
			switch ($fdef['type'])
			{
			case 'bool':
			case 'boolean':
				$value = ($value == "true") ? 't' : 'f';
				break;
			}
			$ret[$fname] = $value;
    	}

		return $ret;
	}

	/*************************************************************************************
	*	Function:		escapeSolrValue	
	*
	*	Purpose:		Escape query values
	*
	*	Arguments:		string - string: valid to escape
	**************************************************************************************/
	function escapeSolrValue($string)
    {
        $match = array('\\', '+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '"', ';', ' ');
        $replace = array('\\\\', '\\+', '\\-', '\\&', '\\|', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\*', '\\?', '\\:', '\\"', '\\;', '\\ ');
        $string = str_replace($match, $replace, $string);
 
        return $string;
    }
}
?>
