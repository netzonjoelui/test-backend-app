<?php
/**
 * Test all common functionality of any DataMappers to make sure they all work the same
 */
namespace NetricTest\WorkFlow\DataMapper;

use Netric\WorkFlow\WorkFlowInstance;
use PHPUnit_Framework_TestCase;
use Netric\WorkFlow\DataMapper\DataMapperInterface;
use Netric\WorkFlow\Action\ActionFactory;
use Netric\EntityQuery\Where;
use Netric\WorkFlow\WorkFlow;
use Netric\Entity\EntityInterface;
use Netric\EntityLoader;

abstract class AbstractDataMapperTests extends PHPUnit_Framework_TestCase
{
    /**
     * Action factory is needed to construct a new workflow
     *
     * @var ActionFactory
     */
    protected $actionFactory = null;

    /**
     * Entity loader for managing entities
     *
     * @var EntityLoader
     */
    protected $entityLoader = null;

    /**
     * Test workflows created that need to be deleted
     *
     * @param WorkFlow[]
     */
    protected $testWorkFlows = array();

    /**
     * Test entities
     *
     * @var EntityInterface[]
     */
    protected $testEntities = array();

    /**
     * Setup dependencies for tests
     */
    protected function setUp()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->actionFactory = new ActionFactory($sm);
        $this->entityLoader = $sm->get("EntityLoader");
    }

    /**
     * Delete any created workflows or entities
     */
    protected function tearDown()
    {
        $dataMapper = $this->getDataMapper();

        foreach ($this->testWorkFlows as $workFlow)
        {
            $dataMapper->delete($workFlow);
        }

        foreach ($this->testEntities as $entity)
        {
            $this->entityLoader->delete($entity, true);
        }
    }

    /**
     * Required function by all specific datamapper tests to get instance of datamapper
     *
     * @return DataMapperInterface
     */
    abstract protected function getDataMapper();

    /**
     * Make sure we can save a new workflow
     */
    public function testSave()
    {
        $dataMapper = $this->getDataMapper();

        // Data to save and test
        $workFlowData = array(
            "name" => "Test Save",
            "obj_type" => "task",
            "notes" => "Details Here",
            "active" => true,
            "on_create" => true,
            "on_update" => true,
            "on_delete" => true,
            "singleton" => false,
            "allow_manual" => false,
            "only_on_conditions_unmet" => true,
            "conditions" => array(
                array(
                    "blogic" => Where::COMBINED_BY_AND,
                    "field_name" => "done",
                    "operator" => Where::OPERATOR_EQUAL_TO,
                    "value" => true,
                )
            ),
            "actions" => array(
                array(
                    "name" => "my action",
                    "type" => "test",
                ),
            ),
        );

        // Create and save the workflow
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);
        $workflowId = $dataMapper->save($workFlow);
        $this->assertNotNull($workflowId);

        // Unset and reload to make sure it was saved right
        unset($workFlow);
        $workFlow = $dataMapper->getById($workflowId);
        $this->testWorkFlows[] = $workFlow; // For cleanup
        $openedData = $workFlow->toArray();
        $this->assertArrayHasKey('id', $openedData);

        /*
         * Test that whatever is in $openedData matches what we set in $workFlowData.
         * We can't just do assertEquals because defaults may have been set in addition
         * to what is in $workFlowData such as 'id' which will cause it to fail.
         */
        foreach ($workFlowData as $key=>$value)
        {
            if (is_array($value))
            {
                // Test expected nested array values
                foreach ($value as $subValueKey=>$subValue)
                {
                    foreach ($subValue as $entryKey=>$entryValue)
                    {
                        $this->assertEquals(
                            $entryValue,
                            $openedData[$key][$subValueKey][$entryKey],
                            "$key does not match"
                        );
                    }
                }
            }
            else
            {
                $this->assertEquals($value, $openedData[$key], "$key does not match");
            }
        }
    }

    /**
     * Make sure we can save an existing WorkFlow
     */
    public function testSave_Update()
    {
        $dataMapper = $this->getDataMapper();

        // Data to save and test
        $workFlowData = array(
            "name" => "Test Save",
            "obj_type" => "task",
            "notes" => "Details Here",
            "active" => true,
            "on_create" => true,
            "conditions" => array(
                array(
                    "blogic" => Where::COMBINED_BY_AND,
                    "field_name" => "done",
                    "operator" => Where::OPERATOR_EQUAL_TO,
                    "value" => true,
                )
            ),
            "actions" => array(
                array(
                    "name" => "my action",
                    "type" => "test",
                    "actions" => array(
                        array(
                            "name" => "my child action",
                            "type" => "test"
                        )
                    )
                ),
            ),
        );

        // Create and save the workflow
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);
        $workflowId = $dataMapper->save($workFlow);

        // Change some values and save again
        $workFlowData['name'] = "Test Update";
        $workFlowData['on_daily'] = true;
        $workFlowData['conditions'][0]['blogic'] = Where::COMBINED_BY_OR;
        $workFlowData['actions'][0]['name'] = "update test action";

        // Save workflow with changes
        $workFlow->fromArray($workFlowData);
        $dataMapper->save($workFlow);

        // Open to test
        unset($workFlow);
        $workFlow = $dataMapper->getById($workflowId);
        $this->testWorkFlows[] = $workFlow; // For cleanup
        $openedData = $workFlow->toArray();

        /*
         * Test that whatever is in $openedData matches what we set in the changed $workFlowData.
         * We can't just do assertEquals because defaults may have been set in addition
         * to what is in $workFlowData such as 'id' which will cause it to fail.
         */
        foreach ($workFlowData as $key=>$value)
        {
            if (is_array($value))
            {
                // Test expected nested array values
                foreach ($value as $subValueKey=>$subValue)
                {
                    foreach ($subValue as $entryKey=>$entryValue)
                    {
                        if (is_array($entryValue))
                        {
                            // We can only go so deep, just check to make sure there same number of elements
                            $this->assertEquals(
                                count($entryValue),
                                count($openedData[$key][$subValueKey][$entryKey])
                            );
                        }
                        else
                        {
                            $this->assertEquals(
                                $entryValue,
                                $openedData[$key][$subValueKey][$entryKey]
                            );
                        }
                    }
                }
            }
            else
            {
                $this->assertEquals($value, $openedData[$key]);
            }
        }
    }

    /**
     * Assure that we can delete a datamapper
     */
    public function testDelete()
    {
        $dataMapper = $this->getDataMapper();

        // Data to save and test
        $workFlowData = array(
            "name" => "Test Save",
            "obj_type" => "task",
            "notes" => "Details Here",
            "active" => true,
            "on_create" => true,
            "conditions" => array(
                array(
                    "blogic" => Where::COMBINED_BY_AND,
                    "field_name" => "done",
                    "operator" => Where::OPERATOR_EQUAL_TO,
                    "value" => true,
                )
            ),
            "actions" => array(
                array(
                    "name" => "my action",
                    "type" => "test",
                ),
            ),
        );

        // Create and save the workflow
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);
        $workflowId = $dataMapper->save($workFlow);

        // Delete
        $dataMapper->delete($workFlow);

        // Try to get and make sure it's null
        $this->assertNull($dataMapper->getById($workflowId));
    }

    /**
     * Check if the datamapper supports opening a workflow by a unique id
     */
    public function testGetById()
    {
        $dataMapper = $this->getDataMapper();

        // Data to save and test
        $workFlowData = array(
            "name" => "Test Save",
            "obj_type" => "task",
            "notes" => "Details Here",
            "active" => true,
            "on_create" => true,
        );

        // Create and save the workflow
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);
        $workflowId = $dataMapper->save($workFlow);
        $this->testWorkFlows[] = $workFlow;

        // Open in a new entity
        $workFlowOpened = $dataMapper->getById($workflowId);

        // Just make sure we got the right entry since testSave* will be more detailed
        $this->assertEquals($workFlow->getId(), $workFlowOpened->getId());
    }

    /**
     * Check if we can load all workflows
     */
    public function testGetWorkFlows()
    {
        $dataMapper = $this->getDataMapper();

        // Create and save the workflow
        $workFlowData = array(
            "name" => "Test Get Workflows",
            "obj_type" => "task",
            "active" => true,
        );
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);
        $dataMapper->save($workFlow);
        $this->testWorkFlows[] = $workFlow;

        // Get workflows and make sure the above is included
        $workFlows = $dataMapper->getWorkFlows();
        $found = false;
        foreach ($workFlows as $wf)
        {
            if ($wf->getId() == $workFlow->getId())
                $found = true;
        }
        $this->assertTrue($found);
    }

    /**
     * Make sure we can load all workflows for a specific object type
     */
    public function testGetWorkFlows_ObjType()
    {
        $dataMapper = $this->getDataMapper();

        // Create and save the workflow
        $workFlowData = array(
            "name" => "Test Get Workflows",
            "obj_type" => "task",
            "active" => true,
        );
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);
        $dataMapper->save($workFlow);
        $this->testWorkFlows[] = $workFlow;

        // Get workflows for this object type and make sure the above is included
        $workFlows = $dataMapper->getWorkFlows($workFlowData['obj_type']);
        $workFlowFound = false;
        $anomalyFound = false;
        foreach ($workFlows as $wf)
        {
            if ($wf->getId() === $workFlow->getId())
                $workFlowFound = true;

            // A returned workflow should never be different than $workFlowData['obj_type']
            if ($wf->getObjType() !=  $workFlowData['obj_type'])
                $anomalyFound = true;
        }
        $this->assertTrue($workFlowFound);
        $this->assertFalse($anomalyFound);
    }

    /**
     * Make sure we load only active workflows
     */
    public function testGetWorkFlows_Active()
    {
        $dataMapper = $this->getDataMapper();

        // Create and save the workflow
        $workFlowData = array(
            "name" => "Test Get Workflows",
            "obj_type" => "task",
            "active" => false,
        );
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);
        $dataMapper->save($workFlow);
        $this->testWorkFlows[] = $workFlow;

        // Get only active workflows and make sure the above is not included
        $workFlows = $dataMapper->getWorkFlows($workFlowData['obj_type'], true);
        $workFlowFound = false;
        foreach ($workFlows as $wf)
        {
            if ($wf->getId() === $workFlow->getId())
                $workFlowFound = true;
        }
        $this->assertFalse($workFlowFound);

    }

    /**
     * Make sure we can load inactive workflows
     */
    public function testGetWorkFlows_Inactive()
    {
        $dataMapper = $this->getDataMapper();

        // Create and save the workflow
        $workFlowData = array(
            "name" => "Test Get Workflows",
            "obj_type" => "task",
            "active" => false,
        );
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);
        $dataMapper->save($workFlow);
        $this->testWorkFlows[] = $workFlow;

        // Second param turns off filtering for active workflows only
        $workFlows = $dataMapper->getWorkFlows($workFlowData['obj_type'], false);
        $workFlowFound = false;
        foreach ($workFlows as $wf)
        {
            if ($wf->getId() === $workFlow->getId())
                $workFlowFound = true;
        }
        $this->assertTrue($workFlowFound);

    }

    public function testSaveWorkFlowInstance()
    {
        $dataMapper = $this->getDataMapper();

        // Create and save the workflow
        $workFlowData = array(
            "name" => "Test Get Workflows",
            "obj_type" => "task",
            "active" => false,
        );
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);
        $dataMapper->save($workFlow);
        $this->testWorkFlows[] = $workFlow;

        // Create a new task for the instance
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "testSaveWorkFlowInstance");
        $this->entityLoader->save($task);
        $this->testEntities[] = $task;

        // Start a new test/fake instance
        $workFlowInstance = new WorkFlowInstance($workFlow->getId(), $task);
        $instanceId = $dataMapper->saveWorkFlowInstance($workFlowInstance);
        $this->assertNotNull($instanceId);

        // Cleanup
        $dataMapper->deleteWorkFlowInstance($instanceId);
    }

    public function testGetWorkFlowInstanceById()
    {
        $dataMapper = $this->getDataMapper();

        // Create and save the workflow
        $workFlowData = array(
            "name" => "Test Get Workflows",
            "obj_type" => "task",
            "active" => false,
        );
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);
        $dataMapper->save($workFlow);
        $this->testWorkFlows[] = $workFlow;

        // Create a new task for the instance
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "testSaveWorkFlowInstance");
        $this->entityLoader->save($task);
        $this->testEntities[] = $task;

        // Start a new test/fake instance
        $workFlowInstance = new WorkFlowInstance($workFlow->getId(), $task);
        $instanceId = $dataMapper->saveWorkFlowInstance($workFlowInstance);
        $workFlowInstanceClone = $dataMapper->getWorkFlowInstanceById($instanceId);

        $this->assertEquals($workFlowInstance->getId(), $workFlowInstanceClone->getId());
        $this->assertEquals($workFlowInstance->getTimeStarted(), $workFlowInstanceClone->getTimeStarted());
        $this->assertEquals($workFlowInstance->getObjTypeId(), $workFlowInstanceClone->getObjTypeId());
        $this->assertEquals($workFlowInstance->getEntityId(), $workFlowInstanceClone->getEntityId());
        $this->assertEquals($workFlowInstance->isCompleted(), $workFlowInstanceClone->isCompleted());

        // Cleanup
        $dataMapper->deleteWorkFlowInstance($instanceId);
    }

    public function testScheduleAndDeleteAction()
    {
        $dataMapper = $this->getDataMapper();

        // Create and save the workflow
        $workFlowData = array(
            "name" => "Test Get Workflows",
            "obj_type" => "task",
            "active" => false,
            "actions" => array(
                array(
                    "name" => "my action",
                    "type" => "test",
                ),
            ),
        );
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);
        $dataMapper->save($workFlow);
        $this->testWorkFlows[] = $workFlow;
        $actionId = $workFlow->getActions()[0]->getId();

        // Create a new task for the instance
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "testSaveWorkFlowInstance");
        $this->entityLoader->save($task);
        $this->testEntities[] = $task;

        // Start a new test/fake instance
        $workFlowInstance = new WorkFlowInstance($workFlow->getId(), $task);
        $instanceId = $dataMapper->saveWorkFlowInstance($workFlowInstance);

        $this->assertTrue($dataMapper->scheduleAction($instanceId, $actionId, new \DateTime()));

        // Cleanup
        $this->assertTrue($dataMapper->deleteScheduledAction($instanceId, $actionId));
    }

    public function testGetScheduledActions()
    {
        $dataMapper = $this->getDataMapper();

        // Create and save the workflow
        $workFlowData = array(
            "name" => "Test Get Workflows",
            "obj_type" => "task",
            "active" => false,
            "actions" => array(
                array(
                    "name" => "my action",
                    "type" => "test",
                ),
            ),
        );
        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);
        $dataMapper->save($workFlow);
        $this->testWorkFlows[] = $workFlow;
        $actionId = $workFlow->getActions()[0]->getId();

        // Create a new task for the instance
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "testSaveWorkFlowInstance");
        $this->entityLoader->save($task);
        $this->testEntities[] = $task;

        // Start a new test/fake instance
        $workFlowInstance = new WorkFlowInstance($workFlow->getId(), $task);
        $instanceId = $dataMapper->saveWorkFlowInstance($workFlowInstance);

        /*
         * Schedule the action for now so it triggers immediately when we
         * look for previously scheduled tasks
         */
        $dataMapper->scheduleAction($instanceId, $actionId, new \DateTime(date("Y-m-d")));

        // Get scheduled actions array and make sure the above added action is there
        $scheduled = $dataMapper->getScheduledActions();
        $found = null;
        foreach ($scheduled as $queuedAction)
        {
            if ($queuedAction['instance']->getId() == $workFlowInstance->getId())
            {
                // Found our scheduled action
                $found = $queuedAction;
                break;
            }
        }
        $this->assertNotNull($found);
        $this->assertEquals($actionId, $found['action']->getId());

        // Cleanup
        $dataMapper->deleteScheduledAction($instanceId, $actionId);
    }
}