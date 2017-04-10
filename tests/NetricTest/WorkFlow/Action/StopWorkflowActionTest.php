<?php
namespace NetricTest\WorkFlow\Action;

use Netric\WorkFlow\Action\ActionInterface;
use Netric\WorkFlow\WorkFlowInstance;

class StopWorkflowActionTest extends AbstractActionTests
{
    /**
     * All action tests must construct the action
     *
     * @return ActionInterface
     */
    protected function getAction()
    {
        return $this->actionFactory->create("stop_workflow");
    }

    /**
     * Make sure we can execute this action type and it works as designed
     *
     * @expectedException \RuntimeException
     */
    public function testExecute()
    {
        $action = $this->getAction();

        // Create a task that will email the owner when completed
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "test");
        $task->setId(321);

        // Create a fake WorkFlowInstance since the action does not a saved workflow or instance
        $workFlowInstance = new WorkFlowInstance(123, $task);

        // This action is not implemented yet so we throw an exception
        $action->execute($workFlowInstance);
    }
}
