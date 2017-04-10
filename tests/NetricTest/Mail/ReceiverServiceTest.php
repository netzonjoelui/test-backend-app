<?php
namespace NetricTest\Mail;

use Netric\EntityQuery;
use Netric\Mail\Storage;
use Netric\Mail\Storage\Imap;
use Netric\Entity\EntityInterface;
use Netric\Entity\ObjType\UserEntity;
use Netric\Entity\ObjType\EmailAccountEntity;
use Netric\Account\Account;
use Netric\EntityGroupings\Group;
use PHPUnit_Framework_TestCase;

class ReceiverServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * The user that owns the email account
     *
     * @var UserEntity
     */
    private $user = null;

    /**
     * Current user before test was run
     *
     * @var UserEntity
     */
    private $origCurrentUser = null;

    /**
     * Test email account for receiving local messages
     *
     * @var EmailAccountEntity
     */
    private $emailAccount = null;

    /**
     * Active test account
     *
     * @var Account
     */
    private $account = null;

    /**
     * Any test entities created
     *
     * @var EntityInterface[]
     */
    private $testEntities = [];

    /**
     * Inbox grouping
     *
     * @var Group
     */
    private $inbox = null;

    /**
     * Setup the service
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");

        // Create a temporary user
        $this->origCurrentUser = $this->account->getUser();
        $this->user = $entityLoader->create("user");
        $this->user->setValue("name", "utest-email-receiver-" . rand());
        $entityLoader->save($this->user);
        $this->testEntities[] = $this->user;
        $this->account->setCurrentUser($this->user);

        // If it does not exist, create an inbox for the user
        $groupingsLoader = $this->account->getServiceManager()->get("Netric/EntityGroupings/Loader");
        $groupings = $groupingsLoader->get(
            "email_message", "mailbox_id", ["user_id"=>$this->user->getId()]
        );
        $inbox = new Group();
        $inbox->name = "Inbox";
        $inbox->isSystem = true;
        $inbox->user_id = $this->user->getId();
        $groupings->add($inbox);
        $groupingsLoader->save($groupings);
        $this->inbox = $groupings->getByPath("Inbox");

        // Create a new test email account with params above
        $this->emailAccount = $entityLoader->create("email_account");
        $this->emailAccount->setValue("type", "imap");
        $this->emailAccount->setValue("name", "test-imap");
        $this->emailAccount->setValue("host", getenv('TESTS_NETRIC_MAIL_HOST'));
        $this->emailAccount->setValue("username", getenv('TESTS_NETRIC_MAIL_USER'));
        $this->emailAccount->setValue("password", getenv('TESTS_NETRIC_MAIL_PASSWORD'));
        $entityLoader->save($this->emailAccount);
        $this->testEntities[] = $this->emailAccount;

        // Create mail records
        $this->account->getApplication()->createEmailDomain(
            $this->account->getId(),
            getenv('TESTS_NETRIC_MAIL_DOMAIN')
        );

        $this->account->getApplication()->createOrUpdateEmailUser(
            $this->account->getId(),
            getenv('TESTS_NETRIC_MAIL_USER'),
            md5(getenv("TESTS_NETRIC_MAIL_PASSWORD"))
        );

        $this->setupMessages();
    }

    protected function tearDown()
    {
        $serviceLocator = $this->account->getServiceManager();
        // Delete the inbox
        $groupingsLoader = $serviceLocator->get("Netric/EntityGroupings/Loader");
        $groupings = $groupingsLoader->get(
            "email_message", "mailbox_id", ["user_id"=>$this->user->getId()]
        );
        $groupings->delete($this->inbox->id);
        $groupingsLoader->save($groupings);

        // Delete any test entities
        $entityLoader = $serviceLocator->get("EntityLoader");
        foreach ($this->testEntities as $entity) {
            $entityLoader->delete($entity, true);
        }

        // Cleanup mail records
        $this->account->getApplication()->deleteEmailUser(
            $this->account->getId(),
            getenv('TESTS_NETRIC_MAIL_USER')
        );

        $this->account->getApplication()->deleteEmailDomain(
            $this->account->getId(),
            getenv('TESTS_NETRIC_MAIL_DOMAIN')
        );

        // Restore original current user
        $this->account->setCurrentUser($this->origCurrentUser);
    }

    private function setupMessages()
    {
        // Connect to mail server
        $imap = new Imap(array(
            'host'     => getenv('TESTS_NETRIC_MAIL_HOST'),
            'user'     => getenv('TESTS_NETRIC_MAIL_USER'),
            'password' => getenv('TESTS_NETRIC_MAIL_PASSWORD')
        ));

        // Clean the mailbox
        if ($imap->countMessages() > 0) {
            $toRemove = [];

            // Queue messages to be deleted by id since you can't iterate after changing
            foreach ($imap as $msgNo=>$message) {
                // Put it at the beginning so we can reverse delete
                array_unshift($toRemove, $msgNo);
            }

            foreach ($toRemove as $msgNo) {
                $imap->removeMessage($msgNo);
            }
        }

        // Append test messages
        $testFilesRoot = __DIR__ . '/_files/';

        // Send unseen message
        $imap->appendMessage(
            file_get_contents($testFilesRoot . DIRECTORY_SEPARATOR . 'm1.example.org.unseen'),
            null,
            [Storage::FLAG_FLAGGED]
        );

        // Send flagged message
        $imap->appendMessage(
            file_get_contents($testFilesRoot . DIRECTORY_SEPARATOR . 'm2.example.org.seen.flagged'),
            null,
            [Storage::FLAG_SEEN, Storage::FLAG_FLAGGED]
        );

        // Send three seen messages
        $messages = array(
            'm3.example.org.seen',
            'm4.example.org.seen',
            'm5.example.org.seen'
        );
        foreach ($messages as $fileName) {
            $imap->appendMessage(
                file_get_contents($testFilesRoot . DIRECTORY_SEPARATOR . $fileName),
                null,
                [Storage::FLAG_SEEN]
            );
        }

        $imap->close();
    }

    public function testSyncMailbox_Download()
    {
        $receiver = $this->account->getServiceManager()->get("Netric/Mail/ReceiverService");

        $this->assertTrue($receiver->syncMailbox($this->inbox->id, $this->emailAccount));

        // Check if we imported 5 messages - the number that got uploaded
        $query = new EntityQuery("email_message");
        $query->where("mailbox_id")->equals($this->inbox->id);
        $query->andWhere("owner_id")->equals($this->user->getId());
        $query->andWhere("email_account")->equals($this->emailAccount->getId());
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $results = $index->executeQuery($query);
        $this->assertEquals(5, $results->getTotalNum());

        // Add imported to queue for cleanup
        for ($i = 0; $i < $results->getTotalNum(); $i++) {
            $this->testEntities[] = $results->getEntity($i);
        }
    }

    public function testSyncMailbox_DownloadSeenFlag()
    {
        $receiver = $this->account->getServiceManager()->get("Netric/Mail/ReceiverService");

        $this->assertTrue($receiver->syncMailbox($this->inbox->id, $this->emailAccount));

        // In setup we set one message to unseen
        $query = new EntityQuery("email_message");
        $query->where("mailbox_id")->equals($this->inbox->id);
        $query->andWhere("owner_id")->equals($this->user->getId());
        $query->andWhere("flag_seen")->equals(false);
        $query->andWhere("email_account")->equals($this->emailAccount->getId());
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $results = $index->executeQuery($query);
        $this->assertEquals(1, $results->getTotalNum());

        // Clean up all messages
        $query = new EntityQuery("email_message");
        $query->where("mailbox_id")->equals($this->inbox->id);
        $query->andWhere("owner_id")->equals($this->user->getId());
        $query->andWhere("email_account")->equals($this->emailAccount->getId());
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $results = $index->executeQuery($query);
        for ($i = 0; $i < $results->getTotalNum(); $i++) {
            $this->testEntities[] = $results->getEntity($i);
        }
    }

    public function testSyncMailbox_DownloadDelete()
    {
        $receiver = $this->account->getServiceManager()->get("Netric/Mail/ReceiverService");

        // Import 5 sample messages from the copied files in the setUp
        $this->assertTrue($receiver->syncMailbox($this->inbox->id, $this->emailAccount));

        // Delete the message on the remote server
        $imap = new Imap([
            'host'     => $this->emailAccount->getValue("host"),
            'user'     => $this->emailAccount->getValue("username"),
            'password' => getenv('TESTS_NETRIC_MAIL_PASSWORD')
        ]);
        // Delete the first message
        foreach ($imap as $msgNo=>$message) {
            $imap->removeMessage($msgNo);
            break;
        }
        $imap->close();

        // Sync again which should delete a local message
        $this->assertTrue($receiver->syncMailbox($this->inbox->id, $this->emailAccount));

        // Check if one message got deleted
        $query = new EntityQuery("email_message");
        $query->where("mailbox_id")->equals($this->inbox->id);
        $query->andWhere("owner_id")->equals($this->user->getId());
        $query->andWhere("email_account")->equals($this->emailAccount->getId());
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $results = $index->executeQuery($query);
        $this->assertEquals(4, $results->getTotalNum());

        // Add imported to queue for cleanup
        for ($i = 0; $i < $results->getTotalNum(); $i++) {
            $this->testEntities[] = $results->getEntity($i);
        }
    }

    public function testSyncMailbox_UploadChange()
    {
        $receiver = $this->account->getServiceManager()->get("Netric/Mail/ReceiverService");
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");

        // Import 7 sample messages from the copied files in the setUp
        $this->assertTrue($receiver->syncMailbox($this->inbox->id, $this->emailAccount));

        // Delete one of the messages locally
        $query = new EntityQuery("email_message");
        $query->where("mailbox_id")->equals($this->inbox->id);
        $query->andWhere("owner_id")->equals($this->user->getId());
        $query->andWhere("email_account")->equals($this->emailAccount->getId());
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $results = $index->executeQuery($query);

        // Change the first entity
        $entity = $results->getEntity(0);
        $entity->setValue('flag_seen', true);
        $entity->setValue('flag_flagged', true);
        $entityLoader->save($entity);

        // Synchronize which should update the flags on the server
        $this->assertTrue($receiver->syncMailbox($this->inbox->id, $this->emailAccount));

        // Make sure messages were updated on the server
        $imap = new Imap([
            'host'     => $this->emailAccount->getValue("host"),
            'user'     => $this->emailAccount->getValue("username"),
            'password' => getenv('TESTS_NETRIC_MAIL_PASSWORD')
        ]);
        $msgNum = $imap->getNumberByUniqueId($entity->getValue("message_uid"));
        $message = $imap->getMessage($msgNum);

        $this->assertEquals(false, $message->hasFlag(Storage::FLAG_UNSEEN));
        $this->assertEquals(true, $message->hasFlag(Storage::FLAG_FLAGGED));

        // Disconnect
        $imap->close();

        // Queue all the messages for cleanup
        for ($i = 0; $i < $results->getTotalNum(); $i++) {
            $this->testEntities[] = $results->getEntity($i);
        }
    }

    public function testSyncMailbox_UploadDelete()
    {
        $receiver = $this->account->getServiceManager()->get("Netric/Mail/ReceiverService");
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");

        // Import 7 sample messages from the copied files in the setUp
        $this->assertTrue($receiver->syncMailbox($this->inbox->id, $this->emailAccount));

        // Delete one of the messages locally
        $query = new EntityQuery("email_message");
        $query->where("mailbox_id")->equals($this->inbox->id);
        $query->andWhere("owner_id")->equals($this->user->getId());
        $query->andWhere("email_account")->equals($this->emailAccount->getId());
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $results = $index->executeQuery($query);
        $entityLoader->delete($results->getEntity(0));

        // Synchronize which should delete the message on the server
        $this->assertTrue($receiver->syncMailbox($this->inbox->id, $this->emailAccount));

        // Delete the message on the remote server
        $imap = new Imap([
            'host' => $this->emailAccount->getValue("host"),
            'user' => $this->emailAccount->getValue("username"),
            'password' => getenv('TESTS_NETRIC_MAIL_PASSWORD')
        ]);
        $this->assertEquals(4, $imap->countMessages());
        $imap->close();

        // Queue all the messages for cleanup
        for ($i = 0; $i < $results->getTotalNum(); $i++) {
            $this->testEntities[] = $results->getEntity($i);
        }
    }
}
