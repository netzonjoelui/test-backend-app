<?php
/**
 * Sync collection
 *
 * @category  AntObjectSync
 * @package   Collection
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\EntitySync\Collection;

use Netric\EntitySync\Commit;
use Netric\Entity\EntityInterface;

/**
 * Class used to represent a sync partner or endpoint
 */
abstract class AbstractCollection
{
	/**
	 * DataMapper for sync operations
	 *
	 * @var \Netric\EntitySync\DataMapperInterface 
	 */
	protected $dataMapper = null;

	/**
	 * Service for managing commits
	 *
	 * @var \Netric\EntitySync\Commit\CommitManager 
	 */
	protected $commitManager = null;

	/**
	 * Internal id
	 *
	 * @var int
	 */
	protected $id = null;

	/**
	 * Partner id
	 *
	 * @var string
	 */
	protected $partnerId = null;

	/**
	 * Object type name
	 *
	 * @var string
	 */
	protected $objType = null;

	/**
	 * Object type name
	 *
	 * @var string
	 */
	protected $fieldName = null;

	/**
	 * Last sync time
	 *
	 * @var \DateTime
	 */
	protected $lastSync = null;

	/**
	 * Last commit id that was exported from this colleciton
	 * 
	 * @var string
	 */
	protected $lastCommitId = null;

	/**
	 * Conditions array
	 *
	 * @var array(array("blogic", "field", "operator", "condValue"));
	 */
    protected $conditions = array();

	/**
	 * Cache change results in a revision increment
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
	 * Constructor
	 *
	 * @param \Netric\EntitySync\DataMapperInterface $dm The sync datamapper
	 */
	public function __construct(
		\Netric\EntitySync\DataMapperInterface $dm, 
		Commit\CommitManager $commitManager)
	{
		$this->dataMapper = $dm;
		$this->commitManager = $commitManager;
	}

    /**
     * Get the head commit for a given collection type
     *
     * @return string The last commit id for the type of data we are watching
     */
    abstract protected function getCollectionTypeHeadCommit();

	/**
	 * Set the last commit id synchronized
	 *
	 * @param string $commitId
	 */
	public function setLastCommitId($commitId)
	{
		$this->lastCommitId = $commitId;
	}

	/**
	 * Get the last commit ID that was syncrhonzied/exported from this collection
	 *
	 * @return string
	 */
	public function getLastCommitId()
	{
		return $this->lastCommitId;
	}

	/**
	 * Set the id of this collection
	 *
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Get the unique id of this collection
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set the partner id of this collection
	 *
	 * @param string $pid
	 */
	public function setPartnerId($pid)
	{
		$this->partnerId = $pid;
	}

	/**
	 * Get the partner id of this collection
	 *
	 * @return string
	 */
	public function getPartnerId()
	{
		return $this->partnerId;
	}

	/**
	 * Set the object type if applicable
	 *
	 * @param string $objType
	 */
	public function setObjType($objType)
	{
		$this->objType = $objType;
	}

	/**
	 * Get the object type if applicable
	 *
	 * @return string
	 */
	public function getObjType()
	{
		return $this->objType;
	}

	/**
	 * Set the name of a grouping field if set
	 *
	 * @param string $fieldName Name of field to set
	 */
	public function setFieldName($fieldName)
	{
		$this->fieldName = $fieldName;
	}

	/**
	 * Get the name of a grouping field if set
	 *
	 * @return string
	 */
	public function getFieldName()
	{
		return $this->fieldName;
	}

	/**
	 * Set last sync timestamp
	 *
	 * @param \DateTime $timestamp When the partnership was last synchronized
	 */
	public function setLastSync(\DateTime $timestamp)
	{
		$this->lastSync = $timestamp;
	}

	/**
	 * Set the revision
	 *
	 * @param string $revision
	 */
	public function setRevision($revision)
	{
		$this->revision = $revision;
	}

	/**
	 * Get the revision
	 *
	 * @return string
	 */
	public function getRevision()
	{
		return $this->revision;
	}

	/**
	 * Set conditions with array
	 *
	 * @param array $conditions array(array("blogic", "field", "operator", "condValue"))
	 */
	public function setConditions($conditions)
	{
		$this->conditions = $conditions;
	}

	/**
	 * Get conditions
	 * 
	 * @return array(array("blogic", "field", "operator", "condValue"))
	 */
	public function getConditions()
	{
		return $this->conditions;
	}

	/**
	 * Get last sync timestamp
	 *
	 * @param string $strFormat If set format the DateTime object as a string and return
	 * @return DateTime|string $timestamp When the partnership was last synchronized
	 */
	public function getLastSync($strFormat=null)
	{
		// If desired return a formatted string version of the timestamp
		if ($strFormat && $this->lastSync)
		{
			return $this->lastSync->format($strFormat);
		}

		return $this->lastSync;
	}

