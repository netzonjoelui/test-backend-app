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
 * Action used for testing
 */
class TestAction extends AbstractAction implements ActionInterface
{
    /**
     * Example of a constructor - must always call the parent
     *
     * @param EntityLoader $entityLoader
     */
    public function __construct(EntityLoader $entityLoader, ActionFactory $actionFactory)
    {
        // TODO: Set dependencies here

        // Should always call the parent constructor for base dependencies
        parent::__construct($entityLoader, $actionFactory);
    }

    /**
     * Execute this action
     *
     * @param WorkFlowInstance $workflowInstance The workflow instance we are executing in
     * @return bool true on success, false on failure
     */
    public function execute(WorkFlowInstance $workflowInstance)
    {
        $entity = $workflowInstance->getEntity();

        // Get merged params
        $params = $this->getParams($entity);

        return true;
    }
}