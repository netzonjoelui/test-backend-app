<?php
/**
 * Test the WorkFlow class
 */
namespace NetricTest\WorkFlow;

use Netric\WorkFlow\WorkFlow;
use Netric\WorkFlow\Action\ActionFactory;
use Netric\EntityQuery\Where;
use PHPUnit_Framework_TestCase;

class WorkFlowTest extends PHPUnit_Framework_TestCase
{
    /**
     * Reference to account running for unit tests
     *
     * @var \Netric\Account\Account
     */
    private $account = null;

    /**
     * Action factory for testing
     *
     * @var ActionFactory
     */
    protected $actionFactory = null;

    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $sl = $this->account->getServiceManager();
        $this->actionFactory = new ActionFactory($sl);
    }

    /**
     * Make sure we can convert a workflow to and from an array
     */
    public function testFromAndToArray()
    {
        $workFlowData = array(
            "id" => 123,
            "name" => "Test",
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
                    "field_name" => "fiest_field",
                    "operator" => Where::OPERATOR_EQUAL_TO,
                    "value" => "someval",
                ),
                array(
                    "blogic" => Where::COMBINED_BY_OR,
                    "field_name" => "second_field",
                    "operator" => Where::OPERATOR_NOT_EQUAL_TO,
                    "value" => "someval",
                ),
            ),
            "actions" => array(
                array(
                    "id" => 456,
                    "name" => "my action",
                    "type" => "test",
                    "workflow_id" => 123,
                    "parent_action_id" => 1,
                    "actions" => array(
                        array(
                            "id" => 567,
                            "name" => "my child action",
                            "type" => "test",
                            "workflow_id" => 123,
                            "parent_action_id" => 456,
                        )
                    )
                ),
            ),
        );

        $workFlow = new WorkFlow($this->actionFactory);
        $workFlow->fromArray($workFlowData);

        // Now get the array back and make sure it matches the original
        $retrievedData = $workFlow->toArray();

        /*
         * Test that whatever is in $retrievedData matches what we set in $workFlowData.
         * We can't just do assertEquals because defaults may have been set in addition
         * to what is in $workFlowData such as 'revision' which will cause it to fail.
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
                                count($retrievedData[$key][$subValueKey][$entryKey])
                            );
                        }
                        else
                        {
                            $this->assertEquals(
                                $entryValue,
                                $retrievedData[$key][$subValueKey][$entryKey]
                            );
                        }
                    }
                }
            }
            else
            {
                $this->assertEquals($value, $retrievedData[$key]);
            }
        }
    }

    public function testRemoveAction()
    {
        $workFlow = new WorkFlow($this->actionFactory);

        // Create a test action
        $action = $this->actionFactory->create("test");
        $action->setId(100);
        $workFlow->addAction($action);

        // Test removing the action when it's the same object
        $this->assertTrue($workFlow->removeAction($action));
        $this->assertEquals(0, count($workFlow->getActions()));
        $this->assertEquals(1, count($workFlow->getRemovedActions()));

        // Add again which should clear it from the 'to be removed' queue
        $workFlow->addAction($action);
        $this->assertEquals(1, count($workFlow->getActions()));
        $this->assertEquals(0, count($workFlow->getRemovedActions()));

        // Now try removing with a new object that has the same id
        $actionClone = $this->actionFactory->create("test");
        $actionClone->setId(100);
        $this->assertTrue($workFlow->removeAction($actionClone));
        $this->assertEquals(0, count($workFlow->getActions()));
        $this->assertEquals(1, count($workFlow->getRemovedActions()));
    }

    public function testGetRemovedActions()
    {
        $workFlow = new WorkFlow($this->actionFactory);

        // Create a test action
        $action = $this->actionFactory->create("test");
        $action->setId(100);
        $workFlow->addAction($action);

        // Now delete it
        $workFlow->removeAction($action);
        $removedActions = $workFlow->getRemovedActions();
        $this->assertEquals($action->getId(), $removedActions[0]->getId());
    }

    public function testAddAction()
    {
        $workFlow = new WorkFlow($this->actionFactory);

        // Create a test action
        $action = $this->actionFactory->create("test");
        $action->setId(100);
        $workFlow->addAction($action);
        $this->assertEquals(1, count($workFlow->getActions()));

        // Try adding the same action again which should result only in one
        $workFlow->addAction($action);
        $this->assertEquals(1, count($workFlow->getActions()));

        // Remove it, then add again and make sure removed is empty
        $workFlow->removeAction($action);
        $workFlow->addAction($action);

        $this->assertEquals(1, count($workFlow->getActions()));
        $this->assertEquals(0, count($workFlow->getRemovedActions()));

    }

    public function testGetActions()
    {
        $workFlow = new WorkFlow($this->actionFactory);

        // Create a test action
        $action = $this->actionFactory->create("test");
        $action->setId(100);
        $workFlow->addAction($action);

        // Make sure the action is in the queue of actions
        $actions = $workFlow->getActions();
        $this->assertEquals($action->getId(), $actions[0]->getId());
    }
}