    /**
     * Determine if this collection is behind the head commit of data it is watching
     *
     * @return bool true if behind, false if no changes have been made since last sync
     */
    public function isBehindHead()
    {
        // Get last commit id for this collection
        $headCommit = $this->getCollectionTypeHeadCommit();

        // Get the current commit for this collection
        $lastCollectionCommit = $this->getLastCommitId();

        return ($lastCollectionCommit < $headCommit);
    }

	/**
	 * Log that a commit was exported from this collection
	 * 
	 * @param int $uniqueId The unique id of the object we sent
	 * @param int $commitId The unique id of the commit we sent
     * @return bool
	 */
	public function logExported($uniqueId, $commitId)
	{
		if (!$this->getId())
			return false;

		$ret = $this->dataMapper->logExported($this->getType(), $this->getId(), $uniqueId, $commitId);

		// Check if there was a problem because that should never happen
		if (!$ret)
		{
			throw new \Exception("Could not log exported sync entry: " . $this->dataMapper->getLastError());
		}

		return $ret;
	}

	/**
	 * Get a list of previously exported commits that have been updated
	 *
	 * This is used to get a list of objects that were previously synchornized
	 * but were later either moved outside the collection (no longer met conditions)
	 * or deleted.
	 *
	 * NOTE: THIS MUST BE RUN AFTER GETTING NEW/CHANGED OBJECTS IN A COLLECTION.
	 * 	1. Get all new commits from last_commit and log the export
	 * 	2. Once all new commit updates were retrieved for a collection then call this
	 *  3. Once this returns empty then fast-forward this collection to head
	 *
	 * @return array(array('id'=>objectId, 'action'=>'delete'))
	 */
	public function getExportedStale()
	{
		if (!$this->getId())
			return array();

		$staleStats = array();

		$stale = $this->dataMapper->getExportedStale($this->getId());
		foreach ($stale as $oid)
		{
			$staleStats[] = array(
				"id" => $oid,
				"action" => 'delete',
			);
		}

		return $staleStats;
	}

	/**
	 * Get a stats of the difference between an import and what is stored locally
	 *
	 * @param array $importList Array of arrays with the following param for each object {uid, revision}
	 * @return array(
	 *		array(
	 *			'uid', // Unique id of foreign object 
	 *			'local_id', // Local entity/object (same thing) id
	 *			'action', // 'chage'|'delete'
	 *			'revision' // Revision of local entity at time of last import
	 *		);
	 */
	public function getImportChanged(array $importList)
	{
		if (!$this->getId())
			return array();

		// Get previously imported list and set the default action to delete
		// --------------------------------------------------------------------
		$changes = $this->dataMapper->getImported($this->getId());
		$numChanges = count($changes);
		for ($i = 0; $i < $numChanges; $i++)
		{
			$changes[$i]['action'] = 'delete';
		}
		
		// Loop through both lists and look for differences
		// --------------------------------------------------------------------
		foreach ($importList as $item) 
		{
			$found = false;

			// Check existing
			for ($i = 0; $i < $numChanges; $i++)
			{
				if ($changes[$i]['remote_id'] == $item['remote_id'])
				{
					if ($changes[$i]['remote_revision'] == $item['remote_revision'])
					{
						array_splice($changes, $i, 1); // no changes, remove
						$numChanges = count($changes);
					}
					else
					{
						$changes[$i]['action'] = 'change'; // was updated on remote source
						$changes[$i]['remote_revision'] = $item['remote_revision'];
					}

					$found = true;
					break;
				}
			}

			if (!$found) // not found locally or revisions do not match
			{
				$changes[] = array(
					"remote_id" => $item['remote_id'],
                    "remote_revision" => $item['remote_revision'],
                    "local_id" => null,
					"local_revision" => isset($item['local_revision']) ? $item['local_revision'] : 1,
					"action" => "change",
				);

				// Update count so we can stay in bounds in the above loop
				$numChanges = count($changes);
			}
		}

		return $changes;
	}

    /**
     * Log an imported object
     *
     * @param string $remoteId The foreign unique id of the object being imported
     * @param int $remoteRevision A revision of the remote object (could be an epoch)
     * @param int $localId If imported to a local object then record the id, if null the delete
     * @param int $localRevision The revision of the local object
     * @return bool true if imported false if failure
     * @throws \InvalidArgumentException
     */
    public function logImported($remoteId, $remoteRevision=null, $localId=null, $localRevision=null)
	{
		if (!$this->getId())
			return false;

		if (!$remoteId)
			throw new \InvalidArgumentException("remoteId was not set and is required.");

		/*
		 * When we import, we should also log that it was exported since
		 * we know that the remote client has the object already.
		 */
        if ($localId && $localRevision) {
            $this->logExported($localId, $localRevision);
        }

		// Log the import and return the results
		return $this->dataMapper->logImported(
            $this->getId(),
            $remoteId,
            $remoteRevision,
            $localId,
            $localRevision
        );
	}


	// LEGACY BELOW
	// -------------------------------------------------

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

}
