<?php
/**
 * Sync Device
 *
 * @category  AntObjectSync
 * @package   Device
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class used to represent a sync device
 */
class AntObjectSync_Device
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
	 * Owner of this device
	 *
	 * @var int
	 */
	public $ownerId = null;

	/**
	 * Device id
	 *
	 * @var string
	 */
	public $deviceId = null;

	/**
	 * Last sync time
	 *
	 * @var string
	 */
	public $lastSync = null;

	/**
	 * Objects this device is listening for
	 *
	 * For example: 'customer','task' would mean the device is
	 * only tracking changes for objects of type customer and task
	 * but will ignore all others. This will keep overhead to a minimal
	 * when tracking changes.
	 *
	 * @var string[]
	 */
	public $entities = array();

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
	 * @param string $devid The unique id of this device
	 * @param AntUser $user Current user object
	 */
	public function __construct($dbh, $devid = null, $user=null)
	{
		$this->dbh = $dbh;
		$this->deviceId = $devid;
		$this->user = $user;
		if ($this->user)
			$this->ownerId = $this->user->id;

		if ($devid != null)
			$this->load($devid);
	}

	/**
	 * Get device from database
	 *
	 * By default this will create the device if it does not
	 * already exist in the device list.
	 *
	 * @param string $devid The device id to load from the database
	 * @param bool $createIfMissing If the device is missing then create it
	 * @return bool true on success, false on failure
	 */
	public function load($devid, $createIfMissing=true)
	{
		if (empty($devid))
			return false;

		$result = $this->dbh->Query("SELECT id, owner_id FROM object_sync_devices WHERE dev_id='".$this->dbh->Escape($devid)."'");
		if ($this->dbh->GetNumberRows($result))
		{
			$row = $this->dbh->GetRow($result, 0);
			$this->id = $row['id'];
			$this->ownerId = $row['owner_id'];
		}
		else if ($createIfMissing)
		{
			$result = $this->dbh->Query("INSERT INTO object_sync_devices(dev_id, owner_id)
			   							 VALUES('".$this->dbh->Escape($devid)."', ".$this->dbh->EscapeNumber($this->ownerId).");
										 SELECT currval('object_sync_devices_id_seq') as id;");
			if ($this->dbh->GetNumberRows($result))
				$this->id = $this->dbh->GetValue($result, 0, "id");
		}

		if ($this->id)
		{
			$this->loadEntities();
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Load entities
	 */
	private function loadEntities()
	{
		if (!$this->id)
			return false;


		$result = $this->dbh->Query("SELECT id, object_type, object_type_id, field_id, field_name, ts_last_sync FROM 
									 object_sync_device_entities WHERE device_id='".$this->id."'");
		$num = $this->dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $this->dbh->GetRow($result, $i);

			$this->entities[] = array(
				"id" => $row['id'],
				"object_type" => $row['object_type'],
				"object_type_id" => $row['object_type_id'],
				"field_id" => $row['field_id'],
				"field_name" => $row['field_name'],
				"ts_last_sync" => $row['ts_last_sync'],
			);
		}
	}

	/**
	 * Save device to the database
	 */
	public function save()
	{
		if (!$this->deviceId)
			return false;

		// Save device info
		$data = array(
			"dev_id" => $this->deviceId,
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

			$query = "UPDATE object_sync_devices SET $update WHERE id='" . $this->id . "';";
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

			$query = "INSERT INTO object_sync_devices($flds) VALUES($vals); 
					  SELECT currval('object_sync_devices_id_seq') as id;";
		}

		$result = $this->dbh->Query($query);
		if ($result == false)
			return false;

		if (!$this->id)
			$this->id = $this->dbh->GetValue($result, 0, "id");


		// Save entities
		$this->saveEntities();

		// Send the id as confirmation that the device has been saved
		return $this->id;
	}

	/**
	 * Save entities to the database
	 */
	private function saveEntities()
	{
		if (!is_numeric($this->id))
			return false;

		foreach ($this->entities as $ent)
		{
			$this->saveEntity($ent);
		}

		return true;
	}

	/**
	 * Save entity to database
	 *
	 * @param array $entity Associative array representing the entity
	 */
	private function saveEntity($entity)
	{
		if (!$this->id)
			return false;

		if ($entity['id'])
		{
			$sql = "UPDATE object_sync_device_entities SET
					device_id='".$this->id."',
					object_type='".$this->dbh->Escape($entity['object_type'])."',
					object_type_id=".$this->dbh->EscapeNumber($entity['object_type_id']).",
					field_name='".$this->dbh->Escape($entity['field_name'])."',
					field_id=".$this->dbh->EscapeNumber($entity['field_id'])."
					WHERE id='" . $entity['id'] . "'";
		}
		else
		{
			$sql = "INSERT INTO object_sync_device_entities(device_id, object_type, object_type_id, field_name, field_id) 
					VALUES(
						'" . $this->id . "',
						'" . $this->dbh->Escape($entity['object_type']) . "',
						" . $this->dbh->EscapeNumber($entity['object_type_id']) . ",
						'" . $this->dbh->Escape($entity['field_name']) . "',
						" . $this->dbh->EscapeNumber($entity['field_id']) . "
					); SELECT currval('object_sync_device_entities_id_seq') as id;";
		}

		// Run query
		$result = $this->dbh->Query($sql);

		if (!$entity['id'])
		{
			$entId = $this->dbh->GetValue($result, 0, "id");

			// Add id to entity array
			for ($i = 0; $i < count($this->entities); $i++)
			{
				$ent = $this->entities[$i];

				if ($entity['object_type'] == $ent['object_type'] && $entity['field_id'] == $ent['field_id'])
				{
					$this->entities[$i]['id'] = $entId;
				}
			}
		}
	}

	/**
	 * Remove
	 */
	public function remove()
	{
		if ($this->id)
			$this->dbh->Query("DELETE FROM object_sync_devices WHERE id='" . $this->id . "'");
		else if ($this->deviceId)
			$this->dbh->Query("DELETE FROM object_sync_devices WHERE dev_id='" . $this->dbh->Escape($this->deviceId) . "'");

		return true;
	}

	/**
	 * Check to see if this device is listening for changes for a specific type of object
	 *
	 * @param string $obj_type The name of the object type to check
	 * @param bool $addIfMissing Add the object type to the list of synchronized objects if it does not already exist
	 * @return array|bool entity associative array if the device is listening, false if it should ignore this object type
	 */
	public function isListening($obj_type, $fieldName=null, $addIfMissing=false)
	{
		$ret = false;

		foreach ($this->entities as $ent)
		{
			if ($obj_type == $ent['object_type'])
			{
				if ($fieldName)
				{
					if ($fieldName == $ent['field_name'])
						$ret = $ent;
				}
				else if (!$ent['field_name'])
				{
					$ret = $ent;
				}
			}
		}

		if (!$ret && $addIfMissing)
			$ret = $this->addListening($obj_type, $fieldName);

		return $ret;
	}

	/**
	 * Add an object type to the list of synchronized objects for this device
	 *
	 * @param string $obj_type The name of the object type to check
	 * @param string $fieldName optional field name of synchronizing grouping fields
	 * @return array|bool entity associative array if the device is listening, false if it should ignore this object type
	 */
	public function addListening($obj_type, $fieldName=null)
	{
		// Make sure we are not already listening
		$ret = $this->isListening($obj_type, $fieldName);
		if ($ret)
			return $ret;

		$odef = CAntObject::factory($this->dbh, $obj_type);
		$fieldId = null;
		if ($fieldName)
		{
			$field = $odef->fields->getField($fieldName);
			$fieldId = $field['id'];
		}

		$ind = count($this->entities);
		$this->entities[$ind] = array(
			"id" => null,
			"object_type" => $obj_type, 
			"object_type_id" => $odef->object_type_id, 
			"field_name" => $fieldName, 
			"field_id" => $fieldId, 
			"ts_last_sync"=>null,
		);

		// Store in database
		if ($this->id)
			$this->saveEntities();

		return $this->entities[$ind];
	}

	/**
	 * Set entity sync timestamp
	 *
	 * @param string $obj_type The name of the object type to check
	 * @param string $fieldName optional field name of synchronizing grouping fields
	 */
	public function setEntitySyncTime($obj_type, $fieldName=null)
	{
		$time = date("Y-m-d H:i:s");

		for ($i = 0; $i < count($this->entities); $i++)
		{
			$ent = $this->entities[$i];

			if ($obj_type == $ent['object_type'] && $fieldName == $ent['field_name'])
			{
				$this->entities[$i]['ts_last_sync'] = $time;
			}
		}

		$odef = CAntObject::factory($this->dbh, $obj_type);
		$fieldId = null;
		if ($fieldName)
		{
			$field = $odef->fields->getField($fieldName);
			$fieldId = $field['id'];
		}

		$sql = "UPDATE object_sync_device_entities SET ts_last_sync='$time' 
				WHERE device_id='" . $this->id . "' AND object_type_id='" . $odef->object_type_id . "'";
		if ($fieldId)
			$sql .= " AND field_id=" . $this->dbh->EscapeNumber($fieldId);
		else
			$sql .= " AND field_id is null";
		$this->dbh->Query($sql);

		return true;
	}
}
