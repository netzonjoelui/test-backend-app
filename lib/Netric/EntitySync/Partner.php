<?php
/**
 * Sync partner represents a foreign datastore and/or device to import and export data to
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2015 Aereus Corporation (http://www.aereus.com)
 */

namespace Netric\EntitySync;

/**
 * Class used to represent a sync partner or endpoint
 */
class Partner
{
	/**
	 * DataMapper handle
	 *
	 * @var Netric\EntitySync\DataMapperInterface
	 */
	private $dataMapper = null;

	/**
	 * Internal unique identifier for this partnership
	 *
	 * @var int
	 */
	private $id = null;

	/**
	 * The netric user who owns this partnership
	 *
	 * @var int
	 */
	private $ownerId = null;

	/**
	 * Partner id which is a foreign id but must be unique
	 *
	 * Mobile devices send unique identifiers like "iphone-43342543543..."
	 *
	 * @var string
	 */
	private $partnerId = null;

	/**
	 * Last sync time
	 *
	 * @var \DateTime
	 */
	private $lastSync = null;

	/**
	 * A log of removed collections for saving
	 *
	 * @var string[]
	 */
	private $removedCollections = array();

	/**
	 * Object collections this partner is listening for
	 *
	 * For example: 'customer','task' would mean the partner is
	 * only tracking changes for objects of type customer and task
	 * but will ignore all others. This will keep overhead to a minimal
	 * when tracking changes. In additional collections can have filters
	 * allowing synchronization of a subset of data.
	 *
	 * @var Netric\EntitySync\Collection\CollectionInterface[]
	 */
	private $collections = array();

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param string $partnerId The unique id of this partnership
	 * @param AntUser $user Current user object
	 */
	public function __construct(
		\Netric\EntitySync\DataMapperInterface $syncDm, 
		$partnerId = null
	)
	{
		$this->dataMapper = $syncDm;
		$this->partnerId = $partnerId;
	}

	/**
	 * Set the internal id of this partner
	 *
	 * @param string $id Unique id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Get the internal id of this partner
	 *
	 * @return string Unique id of this partner if it has one
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set the remote partner id of this partnership
	 *
	 * @param string $partnerId Unique id (from the client) of this partner
	 */
	public function setPartnerId($partnerId)
	{
		$this->partnerId = $partnerId;
	}

	/**
	 * Get the internal id of this partner
	 *
	 * @return string Unique id of this partner
	 */
	public function getPartnerId()
	{
		return $this->partnerId;
	}
	

	/**
	 * Set the owner of this partner
	 *
	 * @param string $ownerId Unique id of the netric user who owns this
	 */
	public function setOwnerId($ownerId)
	{
		$this->ownerId = $ownerId;
	}

	/**
	 * Get the internal id of this partner
	 *
	 * @return string Unique id of this partner if it has one
	 */
	public function getOwnerId()
	{
		return $this->ownerId;
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
	 * Check to see if this partnership is listening for changes for a specific type of object
	 *
	 * @param string $objType The name of the object type to check
	 * @param array $conditions Array of conditions used to filter the collection
	 * @return \Netric\EntitySync\Collection\CollectionInterface if found, null if none found
	 */
	public function getEntityCollection($objType, $conditions=array())
	{
		return $this->getCollection($objType, null, $conditions);
	}

	/**
	 * Check to see if this partnership is listening for changes for a grouping field
	 *
	 * @param string $objType The name of the object type to check
	 * @param string $fieldName Name of a field if this is a grouping collection
	 * @param array $conditions Array of conditions used to filter the collection
	 * @return \Netric\EntitySync\Collection\CollectionInterface if found, null if none found
	 */
	public function getGroupingCollection($objType, $fieldName, $conditions=array())
	{
		return $this->getCollection($objType, $fieldName, $conditions);
	}

	/**
	 * Check to see if this partnership is listening  for entity definitions
	 *
	 * @param array $conditions Array of conditions used to filter the collection
	 * @return \Netric\EntitySync\Collection\CollectionInterface if found, null if none found
	 */
	public function getEntityDefinitionCollection($conditions=array())
	{
		return $this->getCollection(null, null, $conditions);
	}

	/**
	 * Check to see if this partnership is listening for changes for a specific type of object
	 *
	 * @param string $obj_type The name of the object type to check
	 * @param string $fieldName Name of a field if this is a grouping collection
	 * @param array $conditions Array of conditions used to filter the collection
	 * @param bool $addIfMissing Add the object type to the list of synchronized objects if it does not already exist
	 * @return \Netric\EntitySync\Collection\CollectionInterface if found, null if none found
	 */
	private function getCollection($obj_type, $fieldName=null, $conditions=array())
	{
		$ret = null;

		// Make sure conditions is an array so we can loop over them (even if 0)
		if (!is_array($conditions))
			$conditions = array();

		foreach ($this->collections as $col)
		{
			if ($obj_type == $col->getObjType() && count($conditions) == count($col->getConditions()))
			{
				if ($fieldName)
				{
					if ($fieldName == $col->getFieldName())
						$ret = $col;
				}
				else if (!$col->getFieldName())
				{
					$ret = $col;
				}

				// Make sure conditions match - if not set back to false
				if ($ret!=null && count($conditions) > 0)
				{
					$collConds = $col->getConditions();

					// Compare against challenge list
					foreach ($conditions as $cond)
					{
						$found = false;

						foreach ($collConds as $cmdCond)
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
							$ret = null;
							break;
						}
					}

					// Compare against collection conditions
					foreach ($collConds as $cond)
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
							$ret = null;
							break;
						}
					}
				}
			}

            /*
             * If we found a match then there's no point in continuing our search
             */
            if ($ret)
               break;
		}

		return $ret;
	}

	/**
	 * Add a collection to this partner
	 *
	 * @param Netric\EneitySync\Collection\CollectionInterface $collection
	 * @return bool true on success, false on failre
	 */
	public function addCollection(Collection\CollectionInterface $collection)
	{
		// TODO: we should check to make sure this colleciton does not already exist

		// Add the colleciton to to the array
		$this->collections[] = $collection;

		return true;
	}

	/**
	 * Get all collections for this partner
	 *
	 * @return \Netric\EntitySync\Collection\CollectionInterface[]
	 */
	public function getCollections()
	{
		return $this->collections;
	}

	/**
	 * Clear all collections for this partner
	 * 
	 * Note: Calling code must save the partnership to cause the clearing to persist.
	 */
	public function removeCollections()
	{
		// Log each collection that was previously saved with an id for saving later
		foreach ($this->collections as $collection)
		{
			if ($collection->getId())
			{
				$this->removedCollections[] = $collection->getId();
			}
		}

		// Empty the collections id
		$this->collections = array();
	}

	/**
	 * Remove a collection by id
	 *
	 * @param string $collectionId The unique id of the collection to remove
	 * @return bool true if the collection was found and deleted, otherwise false
	 */
	public function removeCollection($collectionId)
	{
		$num = count($this->collections);
		for ($i = 0; $i < $num; $i++)
		{
			$collection = $this->collections[$i];

			if ($collection->getId() && $collection->getId() == $collectionId)
			{
				// Log the removal for saving later
				$this->removedCollections[] = $collectionId;

				// Remove the collection from the array
				array_splice($this->collections, $i, 1);

				// Return right away since we have modified the bounds of the array
				return true;
			}
		}

		return false;
	}

	/**
	 * Get removed collections
	 *
	 * @return string[] Unique id of each removed collection
	 */
	public function getRemovedCollections()
	{
		return $this->removedCollections;
	}
}
