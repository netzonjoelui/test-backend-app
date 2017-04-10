<?php
namespace NetricTest\WorkFlow\Action;

use Netric\WorkFlow\Action\ActionInterface;
use Netric\WorkFlow\WorkFlowInstance;

class TestActionTest extends AbstractActionTests
{
    /**
     * All action tests must construct the action
     *
     * @return ActionInterface
     */
    protected function getAction()
    {
        return $this->actionFactory->create("test");
    }

    /**
     * Make sure we can execute this action type and it works as designed
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

        $this->assertTrue($action->execute($workFlowInstance));
    }
}
