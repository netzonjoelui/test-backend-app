<?php
namespace NetricTest\WorkFlow\Action;

use Netric\WorkFlow\Action\ActionInterface;
use Netric\WorkFlow\WorkFlowInstance;

class UpdateFieldActionTest extends AbstractActionTests
{
    /**
     * Test entities to delete
     *
     * @var EntityInterface[]
     */
    private $testEntities = array();

    /**
     * Cleanup entities
     */
    protected function tearDown()
    {
        foreach ($this->testEntities as $entity)
        {
            $this->entityLoader->delete($entity, true);
        }

        parent::tearDown();
    }

    /**
     * All action tests must construct the action
     *
     * @return ActionInterface
     */
    protected function getAction()
    {
        return $this->actionFactory->create("update_field");
    }

    /**
     * Test that execute actually updates the field value
     */
    public function testExecute()
    {
        // Get and setup action
        $action = $this->getAction();
        $action->setParam('update_field', 'name');
        $action->setParam('update_value', 'edited test');

        // Create a test task
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "test");
        $this->entityLoader->save($task);
        $this->testEntities[] = $task;

        // Create a fake WorkFlowInstance since the action does not a saved workflow or instance
        $workFlowInstance = new WorkFlowInstance(123, $task);

        // Now execute the action and make sure it updated the field in $task
        $action->execute($workFlowInstance);

        // Test the value of $task to see if the action updated it
        $this->assertEquals('edited test', $task->getValue("name"));
    }
}
