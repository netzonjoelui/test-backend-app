<?php
namespace NetricTest\WorkFlow\Action;

use Netric\WorkFlow\Action\ActionInterface;
use Netric\EntityQuery\Where;
use Netric\Entity\EntityInterface;
use Netric\WorkFlow\WorkFlowInstance;
use Netric\WorkFlow\DataMapper\DataMapperInterface;

class CheckConditionActionTest extends AbstractActionTests
{
    /**
     * Test entities to delete
     *
     * @var EntityInterface[]
     */
    private $testEntities = array();

    /**
     * All action tests must construct the action
     *
     * @return ActionInterface
     */
    protected function getAction()
    {
        return $this->actionFactory->create("check_condition");
    }

    /**
     * Setup common fixtures for this action
     */
    protected function setUp()
    {
        // Make sure common fixtures are setup
        parent::setUp();


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
    }

    /**
     * Check that an action only executes child actions if all conditions are met
     */
    public function testExecute()
    {
        // Get the ServiceManager
        $serviceManager = $this->account->getServiceManager();

        // Save an entity where conditions match
        $entityLoader = $serviceManager->get("EntityLoader");
        $task = $entityLoader->create("task");
        $task->setValue("name", "test");
        $task->setValue("done", false);
        $tid = $entityLoader->save($task);
        $this->testEntities[] = $task;

        // Create check condition action and set conditions
        $action = $this->getAction();
        $conditions = array(
            array(
                "blogic" => Where::COMBINED_BY_AND,
                "field_name" => "done",
                "operator" => Where::OPERATOR_EQUAL_TO,
                "value" => false,
            ),
        );
        $action->setParam("conditions", $conditions);

        // Start a new test/fake instance (no need to save since the action does not check)
        $workFlowInstance = new WorkFlowInstance(123, $task);

        // Execute the action and make sure it returns true so we can process children
        $this->assertTrue($action->execute($workFlowInstance));
    }

    /**
     * Check that execute returns false if conditions are not met
     */
    public function testExecute_NotMatch()
    {
        // Get the ServiceManager
        $serviceManager = $this->account->getServiceManager();

        // Save an entity where conditions match
        $entityLoader = $serviceManager->get("EntityLoader");
        $task = $entityLoader->create("task");
        $task->setValue("name", "test");
        $task->setValue("done", true);
        $tid = $entityLoader->save($task);
        $this->testEntities[] = $task;

        // Create check condition action and set conditions
        $action = $this->getAction();
        $conditions = array(
            array(
                "blogic" => Where::COMBINED_BY_AND,
                "field_name" => "done",
                "operator" => Where::OPERATOR_EQUAL_TO,
                "value" => false,
            ),
        );
        $action->setParam("conditions", $conditions);

        // Start a new test/fake instance (no need to save since the action does not check)
        $workFlowInstance = new WorkFlowInstance(123, $task);

        // Execute the action and make sure it returns true so we can process children
        $this->assertFalse($action->execute($workFlowInstance));
    }
}
