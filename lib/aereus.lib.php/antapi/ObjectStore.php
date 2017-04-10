<?php
/**
 * Main abstract class for localized stores of ANT objects
 *
 * Interact with objects locally that are automatically synchronized with ANT.
 * This is the base class used by all the store implementation classes.
 * The goal of local stores is to take ANT objects and put them in a local
 * queryable database for read-only access. This reduces lag and helps with scale
 * because each request does not have to be sent to ANT.
 *
 * NOTE: Be sure and check the settings/variables section of the selected store
 *
 * Global Variables:
 * 	
 * 	$ALIB_ANTAPI_STORE = pgsql|elastic - if empty then local storage not used
 *
 * @category  AntApi
 * @package   ObjectStore
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once(ANTAPI_ROOT.'/antapi/ObjectStore/Elastic.php');
require_once(ANTAPI_ROOT.'/antapi/ObjectStore/Pgsql.php');

/**
 * Base object store class
 */
abstract class AntApi_ObjectStore
{
	/**
	 * Array used to store results of facet counts after a query
	 *
	 * @var array
	 */
	public $facetCounts = array();

	/**
	 * Array of fields to get facet counts for
	 *
	 * @var array
	 */
	public $facetFields = array();

	/**
	 * Handle to local store object
	 *
	 * @var AntAPi_ObjectStore_*
	 */
	protected $localStore = null;

	/**
	 * The object type currently being queried
	 *
	 * @var string
	 */
	var $obj_type = null;

	/**
	 * Reference to object instance for definition
	 *
	 * @var AntApi_Object
	 */
	public $obj = null; // used for definitions

	/**
	 * Used to limit how far back to go for syncing objects
	 *
	 * @var integer
	 */
	protected $syncBackToMin = null;

	/**
	 * Copy of last query run
	 *
	 * This is usually used for troubleshooting and logging
	 *
	 * @var string
	 */
	public $lastQuery = "";

	/**
	 * Array of conditions
	 *
	 * @var array(array('blogic', 'field', 'operator', 'value))
	 */
	public $conditions = array();

	/**
	 * Plain full-text query (search across all text fields)
	 *
	 * @var string
	 */
	public $conditionText = "";

	/**
	 * List of fields to sort/order results by
	 *
	 * @var array('fieldname'=>'asc' | 'desc');
	 */
	public $orderBy = array();
	
	/**
	 * Class constructor
	 *
	 * @param AntApi_Object $obj Optional objecet definition to work with
	 */
	function __construct($obj=null) 
	{
		/*
		$this->obj_type = $obj->obj_type;
		$this->obj = $obj;
		*/

		$this->connect(); // Overridden by implementation of storage class if auth/connection is required
	}

	/**
	 * Cleanup
	 */
	function __destruct() 
	{
	}

	/**
	 * Add sorting
	 *
	 * This is not yet used because the object list manually sets the orderBy property right now
	 *
	 * Sort results by a field to current query. Multi-dimension sort is possible by calling this 
	 * function multiple times with different fields.
	 *
	 * @param string $field The field to sort by
	 * @param string $direction Either "asc" for ascending or "desc" for descending sort
	public function addOrderBy($field, $direction="asc")
	{
		$this->orderBy[] = array($field=>$direction);
	}
	 */

	/**
	 * Add a query condition
	 *
	 * @param string $blogic Can either be "and" or "or"
	 * @param string name The object field name
	 * @param string $operator The opertator to use in this condition
	 * @param string $value The value to query agains
	 */
	public function addCondition($blogic, $fieldName, $operator, $condValue)
	{
		$this->conditions[] = array("blogic"=>$blogic, "field"=>$fieldName, "operator"=>$operator, "value"=>$condValue);
	}

	/**
	 * Add a full-text query condition - search all text fields
	 *
	 * @param string $searchString The sting to search for
	 */
	public function addConditionText($searchString)
	{
		$this->conditionText = $searchString;
	}

	/**
	 * Add a facet field
	 *
	 * Facets return terms with counts for fields.
	 *
	 * @param string $fieldname The name of the field to get facet counts for
	 * @param int $mincount The minimum number of times a term is found to be returned in the result
	 */
	function addFacetField($fieldname, $mincount=1)
	{
		$this->facetFields[$fieldname] = $mincount;
	}

