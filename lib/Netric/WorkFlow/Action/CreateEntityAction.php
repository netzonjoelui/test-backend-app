<?php
/**
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\WorkFlow\Action;

use Netric\Entity\EntityInterface;
use Netric\EntityLoader;
use Netric\WorkFlow\WorkFlowInstance;

/**
 * Action to create new entities from a workflow
 */
class CreateEntityAction extends AbstractAction implements ActionInterface
{
    /**
     * Execute this action
     *
     * @param WorkFlowInstance $workflowInstance The workflow instance we are executing in
     * @return bool true on success, false on failure
     */
    public function execute(WorkFlowInstance $workflowInstance)
    {
        // Get the entity we are executing against
        $entity = $workflowInstance->getEntity();

        // Get merged params
        $params = $this->getParams($entity);

        // Make sure we have what we need
        if (!$params['obj_type'])
        {
            throw new \InvalidArgumentException("Cannot create an entity without obj_type param");
        }

        // Create new entity
        $newEntity = $this->entityLoader->create($params['obj_type']);
        foreach ($params as $fname=>$fval) {
            if ($newEntity->getDefinition()->getField($fname)) {
                if (is_array($fval)) {
                    foreach ($fval as $subval) {
                        $newEntity->addMultiValue($fname, $subval);
                    }
                } else {
                    $newEntity->setValue($fname, $fval);
                }
            }
        }

        return ($this->entityLoader->save($newEntity)) ? true : false;
    }
}