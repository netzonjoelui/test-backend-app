<?php
/**
 * Sync collection
 *
 * @category  AntObjectSync
 * @package   Collection
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class used to represent a sync partner or endpoint
 */
class AntObjectSync_Collection
{
	/**
	 * Database handle
	 *
	 * @var CDatabase
	 */
	private $dbh = null;

	/**
	 * Internal id
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * Partner id
	 *
	 * @var string
	 */
	public $partnerId = null;

	/**
	 * Last sync time
	 *
	 * @var string
	 */
	public $lastSync = null;

	/**
	 * Flag used for debugging
	 *
	 * @var bool
	 */
	public $debug = false;

	/**
	 * Object type name
	 *
	 * @var string
	 */
	public $objectType = "";

	/**
	 * Object def
	 *
	 * @var CAntObject
	 */
	public $objDef = null;

	/**
	 * Object type id
	 *
	 * @var int
	 */
	public $objectTypeId = null;

	/**
	 * Field name if grouping collection
	 *
	 * @var string
	 */
	public $fieldName = null;

	/**
	 * Field id if goruping collection
	 *
	 * @var int
	 */
	public $fieldId = null;

	/**
	 * Time of last update
	 *
	 * @var string
	 */
	public $tsLastSync = "";

	/**
	 * Flag to indicate collection was initialized by pulling existing objects
	 *
	 * @var bool
	 */
	public $fInitialized = false;

	/**
	 * Initilized subjects
	 *
	 * @var array
	 */
	public $initailizedParents = array();

	/**
	 * Optional cutoff date to limit returned items on initialization
	 *
	 * @var EPOCH
	 */
	public $cutoffdate = null;

	/**
	 * Current user
	 *
	 * @var AntUser
	 */
	public $user = null;

	/**
	 * Conditions array
	 *
	 * @var array(array("blogic", "field", "operator", "condValue"));
	 */
    public $conditions = array();

	/**
	 * Cache used to keep from having to ping the DB every single time we check for changes
	 *
	 * @var CCache
	 */
	protected $cache = null;

	/**
	 * Reach change results in a revision increment
	 *
	 * @var double
	 */
	protected $revision = 1;

	/**
	 * Last time this collection was checked for updates for mutiple subsequent calls
	 *
	 * @var float
	 */
	protected $lastRevisionCheck = null;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param int $id The unique id of this collection
	 */
	public function __construct($dbh, $id=null, $user=null)
	{
		$this->dbh = $dbh;
		$this->id = $id;
		$this->user = $user;

		// Initialize cache
		$this->cache = CCache::getInstance();

		if ($id)
			$this->load($id);
	}

	/**
	 * Save the collection
	 */
	public function save()
	{
		if (!$this->partnerId)
			return false;

		if ($this->id)
		{
			$sql = "UPDATE object_sync_partner_collections SET
					partner_id='".$this->partnerId."',
					f_initialized='" . (($this->fInitialized) ? 't' : 'f') . "',
					object_type='".$this->dbh->Escape($this->objectType)."',
					object_type_id=".$this->dbh->EscapeNumber($this->objectTypeId).",
					field_name='".$this->dbh->Escape($this->fieldName)."',
					field_id=".$this->dbh->EscapeNumber($this->fieldId).",
					revision=".$this->dbh->EscapeNumber($this->revision).",
					conditions='".$this->dbh->Escape(serialize($this->conditions))."'
					WHERE id='" . $this->id . "'";
		}
		else
		{
			$sql = "INSERT INTO object_sync_partner_collections(partner_id, object_type, object_type_id, 
						field_name, field_id, conditions, f_initialized, revision) 
					VALUES(
						'" . $this->partnerId . "',
						'" . $this->dbh->Escape($this->objectType) . "',
						" . $this->dbh->EscapeNumber($this->objectTypeId) . ",
						'" . $this->dbh->Escape($this->fieldName) . "',
						" . $this->dbh->EscapeNumber($this->fieldId) . ",
						'" . $this->dbh->Escape(serialize($this->conditions)) . "',
						'" . (($this->fInitialized) ? 't' : 'f') . "',
						" . $this->dbh->EscapeNumber($this->revision) . "
					); SELECT currval('object_sync_partner_collections_id_seq') as id;";
		}