	/**
	 * Synchronize local store with ANT objects
	 *
	 * Sync the local with updates from ANT. Right now this is only a on-eway sync from ANT to a reaad only local store.
	 * Eventually we will add a two-way read-write sync so changes can be posted immediately locally and then sent
	 * to ANT later.
	 *
	 * @param string $type The object to sync
	 * @param string $server The ant server without http or https
	 * @param string $usernname A valid username that has permission to all objects
	 * @param string $password The password for the user above
	 * @param array $conditions Array of filter conditiosn for pulling - array(array('blogic', 'field', 'operator', 'value))
	 * @retrun int The number of records synchronized
	 */
	public function syncLocalWithAnt($type, $server, $username, $password, $conditions=array())
	{
		$ret = 0;
		$offset = 0;

		$timeLastUpdated = $this->getValue("settings/ts_last_updated");
		if ($timeLastUpdated == false && $this->syncBackToMin)
		{
			$this->putValue("settings/ts_last_updated", gmdate("Y-m-d\\TG:i:s\\Z", strtotime("-".$this->syncBackToMin." minutes", time())));
			$timeLastUpdated = $this->getValue("settings/ts_last_updated");
		}

		$olist = new AntApi_ObjectList($server, $username, $password, $type);        
		$olist->setStoreSource("ant"); // Force query to pull directly from ANT
		$olist->setResultType("sync", $timeLastUpdated); // get objects updated since
        
        // Set Obj List Conditions
        foreach($conditions as $key=>$cond)
            $olist->addCondition($cond["blogic"], $cond["field"], $cond["operator"], $cond["value"]);
        
		$olist->getObjects($offset, 100); // get 100 objects at a time to reduce bandwidth
		$num = $olist->getNumObjects();
		for ($i = 0; $i < $num; $i++)
		{
			$obj = $olist->getObject($i);
			$ret++;

			// Update or delete object in local store
			if ($obj->getValue("f_deleted") == "t")
			{
				//echo "<pre>Removed $type:".$obj->id."</pre>";
				$this->removeObject($type, $obj->id);
			}
			else
			{
				//echo "\n\tStored[$type]: ".$obj->id;
				$this->storeObject($obj);
			}

			// Get next page if more than one
			if (($i+1) == $num && ($num+$offset) < $olist->getTotalNumObjects())
			{
				$offset += 99;
				$olist->getObjects($offset, 100); // get next 100 objects
				// Reset counters
				$i = 0;
				$num = $olist->getNumObjects($offset, 100);
			}
		}

		// Return nubmer of items changed (updated or deleted)
		return $ret;
	}

	/**
	 * Synchronize local store with ANT objects using the object sync framework
	 *
	 * This method is preferred to using a timestamp because it is far more efficient. Once a partner id is
	 * registered in ANT then incremental changes are logged which means all objects to not have to be queried.
	 *
	 * @param string $type The object to sync
	 * @param string $partnerId A unique id for this partnership
	 * @param string $server The ant server without http or https
	 * @param string $usernname A valid username that has permission to all objects
	 * @param string $password The password for the user above
	 * @param string $timeLastUpdated Optional date and time of the last update sync received from ANT. When set only changes since then are pulled.
	 * @retrun int The number of records synchronized
	 */
	public function syncWithAntOSync($type, $partnerId, $server, $username, $password)
	{
		$api = AntApi::getInstance($server, $username, $password);

		$data = array("obj_type"=>$type, "partner_id"=>$partnerId);
		$ret = $api->sendRequest("ObjectSync", "getChangedObjects", $data);
		if ($ret)
			$ret = json_decode($ret);

		if (!is_array($ret))
			return 0;

		foreach ($ret as $objdat)
		{
			switch ($objdat->action)
			{
			// Purge deleted
			case 'delete':
				$this->removeObject($type, $objdat->id);
				break;

			// Updates and additions
			case 'change':
			case 'add':
			default:
				$obj = $api->getObject($type, $objdat->id);
				$this->storeObject($obj);
				break;
			}
		}

		// Return nubmer of items changed (updated or deleted)
		return count($ret);
	}

	/**
	 * Get ids of all parent ids in a parent-child grouping relationship
	 *
	 * @param AntApi_Object $objDef The object definition we are working with
	 * @param string $fieldName The groupign field name
	 * @param int $this_id The id of the child element
	 * @return int[] Array of IDs of all the parent groups and this group
	 */
	public function getHeiarchyUp($objDef, $fieldName, $this_id)
	{
		$parent_arr = array($this_id);

		$groupings = $objDef->getGroupingData($fieldName);
		/* Returns
			[	
				{
					"id":"1404",
					"uname":"1404",
					"title":"New Group",
					"heiarch":true,
					"parent_id":null,
					"viewname":"New_Group",
					"color":null,
					"f_closed":false,
					"system":false,
					"children":[]
				}
			]
		*/

		/*
		$dbh = $this->dbh;

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

		 */
		return $parent_arr;
	}

