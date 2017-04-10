<?php
/**
 * Test notification
 */
namespace NetricTest\Entity\ObjType;

use Netric\Entity\EntityInterface;
use Netric\EntityLoader;
use Netric\Entity\ObjType\UserEntity;
use PHPUnit_Framework_TestCase;
use Netric\Mail\Transport\InMemory;
use Netric\EntityQuery;

class NotificationTest extends PHPUnit_Framework_TestCase
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
     * Test user to notify
     *
     * @var User
     */
    private $testUser = null;

    /**
     * EntityLoader
     *
     * @var EntityLoader
     */
    private $entityLoader = null;

    /**
     * List of test entities to cleanup
     *
     * @var EntityInterface[]
     */
    private $testEntities = array();

    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->user = $this->account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);
        $this->entityLoader = $this->account->getServiceManager()->get("EntityLoader");


        // Make sure test user does not exist from previous failed query
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $query = new EntityQuery("user");
        $query->where("name")->equals("notificationtest");
        $result = $index->executeQuery($query);
        for ($i = 0; $i < $result->getNum(); $i++)
        {
            $this->entityLoader->delete($result->getEntity($i), true);
        }

        // Create a test user to assign a task and notification to
        $this->testUser = $this->entityLoader->create("user");
        $this->testUser->setValue("name", "notificationtest");
        $this->testUser->setValue("email", "test@netric.com");
        $this->entityLoader->save($this->testUser);
        $this->testEntities[] = $this->testUser;
    }

    /**
     * Cleanup after each test
     */
    protected function tearDown()
    {
        // Make sure any test entities created are deleted
        foreach ($this->testEntities as $entity)
        {
            // Second param is a 'hard' delete which actually purges the data
            $this->entityLoader->delete($entity, true);
        }
    }

    /**
     * Test dynamic factory of entity
     */
    public function testFactory()
    {
        $entity = $this->account->getServiceManager()->get("EntityFactory")->create("notification");
        $this->assertInstanceOf("\\Netric\\Entity\\ObjType\\NotificationEntity", $entity);
    }

    /**
     * Test that a email is sent
     */
    public function testSendEmailNotification()
    {
        // Set obj_reference to a task
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "A test task");
        $this->entityLoader->save($task);
        $this->testEntities[] = $task;

        // Create a new notification
        $notification = $this->entityLoader->create("notification");
        $notification->setValue("f_email", true);
        $notification->setValue("owner_id", $this->testUser->getId());
        $notification->setValue("creator_id", $this->user->getId());
        $notification->setValue("obj_reference", "task:" . $task->getId(), $task->getName());

        // Setup testable transport
        $transport = new InMemory();
        $notification->setMailTransport($transport);

        // Call onBeforeSave manually
        $notification->onBeforeSave($this->account->getServiceManager());

        $message = $transport->getLastMessage();

        // Make sure the message was sent to the owner_id
        $this->assertEquals(
            $this->testUser->getValue("email"),
            $message->getTo()->current()->getEmail()
        );

        // Check that we sent from the creator name
        $this->assertEquals(
            $this->user->getName(),
            $message->getFrom()->current()->getName()
        );

        // Make sure dropbox email is generated for replying to
        $this->assertContains(
            $this->account->getName() . "-com-task." . $task->getId(),
            $message->getFrom()->current()->getEmail()
        );
    }
}