<?php
/**
 * A DataMapper is responsible for writing and reading data from a persistant store
 *
 * @category	DataMapper
 * @author		joe, sky.stebnicki@aereus.com
 * @copyright	Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Entity;

interface DataMapperInterface
{
	/**
	 * Open object by id
	 *
     * @var Entity $entity The entity to load data into
     * @var string $id The Id of the object
	 * @return bool true on success, false on failure
	 */
	public function getById(&$entity, $id);

	/**
	 * Delete an entity
	 *
	 * @param Entity $entity The enitity to save
	 * @param bool $forceHard If true the data will be purged, if false first it will be archived
	 * @return bool true on success, false on failure
	 */
	public function delete(&$entity, $forceHard=false);

    /**
	 * Get object definition based on an object type
	 *
     * @param string $objType The object type name
     * @param string $fieldName The field name to get grouping data for
	 * @return \Netric\Models\EntityGrouping[]
	 */
	public function getGroupings($objType, $fieldName);

	/**
	 * Save object data
	 *
	 * @param Entity $entity The entity to save
	 * @return string|bool entity id on success, false on failure
	 */
	public function save($entity);

	/**
	 * Set this object as having been moved to another object
	 *
	 * @param EntityDefinition $def The defintion of this object type
	 * @param string $fromId The id to move
	 * @param stirng $toId The unique id of the object this was moved to
	 * @return bool true on succes, false on failure
	 */
	public function setEntityMovedTo(&$def, $fromId, $toId);

	/**
	 * Check if an object has moved
	 *
	 * @param Entity $entity
	 * @param string $id The id of the object that no longer exists - may have moved
	 * @return string|bool New entity id if moved, otherwise false
	 */
	public function checkEntityHasMoved($entity, $id);

	/**
	 * Get Revisions for this object
	 *
	 * @param string $objType The name of the object type to get
	 * @param string $id The unique id of the object to get revisions for
	 * @return array("revisionNum"=>Entity)
	 */
	public function getRevisions($objType, $id);
}
