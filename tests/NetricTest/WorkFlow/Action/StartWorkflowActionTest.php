<?php
namespace NetricTest\WorkFlow\Action;

use Netric\WorkFlow\Action\ActionInterface;
use Netric\WorkFlow\WorkFlow;
use Netric\WorkFlow\WorkFlowInstance;

class StartWorkflowActionTest extends AbstractActionTests
{
    /**
     * Test entities to delete
     *
     * @var EntityInterface[]
     */
    private $testEntities = array();

    /**
     * Test WorkFlows to cleanup
     *
     * @var WorkFlow[]
     */
    private $testWorkFlows = array();
    /**
     * All action tests must construct the action
     *
     * @return ActionInterface
     */
    protected function getAction()
    {
        return $this->actionFactory->create("start_workflow");
    }

    /**
     * Cleanup entities
     */
    protected function tearDown()
    {
        foreach ($this->testEntities as $entity)
        {
            $this->entityLoader->delete($entity, true);
        }

        foreach ($this->testWorkFlows as $workFlow)
        {
            $this->workFlowDataMapper->delete($workFlow);
        }

        parent::tearDown();
    }

    /**
     * Make sure we can execute this action type and it works as designed
     */
    public function testExecute()
    {
        // Create a new workflow with conditions
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->setObjType("task");

        // Setup a test action to change the name to 'automatically changed'
        $actionUpdateField = $this->actionFactory->create("update_field");
        $actionUpdateField->setParam('update_field', 'name');
        $actionUpdateField->setParam('update_value', 'automatically changed');
        $workFlow->addAction($actionUpdateField);

        // Save the workflow
        $this->workFlowDataMapper->save($workFlow);
        $this->testWorkFlows[] = $workFlow;

        /*
         * Create a task to run the new workflow against
         */
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "test");
        $task->setValue("done", false); // should cause it to be ignored by the WorkFlow
        $this->entityLoader->save($task);
        $this->testEntities[] = $task;

        // Create a fake WorkFlowInstance since the action does not a saved workflow or instance
        $workFlowInstance = new WorkFlowInstance(123, $task);

        // Setup action
        $action = $this->getAction();
        $action->setParam("wfid", $workFlow->getId());

        $this->assertTrue($action->execute($workFlowInstance));

        // Make sure the started sub-workflow was started
        $this->assertEquals('automatically changed', $task->getValue("name"));
    }
}
