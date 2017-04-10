<?php
/**
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\WorkFlow\Action;

use Netric\EntityLoader;
use Netric\Error\Error;
use Netric\WorkFlow\WorkFlowInstance;
use Netric\WorkFlow\WorkFlowManager;

/**
 * Action to trigger a child workflow
 */
class StartWorkflowAction extends AbstractAction implements ActionInterface
{
    /**
     * Manager for starting WorkFlows
     *
     * @var WorkFlowManager|null
     */
    private $workFlowManager = null;

    /**
     * This must be called by all derived classes, or $entityLoader should be set in their constructor
     *
     * @param EntityLoader $entityLoader
     * @param ActionFactory $actionFactory For constructing child actions
     * @param WorkFlowManager $workFlowManager For starting a child workflow
     */
    public function __construct(
        EntityLoader $entityLoader,
        ActionFactory $actionFactory,
        WorkFlowManager $workFlowManager
    )
    {
        $this->workFlowManager = $workFlowManager;
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

        if (isset($params['wfid']))
        {
            $this->workFlowManager->startWorkflowById($entity, $params['wfid']);
            return true;
        }

        // Assume failure
        $this->errors[] = new Error("No valid workflow id set to run");
        return false;
    }
}
