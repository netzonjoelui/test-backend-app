<?php
/**
 * Main class for managing object lists - querying multiple lists
 *
 * Nearly everything in ANT is stored as a generic object. Objects have properties
 * defined with common types. This class allows queries of multiple objects based
 * on certain criteria.
 *
 * @category  Ant
 * @package   CAntObjectList
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

if (!defined('ANT_CACHE_LISTS'))
	define("ANT_CACHE_LISTS", false);
require_once("lib/ObjectList/Plugin.php");

/**
 * Main class for querying objects
 */
class CAntObjectList
{
	var $objects = array();
	var $facetCounts = array();
	var $facetFields = array();
	var $aggregateFields = array();
	var $aggregateCounts = array(); // sum, avg, max, min
	var $obj;
	var $obj_type;
	var $dacl; // Dacl class for security
	var $user; // ANT user object
	var $dbh;
	var $total_num; // The total number of objects without offset or limit constraints
	var $fields_def_cache;
	var $hideDeleted = true;
	var $conditionText = ""; // full text search condition
	var $conditionObjects = array(); // array of objects to pull specifically by id
	var $orderBy = array(); // array of CAntObjectSort objects
	var $pullMinFields = array(); // additional fields to pull in initial query list - should be kept to a minimal
	var $lastQuery = "";
    var $lastError = ""; // Used to log the last query error encountered
	var $debug = false; // Used for testing
    
    /** 
     * Will determine if object list is used from sync functions
     *
     * @var Boolean
     */
    public $fromSync = false;

	/** 
	 * Array of conditions
	 *
	 * This must be public because it is accessed by plugins
	 *
	 * @var array
	 */
	public $conditions = array();

	/**
	 * Query loaded flag
	 *
	 * This is set to false or marked dirty any time the params of this list change
	 * or if the query has not yet been executed. This allows us to execute the query 
	 * automatically if getNumObjects is called for the first time
	 *
	 * @var bool
	 */
	private $queryLoaded = false;

	/**
	 * Optional plugin used to extend the object list
	 *
	 * @var AntObjectList_Plugin
	 */
	public $plugin = null;

	/**
	 * Name of the last index used - mostly used for testing
	 *
	 * @var string
	 */
	public $lastIndexUsed = "db";


	/**
	 * Flag used to force list to use alternate index even if not full text query
	 *
	 * We use this in the ObjectList controller to force client requests to use the alternate index (if set)
	 * that is normally only used for full-text queries.
	 *
	 * @var bool
	 */
	public $forceFullTextOnly = false;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to the database
	 * @param string $obj_type The object type to work with
	 * @param AntUser $user The user that is opening this object
	 */
	function __construct($dbh, $obj_type, $user=null)
	{
		global $OBJECT_FIELD_ACLS;

		$this->dbh = $dbh;
		$this->obj_type = $obj_type;
		$this->dacl = null;
		if ($user)
			$this->user = $user;
		else
			$this->user = new AntUser($dbh, USER_SYSTEM);

		$this->obj = new CAntObject($dbh, $obj_type);
		$this->fields_def_cache = $this->obj->def;

		// Set security
		$this->dacl = DaclLoader::getInstance($this->dbh)->byName("/objects/$obj_type");
		if (!$this->dacl->id)
			$this->dacl->save(); // save with default permissions

		// Check for plugin
		$fname = ucfirst($obj_type);
		if (strpos($obj_type, "_") !== false)
		{
			$parts = explode("_", $fname);
			$fname = "";
			foreach ($parts as $word)
				$fname .= ucfirst($word);
		}

		// Dynamically load pligin if it exists
		if (file_exists(dirname(__FILE__)."/ObjectList/Plugin/" . $fname . ".php"))
		{
			$className = "AntObjectList_Plugin_" . $fname;
			if (!class_exists($className, false))
				require_once("lib/ObjectList/Plugin/" . $fname . ".php");

			$this->plugin = new $className($this);
		}
		else
		{
			// Load default
			$this->plugin = new AntObjectList_Plugin($this);
		}
	}

