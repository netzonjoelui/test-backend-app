<?php
/**
 * PgSQL datamapper for synchronization library
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2015 Aereus Corporation (http://www.aereus.com)
 */

 namespace Netric\EntitySync;

 class DataMapperPgsql extends AbstractDataMapper implements DataMapperInterface
 {

	/**
	 * Save partner
	 * 
	 * @param \Netric\EntitySync\Partner
	 * @return bool true on success, false on failure
	 */
	public function savePartner(Partner $partner)
	{
		// PartnerID is a required param
		if (!$partner->getPartnerId())
			return $this->returnError("Partner id is a required param", __FILE__, __LINE__);

		// User id is a required param
		if (!$partner->getOwnerId())
			return $this->returnError("Owner id is a required param", __FILE__, __LINE__);

		// Save partnership info
		$data = array(
			"pid" => $partner->getPartnerId(),
			"owner_id" => $partner->getOwnerId(),
			"ts_last_sync" => $partner->getLastSync("Y-m-d H:i:s"),
		);

		if ($partner->getId())
		{
			$update = "";
			foreach ($data as $col=>$val)
			{
				if ($update)
					$update .= ", ";
				$update .= $col . "=";

				if ($val)
				{
					$update .= "'" . $this->dbh->escape($val) . "'";
				}
				else
				{
					$update .= "NULL";
				}
			}

			$query = "UPDATE object_sync_partners SET $update WHERE id='" . $partner->getId() . "';";
		}
		else
		{
			$flds = "";
			$vals = "";

			foreach ($data as $col=>$val)
			{
				if ($flds)
				{
					$flds .= ", ";
					$vals .= ", ";
				}

				$flds .= $col;

				if ($val)
				{
					$vals .= "'" . $this->dbh->escape($val) . "'";
				}
				else
				{
					$vals .= "NULL";
				}
			}

			$query = "INSERT INTO object_sync_partners($flds) VALUES($vals); 
					  SELECT currval('object_sync_partners_id_seq') as id;";
		}

		$result = $this->dbh->query($query);

		// If there was a problem return the error from the db
		if ($result == false)
			return $this->returnError($this->dbh->getLastError(), __FILE__, __LINE__);;

		// Set the internal id of the parnter if saved for the first time
		if (!$partner->getId())
			$partner->setId($this->dbh->getValue($result, 0, "id"));

		// Save collections 
		$this->savePartnerCollections($partner);

		return true;
	}

	/**
	 * Save partner collections
	 *
	 * @param \Netric\EntitySync\Partner $partner
	 */
	private function savePartnerCollections(Partner $partner)
	{
		if (!$partner->getId())
			return $this->returnError("Cannot save collections because partner is not saved", __FILE__, __LINE__);

		$collections = $partner->getCollections();
		for ($i = 0; $i < count($collections); $i++)
		{
			// If this collection was just added to the partner then it may not
			// have the partner id set yet.
			if ($collections[$i]->getPartnerId() == null)
			{
				$collections[$i]->setPartnerId($partner->getId());
			}

			$this->saveCollection($collections[$i]);
		}

		// Get removed collections
		$removed = $partner->getRemovedCollections();
		foreach ($removed as $removeId)
		{
			$this->deleteCollection($removeId);
		}

		return true;
	}

	/**
	 * Get a partner by unique system id
	 *
	 * @param string $id Netric unique partner id
	 * @return Partner or null if id does not exist
	 */
	public function getPartnerById($id)
	{
		return $this->getPartner($id, null);
	}

	/**
	 * Get a partner by the remote partner id
	 *
	 * @param string $partnerId Remotely provided unique ident
	 * @return Partner or null if id does not exist
	 */
	public function getPartnerByPartnerId($partnerId)
	{
		return $this->getPartner(null, $partnerId);
	}

	/**
	 * Get a partner by either a netric system id or a client partner device id
	 *
	 * @param string $id System id
	 * @param string $partnerId Device id
	 * @return Partner or null if id does not exist
	 */
	private function getPartner($id=null, $partnerId=null)
	{
		// Make sure we have at least one id to pull from
		if (null == $id && null == $partnerId)
		{
			return null;
		}

		$query = "SELECT id, pid, owner_id, ts_last_sync 
				  FROM object_sync_partners WHERE ";

		// Add condition based on the type of id passed
		$query .= ($id) ? "id='".$this->dbh->escape($id)."'" 
					    : "pid='".$this->dbh->escape($partnerId)."'";

		$result = $this->dbh->query($query);
		if ($this->dbh->getNumRows($result))
		{
			$row = $this->dbh->getRow($result, 0);

			$partner = new Partner($this);
			$partner->setId($row['id']);
			$partner->setPartnerId($row['pid']);
			$partner->setOwnerId($row['owner_id']);
			if ($row['ts_last_sync'])
			{
				$partner->setLastSync(new DateTime($row['ts_last_sync']));
			}

			// Get collections
			$this->loadPartnerCollections($partner);

			return $partner;
		}

		// Not found
		return null;
	}

	/**
	 * Delete a partner
	 *
	 * @param \Netric\EntitySync\Partner $partner The partner to delete
	 * @param bool Option to delete by partner id which is useful for purging duplicates
	 * @return bool true on success, false on failure
	 */
	public function deletePartner($partner, $byPartnerId=false)
	{
		if ($partner->getId())
		{
			$query = "DELETE FROM object_sync_partners WHERE ";
			if ($byPartnerId)
			{
				$query .= "id='" . $partner->getId() . "'";
			}
			else
			{
				$query .= "pid='" . $partner->getPartnerId() . "'";
			}

			$this->dbh->query($query);
			return true;
		}

		return false;
	}

	/**
	 * Populate collections array for a given partner using addCollection
	 *
	 * @param \Netric\EntitySync\Partner $partner
	 */
	private function loadPartnerCollections($partner)
	{
		// Make sure the partner was already loaded
		if (!$partner->getId())
			return $this->returnError("Cannot get collections because partner is not saved", __FILE__, __LINE__);

		$result = $this->dbh->query("SELECT * FROM object_sync_partner_collections 
									 WHERE partner_id='".$partner->getId()."'");
		$num = $this->dbh->getNumRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $this->dbh->getRow($result, $i);

			// Unserialize the conditions
			if ($row['conditions'])
			{
				$row['conditions'] = unserialize($row['conditions']);
			}

			// Construct a new collection
			if (!$this->getAccount())
				throw new \Exception("This DataMapper requires a reference to account!");

			/* 
			 * Try to auto detect if we have data and no type.
			 * This is only needed for legacy data since saving now requires a type.
			 */
			if (!$row['type'])
			{
				if ($row['object_type'] && $row['field_name'])
				{
					$row['type'] = \Netric\EntitySync\EntitySync::COLL_TYPE_GROUPING;
				}
				else if ($row['object_type'])
				{
					$row['type'] = \Netric\EntitySync\EntitySync::COLL_TYPE_ENTITY;
				}

				/*
				 * We do not need to auto-detect \Netric\EntitySync\EntitySync::COLL_TYPE_ENTITYDEF
				 * since it is a new collection type and it is now impossible to save without
				 * the type since ::getType is an abstract requirement for all collections.
				 */
			} 
				
			// Use a factory to construct the new colleciton
			$serviceManager = $this->getAccount()->getServiceManager();
			$collection = Collection\CollectionFactory::create($serviceManager, $row['type'], $row);
			
			// Add the collection to the partner object
            if ($collection)
			    $partner->addCollection($collection);
		}
	}

	/**
	 * Save a collection
	 *
	 * @param Collection\CollectionInterface $collection A collection to save
	 * @return bool true on success, false on failure
	 */
	private function saveCollection(Collection\CollectionInterface $collection)
	{
		// TODO: save the collection to the database
		if (!$collection->getPartnerId())
			return $this->returnError("Cannot save collections because partner is not saved", __FILE__, __LINE__);

		if ($collection->getId())
		{
			$sql = "UPDATE object_sync_partner_collections SET
					type ='" . $collection->getType() . "',
					partner_id='" . $collection->getPartnerId() . "',
					last_commit_id=" . $this->dbh->escapeNumber($collection->getLastCommitId()) . ",
					object_type='" . $this->dbh->escape($collection->getObjType()) . "',
					field_name='" . $this->dbh->escape($collection->getFieldName()) . "',
					revision=" . $this->dbh->escapeNumber($collection->getRevision()) . ",
					conditions='" . $this->dbh->escape(serialize($collection->getConditions())) . "'
					WHERE id='" . $collection->getId() . "'";
		}
		else
		{
			$sql = "INSERT INTO object_sync_partner_collections(partner_id, object_type, 
						field_name, conditions, last_commit_id, type, revision) 
					VALUES(
						'" . $collection->getPartnerId() . "',
						'" . $this->dbh->escape($collection->getObjType()) . "',
						'" . $this->dbh->escape($collection->getFieldName()) . "',
						'" . $this->dbh->escape(serialize($collection->getConditions())) . "',
						" . $this->dbh->escapeNumber($collection->getLastCommitId()) . ",
						" . $this->dbh->escapeNumber($collection->getType()) . ",
						" . $this->dbh->escapeNumber($collection->getRevision()) . "
					); SELECT currval('object_sync_partner_collections_id_seq') as id;";
		}

		// Run query
		$result = $this->dbh->query($sql);
		if (!$result)
		{
			return $this->returnError("Error saving: " . $this->dbh->getLastError(), __FILE__, __LINE__);
		}

		if (!$collection->getId() )
			$collection->setId($this->dbh->getValue($result, 0, "id"));

		return true;

	}

	/**
	 * Delete a collection
	 *
	 * @param int $collectionId The id of the collection to delete
	 * @return bool true on success, false on failure
	 */
	private function deleteCollection($collectionId)
	{
		if (!$collectionId)
			return $this->returnError("Collection id is a required param", __FILE__, __LINE__);

		$sql = "DELETE FROM object_sync_partner_collections WHERE id=";
		$sql .= $this->dbh->escapeNumber($collectionId);
		$result = $this->dbh->query($sql);
		if (!$result)
		{
			return $this->returnError(
				"Error deleting addCollection: " . $this->dbh->getLastError(), 
				__FILE__, __LINE__
			);
		}

		return true;
	}

	/**
     * Mark a commit as stale for all sync collections
     *
     * @param int $colType Type from \Netric\EntitySync::COLL_TYPE_*
     * @param string $lastCommitId
     * @param string $newCommitId
     */
    public function setExportedStale($collType, $lastCommitId, $newCommitId)
    {
    	// Set previously exported commits as stale
    	$sql = "UPDATE 
	            object_sync_export 
	        SET 
	            new_commit_id='" . $this->dbh->escape($newCommitId) . "' 
	        WHERE 
	            collection_type='" . $this->dbh->escape($collType) . "' 
	            AND commit_id='" . $this->dbh->escape($lastCommitId) . "';";
        $this->dbh->query($sql);

        // Set previously stale commits as even more stale
        $sql = "UPDATE 
	            object_sync_export 
	        SET 
	            new_commit_id='" . $this->dbh->escape($newCommitId) . "' 
	        WHERE 
	            collection_type='" . $this->dbh->escape($collType) . "' 
	            AND new_commit_id='" . $this->dbh->escape($lastCommitId) . "';";
        $this->dbh->query($sql);
    }

    /**
     * Log that a commit was exported from this collection
     *
     * @param int $colType Type from \Netric\EntitySync::COLL_TYPE_*
     * @param int $collectionId The unique id of the collection we exported for
     * @param int $uniqueId Unique id of the object sent
     * @param int $commitId The commit id synchronized, if null then delete the entry
     * @return bool true on success, false on failure
     */
    public function logExported($collType, $collectionId, $uniqueId, $commitId)
    {
    	$updateSql = "";

    	$existsSql = "SELECT unique_id FROM object_sync_export 
    				  WHERE collection_id=" . $this->dbh->escapeNumber($collectionId) . " 
    				  	AND unique_id=" . $this->dbh->escapeNumber($uniqueId) . ";";
    	if (!$this->dbh->getNumRows($this->dbh->query($existsSql)))
    	{
    		$updateSql = "INSERT INTO object_sync_export(collection_type, commit_id, collection_id, unique_id)
    					  VALUES(
    					  	" . $this->dbh->escapeNumber($collType) . ",
    					  	'" . $this->dbh->escape($commitId) . "',
    					  	" . $this->dbh->escapeNumber($collectionId) . ",
    					  	'" . $this->dbh->escape($uniqueId) . "'
    					  );";
    	}
    	else
    	{

            if (!$commitId)
            {
                $updateSql = "DELETE from object_sync_export WHERE
                              collection_id=" . $this->dbh->escapeNumber($collectionId) . "
                              AND unique_id='" . $this->dbh->escape($uniqueId) . "'";
            }
            else
            {
                $updateSql = "UPDATE object_sync_export
                              SET
                                commit_id='" . $this->dbh->escape($commitId) . "',
                                new_commit_id=NULL
                              WHERE
                                collection_id=" . $this->dbh->escapeNumber($collectionId) . "
                                AND unique_id='" . $this->dbh->escape($uniqueId) . "'";
            }
    	}

    	if (!$this->dbh->query($updateSql))
			return $this->returnError("DB Error: " . $this->dbh->getLastError(), __FILE__, __LINE__);
		else
			return true;
    }

    /**
	 * Get a list of previously exported commits that have been updated
	 *
	 * This is used to get a list of objects that were previously synchornized
	 * but were later either moved outside the collection (no longer met conditions)
	 * or deleted.
	 *
	 * This function will only return 1000 entries at a time so it should be called
	 * repeatedly until the number of stats returned is 0 to process all the way
	 * through the queue.
	 *
	 * NOTE: THIS MUST BE RUN AFTER GETTING NEW/CHANGED OBJECTS IN A COLLECTION.
	 * 	1. Get all new commits from last_commit and log the export
	 * 	2. Once all new commit updates were retrieved for a collection then call this
	 *  3. Once this returns empty then fast-forward this collection to head
	 *
	 * @param int $collectionId The id of the collection we get stats for
	 * @return int[] Array of stale IDs
	 */
	public function getExportedStale($collectionId)
	{
		if (!is_numeric($collectionId))
		{
			throw new \Exception("A valid $collectionId is a required param.");
		}

		$staleStats = array();

		// Get everything from the exported log that is set as stale
    	$sql = "SELECT unique_id FROM object_sync_export 
    			WHERE collection_id=" . $this->dbh->escapeNumber($collectionId) . "
    				AND new_commit_id IS NOT NULL LIMIT 1000;";
        $result = $this->dbh->query($sql);
		if (!$result)
			throw new \Exception("There was a problem querying: " . $this->dbh->getLastError());

		$num = $this->dbh->getNumRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$uniqueId = $this->dbh->getValue($result, $i, "unique_id");
			$staleStats[] = $uniqueId;
		}

		return $staleStats;
	}

	/**
	 * Get a list of previously imported objects
	 *
	 * @param int $collectionId The id of the collection we get stats for
     * @throws \InvalidArgumentException If there is no collection id
     * @throws \Exception if we cannot query the database
     * @return array(array('remote_id', 'remote_revision', 'local_id', 'local_revision'))
	 */
	public function getImported($collectionId)
	{
		if (!is_numeric($collectionId))
		{
			throw new \InvalidArgumentException("A valid $collectionId is a required param.");
		}

		$importedStats = array();

		// Get everything from the exported log that is set as stale
    	$sql = "SELECT unique_id, remote_revision, object_id, revision FROM object_sync_import
    			WHERE collection_id=" . $this->dbh->escapeNumber($collectionId) . ";";
        $result = $this->dbh->query($sql);
		if (!$result)
			throw new \Exception("There was a problem querying: " . $this->dbh->getLastError());

		$num = $this->dbh->getNumRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $this->dbh->getRow($result, $i);
			$importedStats[] = array(
				'remote_id' => $row['unique_id'],
                'remote_revision' => $row['remote_revision'],
				'local_id' => $row['object_id'],
                'local_revision' => $row['revision'],
			);
		}

		return $importedStats;
	}

	/**
     * Log that a commit was exported from this collection
     *
     * @param int $collectionId The id of the collection we are logging changes to
     * @param string $remoteId The foreign unique id of the object being imported
     * @param int $remoteRevision A revision of the remote object (could be an epoch)
     * @param int $localId If imported to a local object then record the id, if null the delete
     * @param int $localRevision The revision of the local object
     * @return bool true if imported false if failure
     */
    public function logImported($collectionId, $remoteId, $remoteRevision=null, $localId=null, $localRevision=null)
    {
    	$updateSql = "";

        if (!$remoteId)
            throw new \InvalidArgumentException("remoteId was not set and is required.");

    	if ($localId)
    	{
    		$existsSql = "SELECT unique_id FROM object_sync_import 
    				  WHERE collection_id=" . $this->dbh->escapeNumber($collectionId) . " 
    				  	AND unique_id=" . $this->dbh->escapeNumber($remoteId) . ";";
	    	if (!$this->dbh->getNumRows($this->dbh->query($existsSql)))
	    	{
	    		$updateSql = "INSERT INTO object_sync_import(unique_id, remote_revision, collection_id, object_id, revision)
	    					  VALUES(
	    					  	'" . $this->dbh->escape($remoteId) . "',
	    					  	" . $this->dbh->escapeNumber($remoteRevision) . ",
	    					  	" . $this->dbh->escapeNumber($collectionId) . ",
	    					  	" . $this->dbh->escapeNumber($localId) . ",
	    					  	" . $this->dbh->escapeNumber($localRevision) . "
	    					  );";
	    	}
	    	else
	    	{
	    		$updateSql = "UPDATE object_sync_import
							  SET
							    remote_revision='" . $this->dbh->escape($remoteRevision) . "',
							  	revision='" . $this->dbh->escape($localRevision) . "',
							  	object_id=" . $this->dbh->escapeNumber($localId) . " 
							  WHERE 
							  	collection_id=" . $this->dbh->escapeNumber($collectionId) . " 
							  	AND unique_id='" . $this->dbh->escape($remoteId) . "'";

	    	}
    	}
    	else
    	{
    		/*
    		 * If we have no localId then that means the import is no longer part of the local store
    		 * and has not been imported so delete the log entry.
    		 */
    		$updateSql = "DELETE FROM object_sync_import
						  WHERE collection_id=" . $this->dbh->escapeNumber($collectionId) . " 
							  	AND unique_id='" . $this->dbh->escape($remoteId) . "'";
    	}
    	

    	if (!$this->dbh->query($updateSql))
			return $this->returnError("DB Error: " . $this->dbh->getLastError(), __FILE__, __LINE__);
		else
			return true;
    }
 }
