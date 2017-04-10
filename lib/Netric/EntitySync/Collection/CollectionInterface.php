<?php
/**
 * Sync collection interface
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\EntitySync\Collection;

interface CollectionInterface
{
	/**
	 * Get a id if it is saved
	 *
	 * @return string
	 */
	public function getId();

	/**
	 * Get a stats list of what has changed locally since the last sync
	 *
	 * @param bool $autoFastForward If true (default) then fast-forward collection commit_id on return
     * @param \DateTime $limitUpdatesAfter If set, only pull updates after a specific date
	 * @return array of associative array [
     *      [
     *          "id", // Unique id of local object
     *          "action", // 'change'|'delete',
     *          "commit_id" // Incremental id of the commits - global revision
     *      ]
     *  ]
	 */
	public function getExportChanged($autoFastForward=true, \DateTime $limitUpdatesAfter = null);

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
	public function getImportChanged(array $importList);

	/**
	 * Get a collection type id
	 *
	 * @return int Type from \Netric\EntitySync::COLL_TYPE_*
	 */
	public function getType();

	/**
	 * Fast forward this collection to current head which resets it to only get future changes
	 */
	public function fastForwardToHead();

	/**
	 * Set the object type if applicable
	 *
	 * @param string $objType
	 */
	public function setObjType($objType);

	/**
	 * Get the object type if applicable
	 *
	 * @return string
	 */
	public function getObjType();

	/**
	 * Set conditions with array
	 *
	 * @param array $conditions array(array("blogic", "field", "operator", "condValue"))
	 */
	public function setConditions($conditions);

	/**
	 * Get conditions
	 *
	 * @return array(array("blogic", "field", "operator", "condValue"))
	 */
	public function getConditions();

	/**
	 * Set the last commit id synchronized
	 *
	 * @param string $commitId
	 */
	public function setLastCommitId($commitId);

    /**
     * Log that a commit was exported from this collection
     *
     * @param int $uniqueId The unique id of the object we sent
     * @param int $commitId The unique id of the commit we sent
     * @return bool
     */
    public function logExported($uniqueId, $commitId);

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
	public function logImported($remoteId, $remoteRevision=null, $localId=null, $localRevision=null);
}