	/**************************************************************************
	* Function: 	getObjects
	*
	* Purpose:		Query objects based on any conditions added to this
	*
	* Params:		int $START = offset
	* 				int $LMIT = low many objects to pull from offset
	**************************************************************************/
	function getObjects($START=0, $LIMIT=250)
	{
		// Check recurrence
		$this->checkForRecurrence();

		// Set loaded flag
		$this->queryLoaded = true;

		$cache = CCache::getInstance();

		// If objects have been set manually, then don't query. This is often used for actions where objects
		// are checked by the user and they perform some action from the UI like "delete"
		if (count($this->conditionObjects))
		{
			foreach ($this->conditionObjects as $id)
			{
				$ind = count($this->objects);
				$this->objects[$ind] = array();
				$this->objects[$ind]['id'] = $id;
				$this->objects[$ind]['obj'] = null;
			}

			$this->total_num = count($this->objects);
		}
		else // query index
		{
			$index = $this->getIndex();
			$this->objects = array(); // Reset objects array
			$index->facetFields = $this->facetFields;
			$index->aggregateFields = $this->aggregateFields;

			// Process conditions to send to indexer
			$conditions = array();
			if (count($this->conditions))
			{
				foreach ($this->conditions as $cond)
				{
					// Look for associated object conditions
					$fieldParts = array($cond['field']);
					if (strpos($cond['field'], '.'))
						$fieldParts = explode(".", $cond['field']);

					$ref_field = (count($fieldParts) > 1) ? $fieldParts[1] : "";
					$field = $this->obj->fields->getField($fieldParts[0]);
					if ($field) // make sure this is a valid field
					{
						// Add condition
						$conditions[] = array("blogic"=>$cond['blogic'], "field"=>$cond['field'], 
												"operator"=>$cond['operator'], "value"=>$cond['value']);

						// Record index stats of non-default-indexed fields if we are using the db as the index
						// this helps keep data tables lean if indexes are not needed, then inserts will be faster
						// without the unneeded overhead of maintaining unused indexes
						if ($this->obj->indexType == "db" && $cond['field'] != "id" && $cond['field'] != "uname"  && $cond['field'] != "f_deleted")
						{
							$cur = $cache->get($this->dbh->dbname . "/objectdefs/" . $this->obj->object_type . "/fldidxstat/" . $cond['field']);
							if ($cur != -1) // if -1 then dynamic index was already created
							{
								if (!is_numeric($cur))
									$cur = 0;

								// Increment counter
								$cache->set($this->dbh->dbname . "/objectdefs/" . $this->obj->object_type . "/fldidxstat/" . $cond['field'], $cur + 1);
							}
						}
					}
				}
			}

			$startNumDbHits = $this->dbh->statNumQueries;

			// Call plugin event
			$this->plugin->onQueryObjectsBefore();

			/*
			 * If we are using an external index to serve full-text only queries, then
			 * fall back to db index if no conditionText has been defined.
			 */
			if ($this->obj->indexType != "db" && empty($this->conditionText) && !$this->forceFullTextOnly && $this->obj->indexFullTextOnly)
			{
				$index = new CAntObjectIndexDb($this->dbh, $this->obj);
				$this->lastIndexUsed = "db";
			}
			else
			{
				$this->lastIndexUsed = $this->obj->indexType;
			}

			$index->debug = $this->debug;

			// Run the actual query
			$ret = $index->queryObjects($this, $this->conditionText, $conditions, $this->orderBy, $START, $LIMIT);

			// Call plugin event
			$this->plugin->onQueryObjectsAfter();

			if (false === $ret || -1 == $ret)
			{
				$this->lastError = $index->lastError . "\nQuery: ".$lastQuery;

				// Fallback to DB as index if there was a problem with the index
				if ($this->obj->indexType != "db")
				{
					AntLog::getInstance()->error("Falling back to 'db' index due to exception");
					$this->obj->setIndex("db");
					return $this->getObjects($START, $LIMIT);
				}
			}

			return $ret;
		}
	}

