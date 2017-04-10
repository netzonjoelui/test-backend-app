<?php
namespace NetricTest\WorkFlow\Action;

use Netric\WorkFlow\WorkFlowInstance;
use Netric\WorkFlow\Action\ActionInterface;
use Netric\Entity\ObjType\UserEntity;
use Netric\EntityQuery;

class CreateEntityActionTest extends AbstractActionTests
{
    /**
     * All action tests must construct the action
     *
     * @return ActionInterface
     */
    protected function getAction()
    {
        return $this->actionFactory->create("create_entity");
    }

    public function testExecute()
    {
        $testLongName = 'utest-workflow-action-create-entity';
        $action = $this->getAction();
        $action->setParam('obj_type', 'task');
        $action->setParam('name', $testLongName);
        $action->setParam('user_id', '<%user_id%>'); // Copy from parent task

        // Get user
        $user = $this->account->getUser(UserEntity::USER_SYSTEM);

        // Create a test task that will create another task that copies the woner
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "test");
        $task->setValue("user_id", $user->getId());
        $task->setId(321);

        // Create a fake WorkFlowInstance since the action does not a saved workflow or instance
        $workFlowInstance = new WorkFlowInstance(123, $task);

        // Now execute the action and make sure it updated the field in $task
        $this->assertTrue($action->execute($workFlowInstance));

        // Get and cleanup
        $newEntityFound = false;
        $query = new EntityQuery("task");
        $query->where('name')->equals($testLongName);
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $result = $index->executeQuery($query);
        for ($i = 0; $i < $result->getNum(); $i++)
        {
            $taskToDelete = $result->getEntity($i);
            // Make sure the user was copied from the parent task via <%user_id%>
            $this->assertEquals($task->getValue("user_id"), $taskToDelete->getValue("user_id"));
            $this->entityLoader->delete($taskToDelete, true);
            $newEntityFound = true;
        }

        // Make sure that crazy entity was found
        $this->assertTrue($newEntityFound);
    }
}