		// Run query
		$result = $this->dbh->Query($sql);

		if (!$this->id)
			$this->id = $this->dbh->GetValue($result, 0, "id");

		return $this->id;
	}

	/**
	 * Load collection data from database
	 *
	 * @param int $id The id of the collection to load
	 */
	public function load($id)
	{
		if (!is_numeric($id))
			return false;
		
		$result = $this->dbh->Query("SELECT 
										partner_id, object_type, object_type_id, field_id, 
										field_name, ts_last_sync, conditions, f_initialized, revision
									 FROM object_sync_partner_collections WHERE id='".$id."'");
		if ($this->dbh->GetNumberRows($result))
		{
			$row = $this->dbh->GetRow($result, 0);

			$this->id = $id;
			$this->partnerId = $row['partner_id'];
			$this->objectType = $row['object_type'];
			$this->objectTypeId = $row['object_type_id'];
			$this->fieldName = $row['field_name'];
			$this->fieldId = $row['field_id'];
			$this->tsLastSync = $row['ts_last_sync'];
			$this->fInitialized = ($row['f_initialized'] == 't') ? true : false;
			$this->objectType = $row['object_type'];
			$this->conditions =  unserialize($row['conditions']);
			if ($row['revision'])
				$this->revision = $row['revision'];

			return true;
		}

		return false;
	}

	/**
	 * Remove this collection & all stats
	 *
	 * @return bool true on success, false on failure
	 */
	public function remove()
	{
		if (!is_numeric($this->id))
			return false;

		$ret = $this->dbh->Query("DELETE FROM object_sync_partner_collections WHERE id='" . $this->id . "'");

		return ($ret === false) ? false : true;
	}

	/**
	 * Test whether a referenced object matches filter conditions for this collection
	 *
	 * @param CAntObject $obj
	 * @return bool true of conditions match, false if they fail
	 */
	public function conditionsMatchObj($obj)
	{
		if (!$obj->id)
			return false; // only saved objects allowed because we use object list to build the query condition

		$pass = true;

		if (count($this->conditions))
		{
			$pass = false; // now assume fail because we need to meet filter conditions
			$olist = new CAntObjectList($this->dbh, $obj->object_type);
			$olist->addCondition("and", "id", "is_equal", $obj->id);
			if ('t' == $obj->getValue("f_deleted"))
				$olist->addCondition("and", "f_deleted", "is_equal", 't');

			foreach ($this->conditions as $cond)
			{
				// If we are working with hierarchy then we need to use is_less_or_equal operator
				// to include children in the query.
				if ($cond['field'] == $obj->fields->parentField && $cond['operator'] == "is_equal")
					$cond['operator'] = "is_less_or_equal";

				$olist->addCondition($cond['blogic'], $cond['field'], $cond['operator'], $cond['condValue']);
			}

			// Run query and see if object meets conditions
			$olist->getObjects(0, 1);

			if ($olist->getNumObjects() == 1)
				$pass = true;
		}

		return $pass;
	}

	/**
	 * Update object stat
	 *
	 * @param CAntObject $obj The object we are updating
	 * @param char $action 'c' for changed and 'd' for deleted
	 * @param true on success, false on failure
	 */
	public function updateObjectStat($obj, $action='c')
	{
		if (!$this->id || !$obj->id)
			return false;

		$parentId = ($obj->fields->parentField) ? $obj->getValue($obj->fields->parentField) : null;
		return $this->updateObjectStatVals($obj->id, $obj->revision, $parentId, $action);
	}

	/**
	 * Update object stat with raw values rather than using the object
	 *
	 * @param int $oid The object id
	 * @param int $revision The revision of this object
	 * @param int $parentId Optional parent id for hierarchy
	 * @param char $action 'c' for changed and 'd' for deleted
	 * @param bool true on success, false on failure
	 */
	public function updateObjectStatVals($oid, $revision, $parentId=null, $action='c')
	{
		if (!$this->id || !$oid)
			return false;

		$odef = CAntObject::factory($this->dbh, $this->objectType);

		// First check to see if this update is from an import to limit circular updates within collections
		$sql = "SELECT id FROM object_sync_import WHERE collection_id='" . $this->id . "' 
				AND object_id='" . $oid . "' 
				AND revision=".$this->dbh->EscapeNumber($revision)."";
		if ($parentId)
			$sql .= " AND parent_id=".$this->dbh->EscapeNumber($parentId)."";

		if ($this->dbh->GetNumberRows($this->dbh->Query($sql)))
		{
			// We do not need to stat this because it is the result of an import and data has not changed since
			// the import because the revisions are exactly the same. More than likely, this stat update
			// is being called directly from the import save so we can safely skip.
			return false;
		}

		// Delete previous entries - no need to stack updates
		/*
		$sql = "DELETE FROM object_sync_stats WHERE collection_id='" . $this->id . "' 
				AND object_id='" . $oid . "'";
		 */
		$sql = "DELETE FROM object_sync_stats WHERE collection_id='" . $this->id . "' 
				AND object_id='" . $oid . "' AND object_type_id='".$odef->object_type_id."'
				AND action='" . $this->dbh->Escape($action) . "'";
		if ($parentId)
			$sql .= " AND parent_id=".$this->dbh->EscapeNumber($parentId)."";
		$this->dbh->Query($sql);

		// Add this entry
		$sql = "INSERT INTO object_sync_stats(collection_id, object_type_id, parent_id, object_id, action, revision, ts_entered)
							VALUES('" . $this->id . "', '".$odef->object_type_id."', ".$this->dbh->EscapeNumber($parentId).",
							'" . $oid . "', '$action', ".$this->dbh->EscapeNumber($revision).", 'now');";
		$ret = $this->dbh->Query($sql);

		if ($ret === false)
		{
            echo $this->dbh->schema . ":" . $this->dbh->getLastError() . "\n";
			return false;
		}
		else
		{
			$this->updateRevision();
			return true;
		}
	}

	/**
	 * Update grouping stat
	 *
	 * @param string $objType The object type we are updating
	 * @param string $fieldName The name of the field we are updating
	 * @param int $fieldVal The value of the grouping field
	 * @param char $action The action performed 'c' for changed and 'd' for deleted
	 */
	public function updateGroupingStat($fieldVal, $action='c')
	{
		if (!$this->objectType || !$this->fieldName || !$fieldVal)
			return false;

		$odef = CAntObject::factory($this->dbh, $this->objectType);
		$field = $odef->fields->getField($this->fieldName);

		if (!$field)
			return false;

		$sql = "INSERT INTO object_sync_stats(collection_id, object_type_id, field_id, field_name, field_val, action, ts_entered)
				VALUES(
					'" . $this->id . "', 
					'" . $odef->object_type_id . "',  
					" . $this->dbh->EscapeNumber($field['id']) . ",
					'" . $this->dbh->Escape($this->fieldName) . "',
					" . $this->dbh->EscapeNumber($fieldVal) . ",
					'c',
					'now'
				);";
		$ret = $this->dbh->Query($sql);

		if ($ret === false)
		{
			return false;
		}
		else
		{
			$this->updateRevision();
			return true;
		}
	}

	/**
	 * Delete a stat
	 *
	 * @param string $statId The id of the stat to delete
	 * @param int $parentId If we are filtering by a parentField then send parentId
	 */
	public function deleteStat($statId, $parentId=null)
	{
		if ($this->fieldName)
		{
			if (!$this->fieldId)
			{
				if (!$this->objDef)
					$this->objDef = CAntObject::factory($this->dbh, $this->objectType);

				$field = $odef->fields->getField($this->fieldName);
				$this->fieldId = $field['id'];
			}

			$this->dbh->Query("DELETE FROM object_sync_stats WHERE collection_id='" . $this->id . "' 
								AND field_id='" . $this->fieldId . "' AND field_val='" . $statId. "';");
		}
		else
		{
			if (!$this->objDef)
				$this->objDef = CAntObject::factory($this->dbh, $this->objectType);

			$sql = "DELETE FROM object_sync_stats WHERE collection_id='" . $this->id . "' 
								AND object_id='" . $statId . "'
								AND object_type_id='" . $this->objDef->object_type_id . "'";
			if ($parentId)
				$sql .= "AND parent_id='$parentId'";
			else
				$sql .= "AND parent_id is NULL";
			$this->dbh->Query($sql);
		}

		return true;
	}

	/**
	 * Save the stat params for an imported object
	 *
	 * @parm string $devid Unique device id
	 * @param string $uid The unique id of the remove object
	 * @param int $revision The remote revision of the object when saved
	 * @param int $oid The object id to update
	 * @param int $parentId If set, pull all objects that are a child of the parent id only
	 */
	public function updateImportObjectStat($uid, $revision, $oid=null, $parentId=null)
	{
		// First remove if already exists
		$this->dbh->Query("DELETE FROM object_sync_import WHERE collection_id='" . $this->id . "' 
							AND unique_id='" . $this->dbh->Escape($uid) . "' and field_id is NULL");


		if ($oid)
		{
			if (!$this->objDef)
				$this->objDef = CAntObject::factory($this->dbh, $this->objectType);

			// Frist remove from stat outgoing table if already entered like object just saved before updating the import stat
			$sql = "DELETE FROM object_sync_stats WHERE collection_id='" . $this->id . "' 
					AND object_id='" . $oid . "' 
					AND object_type_id='" . $this->objDef->object_type_id . "'
					AND revision=".$this->dbh->EscapeNumber($revision)."";
			if ($parentId)
				$sql .= " AND parent_id=".$this->dbh->EscapeNumber($parentId)."";
			$this->dbh->Query($sql);

			// Now insert import stat
			$sql = "INSERT INTO object_sync_import(collection_id, object_type_id, object_id, unique_id, revision, parent_id)
								VALUES('" . $this->id . "', '" . $this->objectTypeId . "', 
										'" . $oid . "', '" . $this->dbh->Escape($uid) . "', 
										".$this->dbh->EscapeNumber($revision).", 
										".$this->dbh->EscapeNumber($parentId).");";

			$ret = $this->dbh->Query($sql);
		}
		else 
		{
			// if oid is null then do nothing, the $uid has been deleted from imported stats
			// because it no longer is represented by a local object id
			$ret = true;
		}

		if ($ret === false)
			return false;
		else
			return true;
	}

	/**
	 * Delete an imported object stat
	 *
	 * @parm string $devid Unique device id
	 * @param string $uid The unique id of the remove object
	 * @param int $revision The remote revision of the object when saved
	 * @param int $oid The object id to update
	 * @param int $parentId If set, pull all objects that are a child of the parent id only
	 */
	public function deleteImportObjectStat($uid, $oid=null, $parentId=null)
	{
		// First remove if already exists
		$this->dbh->Query("DELETE FROM object_sync_import WHERE collection_id='" . $this->id . "' 
							AND unique_id='" . $this->dbh->Escape($uid) . "' and field_id is NULL");


		if ($oid)
		{
			if (!$this->objDef)
				$this->objDef = CAntObject::factory($this->dbh, $this->objectType);

			// Frist remove from stat outgoing table if already entered like object just saved before updating the import stat
			$sql = "DELETE FROM object_sync_stats WHERE collection_id='" . $this->id . "' 
					AND object_id='" . $oid . "' AND object_type_id='" . $this->objDef->object_type_id . "' ";
			if ($parentId)
				$sql .= " AND parent_id=".$this->dbh->EscapeNumber($parentId)."";
			$this->dbh->Query($sql);
		}
		else 
		{
			// if oid is null then do nothing, the $uid has been deleted from imported stats
			// because it no longer is represented by a local object id
			$ret = true;
		}

		if ($ret === false)
			return false;
		else
			return true;
	}

	/**
	 * Reset or clear all existing stats for this collection
	 *
	 * @param int $parentId If set then only reset stats with a certain parent subset
	 * @return bool true on success, false on failure
	 */
	public function resetStats($parentId=null)
	{
		if (!$this->id)
			return false;

		$sql = "DELETE FROM object_sync_stats WHERE collection_id='" . $this->id . "'";
		if ($parentId)
			$sql .= " AND parent_id='$parentId'";

		$ret = $this->dbh->Query($sql);

		return ($ret === false) ? false : true;
	}

	/**
	 * Initialize collection with existing objects
	 *
	 * @param int $parentId If set, pull all objects that are a child of the parent id only
	 */
	public function initObjectCollection($parentId=null)
	{
		$odef = CAntObject::factory($this->dbh, $this->objectType);

        // Set initialized to true first because the below process may take some time
        // and we don't want multiple instances of this collection initailizing at once
        // due to multiple apache threads running
        $this->dbh->Query("UPDATE object_sync_partner_collections SET f_initialized='t' WHERE id='".$this->id."'");
		//$this->setIsInitialized($parentId);

		// Add all non-deleted objects to object_sync_stats table
		$olist = new CAntObjectList($this->dbh, $this->objectType);
		foreach ($this->conditions as $cond)
		{
			$olist->addCondition($cond['blogic'], $cond['field'], $cond['operator'], $cond['condValue']);
		}
		if ($this->cutoffdate)
			$olist->addCondition("and", "ts_updated", "is_greater_or_equal", date("Y-m-d", $this->cutoffdate));
		if ($odef->def->parentField)
			$olist->addMinField($odef->def->parentField);
		$olist->getObjects(0, 1000);
		$num = $olist->getNumObjects();
		$totalNum = $olist->getTotalNumObjects();
		$offset = 0;
		for ($i = 0; $i < $num; $i++)
		{
			$objMin = $olist->getObjectMin($i);
			$pnt = ($odef->def->parentField) ? $objMin[$odef->def->parentField] : null;

			$sql = "INSERT INTO object_sync_stats(collection_id, object_type_id, parent_id, object_id, action, revision)
						VALUES('".$this->id."', '".$this->objectTypeId."', 
							   " . $this->dbh->EscapeNumber($pnt) . ",
							   '".$objMin['id']."', 'c', '".$objMin['revision']."');";
			$this->dbh->Query($sql);

			// Get next page if more
			if (($i+1) == $num && ($num+$offset) < $totalNum)
			{
				$offset += 1000;
				$olist->getObjects($offset, 1000); // get next 1000 objects
				$num = $olist->getNumObjects($offset, 1000); // update number of objects to loop through
				$i = 0; // Reset counter
			}
		}

		$this->fInitialized = true;
	}

	/**
	 * Initialize collection with existing groupings
	 */
	public function initGroupingCollection()
	{
		// Check for owner id condition
		$user = null;
		foreach ($this->conditions as $cond)
		{
			if ($cond['field'] == "owner_id" && $cond['operator'] == "is_equal")
				$user = new AntUser($this->dbh, $cond['condValue']);
		}

		$odef = CAntObject::factory($this->dbh, $this->objectType, null, $user);
		$field = $odef->fields->getField($this->fieldName);

		$groupings = $odef->getGroupingData($this->fieldName);
		foreach ($groupings as $grp)
		{
			$sql = "INSERT INTO object_sync_stats(collection_id, object_type_id, field_id, field_name, field_val, action)
					VALUES(
						'" . $this->id . "', 
						'" . $odef->object_type_id . "',  
						" . $this->dbh->EscapeNumber($field['id']) . ",
						'" . $this->dbh->Escape($this->fieldName) . "',
						" . $this->dbh->EscapeNumber($grp['id']) . ",
						'c'
					);";

			$this->dbh->Query($sql);
		}

		//$this->setIsInitialized();
		$this->dbh->Query("UPDATE object_sync_partner_collections SET f_initialized='t' WHERE id='".$this->id."'");
	}

	/**
	 * Get changed objects
	 *
	 * @param int $parentId If set, pull all objects that are a child of the parent id only
	 * @param bool $autoClear If true (default) then purge stats as soon as they are returned
	 * @return array of assoiative array [["id"=><object_id>, "action"=>'change'|'delete']]
	 */
	public function getChangedObjects($parentId=null, $autoClear=true)
	{
		$changed = array();

		// Find out if this is our first sync
		if (false == $this->fInitialized)
        {
            $this->initObjectCollection();
        }

		// Get list of updates 1000 objects at a time because subsequent calls will pick up where last left off
		$sql = "SELECT object_id, action FROM object_sync_stats 
				 WHERE collection_id='".$this->id."' ";
		if ($parentId)
			$sql .= "AND parent_id='$parentId'";
		else
			$sql .= "AND parent_id is NULL";
		$sql .= " ORDER BY ts_entered LIMIT 1000";
		$result = $this->dbh->Query($sql);
		$num = $this->dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $this->dbh->GetRow($result, $i);
			$stat = array('id'=>$row['object_id']);

			switch ($row['action'])
			{
			case 'd':
				$stat['action'] = 'delete';
				break;
			case 'a':
			case 'c':
			default:
				$stat['action'] = 'change';
				break;
			}

			$changed[] = $stat;

			// Clear stat for future calls
			if ($autoClear)
				$this->deleteStat($stat['id'], $parentId);

			// Update import to limit circular deletes where
			// 1. Deleted from local store > delete on remote
			// 2. Sync with imported remote causes delete on local store (by id) which causes problems if
			// the reason for the delete is that the object was moved - like email being moved to new grouping
			if ("delete" == $stat['action'])
			{
				$sql = "DELETE FROM object_sync_import WHERE collection_id='" . $this->id . "' 
						AND object_id='" . $stat['id'] . "'";
				if ($parentId)
					$sql .= " AND parent_id=".$this->dbh->EscapeNumber($parentId)."";
				$this->dbh->Query($sql);
			}
		}
		$this->dbh->FreeResults($result);

		return $changed;
	}

	/**
	 * Get changed groupings
	 * 
	 * @param bool $autoClear If true (default) then purge stats as soon as they are returned
	 * @return array of assoiative array [["id"=><object_id>, "action"=>'change'|'delete']]
	 */
	public function getChangedGroupings($autoClear=true)
	{	
		$changed = array();

		$odef = CAntObject::factory($this->dbh, $this->objectType);
		$field = $odef->fields->getField($this->fieldName);

		// Find out if this is our first sync
		if (false == $this->fInitialized)
			$this->initGroupingCollection();

		// Get list of updates 1000 objects at a time because subsequent calls will pick up where last left off
		$result = $this->dbh->Query("SELECT field_val, action FROM object_sync_stats 
									 WHERE collection_id='" . $this->id . "' AND field_id='" . $field['id'] . "'
									 AND object_id is NULL 
									 AND object_type_id='" . $odef->object_type_id . "'
									 AND field_val IS NOT NULL
									 LIMIT 1000");
		$num = $this->dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $this->dbh->GetRow($result, $i);
			$stat = array('id'=>$row['field_val']);

			switch ($row['action'])
			{
			case 'd':
				$stat['action'] = 'delete';
				break;
			case 'a':
			case 'c':
			default:
				$stat['action'] = 'change';
				break;
			}

			$changed[] = $stat;

			// Clear stat for future calls
			if ($autoClear)
				$this->deleteStat($stat['id']);
		}
		$this->dbh->FreeResults($result);

		return $changed;
	}

	/**
	 * Get stats array with a diff from the previous import for objects in this collection
	 *
	 * @param array $importList Array of arrays with the following param for each object {uid, revision}
	 * @param int $parentId If set, pull all objects that are a child of the parent id only
	 * @return array(array('uid', 'object_id', 'action', 'revision');
	 */
	public function importObjectsDiff($importList, $parentId=null)
	{
		$changes = array();

		// Get previously imported list
		// --------------------------------------------------------------------
		$sql = "SELECT unique_id, object_id, revision FROM object_sync_import WHERE collection_id='" . $this->id . "'";
		if (is_numeric($parentId))
			$sql .= " AND parent_id='$parentId'";
		$result = $this->dbh->Query($sql);
		$num = $this->dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $this->dbh->GetRow($result, $i);
			
			// Mark all local to be deleted unless still exists in the imported list
			$changes[] = array(
				'uid' => $row['unique_id'],
				'object_id' => $row['object_id'],
				'revision' => $row['revision'],
				'action' => 'delete',
			);
		}
		
		// Loop through both lists and look for differences
		// --------------------------------------------------------------------
		foreach ($importList as $item) 
		{
			$found = false;

			// Check existing
			for ($i = 0; $i < count($changes); $i++)
			{
				if ($changes[$i]['uid'] == $item['uid'])
				{
					if ($changes[$i]['revision'] == $item['revision'])
					{
						array_splice($changes, $i, 1); // no changes, remove
					}
					else
					{
						$changes[$i]['action'] = 'change'; // was updated on remote source
						$changes[$i]['revision'] = $item['revision'];
					}

					$found = true;
					break;
				}
			}

			if (!$found) // not found locally or revisions do not match
			{
				$changes[] = array(
					"uid" => $item['uid'], 
					"object_id" => $item['object_id'], 
					"revision" => $item['revision'], 
					"action" => "change",
				);
			}
		}

		return $changes;
	}

	/**
	 * Import groupings from device, keep history for incremental changes
	 *
	 * Sync local groupings with list from remote device. We do not need to export changes
	 * because that is handled real time with the device stat table as objects are updated.
	 *
	 * @param string[] $groupList Array of all groupings from the device
	 * @param string $delimiter Hierarchical delimiter to use when parsing groups
	 * @return array of assoiative array [["id"=><grouping.id>, "action"=>'change'|'delete']]
	 */
	public function importGroupingDiff($groupList, $delimiter='/')
	{
		$changed = array();
		$fieldName = $this->fieldName;
		$odef = ($objDef) ? $objDef : CAntObject::factory($this->dbh, $this->objectType, null, $this->user);

		// Leave if no field name or groups to sync
		if (!$fieldName || !is_array($groupList))
			return $changed;

		$field = $odef->fields->getField($fieldName);
		if (!is_array($field))
			return $changed;

		// Get previously imported list
		// --------------------------------------------------------------------
		$local = array();
		$result = $this->dbh->Query("SELECT unique_id FROM object_sync_import WHERE collection_id='" . $this->id . "'
									 AND field_id='".$field['id']."'");
		$num = $this->dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
			$local[] = $this->dbh->GetValue($result, $i, "unique_id");

		// Mark all local to be deleted unless still exists in the imported list
		foreach ($local as $lpath)
			$changed[] = array("unique_id"=>$lpath, "action"=>"delete");
		unset($local);

		// Loop through both lists and look for differences
		// --------------------------------------------------------------------
		foreach ($groupList as $grpName) 
		{
			$found = false;

			// Check existing
			for ($i = 0; $i < count($changed); $i++)
			{
				if ($changed[$i]['unique_id'] == $grpName)
				{
					array_splice($changed, $i, 1);
					$found = true;
					break;
				}
			}

			if (!$found) // not found locally
				$changed[] = array("unique_id"=>$grpName, "action"=>"change");
		}

		$odef->skipObjectSyncStat = true; // Do no sync changes up after importing thus creating an endless loop

		// Save new list to import
		// --------------------------------------------------------------------
		foreach ($changed as $ch)
		{
			// Translate hierarchical path
			if ($delimiter != "/") 
			   $grpPath = str_replace($delimiter, "/", $ch['unique_id']);
			else
				$grpPath = $ch['unique_id'];

			switch ($ch['action'])
			{
			case 'delete':
				$this->dbh->Query("DELETE FROM object_sync_import WHERE collection_id='" . $this->id . "'
									AND field_id='".$field['id']."' AND unique_id='" . $this->dbh->Escape($ch['unique_id']) . "'");

				// Delete grouping if it exists
				$grp = $odef->getGroupingEntryByPath($fieldName, $grpPath);
				if (is_array($grp) && $grp['id'])
					$odef->deleteGroupingEntry($fieldName, $grp['id']);

				break;
			case 'change':
				$this->dbh->Query("INSERT INTO object_sync_import(collection_id, object_type_id, field_id, unique_id) 
								   VALUES(
										'" . $this->id . "',
										'" . $this->objectTypeId. "',
										'" . $field['id'] . "',
										'" . $this->dbh->Escape($ch['unique_id']) . "'
								   );");

				// Add grouping if not exists
				$grp = $odef->getGroupingEntryByPath($fieldName, $grpPath);
				if (!$grp)
					$odef->addGroupingEntry($fieldName, $grpPath);
				break;
			}
		}

		$odef->skipObjectSyncStat = false; // Turn sync back on

		return $changed;
	}

	/**
	 * Determin if this collection has any changes to sync
	 *
	 * This is used to decrease performance load. It is especially useful for
	 * hierarchy collections like file systems because a call to the root parent
	 * will indicate the change status of all children as well.
	 */
	public function changesExist()
	{
		if (!$this->id)
			return false;

		if (!$this->fInitialized)
			$this->initObjectCollection();

		// This is a subsequent call, use cache to check if another process has updated and limit db queries
		if ($this->lastRevisionCheck)
		{
			$currentRevision = $this->cache->get($this->dbh->accountId . "/objectsync/collections/" . $this->id . "/revision");
			if (is_numeric($currentRevision))
			{
				$hasChanged = ($this->lastRevisionCheck < $currentRevision) ? true : false;
				$this->lastRevisionCheck = $currentRevision;
				return $hasChanged;
			}
            else
            {
                // Current revision has not been updated which means the collection has not been modified since last check
                return false;
            }
		}

		// Check if we have any stats to work with
		$result = $this->dbh->Query("select 1 as exists FROM object_sync_stats where collection_id='" . $this->id . "' limit 1");
		$hasChanged = ($this->dbh->GetNumberRows($result) > 0) ? true : false;

		if ($zlog)
		{
			if ($hasChanged)
        		ZLog::Write(LOGLEVEL_DEBUG, sprintf("COLLECTION->changesExist(): Found changes for '%s'", $this->id));
			else
        		ZLog::Write(LOGLEVEL_DEBUG, sprintf("COLLECTION->changesExist(): Did not find changes for '%s'", $this->id));
		}

		// Set last checked revision for subsequent calls resulting in minimal db hits
		$this->lastRevisionCheck = $this->revision;

		return $hasChanged;
	}

	/**
	 * Increment the interval collection revision
	 */
	private function updateRevision()
	{
		// Increment
		$this->revision++;

		if (!$this->id)
			return false;

		// Save to persistant store
		$this->dbh->Query("UPDATE object_sync_partner_collections 
						   SET ts_last_sync='now', revision='" . $this->revision . "' 
						   WHERE id='".$this->id."'");
		
		// Save to cache for parallel processes
		$this->cache->set($this->dbh->accountId . "/objectsync/collections/" . $this->id . "/revision", $this->revision);
	}

	/**
	 * Check if this collection, or a subset of this collection by parentId, is initailized
	 *
	 * @param int $parentId Optional subset of collection
	 * @return bool true if collection or subject has been initialized
	 */
	public function isInitialized($parentId=null)
	{
		if (!$this->id)
			return false;

		if ($this->initialized)

		// If no parent or heirarch then just use collection init flag
		if (null == $parentId && false == $this->fInitialized)
			return false;

		if (null == $parentId && true == $this->fInitialized)
			return true;

		if (isset($this->initailizedParents[$parentId]))
			return $this->initailizedParents[$parentId];

		// Get from table
		$res = $this->dbh->Query("SELECT ts_completed FROM object_sync_partner_collection_init 
						   		  WHERE collection_id='".$this->id."' AND parent_id='" . (($parentId)?$parentId:'0') . "'");

		$isInit = ($this->dbh->GetNumberRows($res)>0) ? true : false;

		$this->initailizedParents[$parentId] = $isInit;

		return $isInit;
	}

	/**
	 * Set if this collection has been initialized or not
	 *
	 * @param int $parentId Optional subset of collection
	 */
	public function setIsInitialized($parentId=null, $isInit=true)
	{
		$this->fInitialized = $isInit;
		$this->initailizedParents[$parentId] = $isInit;

		if (!$this->id)
			return false;

        // Set initialized to true first because the below process may take some time
        // and we don't want multiple instances of this collection initailizing at once
        // due to multiple apache threads running
        $this->dbh->Query("UPDATE object_sync_partner_collections SET f_initialized='t' WHERE id='".$this->id."'");

		$res = $this->dbh->Query("SELECT ts_completed FROM object_sync_partner_collection_init 
						   		  WHERE collection_id='".$this->id."' AND parent_id='" . (($parentId) ? $parentId : '0') . "'");
		if ($this->dbh->GetNumberRows($res)==0)
		{
			$this->dbh->Query("INSERT INTO object_sync_partner_collection_init(collection_id, parent_id, ts_completed) 
								VALUES('".$this->id."', '".(($parentId) ? $parentId : '0')."', 'now');");
		}
	}
}
