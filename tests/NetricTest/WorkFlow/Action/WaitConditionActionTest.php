<?php
namespace NetricTest\WorkFlow\Action;

use Netric\WorkFlow\Action\ActionInterface;
use Netric\WorkFlow\WorkFlow;
use Netric\WorkFlow\DataMapper\DataMapperInterface;
use Netric\WorkFlow\WorkFlowInstance;

class WaitConditionActionTest extends AbstractActionTests
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
     * All action tests must construct the action
     *
     * @return ActionInterface
     */
    protected function getAction()
    {
        return $this->actionFactory->create("wait_condition");
    }

    /**
     * Test execution with delayed time
     */
    public function testExecute()
    {
        // Get the ServiceManager
        $serviceManager = $this->account->getServiceManager();

        // Create and save the workflow
        $workFlowData = array(
            "name" => "Test Get Execute Wait Action",
            "obj_type" => "task",
            "active" => true,
        );
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);

        // Create wait condition action and set params for 1 week wait
        $action = $this->getAction();
        $action->fromArray(array("name"=>"testExecuteWaitAction"));
        $action->setParam("when_unit", WorkFlow::TIME_UNIT_WEEK);
        $action->setParam("when_interval", 1);

        // Add to WorkFlow for saving
        $workFlow->addAction($action);

        // Save the WorkFlow and action
        $this->workFlowDataMapper->save($workFlow);

        // Save an entity to run workflow instance on
        $entityLoader = $serviceManager->get("EntityLoader");
        $task = $entityLoader->create("task");
        $task->setValue("name", "test");
        $entityLoader->save($task);
        $this->testEntities[] = $task;

        // Start a new instance from the workflow and task
        $workFlowInstance = new WorkFlowInstance($workFlow->getId(), $task);
        $instanceId = $this->workFlowDataMapper->saveWorkFlowInstance($workFlowInstance);

        // Execute the action and make sure it returns false because it was scheduled for the future
        $this->assertFalse($action->execute($workFlowInstance));

        // Make sure the action has been scheduled correctly
        $executeDate = $this->workFlowDataMapper->getScheduledActionTime($instanceId, $action->getId());
        $this->assertEquals(date("Y-m-d", strtotime("+1 week")), $executeDate->format("Y-m-d"));

        // Run execute again (as if in a scheduled job) and it should return true
        $this->assertTrue($action->execute($workFlowInstance));

        // Make sure the scheduled action has been deleted
        $this->assertNull($this->workFlowDataMapper->getScheduledActionTime($instanceId, $action->getId()));
    }

    public function testGetExecuteDate()
    {
        $action = $this->getAction();

        // Test minutes
        $executeDate = $action->getExecuteDate(WorkFlow::TIME_UNIT_MINUTE, 60);
        $this->assertEquals(date("H:i", strtotime("+60 minutes")), $executeDate->format("H:i"));

        // Test hours
        $executeDate = $action->getExecuteDate(WorkFlow::TIME_UNIT_HOUR, 24);
        $this->assertEquals(date("Y-m-d", strtotime("+24 hours")), $executeDate->format("Y-m-d"));

        // Test days
        $executeDate = $action->getExecuteDate(WorkFlow::TIME_UNIT_DAY, 3);
        $this->assertEquals(date("Y-m-d", strtotime("+3 days")), $executeDate->format("Y-m-d"));

        // Test weeks
        $executeDate = $action->getExecuteDate(WorkFlow::TIME_UNIT_WEEK, 2);
        $this->assertEquals(date("Y-m-d", strtotime("+2 weeks")), $executeDate->format("Y-m-d"));

        // Test months
        $executeDate = $action->getExecuteDate(WorkFlow::TIME_UNIT_MONTH, 4);
        $this->assertEquals(date("Y-m-d", strtotime("+4 months")), $executeDate->format("Y-m-d"));

        // Test years
        $executeDate = $action->getExecuteDate(WorkFlow::TIME_UNIT_YEAR, 1);
        $this->assertEquals(date("Y-m-d", strtotime("+1 year")), $executeDate->format("Y-m-d"));
    }
}
