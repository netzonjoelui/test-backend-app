<?php
/**
 * Test entity email thread class
 */
namespace NetricTest\Entity\ObjType;

use Netric\Entity;
use Netric\Entity\EntityInterface;
use Netric\Mime;
use Netric\Mail;
use PHPUnit_Framework_TestCase;

/**
 * @group integration
 */
class EmailThreadTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tenant account
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
     * Test entities to delete
     *
     * @var EntityInterface[]
     */
    private $testEntities = [];

    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->user = $this->account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);
    }

    /**
     * Cleanup
     */
    protected function tearDown()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        foreach ($this->testEntities as $entity) {
            $entityLoader->delete($entity, true);
        }
    }

    /**
     * Test dynamic factory of entity
     */
    public function testFactory()
    {
        $entity = $this->account->getServiceManager()->get("EntityFactory")->create("email_thread");
        $this->assertInstanceOf("\\Netric\\Entity\\ObjType\\EmailThreadEntity", $entity);
    }

    /**
     * When we soft-delete a thread, it should remove all messages
     */
    public function testOnAfterSave_Remove()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");

        // Create a thread and an email message for testing
        $thread = $entityLoader->create("email_thread");
        $thread->setValue("subject", "My New test Thread");
        $tid = $entityLoader->save($thread);
        $this->testEntities[] = $thread;

        $message = $entityLoader->create("email_message");
        $message->setValue("thread", $tid);
        $eid = $entityLoader->save($message);
        $this->testEntities[] = $message;

        // Remove the thread
        $entityLoader->delete($thread);

        // Check to make sure the message was soft-deleted as well
        $reloadedMessage = $entityLoader->get("email_message", $eid);
        $this->assertTrue($reloadedMessage->getValue("f_deleted"));
    }

    /**
     * When we undelete a thread, it should restore any deleted messages
     */
    public function testOnAfterSave_Undelete()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");

        // Create a thread and an email message for testing
        $thread = $entityLoader->create("email_thread");
        $thread->setValue("subject", "My New test Thread");
        $tid = $entityLoader->save($thread);
        $this->testEntities[] = $thread;

        $message = $entityLoader->create("email_message");
        $message->setValue("thread", $tid);
        $eid = $entityLoader->save($message);
        $this->testEntities[] = $message;

        // Soft delete the thread which will also soft delete the message (see testOnAfterSave_Remove)
        $entityLoader->delete($thread);

        // Now undelete the thread which should undelete the messsage
        $thread->setValue("f_deleted", false);
        $entityLoader->save($thread);

        // Check to make sure the message was soft-deleted as well
        $reloadedMessage = $entityLoader->get("email_message", $eid);
        $this->assertFalse($reloadedMessage->getValue("f_deleted"));
    }

    /**
     * Make sure that on a hard delete, all messages are purged
     */
    public function testOnAfterDeleteHard()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");

        // Create a thread and an email message for testing
        $thread = $entityLoader->create("email_thread");
        $thread->setValue("subject", "My New test Thread");
        $tid = $entityLoader->save($thread);
        $this->testEntities[] = $thread;

        $message = $entityLoader->create("email_message");
        $message->setValue("thread", $tid);
        $eid = $entityLoader->save($message);
        $this->testEntities[] = $message;

        // Remove the thread
        $entityLoader->delete($thread, true);

        // Make sure message was also purged
        $this->assertNull($entityLoader->get("email_message", $eid));
    }

    public function testAddToSenders()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $thread = $entityLoader->create("email_thread");
        $thread->addToSenders("test1@myaereus.com, test2@myaereus.com");
        $this->assertEquals("test1@myaereus.com,test2@myaereus.com", $thread->getValue("senders"));

        // Re-order by adding test2 again and appending test3
        $thread->addToSenders("test2@myaereus.com, test3@myaereus.com");
        $this->assertEquals(
            "test2@myaereus.com,test3@myaereus.com,test1@myaereus.com",
            $thread->getValue("senders")
        );
    }


    public function testAddToReceivers()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $thread = $entityLoader->create("email_thread");
        $thread->addToSenders("test1@myaereus.com, test2@myaereus.com");
        $this->assertEquals("test1@myaereus.com,test2@myaereus.com", $thread->getValue("senders"));

        // Re-order by adding test2 again and appending test3
        $thread->addToSenders("test2@myaereus.com, test3@myaereus.com");
        $this->assertEquals(
            "test2@myaereus.com,test3@myaereus.com,test1@myaereus.com",
            $thread->getValue("senders")
        );
    }
}
