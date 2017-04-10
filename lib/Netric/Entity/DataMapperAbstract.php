<?php
/**
 * A DataMapper is responsible for writing and reading data from a persistant store
 *
 * @category	DataMapper
 * @author		joe, sky.stebnicki@aereus.com
 * @copyright	Copyright (c) 2003-2014 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Entity;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use Netric\EntityDefinition\Exception\DefinitionStaleException;
use Netric\Entity\Recurrence\RecurrenceIdentityMapper;
use Netric\Entity\EntityAggregator;

abstract class DataMapperAbstract extends \Netric\DataMapperAbstract
{
    /**
     * The type of object this data mapper is handling
     *
     * @var string
     */
    protected $objType = "";

	/**
	 * Record of moved-to references
	 *
	 * @var array
	 */
	 protected $movedToRef = array();

	 /**
	  * Commit manager used to crate global commits for sync
	  *
	  * @var \Netric\EntityDefinition\Commit\Manager
	  */
	 protected $commitManager = null;

    /**
     * Recurrence Identity Mapper
     *
     * @var RecurrenceIdentityMapper
     */
    private $recurIdentityMapper = null;

	/**
	 * Caches the results on checking if entity has moved
	 *
	 * @var Array
	 */
	private $cacheMovedEntities = null;

	/**
	 * Class constructor
	 *
	 * @param ServiceLocator $sl The ServiceLocator container
	 * @param string $accountName The name of the ANT account that owns this data
	 */
	public function __construct(\Netric\Account\Account $account)
	{
		$this->setAccount($account);
		$this->setUp();

		// Clear the moved entities cache
		$this->cacheMovedEntities = array();

        $this->recurIdentityMapper = $account->getServiceManager()->get("RecurrenceIdentityMapper");
		$this->commitManager = $account->getServiceManager()->get("EntitySyncCommitManager");
		$this->entitySync = $account->getServiceManager()->get("EntitySync");;
	}

    /**
	 * Get object definition based on an object type
	 *
     * @param string $objType The object type name
     * @param string $fieldName The field name to get grouping data for
	 * @return \Netric\\EntityGroupings
	 */
	//abstract public function getGroupings($objType, $fieldName, $filters=array());

    /**
     * Save groupings
     *
     * @param \Netric\EntityGroupings
     * @param int $commitId The new commit id
     */
    abstract protected function _saveGroupings(\Netric\EntityGroupings $groupings, $commitId);

	/**
	 * Set this object as having been moved to another object
	 *
	 * @param EntityDefinition $def The defintion of this object type
	 * @param string $fromId The id to move
	 * @param stirng $toId The unique id of the object this was moved to
	 * @return bool true on succes, false on failure
	 */
	//abstract public function setEntityMovedTo(&$def, $fromId, $toId);

	/**
	 * The setup function is used by all derrived classes as constructors
	 */
	abstract protected function setUp();

	/**
	 * Open object by id
	 *
     * @var Entity $entity The entity to load data into
     * @var string $id The Id of the object
	 * @return bool true on success, false on failure
	 */
	abstract protected function fetchById(&$entity, $id);

	/**
	 * Purge data from the database
	 *
     * @var Entity $entity The entity to load data into
	 * @return bool true on success, false on failure
	 */
	abstract protected function deleteHard(&$entity);

	/**
	 * Flag data as deleted or archive but don't actually delete it
	 *
     * @var Entity $entity The entity to load data into
	 * @return bool true on success, false on failure
	 */
	abstract protected function deleteSoft(&$entity);

	/**
	 * Save object data
	 *
	 * @param Entity $entity The entity to save
	 * @return string|bool entity id on success, false on failure
	 */
	abstract protected function saveData($entity);

	/**
	 * Check if an object has moved
	 *
	 * @param Entity $entity
	 * @param string $id The id of the object that no longer exists - may have moved
	 * @return string|bool New entity id if moved, otherwise false
	 */
	abstract protected function entityHasMoved($entity, $id);

	/**
	 * Save revision snapshot
	 *
	 * @param Entity $entity The entity to save
	 * @return string|bool entity id on success, false on failure
	 */
	abstract protected function saveRevision($entity);

	/**
	 * Get Revisions for this object
	 *
	 * @param string $objType The name of the object type to get
	 * @param string $id The unique id of the object to get revisions for
	 * @return array("revisionNum"=>Entity)
	 */
	abstract public function getRevisions($objType, $id);

	/**
	 * Save main processor
	 *
	 * @param Entity $entity The enitity to save
     * @param \Netric\Entity\ObjType\UserEntity $user Optional user performing the save if other than current in $this->account
     * @return int|bool If success the id of the saved entity will be returned, false if failure
	 */
	public function save($entity, $user=null)
	{
        $serviceManager = $this->getAccount()->getServiceManager();
        $def = $entity->getDefinition();

        // First validate that this entity is ok to be written
        $entityValidator = $serviceManager->get('Netric\Entity\Validator\EntityValidator');
        if (!$entityValidator->isValid($entity))
        {
            $this->errors = array_merge($this->errors, $entityValidator->getErrors());
            return false;
        }

		// Increment revision for this save
		$revision = $entity->getValue("revision");
		$revision = (!$revision) ? 1 : ++$revision;
		$entity->setValue("revision", $revision);

		// Create new global commit revision
		$lastCommitId = $entity->getValue('commit_id');
		$commitId = $this->commitManager->createCommit("entities/" . $def->getObjType());
		$entity->setValue('commit_id', $commitId);

        // Set defaults
        $event = ($entity->getId()) ? "update" : "create";
        $user = $this->getAccount()->getUser();
        $entity->setFieldsDefault($event, $user);

        // Update foreign key names
        $this->updateForeignKeyNames($entity);

        /*
         * If the entity has a new recurrence pattern, then we need to get the next recurring id
         * now so we can save it to the entity before saving the recurring patterns itself.
         * This is the result of a circular reference where the recurrence pattern has a
         * reference to the first entity id, and the entity has a reference to the recurrence
         * pattern. We might want to come up with a better overall solution. - joe
         */
		$useRecurId = null;
		if ($entity->getRecurrencePattern() && $def->recurRules)
		{
			if (!$entity->getValue($def->recurRules['field_recur_id']))
			{
				$useRecurId = $this->recurIdentityMapper->getNextId();
                $entity->getRecurrencePattern()->setId($useRecurId);
				$entity->setValue($def->recurRules['field_recur_id'], $useRecurId);
			}
		}

		// Call beforeSave
        $entity->beforeSave($serviceManager);

		// Save data to DataMapper implementation
		$ret = null;
		try
		{
			$ret = $this->saveData($entity);
		}
		catch (DefinitionStaleException $ex)
		{
			/*
			 * We tried to save but there was something wrong with the definition (field not added?)
			 * Sometimes we need to force the system fields to reset in order to update
			 * the entity database -- especially if a new field was added to system fields.
			 * Try to update the definition in case it is out of sync
			 */
			if ($serviceManager)
			{
				$entityDefLoader = $serviceManager->get("EntityDefinitionLoader");
				$entityDefLoader->forceSystemReset($def->getObjType());

				// Try saving again
				$ret = $this->saveData($entity);
			}
		}

		// Save revision for historical reference
		if ($def->storeRevisions)
			$this->saveRevision($entity);

		// Save data to EntityQuery Index
		$serviceManager->get("EntityQuery_Index")->save($entity);

		// Clear cache in the EntityLoader
        $serviceManager->get("EntityLoader")->clearCache($def->getObjType(), $entity->getId());

		// Log the change in entity sync
		if ($ret && $lastCommitId && $commitId)
		{
			$this->entitySync->setExportedStale(
				\Netric\EntitySync\EntitySync::COLL_TYPE_ENTITY,
				$lastCommitId, $commitId);
		}

		// Send notifications
		$serviceManager->get("Netric/Entity/Notifier/Notifier")->send($entity, $event);

		// Call onAfterSave
        $entity->afterSave($serviceManager);

		// Update any aggregates that could be impacted by saving $entity
        $this->getAccount()
            ->getServiceManager()
            ->get("Netric/Entity/EntityAggregator")
            ->updateAggregates($entity);

		// Reset dirty flag and changelog
		$entity->resetIsDirty();

        /*
         * If this is part of a recurring series - which means it has a recurrence pattern -
         * and not an exception, then save the recurrence pattern.
         */
		if (!$entity->isRecurrenceException() && $entity->getRecurrencePattern())
		{
            $this->recurIdentityMapper->saveFromEntity($entity);
		}

        // Log the activity
        $serviceManager->get("Netric/Entity/ActivityLog")->log($user, $event, $entity);

		return $ret;
	}

	/**
	 * Get an entity by id
	 *
	 * @param Entity $entity The enitity to save
	 * @return bool true if found and loaded successfully, false if not found or failed
	 */
	public function getById(&$entity, $id)
	{
		$ret = $this->fetchById($entity, $id);

        if (!empty($id) && !is_numeric($id)) {
            throw new \InvalidArgumentException("$id is not a valid entity id");
        }

		if (!$ret)
		{
			$movedToId = $this->entityHasMoved($entity->getDefinition(), $id);
			if ($movedToId && $movedToId != $id)
				$ret = $this->fetchById($entity, $movedToId);
		}

        // Load a recurrence pattern if set
        if ($entity->getDefinition()->recurRules)
        {
            // If we have a recurrence pattern id then load it
            $recurId = $entity->getValue($entity->getDefinition()->recurRules['field_recur_id']);
			if ($recurId)
            {
                $recurPattern = $this->recurIdentityMapper->getById($recurId);
                if ($recurPattern) {
                    $entity->setRecurrencePattern($recurPattern);
                }
            }
        }

        // Reset dirty flag and changelog since we just loaded
        $entity->resetIsDirty();

		return $ret;
	}

	/**
	 * Delete an entity
	 *
	 * @param Entity $entity The enitity to save
	 * @param bool $forceHard If true the data will be purged, if false first it will be archived
	 * @return bool true on success, false on failure
	 */
	public function delete(&$entity, $forceHard=false)
	{
        $user = $this->getAccount()->getUser();
        $serviceManager = $this->getAccount()->getServiceManager();
      
		$lastCommitId = $entity->getValue("commit_id");
		// Create new global commit revision
		$commitId = $this->commitManager->createCommit("entities/" . $entity->getDefinition()->getObjType());

		// Determine if we are flagging the entity as deleted or actually purging
		if ($entity->getValue("f_deleted") || $forceHard)
		{
			// Call beforeDeleteHard so the entity can do any pre-purge operations
            $entity->beforeDeleteHard($serviceManager);

            // Purge the recurrence pattern if set
            if ($entity->getRecurrencePattern())
            {
                // Only delete the recurrence pattern if this is the original
                if ($entity->getRecurrencePattern()->entityIsFirst($entity))
                {
                    $this->recurIdentityMapper->delete($entity->getRecurrencePattern());
                }
            }

            // Perform the delete from the data store
			$ret = $this->deleteHard($entity);

			// Call onBeforeDeleteHard so the entity can do any post-purge operations
            $entity->afterDeleteHard($serviceManager);

			// Delete from EntityCollection_Index
			$serviceManager->get("EntityQuery_Index")->save($entity);

			// Remove unique DACL. Of course, we don't want to delete the dacl for all object types, just for this id
			//if ($this->daclIsUnique && $this->dacl)
				//$this->dacl->remove();
		}
		else
		{
			$entity->setValue('commit_id', $commitId);

			$ret = null;
			try
			{
				$ret = $this->deleteSoft($entity);
			}
			catch (DefinitionStaleException $ex)
			{
				/*
                 * We tried to save but there was something wrong with the definition (field not added?)
                 * Sometimes we need to force the system fields to reset in order to update
                 * the entity database -- especially if a new field was added to system fields.
                 */

				// Try to update the definition in case it is out of sync
                $entityDefLoader = $serviceManager->get("EntityDefinitionLoader");
                $entityDefLoader->forceSystemReset($entity->getDefinition()->getObjType());

                // Try deleting again
                $ret = $this->deleteSoft($entity);
			}

			// Delete from EntityCollection_Index
			//$this->getServiceLocator()->get("EntityCollection_Index")->delete($entity);

            // Log the activity
            $alog = $serviceManager->get("Netric/Entity/ActivityLog");
            $alog->log($user, "delete", $entity);
		}

		// Log the change in entity sync
		if ($ret && $lastCommitId && $commitId)
		{
			$this->entitySync->setExportedStale(
				\Netric\EntitySync\EntitySync::COLL_TYPE_ENTITY,
				$lastCommitId, $commitId);
		}

		// Clear cache in the EntityLoader
        $serviceManager->get("EntityLoader")->clearCache($entity->getDefinition()->getObjType(), $entity->getId());

		return $ret;
	}

    /**
     * Save groupings
     *
     * @param \Netric\EntityGroupings
     */
    public function saveGroupings(\Netric\EntityGroupings $groupings)
    {
    	// Increment head commit for groupings which triggers all collections to sync
		$commitHeadIdent = "groupings/" . $groupings->getObjType() . "/";
		$commitHeadIdent .= $groupings->getFieldName() . "/";
		$commitHeadIdent .= $groupings::getFiltersHash($groupings->getFilters());

    	/*
		 * Groupings are all saved as a single collection, but only updated
		 * groupings will shre a new commit id.
		 */
		$nextCommit = $this->commitManager->createCommit($commitHeadIdent);

		// Save the grouping
        $log = $this->_saveGroupings($groupings, $nextCommit);

        /* No need to log changes because the sync function will get all newer commits
        foreach ($log['changed'] as $gid=>$lastCommitId)
        {
            // Log the change in entity sync
			if ($gid && $lastCommitId && $nextCommit)
			{
				$this->entitySync->setExportedStale(
					\Netric\EntitySync\EntitySync::COLL_TYPE_GROUPING,
					$lastCommitId, $nextCommit);
			}
        }
        */

        foreach ($log['deleted'] as $gid=>$lastCommitId)
        {
            // Log the change in entity sync
			if ($gid && $lastCommitId && $nextCommit)
			{
				$this->entitySync->setExportedStale(
					\Netric\EntitySync\EntitySync::COLL_TYPE_GROUPING,
					$lastCommitId, $nextCommit);
			}
        }

		return true;
    }

    /**
     * Update foreign key name cache
     *
     * All foreign key (fkey, fkey_multi, object, object_multi) fields
     * cache the name of the foreign key for faster performance. The risk
     * with this is that the cache gets out of date if a referenced object
     * is updated. This function makes sure that all names for foreign references
     * are refreshed any time the entity is saved.
     *
     * @param Entity $entity The entity to update
     */
    private function updateForeignKeyNames(Entity $entity)
    {
        $serviceManager = $this->getAccount()->getServiceManager();
        $groupingsLoader = $serviceManager->get("EntityGroupings_Loader");
        $entityLoader = $serviceManager->get("EntityLoader");

		// Setup filters for groupings if this is a private object
		$groupingFilter = array();

		if ($entity->getDefinition()->isPrivate()) {
			if ($entity->getValue("owner_id")) {
				$groupingFilter['owner_id'] = $entity->getValue("owner_id");
			} else if ($entity->getValue("user_id")) {
				// All entities have owner_id, but some old entities use user_id
				$groupingFilter['user_id'] = $entity->getValue("user_id");
			}
		}

        $fields = $entity->getDefinition()->getFields();
        foreach ($fields as $field)
        {
            $value = $entity->getValue($field->name);

            // Skip over null/empty fields
            if (!$value)
                continue;

            switch ($field->type)
            {
                case 'object':
                    $objType = $field->subtype;
                    $id = $value;
                    if (!$objType)
                    {
                        $refParts = Entity::decodeObjRef($value);
                        $objType = $refParts['obj_type'];
                        $id = $refParts['id'];
                    }

                    // Get referenced object name
                    if ($objType && $id)
                    {
                        $ent = $entityLoader->get($objType, $id);
						if ($ent) {
							$entity->setValue($field->name, $value, $ent->getName());
						} else {
							// Referenced entity was removed, so clear the value
							$entity->setValue($field->name, null);
						}
                    }

                    break;

                case 'object_multi';
                    $objType = $field->subtype;

                    if (is_array($value))
                    {
                        foreach ($value as $valPart)
                        {
                            $id = $valPart;
                            if (empty($field->subtype))
                            {
                                $refParts = Entity::decodeObjRef($valPart);
                                $objType = $refParts['obj_type'];
                                $id = $refParts['id'];
                            }

                            // Get referenced object name
                            if ($objType && $id)
                            {
                                $ent = $entityLoader->get($objType, $id);
								if ($ent) {
									$entity->addMultiValue($field->name, $valPart, $ent->getName());
								} else {
									// Referenced entity was removed, so clear the value
									$entity->removeMultiValue($field->name, $valPart);
								}
                            }
                        }
                    }

                    break;

                case 'fkey':
                    $objType = $entity->getDefinition()->getObjType();
                    $groups = $groupingsLoader->get($objType, $field->name, $groupingFilter);
                    $group = $groups->getById($value);
                    if ($group)
                        $entity->setValue($field->name, $value, $group->name);
                    break;

                case 'fkey_multi':
                    $objType = $entity->getDefinition()->getObjType();
                    $groups = $groupingsLoader->get($objType, $field->name, $groupingFilter);
                    if (is_array($value))
                    {
                        foreach ($value as $valPart)
                        {
                            $group = $groups->getById($valPart);
                            if ($group)
                                $entity->addMultiValue($field->name, $valPart, $group->name);
                        }
                    }

                    break;
            }
        }
    }

	/**
	 * Make sure that a uname is still unique
	 *
	 * This should safe-gard against values being saved in the object that change the namespace
	 * of the unique name causing unique collision.
	 *
	 * @param Entity $entity The entity to save
	 * @param string $uname The name to test for uniqueness
	 * @param bool $reset If true then reset 'uname' field with new unique name
	 */
	public function verifyUniqueName($entity, $uname)
	{
		if (!$uname)
			return false;

		$def = $entity->getDefinition();

		// If we are not using unique names with this object just succeed
		if (!$def->unameSettings)
			return true;

        /*
         * TODO: this needs to be fixed
         */
        //$this->getAccount()->getServiceManager()->get("EntityLoader");

		// TODO: we need to move this to collections but collections are not yet built
		// Search objects to see if the uname exists
		$olist = new CAntObjectList($this->dbh, $this->object_type, $this->user);
		$olist->addCondition("and", "uname", "is_equal", $uname);

		// Exclude this object from the query because of course it will be a duplicate
		if ($this->id)
			$olist->addCondition("and", "id", "is_not_equal", $this->id);

		// Loop through all namespaces if set with ':' in the settings
		$nsParts = explode(":", $def->unameSettings);
		if (count($nsParts) > 1)
		{
			// Use all but last, which is the uname field
			for ($i = 0; $i < (count($nsParts) - 1); $i++)
			{
				$olist->addCondition("and", $nsParts[$i], "is_equal", $this->getValue($nsParts[$i]));
			}
		}

		// Check if any objects match
		$olist->getObjects(0, 1);
		if ($olist->getNumObjects() > 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Check if an object has moved
	 *
	 * @param Entity $entity
	 * @param string $id The id of the object that no longer exists - may have moved
	 * @return string|bool New entity id if moved, otherwise false
	 */
	public function checkEntityHasMoved($entity, $id)
	{

		// If we have already checked the this entity, then return the result
		if(isset($this->cacheMovedEntities[$id])) {
			return $this->cacheMovedEntities[$id];
		}

		// Check if entity has moved
		$movedToId = $this->entityHasMoved($entity, $id);

		// Store the result in the cache
		$this->cacheMovedEntities[$id] = $movedToId;

		return $movedToId;
	}
}
