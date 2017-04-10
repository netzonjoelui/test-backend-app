<?php
/**
 * Geneic object sync class
 *
 * The main idea is that this class should return a list of entities
 * that have been added, changed or deleted since the last sync.
 *
 * @category Netric
 * @package EntitySync
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2015 Aereus Corporation (http://www.aereus.com)
 */

namespace Netric\EntitySync;

/**
 * Sync class
 */
class EntitySync
{
    /**
     * Collection types
     */
    const COLL_TYPE_ENTITY = 1;
    const COLL_TYPE_GROUPING = 2;
    const COLL_TYPE_ENTITYDEF = 3;

    /**
     * Current sync partner instance
     */
    private $partner = null;

    /**
     * If set then skip over a specific collection
     *
     * @var int
     */
    public $ignoreCollection = null;

    /**
     * DataMapper for persistent storage
     *
     * @var DataMapperInterface
     */
    private $dataMapper = null;

    /**
     * Constructor
     *
     * @param \Netric\EntitySync\DataMapperInterface $dm
     */
    public function __construct(DataMapperInterface $dm) 
    {
            $this->dataMapper = $dm;
    }

    /**
     * Get device
     *
     * @param string $devid The device id to query stats for
     * @throws \Exception if no partner id is defined
     * @return \Netric\EntitySync\Partner
     */
    public function getPartner($pid)
    {
        if (!$pid)
        {
            throw new \Exception("Partner id is required");
        }

        // First get cached partners because we do not want to load them twice
        if ($this->partner)
        {
            if ($this->partner->getPartnerId() == $pid)
            {
                return $this->partner;
            }
        }

        // Load the partner from the database
        $this->partner = $this->dataMapper->getPartnerByPartnerId($pid);

        return $this->partner;		
    }

    /**
     * Create a new partner
     *
     * @param string $pid The unique partner id
     * @param string $ownerId The unique id of the owning user
     * @return \Netric\EntitySync\Partner
     */
    public function createPartner($pid, $ownerId)
    {
        $partner = new Partner($this->dataMapper);
        $partner->setPartnerId($pid);
        $partner->setOwnerId($ownerId);
        $this->dataMapper->savePartner($partner);
        return $partner;
    }

    /**
     * Save a partner
     *
     * @params \Netric\EntitySync\Partner $partner
     */
    public function savePartner(Partner $partner)
    {
        $this->dataMapper->savePartner($partner);
    }

    /**
     * Delete a partner
     *
     * @param Partner $partner
     */
    public function deletePartner(Partner $partner)
    {
        $this->dataMapper->deletePartner($partner);
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
        return $this->dataMapper->setExportedStale($collType, $lastCommitId, $newCommitId);
    }

    /**
     * Send object grouping/field change
     *
     * Update stat partnership collections if any match the given field and value
     *
     * @param string $objType The type of entity we are working with
     * @param string $fieldName The name of the grouping field that was changed
     * @param int $fieldVal The id of the grouping that was changed
     * @param char $action The action taken: 'c' = changed, 'd' = deleted
     * @return int[] IDs of collections that were updated with the object
     */
    public function updateGroupingStat($objType, $fieldName, $fieldVal, $action='c')
    {
        $ret = array();
        $field = $this->obj->def->getField($fieldName);

        if (!$field)
                return false;

        // Get all collections that match the conditions
        $partnerships = $this->dataMapper->getListeningPartners($fieldName);
        foreach ($partnerships as $partner)
        {
            $collections = $partner->getGroupingCollections($this->obj->object_type, $fieldName);
            foreach ($collections as $coll)
            {
                $coll->updateGroupingStat($fieldVal, $action);
                $ret[] = $coll->id;
            }
        }

        return $ret;
    }

    
}
