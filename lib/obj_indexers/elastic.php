<?php
/**
 * This indexer uses ElasitSearch as the backend to query objects
 *
 * @author joe <sky.stebnicki@aereus.com>
 */

/**
 * Indexer implementation
 */
class CAntObjectIndexElastic extends CAntObjectIndex
{
	/**
	 * Handle to elastic search client
	 *
	 * @var Elastica_Client
	 */
	private $esClient = null;

	/**
	 * Handle to elastic search index for active (non-deleted) objects
	 *
	 * @var Elastica_Index
	 */
	private $esIndexAct = null;

	/**
	 * Handle to elastic search index for deleted objects
	 *
	 * @var Elastica_Index
	 */
	private $esIndexDel = null;

	/**
	 * Handle to elastic search index alias for queries
	 *
	 * This cannot be used for updates of any kind because it is an alias
	 * to the *_act and *_del indices for this account.
	 *
	 * @var Elastica_Index
	 */
	private $esIndexAlias = null;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to active account database
	 * @param CAntObject $obj Object definition for this index
	 */
	public function __construct($dbh, $obj) 
	{ 
		// Increment this if major changes are made to the way objects are indexed
		// causing them to be reindexed
		$this->engineRev = 2; 

		// Set the indexid for elastic, this is constant and may never be changed
		$this->indexId = 2;

		// Initialize the client and the index for this object type and database
		if (defined("ANT_INDEX_ELASTIC_HOST"))
		{
			$this->esClient = new Elastica_Client(array('host'=>ANT_INDEX_ELASTIC_HOST));
		}

		// Make sure parent constructor is called
        parent::__construct($dbh, $obj); 
    }

	/**
	 * Initialize the index
	 *
	 * @param int $lastRev Revision of the last init, if 0 then never yet initialized
	 */
	public function init($lastRev=0)
	{
		if ($lastRev == 0)
		{
			$this->createIndices($this->dbh->dbname);
		}

		return true;
	}

	/**
	 * Refresh the index for real-time results
	 */
	public function commit()
	{
		try 
		{
			$this->getIndex()->refresh();
			$this->getIndex(true)->refresh();
		}
		catch (Elastica_Exception_Response $ex) 
		{
			AntLog::getInstance()->error("Elastic error trying to refresh: " . $ex->getMessage());
		}
	}

	/** 
	 * Index an object
	 *
	 * @param AntApi_Obj $obj The object to be indexed
	 * @param bool $refresh By default the index will be refreshed, but this can be skipped for perfomance
	 */
	public function indexObject($obj, $refresh=true)
	{
		global $G_OBJ_IND_EXISTS;

		if (!$obj->id || !$obj->object_type_id)
			return false;

		$ret = true;
		$dbh = $this->dbh;

		//$this->getIndexAlias(); // Make sure aliases exist
		$indx = $this->getIndex($obj->isDeleted());
		$type = $indx->getType($this->obj->object_type);
		
		$obj_data = array();
		$buf = "";
		$snippet = "";
		$fields = $obj->fields->getFields();
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
						$obj_data[$this->escapeField($field)] = $vals;
						$obj_data[$field."_tsort"] = $vals[0];
					}
					break;
				case 'object_multi':
					$vals = $obj->getValue($field);
					if (is_array($vals) && count($vals))
					{
						$obj_data[$this->escapeField($field)] = $vals;
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

						$obj_data[$this->escapeField($field)] = $val;
						// Add string version of field for wildcard queries
						$obj_data[$this->escapeField($field)."_s"] = $val_s;
					}
					break;
				case 'fkey':
				case 'number':
				//case 'integer':
					$val = $obj->getValue($field);
					if (is_numeric($val))
						$obj_data[$this->escapeField($field)] = $val;
					break;
				case 'object':
					$val = $obj->getValue($field);
					if ($val)
					{
						$obj_data[$this->escapeField($field)] = $val;
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

					$obj_data[$this->escapeField($field)] = $val;
					break;
				case 'text':
				default:
					$val = $obj->getValue($field);
					if ($val)
					{
						$obj_data[$this->escapeField($field)] = $val;
						$obj_data[$field."_tsort"] = $val;
						if ($fdef['subtype']) // save original for facets
							$obj_data[$field."_s"] = $val;
					}
					break;
				}

