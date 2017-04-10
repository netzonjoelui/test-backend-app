<?php
/**
 * Test entity project class
 */
namespace NetricTest\Entity\ObjType;

use Netric\Entity;
use Netric\EntityQuery;
use Netric\EntityQuery\Index\Pgsql;
use Netric\Entity\ObjType\UserEntity;
use PHPUnit_Framework_TestCase;

class ProjectTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tennant account
     *
     * @var \Netric\Account\Account
     */
    private $account = null;

    /**
     * Administrative user
     *
     * @var \Netric\User
     */
    private $user = null;


    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->user = $this->account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);
    }

    /**
     * Test dynamic factory of entity
     */
    public function testFactory()
    {
        $def = $this->account->getServiceManager()->get("EntityDefinitionLoader")->get("project");
        $entity = $this->account->getServiceManager()->get("EntityFactory")->create("project");
        $this->assertInstanceOf("\\Netric\\Entity\\ObjType\\ProjectEntity", $entity);
    }

    /**
     * Test the cloning of project
     */
    public function testCloneTo()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $proj1 = $this->account->getServiceManager()->get("EntityFactory")->create("project");
        $proj2 = $this->account->getServiceManager()->get("EntityFactory")->create("project");
        $task = $this->account->getServiceManager()->get("EntityFactory")->create("task");

        // Create orginal object
        $proj1->setValue("name", "Project One");
        $proj1->setValue("date_deadline", "1/1/2013");
        $pid_1 = $entityLoader->save($proj1);

        // Add task to project 1
        $task->setValue("name", "Project One");
        $task->setValue("deadline", "1/7/2013"); // 1 week later
        $task->setValue("project", $pid_1);
        $tid = $entityLoader->save($task);

        // Create a new project and clone the references
        $proj2->setValue("name", "Project Clone");
        $proj2->setValue("date_deadline", "2/1/2013");
        $pid_2 = $entityLoader->save($proj2);

        // Clone the task from the first
        $proj1->cloneTo($proj2);

        // Get the new task
        $query = new EntityQuery("task");
        $query->where('project')->equals($pid_2);

        $queryIndex = new Pgsql($this->account);
        $res = $queryIndex->executeQuery($query);
        $num = $res->getNum();
        $newTask = $res->getEntity(0);

        $this->assertEquals($newTask->getValue("name"), "Project One");

        // Cleanup
        $entityLoader->delete($proj1, true);
        $entityLoader->delete($proj2, true);
        $entityLoader->delete($task, true);
        $entityLoader->delete($newTask, true);
    }
}