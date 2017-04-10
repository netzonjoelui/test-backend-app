<?php
/**
 * Interface defining what an EntitySync must implement
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\EntitySync;

interface DataMapperInterface
{
	/**
	 * Get a partner by unique system id
	 *
	 * This is also responsible for loading collections
	 *
	 * @param string $id Netric unique partner id
	 * @return Partner or null if id does not exist
	 */
	public function getPartnerById($id);

	/**
	 * Get a partner by the remote partner id
	 *
	 * This is also responsible for loading collections
	 *
	 * @param string $partnerId Remotely provided unique ident
	 * @return Partner or null if id does not exist
	 */
	public function getPartnerByPartnerId($partnerId);

	/**
	 * Get a partner by id
	 *
	 * This is also responsible for saving collections for the partner
	 *
	 * @param Partner $partner Will set the id if new partner
	 * @return bool true on success, false on failure
	 */
	public function savePartner(Partner $partner);

	/**
	 * Delete a partner by id
	 *
	 * @param string $id The unique id of the partnership to delete
	 * @return bool true on success, false on failure
	 */
	public function deletePartner($id);

	/**
     * Mark a commit as stale for all sync collections
     *
     * @param int $colType Type from \Netric\EntitySync::COLL_TYPE_*
     * @param string $lastCommitId
     * @param string $newCommitId
     */
    public function setExportedStale($collType, $lastCommitId, $newCommitId);

    /**
     * Log that a commit was exported from this collection
     *
     * @param int $colType Type from \Netric\EntitySync::COLL_TYPE_*
     * @param int $collectionId The unique id of the collection we exported for
     * @param int $uniqueId Unique id of the object sent
     * @param int $commitId The commit id synchronized, if null then delete the entry
     * @return bool true on success, false on failure
     */
    public function logExported($collType, $collectionId, $uniqueId, $commitId);

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
	public function logImported($collectionId, $remoteId, $remoteRevision=null, $localId=null, $localRevision=null);

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
	 * @return array(array('id'=>objectId, 'action'=>'delete'))
	 */
	public function getExportedStale($collectionId);

	/**
	 * Get a list of previously imported objects
	 *
	 * @param int $collectionId The id of the collection we get stats for
	 * @return array(array('uid', 'local_id', 'revision'))
	 */
	public function getImported($collectionId);

	/**
	 * Get listening partnership for this object type
	 *
	 * @param string $fieldName If the fieldname is set then try to find devices listening for an object grouping change
	 * @return AntObjectSync_Device[]
	 */
	//public function getListeningPartners($fieldName=null);
	/*
	{
		$ret = array();

		$field = ($fieldName) ? $this->obj->def->getField($fieldName) : false;

		$sql = "SELECT pid from object_sync_partners WHERE id IN (";
		$sql .= "SELECT partner_id FROM object_sync_partner_collections WHERE ";
		if (is_array($field)) // field id is unique so no object type is needed
			$sql .= " field_id='" . $field['id'] . "'";
		else
			$sql .= " object_type_id='" . $this->obj->object_type_id . "'";
		$sql .= ");";

		$result = $this->dbh->Query($sql);
		$num = $this->dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$ret[] = new AntObjectSync_Partner($this->dbh, $this->dbh->GetValue($result, $i, "pid"), $this->user);
		}

		return $ret;
	}
	*/
}