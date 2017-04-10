<?php
/**
 * Sync Partner - replacing devices
 *
 * @category  AntObjectSync
 * @package   Partner
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class used to represent a sync partner or endpoint
 */
class AntObjectSync_Partner
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
	 * Current user
	 *
	 * @var AntUser
	 */
	public $user = null;

	/**
	 * Owner of this partnership 
	 *
	 * @var int
	 */
	public $ownerId = null;

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
	 * Object collections this partner is listening for
	 *
	 * For example: 'customer','task' would mean the partner is
	 * only tracking changes for objects of type customer and task
	 * but will ignore all others. This will keep overhead to a minimal
	 * when tracking changes. In additional collections can have filters
	 * allowing synchronization of a subset of data.
	 *
	 * @var AntObjectSync_Collection[]
	 */
	public $collections = array();

	/**
	 * Flag used for debugging
	 *
	 * @var bool
	 */
	public $debug = false;


	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param string $partnerId The unique id of this partnership
	 * @param AntUser $user Current user object
	 */
	public function __construct($dbh, $partnerId = null, $user=null)
	{
		$this->dbh = $dbh;
		$this->partnerId = $partnerId;
		$this->user = $user;
		if ($this->user)
			$this->ownerId = $this->user->id;

		if ($partnerId != null)
			$this->load($partnerId);
	}

	/**
	 * Get partnership data from database
	 *
	 * By default this will create the partnership if it does not
	 * already exist in the partner list.
	 *
	 * @param string $partnerId The partneer id to load from the database
	 * @param bool $createIfMissing If the partnership is missing then create it
	 * @return bool true on success, false on failure
	 */
	public function load($partnerId, $createIfMissing=true)
	{
		if (empty($partnerId))
			return false;

		$result = $this->dbh->Query("SELECT id, owner_id FROM object_sync_partners WHERE pid='".$this->dbh->Escape($partnerId)."'");
		if ($this->dbh->GetNumberRows($result))
		{
			$row = $this->dbh->GetRow($result, 0);
			$this->id = $row['id'];
			$this->ownerId = $row['owner_id'];
		}
		else if ($createIfMissing)
		{
			$result = $this->dbh->Query("INSERT INTO object_sync_partners(pid, owner_id)
			   							 VALUES('".$this->dbh->Escape($partnerId)."', ".$this->dbh->EscapeNumber($this->ownerId).");
										 SELECT currval('object_sync_partners_id_seq') as id;");
			if ($this->dbh->GetNumberRows($result))
				$this->id = $this->dbh->GetValue($result, 0, "id");
		}

		if ($this->id)
		{
			$this->loadCollections();
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Load collections
	 */
	private function loadCollections()
	{
		if (!$this->id)
			return false;


		$result = $this->dbh->Query("SELECT id FROM object_sync_partner_collections WHERE partner_id='".$this->id."'");
		$num = $this->dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $this->dbh->GetRow($result, $i);

			$this->collections[] = new AntObjectSync_Collection($this->dbh, $row['id'], $this->user);
			/*
			$this->collections[] = array(
				"id" => $row['id'],
				"object_type" => $row['object_type'],
				"object_type_id" => $row['object_type_id'],
				"field_id" => $row['field_id'],
				"field_name" => $row['field_name'],
				"ts_last_sync" => $row['ts_last_sync'],
				"conditions" => unserialize($row['conditions']),
			);
			 */
		}
	}

	/**
	 * Save partnership to the database
	 */
	public function save()
	{
		if (!$this->partnerId)
			return false;

		// Save partnership info
		$data = array(
			"pid" => $this->partnerId,
			"owner_id" => $this->ownerId,
		);

		if ($this->id)
		{
			$update = "";
			foreach ($data as $col=>$val)
			{
				if ($update)
					$update .= ", ";
				$update .= $col . "='" . $this->dbh->Escape($val) . "'";
			}

			$query = "UPDATE object_sync_partners SET $update WHERE id='" . $this->id . "';";
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
				$vals .= "'" . $this->dbh->Escape($val) . "'";
			}

			$query = "INSERT INTO object_sync_partners($flds) VALUES($vals); 
					  SELECT currval('object_sync_partners_id_seq') as id;";
		}

		$result = $this->dbh->Query($query);
		if ($result == false)
			return false;

		if (!$this->id)
			$this->id = $this->dbh->GetValue($result, 0, "id");


		// Save collections 
		$this->saveCollections();

		// Send the id as confirmation that the partnership has been saved
		return $this->id;
	}

	/**
	 * Save collections to the database
	 */
	private function saveCollections()
	{
		if (!is_numeric($this->id))
			return false;

		for ($i = 0; $i < count($this->collections); $i++)
		{
			$this->collections[$i]->partnerId = $this->id;
			$this->collections[$i]->save();
			//$this->saveCollection($this->collections[$i]);
		}

		return true;
	}

	/**
	 * Remove
	 */
	public function remove()
	{
		if ($this->id)
			$this->dbh->Query("DELETE FROM object_sync_partners WHERE id='" . $this->id . "'");
		else if ($this->partnerId)
			$this->dbh->Query("DELETE FROM object_sync_partners WHERE pid='" . $this->dbh->Escape($this->partnerId) . "'");

		return true;
	}

	/**
	 * Check to see if this partnership is listening for changes for a specific type of object
	 *
	 * @param string $obj_type The name of the object type to check
	 * @param string $fieldName Name of a field if this is a grouping collection
	 * @param array $conditions Array of conditions used to filter the collection
	 * @param bool $addIfMissing Add the object type to the list of synchronized objects if it does not already exist
	 * @return AntObjectSync_Collection|bool collection on found, false if none found
	 */
	public function getCollection($obj_type, $fieldName=null, $conditions=array(), $addIfMissing=false)
	{
		$ret = false;
		if (!is_array($conditions))
			$conditions = array();

		foreach ($this->collections as $ent)
		{
			if (!is_array($ent->conditions))
				$ent->conditions = array();

			if ($obj_type == $ent->objectType && count($conditions) == count($ent->conditions))
			{
				if ($fieldName)
				{
					if ($fieldName == $ent->fieldName)
						$ret = $ent;
				}
				else if (!$ent->fieldName)
				{
					$ret = $ent;
				}

				// Make sure conditions match - if not set back to false
				if ($ret!=false && count($conditions) > 0)
				{
					// Compare against challenge list
					foreach ($conditions as $cond)
					{
						$found = false;
						foreach ($ent->conditions as $cmdCond)
						{
							if ($cmdCond['blogic'] == $cond['blogic'] 
								&& $cmdCond['field'] == $cond['field'] 
								&& $cmdCond['operator'] == $cond['operator'] 
								&& $cmdCond['condValue'] == $cond['condValue'])
							{
								$found = true;
							}
						}

						if (!$found)
						{
							$ret = false;
							break;
						}
					}

					// Compare against collection conditions
					foreach ($ent->conditions as $cond)
					{
						$found = false;
						foreach ($conditions as $cmdCond)
						{
							if ($cmdCond['blogic'] == $cond['blogic'] 
								&& $cmdCond['field'] == $cond['field'] 
								&& $cmdCond['operator'] == $cond['operator'] 
								&& $cmdCond['condValue'] == $cond['condValue'])
							{
								$found = true;
							}
						}

						if (!$found)
						{
							$ret = false;
							break;
						}
					}
				}
			}
		}

		if (!$ret && $addIfMissing)
			$ret = $this->addCollection($obj_type, $fieldName, $conditions);

		return $ret;
	}

	/**
	 * Add an object type to the list of synchronized objects for this partnership
	 *
	 * @param string $obj_type The name of the object type to check
	 * @param string $fieldName optional field name of synchronizing grouping fields
	 * @param CAntObjectCond[] $conditions Array of conditions used to filter the collection
	 * @return array|bool entity associative array if the partner is listening, false if it should ignore this object type
	 */
	public function addCollection($obj_type, $fieldName=null, $conditions=array())
	{
		// Make sure we are not already listening
		$ret = $this->getCollection($obj_type, $fieldName);
		if ($ret)
			return $ret;

		$odef = CAntObject::factory($this->dbh, $obj_type);
		$fieldId = null;
		if ($fieldName)
		{
			$field = $odef->fields->getField($fieldName);
			$fieldId = $field['id'];
		}

		$col = new AntObjectSync_Collection($this->dbh, null, $this->user);
		$col->objectType = $obj_type;
		$col->objectTypeId = $odef->object_type_id;
		$col->fieldName = $fieldName;
		$col->fieldId = $fieldId;
		$col->conditions = $conditions;
		if ($this->id)
		{
			$col->partnerId = $this->id;
			$col->save();
		}
		$this->collections[] = $col;

		/*
		$ind = count($this->collections);
		$this->collections[$ind] = array(
			"id" => null,
			"object_type" => $obj_type, 
			"object_type_id" => $odef->object_type_id, 
			"field_name" => $fieldName, 
			"field_id" => $fieldId, 
			"ts_last_sync"=>null,
			"conditions"=>$conditions,
		);

		// Store in database
		if ($this->id)
			$this->saveCollections();

		return $this->collections[$ind];
		 */

		return $col;
	}

	/**
	 * Clear all collections
	 */
	public function removeCollections()
	{
		// TODO: delete saved collections
		
		$this->collections = array();
	}

	/**
	 * Get collections for an object
	 *
	 * @param CAntObject $obj The prospective object to check against collections
	 * @return AntObjectSync_Collection[]
	 */
	public function getObjectCollections($obj)
	{
		$ret = array();

		foreach ($this->collections as $coll)
		{
			if ($coll->objectType == $obj->object_type)
			{
				if ($coll->conditionsMatchObj($obj))
				{
					$ret[] = $coll;
				}
			}
		}

		return $ret;
	}

	/**
	 * Get collections for an object
	 *
	 * @param string $objType The object type name
	 * @param string $fieldName The field name
	 */
	public function getGroupingCollections($objType, $fieldName)
	{
		$ret = array();

		foreach ($this->collections as $coll)
		{
			if ($coll->objectType == $objType && $fieldName == $coll->fieldName)
				$ret[] = $coll;
		}

		return $ret;
	}
}
