<?php
/**
 * Generic object sync class
 *
 * The main idea is that this class should return a list of objects
 * that have been added, changed or deleted since the last sync.
 *
 * @category  CAntObjectList
 * @package   Sync
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");
require_once("lib/ObjectSync/Partner.php");
require_once("lib/ObjectSync/Collection.php");

/**
 * Synd class
 */
class AntObjectSync extends CAntObjectList
{
	/**
	 * Current sync partner instance
	 */
	public $partner = null;

	/**
	 * If set then skip over a specific collection
	 *
	 * @var int
	 */
	public $ignoreCollection = null;

	/**
	 * Get device
	 *
	 * @param string $devid The device id to query stats for
	 */
	public function getPartner($pid)
	{
		if (!$pid)
			throw new Exception("Partner id is required");

		if ($this->partner)
		{
			if ($this->partner->partnerId != $pid)
				$this->partner = null; // Reset
		}
		
		if (!$this->partner)
			$this->partner = new AntObjectSync_Partner($this->dbh, $pid, $this->user);

		return $this->partner;
	}

	/**
	 * Get listening partnership for this object type
	 *
	 * @param string $fieldName If the fieldname is set then try to find devices listening for an object grouping change
	 * @return AntObjectSync_Device[]
	 */
	public function getListeningPartners($fieldName=null)
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

	/**
	 * Send object change
	 *
	 * This function takes a changed object as a param and will propogate the change
	 * to all partnerships>collections that are listening for a change with a matching object
	 * type and filter.
	 *
	 * @param int $oid The object id to stat
	 * @param char $action The action taken: 'c' = changed, 'd' = deleted
	 * @param int $revision Can be used to log a specific revision
	 * @param int $parentId Can be used to log a specific parent - useful for historical actions
	 * @return int[] IDs of collections that were updated with the object
	 */
	public function updateObjectStat($oid, $action='c', $revision=null, $parentId=null)
	{
		$ret = array();
		$obj = CAntObject::factory($this->dbh, $this->obj->object_type, $oid, $this->user);

		// Get all collections that match the conditions
		$partnerships = $this->getListeningPartners();
		foreach ($partnerships as $partner)
		{
			$collections = $partner->getObjectCollections($obj);
			foreach ($collections as $coll)
			{
				if ($this->debug)
					$coll->debug = true;

				// Check if this collection was set to be ignored
				if ($coll->id == $this->ignoreCollection)
					continue;

				if ($revision) // Stat a specific revision
					$coll->updateObjectStatVals($oid, $revision, $parentId, $action);
				else
					$coll->updateObjectStat($obj, $action);

				$ret[] = $coll->id;
			}
		}

		return $ret;
	}

	/**
	 * Send object grouping/field change
	 *
	 * Update stat partnership collections if any match the given field and value
	 *
	 * @param string $fieldName The name of the grouping field that was changed
	 * @param int $fieldVal The id of the grouping that was changed
	 * @param char $action The action taken: 'c' = changed, 'd' = deleted
	 * @return int[] IDs of collections that were updated with the object
	 */
	public function updateGroupingStat($fieldName, $fieldVal, $action='c')
	{
		$ret = array();
		$field = $this->obj->def->getField($fieldName);

		if (!$field)
			return false;

		// Get all collections that match the conditions
		$partnerships = $this->getListeningPartners($fieldName);
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