				//if ($obj->object_type == "email_thread" && $field=='num_attachments')
					//AntLog::getInstance()->info("Getting value for num_attachments on ".$obj->id." returned: " . $obj->getValue($field));

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
						$obj_data[$field."_fval_s"] = json_encode($fval);
					break;
				}
			}

			if ($obj->isPrivate() && $obj->owner_id)
			{
				$obj_data["idx_private_owner_id"] = $obj->owner_id;
			}

			try
			{
				$doc = new Elastica_Document($obj->id, $obj_data);
				$resp = $type->addDocument($doc);

				if ($refresh)
					$indx->refresh();

				// Check if we should purge from alternate deleted status index
				if ($obj->deletedStatusChanged())
				{
					$altInd = ($obj->isDeleted()) ? $this->getIndex() : $this->getIndex(true);
					$results = $this->esClient->deleteIds(array($obj->id), $altInd->getName(), $obj->object_type);
					
					if ($refresh)
						$altInd->refresh();
				}

				if ($resp->hasError())
					$ret = false;
			}
			catch (Elastica_Exception_Response $ex)
			{
				AntLog::getInstance()->error($ex->getMessage() . " TOBEINDEXED: " . 
											 var_export($doc, true) . " RAW: " . var_export($obj->getDataArray(), true));
				$ret = false;
			}
		}

		return $ret;
	}

	/**
	 * Remove an object from the index
	 *
	 * @param CAntObject $obj The object to remove
	 */
	public function removeObject($obj)
	{
		global $G_OBJ_IND_EXISTS;

		if (!$obj->id || !$obj->object_type_id)
			return false;

		$dbh = $this->dbh;

		try 
		{
			$this->esClient->deleteIds(array($obj->id), $this->getIndex()->getName(), $obj->object_type);
			$this->esClient->deleteIds(array($obj->id), $this->getIndex(true)->getName(), $obj->object_type);
		}
		catch (Elastica_Exception_Response $ex)
		{
			return false;
		}

		return true;
	}

	/**
	 * Query the index for objects.
	 *
	 * If conditions are set this function is responsible for building an executing the query.
	 *
	 * @param CAntObjectList $objList The list to populate
	 * @param string $conditionText Optional full text search
	 * @param array $conditions Array of an associative array of conditions
	 * @param array $orderBy Optional list of fields to order results by
	 * @param integer $START Start offset position. Defaults to 0.
	 * @param integer $LIMIT The maximum number of items to return with each page/set.
	 */
	public function queryObjects($objList, $conditionText="", $conditions=array(), $orderBy=array(), $START=0, $LIMIT=1000)
	{
        parent::queryObjects($objList, $conditionText, $conditions, $orderBy, $START, $LIMIT); 

		$esIndexAlias = $this->getIndexAlias();
		if ($this->esClient == null || $esIndexAlias == null)
			return false;

		$type = $esIndexAlias->getType($this->objList->obj_type);

		// Set default f_deleted condition
		if ($this->obj->fields->getField("f_deleted"))
		{
			$fDeletedCondSet = false;

			if (count($conditions))
			{
				foreach ($conditions as $cond)
				{
					if ($cond['field'] == "f_deleted")
						$fDeletedCondSet = true;
				}
			}

			if (!$fDeletedCondSet && $this->objList->hideDeleted)
				$conditions[] = array('blogic'=>"and", "field"=>"f_deleted", "operator"=>"is_equal", "value"=>'f');
		}

		// Build condition string
		$query = "";
		if (count($conditions) || $conditionText!="")
		{
			$query = $this->buildConditionString($conditionText, $conditions);
		}

		// Add order by
		$order_cnd = array();
		if (count($orderBy) && !$conditionText) // We do not sort full text queries
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
					if ($fld['type'] == "text" || $fld['type'] == "object" || $fld['type'] == "object_multi")
						$fld_name = $sortObj->fieldName."_tsort";
					else	
						$fld_name = $this->escapeField($sortObj->fieldName);

					$order_cnd[] = array($fld_name => array("order"=>strtolower($sortObj->direction)));
				}
			}
		}
		
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

			$query_cache_str = $this->objList->obj_type.":".$query . ":f_deleted:false";
		}
		else if (!$query && $this->objList->hideDeleted && $this->obj->fields->getField("f_deleted"))
		{
			$qbuf['constant_score'] = array();
			$qbuf['constant_score']['filter'] = array("term"=>array("f_deleted"=>"false"));

			$query_cache_str = $this->objList->obj_type."::f_deleted:false";
		}
		else
		{
			$qbuf = array("query_string"=>array("query"=>$query));
			$query_cache_str = $this->objList->obj_type.":$query";
		}
		$arrQuery = array();
		$arrQuery['query'] = $qbuf;

		//echo "<pre>";
		//echo var_export($arrQuery, true);
		//echo "</pre>";
		$this->objList->lastQuery = $arrQuery;

		// Check for query cache
		if (count($order_cnd))
			foreach ($order_cnd as $ofld=>$odir)
				$query_cache_str .= ":$ofld-$odir";
		if ($START)
			$query_cache_str .= ":start=$START";
		if ($LIMIT)
			$query_cache_str .= ":limit=$LIMIT";
        $pullCached = false;
		if (!$fPullCached) // query index if not already cached
		{
			$queryObject = new Elastica_Query();
			$queryObject->setRawQuery($arrQuery);
			if (count($order_cnd))
				$queryObject->setSort($order_cnd);
			if ($LIMIT)
				$queryObject->setLimit($LIMIT);
			if ($START)
				$queryObject->setFrom($START);

			// Add facet
			if (is_array($this->facetFields) && count($this->facetFields))
			{
				foreach ($this->facetFields as $fldname=>$fldcnt)
				{
					//echo "<pre>Adding: ".$fldname."</pre>";
					$facet = new Elastica_Facet_Terms($fldname."_term");
					$facet->setField($this->escapeField($fldname));
					$queryObject->addFacet($facet);
				}
			}

			// Add aggregates
			if (is_array($this->aggregateFields) && count($this->aggregateFields))
			{
				foreach ($this->aggregateFields as $fldname=>$funct)
				{
					$facet = new Elastica_Facet_Statistical($fldname."_stat");
					$facet->setField($this->escapeField($fldname));
					$queryObject->addFacet($facet);
				}
			}

			try
			{
				$resultSet = $type->search($queryObject);
			}
			catch (Elastica_Exception_Response $ex)
			{
				AntLog::getInstance()->error("Exception when trying to query " . $this->obj->object_type . " :" . $ex->getMessage());
				$this->objList->total_num = 0;
				return false;
				//echo "<pre>".$ex->getCode()." - ".$ex->getMessage()."</pre>";
				/*
				if ($ex->getCode() == 0) // type missing
				{
					$this->obj->createObjectTypeIndexElastic();
					$resultSet = $type->search($queryObject);
				}
				else
					return false;
				 */
			}

			$this->objList->total_num = $resultSet->getTotalHits();
			$results = $resultSet->getResults();

			for ($i = 0; $i < count($results); $i++)
			{
				$doc = $results[$i];
				$data = $this->unescapeData($doc->getData());

				$id = $doc->getId();

				if (isset($data['owner_id']))
					$is_owner = ($this->objList->user->id==$data['owner_id'])?true:false;
				else if (isset($data['user_id']))
					$is_owner = ($this->objList->user->id==$data['user_id'])?true:false;
				else
					$is_owner = false;

				$ind = count($this->objList->objects);
				$this->objList->objects[$ind] = array();
				$this->objList->objects[$ind]['id'] = $id;
				$this->objList->objects[$ind]['obj'] = null;
				$this->objList->objects[$ind]['revision'] = ($data['revision']) ? $data['revision'] : 1;
				$this->objList->objects[$ind]['owner_id'] = (isset($data['owner_id'])) ? $data['owner_id'] : null;
				$this->objList->objects[$ind]['data_min'] = $data;
				$this->objList->objects[$ind]['data'] = $data; //$data;
			}

			// Set facet counts
			$facets = $resultSet->getFacets();
			if (is_array($facets) && count($facets))
			{
				//echo "<pre>".var_export($facets, true)."</pre>";

				foreach ($facets as $facetname=>$fres)
				{
					// Set terms
					if ($fres['_type'] == "terms")
					{
						$fldname = substr($facetname, 0, -5); // remove _term from the name
						$this->objList->facetCounts[$fldname] = array();
						foreach ($fres['terms'] as $termres)
						{
							$this->objList->facetCounts[$fldname][$termres['term']] = $termres['count'];
						}
					}

					// Set aggregates
					if ($fres['_type'] == "statistical")
					{
						$fldname = substr($facetname, 0, -5); // Remove _stat from the name
						$this->objList->aggregateCounts[$fldname] = array();
						$this->objList->aggregateCounts[$fldname]["sum"] = $fres['total'];
						$this->objList->aggregateCounts[$fldname]["count"] = $fres['count'];
						$this->objList->aggregateCounts[$fldname]["avg"] = $fres['mean'];
						$this->objList->aggregateCounts[$fldname]["min"] = $fres['min'];
						$this->objList->aggregateCounts[$fldname]["max"] = $fres['max'];
					}
				}
			}
		}
	}

	/**
	 * Construct condition string
	 */
	function buildConditionString($conditionText, $conditions)
	{
		$dbh = $this->dbh;
		$cond_str = "";
		$ofields = $this->obj->fields->getFields();

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
			//$parts = explode(" ", $conditionText);
			//preg_match_all('~(?|"([^"]+)"|(\S+))~', $conditionText, $parts);
			//preg_match_all('/(?<!")\b\w+\b|(?<=")\b[^"]+/', $conditionText, $parts, PREG_PATTERN_ORDER);
			$parts = $this->queryStringToTerms($conditionText);
			foreach ($parts as $part)
			{
				if ($part)
				{
					// Add wild card
					if (strpos($part, " ") === false)
						$part .= '*';

					// Add quotes around email
					if (strpos($part, "@") !== false)
						$part = '"' . $part . '"';

					$txt_cond .= ($txt_cond) ? " AND " : "";
					$txt_cond .= $part;
				}
			}
			//$txt_cond = $conditionText;
		}

		if ($cond_str && $txt_cond)
			$cond_str .= " AND ($txt_cond) ";
		else if (!$cond_str && $txt_cond)
			$cond_str = $txt_cond;

		return $cond_str;
	}

	function buildAdvancedConditionString($conditions)
	{
		global $USERID;
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
				$operator = $cond['operator'];
				$condValue = $cond['value'];
				$buf = "";

				// Look for associated object conditions
				$parts = array($fieldName);
				if (strpos($fieldName, '.'))
					$parts = explode(".", $fieldName);

				$field = $this->obj->fields->getField($parts[0]);
				if (count($parts) > 1)
				{
					$fieldName = $parts[0];
					$ref_field = $parts[1];
					$field['type'] = "object_dereference";
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
						&& $condValue=='-3' && $USERID)
				{
					$condValue = $USERID;
				}

				// Replace current user team
				if ($field['type'] == "fkey" && $field['subtype'] == "user_teams" && $condValue=='-3' && $USERTEAM)
					$condValue = $USERTEAM;

				// Replace time/date
				 */

				if (!$field)
					continue; // Skip non-existant field

				// Convert now timestamp
				if ($condValue == "now" && ($field['type'] == "timestamp" || $field['type'] == "date"))
					$condValue = date("Y-m-d h:m:s A");

				// Escape field name
				$fieldNameEs = $this->escapeField($fieldName);

				// Build Query String
				// -----------------------------------------------------
				switch ($operator)
				{
				case 'is_equal':
					switch ($field["type"])
					{
					// TODO: add timestamp, time, and date types
					case 'object_dereference':
						if ($field['subtype'] && $ref_field)
						{
							$objList = new CAntObjectList($this->dbh, $field['subtype']);
							$objList->addCondition("and", $ref_field, $operator, $condValue);
							$objList->getObjects(0, 1000); // max 1000 references
							$tmp_cond_str = "";
							for ($l = 0; $l < $objList->getNumObjects(); $l++)
							{
								$odat = $objList->getObjectMin($l);

								if ($tmp_cond_str) $tmp_cond_str .= " OR ";
								$tmp_cond_str .= "$fieldNameEs:\"" . $odat['id'] . "\" ";
							}

							if ($tmp_cond_str)
								$buf .= " ($tmp_cond_str)";
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

					case 'boolean':
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

					case 'text':
						if ($condValue == "" || $condValue == "NULL")
							$buf .= "-$fieldNameEs:[* TO *] ";
						else
							$buf .= $this->escapeField($fieldName, false, true).":\"".$this->escapeValue($condValue) . '"';
						break;
					case 'number':
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
					case 'object_dereference':
						if ($field['subtype'] && $ref_field)
						{
							$objList = new CAntObjectList($this->dbh, $field['subtype']);
							$objList->addCondition("and", $ref_field, $operator, $condValue);
							$objList->getObjects(0, 1000); // max 1000 references
							$tmp_cond_str = "";
							for ($l = 0; $l < $objList->getNumObjects(); $l++)
							{
								$odat = $objList->getObjectMin($l);

								if ($tmp_cond_str) $tmp_cond_str .= " OR ";
								$tmp_cond_str .= "$fieldNameEs:\"" . $odat['id'] . "\" ";
							}

							if ($tmp_cond_str)
								$buf .= " ($tmp_cond_str)";
						}
						break;
					case 'object_multi':
					case 'object':
						if ($condValue == "" || $condValue == "NULL")
						{
							$buf .= "$fieldNameEs:[* TO *] ";
						}
						else if (isset($field["subtype"]) && $this->obj->fields->parentField == $fieldName && is_numeric($condValue))
						{
							$tmp_cond_str = "";
							$children = $this->objList->getHeiarchyDownObj($field["subtype"], $condValue);
							foreach ($children as $child)
							{
								if ($tmp_cond_str) $tmp_cond_str .= " OR ";
								$tmp_cond_str .= "$fieldNameEs:\"$child\"";
							}

							if ($tmp_cond_str)
								$buf .= "-($tmp_cond_str) ";
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
							$buf .= "-$fieldNameEs:\"".$this->escapeValue($condValue) . '"';
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
							$buf .= "$fieldNameEs:{* TO ".gmdate("Ymd\\TG:i:s", $time)."} ";
						break;
					case 'timestamp':
						$time = @strtotime($condValue);
						if ($time !== false)
							$buf .= "$fieldNameEs:{* TO ".gmdate("Ymd\\TG:i:s", $time)."} ";
						break;
					default:
						//$buf .= " $fieldName<'".$dbh->Escape($condValue)."' ";
						break;
					}
					break;
				case 'is_greater_or_equal':
					switch ($field["type"])
					{
					case 'object':
						// TODO: do we want to query backwards for heiarchial path?
						break;
					case 'object_multi':
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
						//$buf .= " $fieldName>='".$dbh->Escape($condValue)."' ";
						break;
					}
					break;
				case 'is_less_or_equal':
					switch ($field["type"])
					{
					case 'object':
						if ($condValue && $field['subtype'])
						{
							$tmp_cond_str = "";
							$children = $this->objList->getHeiarchyDownObj($field["subtype"], $condValue);
							foreach ($children as $child)
							{
								if ($tmp_cond_str) $tmp_cond_str .= " OR ";
								$tmp_cond_str .= "$fieldNameEs:\"$child\"";
							}

							if ($tmp_cond_str)
								$buf .= "-($tmp_cond_str) ";
						}
						break;
					case 'object_multi':
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
						//$buf .= " $fieldName<='".$dbh->Escape($condValue)."' ";
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
				case 'day_is_equal':
					if ($field["type"] == "date" || $field["type"] == "timestamp")
					{
						switch ($condValue)
						{
						case '<%current_day%>':
							$tmpcond = gmdate("d");
							break;
						default:
							$tmpcond = gmdate("d", strtotime(date("Y")."-".date("m")."-".$condValue));
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
							$tmpcond = gmdate("m");
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

						$buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s", strtotime("$tmpcond-01-01"))." TO ";
						$buf .= gmdate("Ymd\\TG:i:s", strtotime("$tmpcond-12-31"))."] ";
					}
					break;
				case 'last_x_days':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s", strtotime("-".$condValue." days"))." TO ".gmdate("Ymd\\TG:i:s")."] ";
					}
					break;
				case 'last_x_weeks':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s", strtotime("-".($condValue*7)." days"))." TO ".gmdate("Ymd\\TG:i:s")."] ";
					}
					break;
				case 'last_x_months':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s", strtotime("-".$condValue." months"))." TO ".gmdate("Ymd\\TG:i:s")."] ";
					}
					break;
				case 'last_x_years':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s", strtotime("-".$condValue." years"))." TO ".gmdate("Ymd\\TG:i:s")."] ";
					}
					break;
				case 'next_x_days':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s")." TO ".gmdate("Ymd\\TG:i:s", strtotime("+".$condValue." days"))."] ";
					}
					break;
				case 'next_x_weeks':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s")." TO ".gmdate("Ymd\\TG:i:s", strtotime("+".($condValue*7)." days"))."] ";
					}
					break;
				case 'next_x_months':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s")." TO ".gmdate("Ymd\\TG:i:s", strtotime("+".$condValue." months"))."] ";
					}
					break;
				case 'next_x_years':
					if ($field["type"] == "date" || $field["type"] == "timestamp" && is_numeric($condValue))
					{
						$buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s")." TO ".gmdate("Ymd\\TG:i:s", strtotime("+".$condValue." years"))."] ";
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
	 * Take data array with escaped names and unescape
	 *
	 * @param array $data Associateive array with field_name=>value to be escaped
	 */
	private function unescapeData($data)
	{
		if (!$data)
			return array();

		$ret = array();
		foreach($data as $var=>$value) 
		{
			$fname = $this->unescapeField($var);
			$fdef = $this->obj->fields->getField($fname);
			switch ($fdef['type'])
			{
			case 'time':
			case 'timestamp':
			case 'date':
				// Get string representation with timezone
				$value = $data[$var."_s"];
				break;
			case 'bool':
			case 'boolean':
				$value = ($value == "true") ? 't' : 'f';
				break;
			case 'fkey_multi':
			case 'object_multi':
				if (!is_array($value) && $value)
					$value = array($value);
				break;
			}
			$ret[$fname] = $value;
    	}

		return $ret;
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
				$ret = $fname."_d";
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
			if ($field['subtype'])
				$ret = $fname."_i";
			else
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
	*	Function:		escapeValue	
	*
	*	Purpose:		Escape query values
	*
	*	Arguments:		string - string: valid to escape
	**************************************************************************************/
	function escapeValue($string)
    {
        $match = array('\\', '+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '"', ';', ' ');
        $replace = array('\\\\', '\\+', '\\-', '\\&', '\\|', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\*', '\\?', '\\:', '\\"', '\\;', '\\ ');
        $string = str_replace($match, $replace, $string);
 
        return $string;
    }

	/**
	 * Create index and set mapping
	 *
	 * @param Elastica_Index $index A newly created index to set mapping for
	 */
	public function createIndices($indexname)
	{
        if(empty($indexname)) // Do not try to create indices if index name is empty
            return;
            
		// Crete indices along with alias
		// --------------------------------------------
		try 
		{
			// Create active index
			$act = $this->esClient->getIndex($indexname . "_act");
			if (!$act->exists())
				$this->createIndex($indexname . "_act");

			// Create deleted/archived index
			$del = $this->esClient->getIndex($indexname . "_del");
			if (!$del->exists())
				$this->createIndex($indexname . "_del");

			// Create alias
			$alias = array(
				"actions" => array(
					array(
						"add" => array(
							"index" => $indexname . "_act",
							"alias" => $indexname,
							"filter" => array("term"=>array("f_deleted"=>"false")),
						),
					),
				),
			);
			$ret = $this->esClient->request("_aliases", Elastica_Request::POST, $alias);

			$alias = array(
				"actions" => array(
					array(
						"add" => array(
							"index" => $indexname . "_del",
							"alias" => $indexname,
							"filter" => array("term"=>array("f_deleted"=>"true")),
						),
					),
				),
			);
			$ret = $this->esClient->request("_aliases", Elastica_Request::POST, $alias);

			// Set the alias index reference
			$this->esIndexAlias = $this->esClient->getIndex($indexname);

		} 
		catch(Elastica_Exception_Response $e) 
		{
			AntLog::getInstance()->error("PROBLEM CREATING INDEX: " . $e->getMessage());
		}
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
									"index" => "not_analyzed",
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
			$ret = $this->esClient->request($indexname, Elastica_Request::POST, $mapping);
		} 
		catch(Elastica_Exception_Response $e) 
		{
			AntLog::getInstance()->error("ERROR CREATING INDEX $indexname: " . $e->getMessage());
		}
	}

	/**
	 * Get index alias
	 */
	private function getIndexAlias()
	{
		if ($this->esIndexAlias == null)
		{
			$this->esIndexAlias = $this->esClient->getIndex($this->dbh->dbname);
			/* this is now handled with init of index
			if (!$this->esIndexAlias->exists())
				$this->createIndices($this->dbh->dbname);
			 */
		}

		return $this->esIndexAlias;
	}

	/**
	 * Get index
	 *
	 * @param bool $deleted If true, then pull from deleted archived index
	 */
	private function getIndex($deleted=false)
	{
		if ($deleted)
		{
			if (!$this->esIndexDel)
				$this->esIndexDel = $this->esClient->getIndex($this->dbh->dbname . "_del");

			return $this->esIndexDel;
		}
		else
		{
			if (!$this->esIndexAct)
				$this->esIndexAct = $this->esClient->getIndex($this->dbh->dbname . "_act");

			return $this->esIndexAct;
		}
	}
}
