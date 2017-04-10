<?php
/**
 * Provides extensions for the Comment object
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\ObjType;

use Netric\ServiceManager\AccountServiceManagerInterface;
use Netric\Entity\Entity;
use Netric\Entity\EntityInterface;

/**
 * Comment represents a single comment on any entity
 */
class CommentEntity extends Entity implements EntityInterface
{
    /**
     * Callback function used for derrived subclasses
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeSave(AccountServiceManagerInterface $sm)
    {
        $entityLoader = $sm->get("EntityLoader");
        $currentUser = $sm->getAccount()->getUser();

        // Set comments associations to all directly associated objects if new
        if ($this->getValue('obj_reference'))
        {
            $objRef = Entity::decodeObjRef($this->getValue('obj_reference'));
            if (count($objRef) > 1)
            {
                $entityCommentedOn = $entityLoader->get($objRef['obj_type'], $objRef['id']);

                // Update the num_comments field of the entity we are commenting on
                if (!$this->getId() || ($this->isDeleted() && $this->fieldValueChanged('f_deleted')))
                {
                    // Determine if we should increment or decrement
                    $added =  ($this->isDeleted()) ? false : true;
                    $entityCommentedOn->setHasComments($added);
                }

                // Add object references to the list of associations
                $this->addMultiValue(
                    "associations",
                    $objRef['obj_type'] . ":" . $objRef['id'],
                    $entityCommentedOn->getName()
                );

                /**
                 * Copy associations for everything but status updates
                 * since status updates are really just like comments themselves.
                 * Only do this if it's a new comment - only needed once
                 */
                if ($objRef['obj_type'] != "status_update" && !$this->getId())
                {
                    $fields = $entityCommentedOn->getDefinition()->getFields();
                    foreach ($fields as $field)
                    {
                        if ($field->type == 'object'
                            && ($field->subtype || $field->name == "obj_reference"))
                        {
                            $val = $entityCommentedOn->getValue($field->name);
                            if ($val)
                            {
                                if ($field->subtype)
                                {
                                    $this->addMultiValue("associations", $field->subtype.":".$val);
                                }
                                else if (count(explode(":", $val))>1)
                                {
                                    // The value is already an encoded object reference
                                    $this->addMultiValue("associations", $val);
                                }
                            }
                        }
                    }
                }

                // Make sure followers of this comment are synchronized with the entity
                $this->syncFollowers($entityCommentedOn);

                // Save the entity we are commenting on if there were changes
                if ($entityCommentedOn->isDirty())
                {
                    $entityLoader->save($entityCommentedOn);
                }
            }
        }

        // Set who this was sent by if not already set
        if (!$this->getValue('sent_by'))
        {
            $this->setValue("sent_by", "user:" . $currentUser->getId());
        }
    }

    /**
     * Callback function used for derrived subclasses
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onAfterSave(AccountServiceManagerInterface $sm)
    {
    }

    /**
     * Called right before the entity is purged (hard delete)
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeDeleteHard(AccountServiceManagerInterface $sm)
    {

    }
}

