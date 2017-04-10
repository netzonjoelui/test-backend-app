<?php
/**
 * Activity entity extension
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Entity\ObjType;

use Netric\Entity\Entity;
use Netric\Entity\EntityInterface;

/**
 * Activty entity used for logging activity logs
 */
class ActivityEntity extends Entity implements EntityInterface
{
    /**
     * Verbs
     *
     * @const int
     */
    const VERB_CREATED = 'created';
    const VERB_UPDATED = 'updated';
    const VERB_DELETED = 'deleted';
    const VERB_READ = 'read';
    const VERB_SHARED = 'shared';
    const VERB_SENT = 'sent';
    const VERB_COMPLETED = 'completed';
    const VERB_APPROVED = 'approved';

    /**
     * Callback function used for derrived subclasses
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeSave(\Netric\ServiceManager\AccountServiceManagerInterface $sm)
    {
        // Set association for the object which is used for queries
        if ($this->getValue('obj_reference'))
        {
            $objRef = $this->getValue('obj_reference');
            if ($objRef)
            {
                $this->addMultiValue("associations", 
                    $objRef, 
                    $this->getValueName('obj_reference')
                );
            }
        }
    }
}