	/**************************************************************************
	* Function: 	checkForRecurrence
	*
	* Purpose:		Check for recurrence. If a condition has been passed for a 
	*				recurrable object that goes beyond today which will be handled 
	*				on a daily cron job, then we will create instances
	**************************************************************************/
	function checkForRecurrence()
	{
		$dbh = $this->dbh;

		// Is this object type recurrable?
		if ($this->obj->fields->recurRules != null)
		{
			// Do we have a condition set for this list that goes beyond today for
			// the field_start_date of this object?
			$processTo = "";
			if (is_array($this->conditions) && count($this->conditions))
			{
				foreach ($this->conditions as $cond)
				{
					if ($cond['field'] == $this->obj->fields->recurRules['field_date_start'] 
						|| $cond['field'] == $this->obj->fields->recurRules['field_date_end'])
					{
						// Process next 'x' number of 'y'
						if (is_numeric($cond['value']))
						{
							$inter = "";
							switch ($cond['operator'])
							{
							case 'next_x_days':
								$inter = "days";
								break;
							case 'next_x_weeks':
								$inter = "weeks";
								break;
							case 'next_x_months':
								$inter = "months";
								break;
							case 'next_x_years':
								$inter = "months";
								break;
							}

							if ($inter)
								$processTo = date("m/d/Y", strtotime("+ ".$cond['value']." ".$inter, time()));
						}
						else if ($cond['value'])
						{
							if (@strtotime($cond['value'])!==false)
							{
								$set = true;

								if ($processTo && @strtotime($processTo)!==false) // processTo was already set
								{
									// This field has already been encountered, only use the latest option
									if (strtotime($processTo) > strtotime($cond['value']))
										$set = false;
								}

								if ($set)
									$processTo = date("m/d/Y", strtotime($cond['value'])); 
							}
						}
					}
				}
			}

			if ($processTo)
			{
				// only process if date is beyond today - just trying to reduce useless queries
				//if (strtotime($processTo) > strtotime(date("m/d/Y")))
				//{
					$query = "select id from object_recurrence where f_active is true and 
								date_processed_to<'".$processTo."' and (date_end is null or date_end>=date_processed_to)
								and object_type_id='".$this->obj->object_type_id."'";
					$result = $dbh->Query($query);
					$num = $dbh->GetNumberRows($result);
					for ($i = 0; $i < $num; $i++)
					{
						$rid = $dbh->GetValue($result, $i, "id");
						$rp = new CRecurrencePattern($dbh, $rid);
						//$rp->debug = true;
						//echo "<pre>";
						//echo "<pre>Creating to $processTo</pre>";
						$numCreated = $rp->createInstances($processTo, true); // Create instances up to today
						//echo "Created: $numCreated</pre>";
						// Create instances will automatically set date_processed_to
					}
					$dbh->FreeResults($result);
				//}
			}
		}
	}

	/**************************************************************************
	* Function: 	getIndex
	*
	* Purpose:		Get the indexer and set if not already set
	*
	* Params:		(string) $type = the name of the indexer (db, elatic....)
	**************************************************************************/
	function getIndex($type=null)
	{
		return $this->obj->getIndex($type);
	}

	/**************************************************************************
	* Function: 	setIndex
	*
	* Purpose:		Set the local indexer type
	*
	* Params:		(string) $type = the name of the indexer (db, elatic....)
	**************************************************************************/
	function setIndex($type=null)
	{
		return $this->obj->setIndex($type);
	}

	/**************************************************************************
	* Function: 	getTotalNumObjects
	*
	* Purpose:		Get number of all objects matching conditions (no pagination)
	**************************************************************************/
	function getTotalNumObjects()
	{
		if (!$this->queryLoaded)
			$this->getObjects();

		return $this->total_num;
	}

	/**************************************************************************
	* Function: 	getNumObjects
	*
	* Purpose:		Get number of objects in this set (page) of results
	**************************************************************************/
	function getNumObjects()
	{
		if (!$this->queryLoaded)
			$this->getObjects();

		return count($this->objects);
	}

	/**
	 * Load the object at the specified index
	 *
	 * @param int $idx The index of the object to load
	 * @return CAntObject on success, null on failure
	 */
	public function getObject($idx)
	{
		if ($this->objects[$idx]['obj'] == null)
		{
            $revision = null;
            $data = null;
            
            if(isset($this->objects[$idx]['revision']))
                $revision = $this->objects[$idx]['revision'];
                
            if(isset($this->objects[$idx]['data']))
                $data = $this->objects[$idx]['data'];
                
			// Load object from cached data
			$this->objects[$idx]['obj'] = CAntObject::factory($this->dbh, $this->obj_type, $this->objects[$idx]['id'], 
														$this->user, null, $revision, $data);
		}
		
		return $this->objects[$idx]['obj'];
	}

