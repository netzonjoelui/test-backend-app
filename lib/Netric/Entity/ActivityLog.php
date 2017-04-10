<?php
/**
 * Activity log for entities
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity;

use Netric\Entity\ObjType\ActivityEntity;
use Netric\EntityDefinition;
use Netric\EntityLoader;
use Netric\EntityGroupings;
use Netric\EntityGroupings\Group;


/**
 * Class for managing an entity activity log
 */
class ActivityLog
{
    /**
     * Handle to the entity loader for creating and loading entities
     *
     * @var EntityLoader
     */
    private $entityLoader = null;

    /**
     * Currently logged in user
     * 
     * @var ObjType\UserEntity
     */
    private $currentUser = null;

    /**
     * Loader to get and save entity groupings
     *
     * @var EntityGroupings\Loader|null
     */
    private $groupingsLoader = null;

    /**
     * Class constructor to set up dependencies
     *
     * @param EntityLoader $entityLoader Loader for getting referenced entities
     * @param EntityGroupings\Loader $groupingsLoader Loader for getting/setting groupings
     * @param ObjType\UserEntity $currentUser
     */
    public function __construct(
        EntityLoader $entityLoader,
        EntityGroupings\Loader $groupingsLoader,
        ObjType\UserEntity $currentUser)
    {
        $this->entityLoader = $entityLoader;
        $this->groupingsLoader = $groupingsLoader;
        $this->currentUser = $currentUser;
    }

    /**
     * Log an activity performed on an entity
     *
     * Theory of operation includes three main elements:
     *  subject (what did the action)
     *  verb (what the action was)
     *  object (what the verb was performed on), notes
     *
     * @param Entity $subject The entity performing the action - usually a user
     * @param string $verb The action performed from ActivityEntity::VERB_*
     * @param Entity $object The entity being acted on
     * @param string $notes Details for the activity
     * @param int $level Optional log level
     * @return Entity The created activity or null on failure
     */
    public function log(Entity $subject, $verb, Entity $object, $notes = "", $level = null)
    {
        $objDef = $object->getDefinition();
        $objType = $objDef->getObjType();

        // We don't add activities of activities - that could create an endless loop
        if ("activity" == $objType)
            return;

        /*
         * Get the name of the object acted on.
         * Since activities are entities also, we use the name of the
         * object acted on as the name of the activity.
         */
        $name = "";

        // If we created a comment, then get the name from the object commented on
        if (("comment" == $objType) && $object->getValue("obj_reference"))
        {
            $parts = Entity::decodeObjRef($object->getValue("obj_reference"));
            if (isset($parts['name']))
            {
                // Get the cached name of the entity we commented on
                $name = $parts['name'];
            }
            else if ($parts > 1)
            {
                // Name was not cached in there reference, then load the entity commented on to get it
                $entityReferenced = $this->entityLoader->get($parts['obj_type'], $parts['id']);
                $name = $entityReferenced->getName();
            }
        }

        // Default to the name of the object acted on
        if (!$name)
            $name = $object->getName();

        // Get notes from the entity
        if (!$notes)
        {
            $notes = "";
            if ($verb == ActivityEntity::VERB_UPDATED)
                $notes = $object->getChangeLogDescription();
            if ($verb == ActivityEntity::VERB_CREATED)
                $notes = $object->getDescription();
        }

        $actEntity = $this->entityLoader->create("activity");
        $actEntity->setValue("name", $name);
        $actEntity->setValue("notes", $notes);
        $actEntity->setValue("verb", $verb);

        // If the object we acted on is private, then mark this activity as private
        $actEntity->setValue("f_private", $objDef->isPrivate);

        /*
         * obj_reference is a reference to the entity object being acted on.
         * If we are acting on a comment, then record the action as being on the object
         * being commented on, otherwise just record the action on the object itself.
         */
        if ("comment" == $objType && $object->getValue("obj_reference"))
            $actEntity->setValue("obj_reference", $object->getValue("obj_reference"));
        else
            $actEntity->setValue("obj_reference", $object->getObjRef());

        // Get the type of activity which is just a grouping entiry for the objType
        $group = $this->getActivityTypeGroup($objDef);
        $actEntity->setValue("type_id", $group->id, $group->name);

        // Log which entity performed the action
        $actEntity->setValue("subject", $subject->getObjRef(), $subject->getName());

        /*
         * user_id is used for querying all entities for a given user or user's team
         *
         * We may not need it any more
         *
        if ($userid)
        {
            $actEntity->setValue("user_id", $userid);
        }
        else if ($this->currentUser)
        {
            $actEntity->setValue("user_id", $this->currentUser->id);
        }
        */

        // Add referenced entity to activity associations
        $actEntity->addMultiValue("associations", $object->getObjRef(), $object->getName());

        /*
         * Copy associations from the referenced object so that
         * we can associate this activity log with all associated entities
         */
        $associations = $object->getValue("associations");
        if (is_array($associations) && count($associations))
        {
            foreach ($associations as $assoc)
            {
                $assocName = $object->getValue("associations", $assoc);
                $actEntity->addMultiValue("associations", $assoc, $assocName);
            }
        }

        /*
         * Now associate activity with all referenced objects not in 'associations'
         * which should technically never happen, but better safe than sorry.
         */
        $fields = $objDef->getFields();
        foreach ($fields as $field)
        {
            if ('object' == $field->type)
            {
                $refObjId = $object->getValue($field->name);
                if ($refObjId)
                {
                    // If we have a subtype then $refObjId is only the numeric id
                    if ($field->subtype)
                    {
                        $refObjName = $object->getValueName($field->name, $refObjId);
                        $assocObjRef = Entity::encodeObjRef($field->subtype, $refObjId);
                        $actEntity->addMultiValue("associations", $assocObjRef, $refObjName);
                    }
                    else
                    {
                        $actEntity->addMultiValue("associations", $refObjId);
                    }
                }
            }
        }

        // Associate with the currently active user
        if ($this->currentUser)
        {
            $actEntity->addMultiValue(
                "associations",
                $this->currentUser->getObjRef(),
                $this->currentUser->getName()
            );
        }

        // If we're working with a comment copy attachments
        if ("comment" == $objType)
        {
            $attachments = $object->getValue("attachments");
            if (is_array($attachments) && count($attachments))
            {
                foreach ($attachments as $attId)
                {
                    $attName = $object->getValueName("attachments", $attId);
                    $actEntity->addMultiValue("attachments", $attId, $attName);
                }
            }
        }

        // Now set level - if system activity then put it low to keep logs clean
        $level = ($this->currentUser && $this->currentUser->isSystem()) ? 1 : $objDef->defaultActivityLevel;
        $actEntity->setValue("level", $level);

        // Try saving the new activity
        if ($this->entityLoader->save($actEntity))
            return $actEntity;
        else
            return null;
    }

    /**
     * Get the activity grouping id for a given objType
     *
     * @param EntityDefinition $objDef The type of object to get the grouping type for
     * @param bool $createIfMissing If true then create a grouping if missing
     * @return Group
     */
    private function getActivityTypeGroup(EntityDefinition $objDef, $createIfMissing = true)
    {
        $groupings = $this->groupingsLoader->get("activity", "type_id");

        $existing = $groupings->getByName($objDef->title);
        if ($existing)
            return $existing;

        if (!$createIfMissing)
            return null;

        // This is a new type grouping, add it
        $group = new Group();
        $group->name = $objDef->title;
        $groupings->add($group);
        $this->groupingsLoader->save($groupings);

        // Return the newly created group id
        return $group;
    }
}