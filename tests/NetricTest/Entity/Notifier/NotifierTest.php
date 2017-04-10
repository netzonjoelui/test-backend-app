<?php
/**
 * Test the notifier class
 */
namespace NetricTest\Entity\Notifier;

use Netric\EntityQuery;
use PHPUnit_Framework_TestCase;
use Netric\Entity\Notifier\Notifier;
use Netric\EntityLoader;
use Netric\Account\Account;
use Netric\Entity\ObjType\ActivityEntity;
use Netric\Entity\ObjType\UserEntity;
use Netric\Entity\EntityInterface;

class NotifierTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tenant account
     *
     * @var Account
     */
    private $account = null;

    /**
     * Administrative user
     *
     * @var \Netric\User
     */
    private $user = null;

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
     * Notifier to test
     *
     * @var Notifier
     */
    private $notifier = null;

    /**
     * Test user to notify
     *
     * @var User
     */
    private $testUser = null;

    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $this->notifier = $this->account->getServiceManager()->get("Netric/Entity/Notifier/Notifier");

        // Make sure test user does not exist from previous failed query
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $query = new EntityQuery("user");
        $query->where("name")->equals("notifiertest");
        $result = $index->executeQuery($query);
        for ($i = 0; $i < $result->getNum(); $i++)
        {
            $this->entityLoader->delete($result->getEntity($i), true);
        }

        // Create a test user to assign a task and notification to
        $this->testUser = $this->entityLoader->create("user");
        $this->testUser->setValue("name", "notifiertest");
        $this->entityLoader->save( $this->testUser);
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
     * Test creating new notifications and sending them to followers of an entity
     */
    public function testSend()
    {
        // Create a test task entity and assign it to $this->testUser
        $task = $this->entityLoader->create("task");
        $task->setValue("user_id", $this->testUser->getId());
        $task->setValue("name", "test task");
        $this->entityLoader->save($task);
        $this->testEntities[] = $task;

        // Saving created notices automatically, mark them all as read for the test
        $this->notifier->markNotificationsSeen($task);

        // Now re-create notifications
        $notificationIds = $this->notifier->send($task, ActivityEntity::VERB_CREATED);

        // Exactly one notification should have been created for the test user
        $this->assertEquals(1, count($notificationIds));

        // Check that the test notification has the right values
        $notification = $this->entityLoader->get("notification", $notificationIds[0]);
        $this->testEntities[] = $notification;

        // Make sure we created a notice for the test user
        $this->assertEquals($this->testUser->getId(), $notification->getValue("owner_id"));

        // Test private getNameFromEventVerb
        $this->assertEquals("Added Task", $notification->getValue("name"));

        /*
         * Test private getNotification by re-creating entities,
         * this should just reuse the unseen notices created above.
         */
        $newNotificationIds = $this->notifier->send($task, ActivityEntity::VERB_CREATED);
        $this->assertEquals($notificationIds, $newNotificationIds);
    }

    /**
     * Make sure we can mark all unseen notifications as ween
     */
    public function testMarkNotificationsSeen()
    {
        // Index for querying entities
        $entityIndex = $this->account->getServiceManager()->get("EntityQuery_Index");

        // Create a test task entity and assign it to $this->testUser
        $task = $this->entityLoader->create("task");
        $task->setValue("user_id", $this->testUser->getId());
        $task->setValue("name", "test task");
        $this->entityLoader->save($task);
        $this->testEntities[] = $task;

        // Now re-create notifications
        $this->notifier->send($task, ActivityEntity::VERB_CREATED);

        // Query to make sure we have an unseen notification for the test user
        $query = new EntityQuery("notification");
        $query->where("owner_id")->equals($this->testUser->getId());
        $query->andWhere("obj_reference")->equals("task:" . $task->getId());
        $query->andWhere("f_seen")->equals(false);
        $result = $entityIndex->executeQuery($query);
        $this->assertEquals(1, $result->getNum());

        // Mark them all as seen for the test user
        $this->notifier->markNotificationsSeen($task, $this->testUser);

        // Query to make sure no unseen entities exist for the current user
        $query = new EntityQuery("notification");
        $query->where("owner_id")->equals($this->testUser->getId());
        $query->andWhere("obj_reference")->equals("task:" . $task->getId());
        $query->andWhere("f_seen")->equals(false);
        $result = $entityIndex->executeQuery($query);

        // Make sure none were found
        $this->assertEquals(0, $result->getNum());
    }
}