	/**
	 * Do not load instance of object, just send id, revision, and owner
	 *
	 * @param int $idx The index of the object to load
	 * @return array of data defined by min_fields (addMinField)
	 */
	public function getObjectMin($idx)
	{
		$ret = array();
		$ret['id'] = $this->objects[$idx]['id'];
		$ret['revision'] = $this->objects[$idx]['revision'];
		$ret['owner_id'] = $this->objects[$idx]['owner_id'];
		foreach ($this->pullMinFields as $fldname)
		{
			// Set empty to empty strings
			if (!isset($this->objects[$idx]['data_min'][$fldname]) || $this->objects[$idx]['data_min'][$fldname]==null)
				$ret[$fldname] = "";
			else
				$ret[$fldname] = $this->objects[$idx]['data_min'][$fldname];
		}
		
		return $ret;
	}

	/****************************************************************
	* Function:		unsetObject
	*
	* Purpose:		Preserve memeory
	****************************************************************/
	function unsetObject($idx)
	{
		if ($this->objects[$idx]['obj'] != null)
			$this->objects[$idx]['obj'] = null;
	}

	/**
	 * Get ids of all parent ids in a parent-child relationship
	 *
	 * @param string $table The table to query
	 * @param string $parent_field The field containing the id of the parent entry
	 * @param int $this_id The id of the child element
	 */
	public function getHeiarchyUp($table, $parent_field, $this_id)
	{
		$dbh = $this->dbh;
		$parent_arr = array($this_id);

		if ($this_id && $parent_field)
		{
			$query = "select $parent_field as pid from $table where id='$this_id'";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);

				$subchildren = $this->getHeiarchyUp($table, $parent_field, $row['pid']);

				if (count($subchildren))
					$parent_arr = array_merge($parent_arr, $subchildren);
			}
			$dbh->FreeResults($result);
		}

		return $parent_arr;
	}

	/**
	 * Get ids of all child entries in a parent-child relationship
	 *
	 * @param string $table The table to query
	 * @param string $parent_field The field containing the id of the parent entry
	 * @param int $this_id The id of the child element
	 */
	public function getHeiarchyDown($table, $parent_field, $this_id)
	{
		$dbh = $this->dbh;
		$children_arr = array($this_id);

		if ($this_id && $parent_field && $table)
		{
			$query = "select id from $table where $parent_field='$this_id'";
			$result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);

				$subchildren = $this->getHeiarchyDown($table, $parent_field, $row['id']);

				if (count($subchildren))
					$children_arr = array_merge($children_arr, $subchildren);
			}
			$dbh->FreeResults($result);
		}

		return $children_arr;
	}

	/**
	 * Get ids of all child entries in a parent-child relationship of an object
	 *
	 * @param string $table The table to query
	 * @param string $parent_field The field containing the id of the parent entry
	 * @param int $this_id The id of the child element
	 */
	public function getHeiarchyDownObj($objType, $oid)
	{
		$dbh = $this->dbh;
		$children_arr = array($oid);

		if ($oid && $objType)
		{
			$obj = AntObjectLoader::getInstance($this->dbh)->byId($objType, $oid, $this->user);
			//$obj = CAntObject::factory($this->dbh, $objType, $oid);
			if ($obj->fields->parentField)
			{
				if ($obj->getValue($obj->fields->parentField))
				{
					$subchildren = $this->getHeiarchyDownObj($objType, $obj->getValue($obj->fields->parentField));
					if (count($subchildren))
						$children_arr = array_merge($children_arr, $subchildren);
				}
			}
		}

		return $children_arr;
	}

	/*************************************************************************************
	*	Function:		addFacetField	
	*
	*	Purpose:		Add a facet field. Facets return terms with counts for feilds.
	*					This field will probably not need to be overridden.
	*
	*	Arguments:		string $fieldname = the name of the field to facet
	*					int $mincount = the minimal number of results needed before including in results.
	**************************************************************************************/
	function addFacetField($fieldname, $mincount=1)
	{
		$this->queryLoaded = false;

		$this->facetFields[$fieldname] = $mincount;
	}

	/*************************************************************************************
	*	Function:		addAggregateField
	*
	*	Purpose:		Gather aggregate stats on a particular field
	*
	*	Arguments:		string $fieldname = the name of the field to facet
	*					int $type = sum:avg:max:min
	**************************************************************************************/
	function addAggregateField($fieldname, $type="sum")
	{
		$this->queryLoaded = false;

		$this->aggregateFields[$fieldname] = $type;
	}

	/*************************************************************************************
	*	Function:		addCondition	
	*
	*	Purpose:		Add a condition to this query
	*
	*	Arguments:		logic - string: "and" "or"
	*					name - string: filed name
	*					operator - string: operator
	*					value - string: value to test for
	**************************************************************************************/
	function addCondition($blogic, $fieldName, $operator, $condValue, $group=1)
	{
		$this->queryLoaded = false;

		// Look for associated object conditions
		if (strpos($fieldName, '.'))
			$fieldParts = explode(".", $fieldName);
        else
		    $fieldParts = array($fieldName);

		$ref_field = (count($fieldParts) > 1) ? $fieldParts[1] : "";
		$field = $this->obj->fields->getField($fieldParts[0]);
		if (!$field) // make sure this is a valid field
			return;

		// Replace current user
		if (((($field['type'] == "fkey" || $field['type'] == "fkey_multi") && $field['subtype'] == "users") 
			  || ($field['type'] == "object" && $field['subtype'] == "user")) 
			&& $condValue==USER_CURRENT && $this->user->id!=null && !$ref_field)
		{
			$condValue = $this->user->id;
		}

		// Replace dereferenced current user team
		if ($field['type'] == "object" && $field['subtype'] == "user" && $ref_field == "team_id"
			&& ($condValue==USER_CURRENT || $condValue==TEAM_CURRENTUSER)  && $this->user->teamId)
			$condValue = $this->user->teamId;

		// Replace current user team
		if ($field['type'] == "fkey" && $field['subtype'] == "user_teams" 
			&& ($condValue==USER_CURRENT || $condValue==TEAM_CURRENTUSER) && $this->user->teamId)
			$condValue = $this->user->teamId;

		// Replace object reference with user variables
		if (($field['type'] == "object" || $field['type'] == "object_multi") && !$field['subtype']
			&& $condValue == "user:".USER_CURRENT && $this->user!=null)
			$condValue = "user:" . $this->user->id;

		// Replace grouping labels with id
		if (($field['type'] == "fkey" || $field['type'] == "fkey_multi") && $condValue && !is_numeric($condValue))
		{
			$grp = $this->obj->getGroupingEntryByName($fieldParts[0], $condValue);
			if ($grp)
				$condValue = $grp['id'];
			else
				return;
		}

		$this->conditions[] = array("blogic"=>$blogic, "field"=>$fieldName, "operator"=>$operator, "value"=>$condValue);
	}

	/*************************************************************************************
	*	Function:		addConditionText	
	*
	*	Purpose:		Add a fulltext query condition
	*
	*	Arguments:		$searchString
	**************************************************************************************/
	function addConditionText($searchString, $group=1)
	{
		$this->queryLoaded = false;

		$this->conditionText = $searchString;
	}

	/*************************************************************************************
	*	Function:		addOrderBy	
	*
	*	Purpose:		Sort results by a field. Multi-dimension sort is possible by
	*					calling this function with different fields.
	*
	*	Arguments:		$field - string: the name of the field to sort by
	*					$direction - string: "asc" or "desc"
	**************************************************************************************/
	function addOrderBy($field, $direction="asc")
	{
		$this->queryLoaded = false;

		$this->orderBy[] = new CAntObjectSort($field, $direction);
	}

	/*************************************************************************************
	*	Function:		addMinField
	*
	*	Purpose:		Add a field to be pulled in initial query.
	*
	*	Arguments:		$field - string: the name of the field to sort by
	*					$direction - string: "asc" or "desc"
	**************************************************************************************/
	function addMinField($field)
	{
		$this->pullMinFields[] = $field;
	}

	/**
	 * Reset/remove all current conditions
	 */
	public function clearConditions()
	{
		$this->conditions = array();
	}

	/**
	 * Build query from form submission
	 *
	 * If 'objects[]' is populated then work on inidividual objects
	 * Otherwise build query based on params.
	 *
	 * @param array $requestVars Array of request vars submitted to this page
	 * @return bool true if form conditions were found, false if none were found
	 */
	public function processFormConditions($requestVars)
	{
		$processed = false;
        $allSelected = null;
        
        if(isset($requestVars['all_selected']))
            $allSelected = $requestVars['all_selected'];
            
		if (isset($requestVars['objects']) && $requestVars['objects'] && is_array($requestVars['objects']) && !$allSelected)
		{
			foreach ($requestVars['objects'] as $id)
			{
				// Make sure we exclude browse objects
				if (strpos($id, "browse:") === false)
				{
					$this->conditionObjects[] = $id;
					
					$processed = true;
				}
			}
		}
		else
		{
			// Build full text search
			if (isset($requestVars['cond_search']) && $requestVars['cond_search'])
			{
				$this->addConditionText($requestVars['cond_search']);

				$processed = true;
			}

			// Add advanced query conditions
			if (isset($requestVars['conditions']) && $requestVars['conditions'] && is_array($requestVars['conditions']))
			{
				foreach ($requestVars['conditions'] as $condid)
				{
					$this->addCondition($requestVars['condition_blogic_'.$condid], $requestVars['condition_fieldname_'.$condid], 
										$requestVars['condition_operator_'.$condid], $requestVars['condition_condvalue_'.$condid]);
					
					$processed = true;
				}
			}
		}
		
		if (isset($requestVars['all_selected']) && $requestVars['all_selected'])
			$processed = true;

		// Handle sort order
		if (isset($requestVars["order_by"]) && $requestVars["order_by"])
		{
			foreach ($requestVars["order_by"] as $strOrder)
			{
				$strOrder = trim($strOrder);
				$parts = explode(" ", $strOrder);
				if (count($parts) == 2)
				{
					$field = $parts[0];
					$direction = $parts[1];
				}
				else
				{
					$field = $strOrder;
					$direction = "asc";
				}

				$this->addOrderBy($field, $direction);
			}
		}

		// Handle facet order
		if (isset($requestVars["facet"]) && $requestVars["facet"])
		{
			foreach ($requestVars["facet"] as $field)
				$this->addFacetField($field);
		}

		return $processed;
	}

    /**
     * @depricated I believe this is no longer in use
     * 
     * @return 
     */
    function getMinFieldsForeignValue()
    {
        $ret = array();
        
        foreach ($this->pullMinFields as $field)
        {
            // check field if foreignKey
            $fieldInfo = null;
            $objectTable = "";
            $fieldName = "";
            
            if(isset($this->obj->fields->fields[$field]))
                $fieldInfo = $this->obj->fields->fields[$field];
            else
                continue;
            
            switch($fieldInfo['type'])
            {
                case "object":
                case "object_multi":
                    $fieldObject = new CAntObject($this->dbh, $fieldInfo['subtype']);
                    $objectTable = $fieldObject->object_table;
                    $fieldName = $fieldInfo['fkey_table']['title'];
                    break;
                case "fkey":
                case "fkey_multi":
                    $objectTable = $fieldInfo['subtype'];
                    $fieldName = $fieldInfo['fkey_table']['title'];
                    break;
                default:
                    continue;
                    break;
            }
            
            if(!empty($objectTable))
            {
                if(empty($fieldName))
                    $fieldName = "name";
                    
                $query = "select id, $fieldName from $objectTable";
                $result = $this->dbh->Query($query);
                $num = $this->dbh->GetNumberRows($result);
                for ($i = 0; $i < $num; $i++)
                {
                    $row = $this->dbh->GetNextRow($result, $i);
                    $id = $row['id'];
                    $ret[$field][$id] = $row[$fieldName];
                }
            }
        }
        
        return $ret;
    }
}