	/**
	 * Get ids of all child ids in a parent-child grouping relationship
	 *
	 * @param AntApi_Object $objDef The object definition we are working with
	 * @param string $fieldName The groupign field name
	 * @param int $this_id The id of the child element
	 * @return int[] Array of IDs of all the child groups and this group
	 */
	public function getHeiarchyDown($objDef, $fieldName, $this_id)
	{
		$children_arr = array($this_id);

		$groupings = $objDef->getGroupingData($fieldName);
		$entry = $this->findGroupingById($this_id, $groupings);
		$subchildren = $this->getGroupingChildren($entry->children);
		if (count($subchildren))
			$children_arr = array_merge($children_arr, $subchildren);
		/* Returns
			[	
				{
					"id":"1404",
					"uname":"1404",
					"title":"New Group",
					"heiarch":true,
					"parent_id":null,
					"viewname":"New_Group",
					"color":null,
					"f_closed":false,
					"system":false,
					"children":[]
				}
			]
		*/
		
		/*
		$dbh = $this->dbh;

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
		 */

		return $children_arr;
	}

	/**
	 * Traverse a grouping tree and find a node by id
	 *
	 * @param int $id The id of the node we are looking for
	 * @param array $gdata Data tree with all grouping data
	 */
	private function findGroupingById($id, &$gdata)
	{
		if (!is_array($gdata))
			return null;

		foreach ($gdata as $entry)
		{
			if ($entry->id == $id)
			{
				return $entry;
			}
			else if (count($entry->children))
			{
				$ret = $this->findGroupingById($id, $entry->children);
				if ($ret)
					return $ret;
			}
		}

		return null;
	}

	/**
	 * Get all children of a grouping entry
	 *
	 * @param array $gdata Data tree with all grouping data
	 */
	private function getGroupingChildren(&$gdata)
	{
		$ret = array();

		foreach ($gdata as $entry)
		{
			$ret[] = $entry->id;

			if (count($entry->children))
			{
				$subchildren = $this->getGroupingChildren($entry->children);
				if (count($subchildren))
					$ret = array_merge($ret, $subchildren);
			}
		}

		return $ret;
	}

	/*===================================================================================
	*	OPTIONAL: Implementation Functions below are all overridden by extended storage classes
	*===================================================================================*/

	/**
	 * Dummy function that is usually set by inherited classes to handle data store connections
	 */
	public function connect()
	{
		return false;
	}

	/*===================================================================================
	*	REQUIRED: Implementation Functions below are all overridden by extended storage classes
	*===================================================================================*/

	/**
	 * Open an object from the local store
	 *
	 * @param string $type The object type name to open
	 * @param int $oid The unique id of the object to open
	 *
	 * @return false if the object was not found and AntApi_Object if found
	 */
	abstract public function openObject($type, $oid);

	/**
	 * Store object values in the local store (including an index if needed)
	 *
	 * @param AntApi_Object The object to be store locally
	 *
	 * @return bool true on success and false on failure
	 */
	abstract public function storeObject($obj);

	/**
	 * Remove an object from the local store
	 *
	 * @param string $type The object type name to open
	 * @param int $oid The unique id of the object to remove
	 *
	 * @return bool true on success and false on failure
	 */
	abstract public function removeObject($type, $oid);

	/**
	 * Retrieve objects list from the local store/index
	 *
	 * @param AntApi_Object $objDef The object type object being queried
	 * @param int $start The starting offset, defaults to 0
	 * @param int $limit The maximum number of objects to retrieve per page/set
	 *
	 * @return int The number of objects found
	 */
	abstract public function queryObjects($objDef, $start=0, $limit=500);

	/**
	 * Save a key/value settings pair in local store
	 *
	 * @param string $key The unique key/name of this value
	 * @param string $value The value to store for the given key
	 */
	abstract public function putValue($key, $value);

	/**
	 * Get a name/values setting from the local store
	 *
	 * @param string $key The unique key/name of the value to retrieve
	 * 
	 * @return string The value store for the key/name or false on failure
	 */
	abstract public function getValue($key);

	/**
	 * Delete a key/value settings pair in local store
	 *
	 * @param string $key The unique key
	 *
	 * @return bool false on failure, true on success
	 */
	abstract public function deleteValue($key);
}
