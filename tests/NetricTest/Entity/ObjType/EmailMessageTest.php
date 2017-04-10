<?php
/**
 * Test entity email class
 */
namespace NetricTest\Entity\ObjType;

use Netric\Entity;
use Netric\Entity\ObjType\EmailMessageEntity;
use Netric\Entity\EntityInterface;
use Netric\Mime;
use Netric\Mail;
use PHPUnit_Framework_TestCase;

class EmailMessageTest extends PHPUnit_Framework_TestCase
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
        foreach ($this->testEntities as $entity)
        {
            $entityLoader->delete($entity, true);
        }
    }

    /**
     * Test dynamic factory of entity
     */
    public function testFactory()
    {
        $entity = $this->account->getServiceManager()->get("EntityFactory")->create("email_message");
        $this->assertInstanceOf("\\Netric\\Entity\\ObjType\\EmailMessageEntity", $entity);
    }

    /**
     * Make sure we can parse , or ; separated message lists
     */
    public function testGetAddressListFromString()
    {
        $method = new \ReflectionMethod('Netric\Entity\ObjType\EmailMessageEntity', 'getAddressListFromString');
        $method->setAccessible(true);

        $entityFactory = $this->account->getServiceManager()->get("EntityFactory");
        $emailEntity = $entityFactory->create("email_message");
        $addresses = "\"Test\" <test@test.com>, test@test2.com";
        $addressList = $method->invoke($emailEntity, $addresses);
        $this->assertEquals(2, $addressList->count());

        $addresses2 = "\"Test\" <test@test.com>;, test@test2.com";
        $addressList = $method->invoke($emailEntity, $addresses2);
        $this->assertEquals(2, $addressList->count());
    }

    /**
     * Test convert a EmailMessageEntity into a Mail/Message that is mime encoded
     */
    public function testToMailMimeMessage()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $emailMessage = $entityLoader->create("email_message");
        $emailMessage->setValue("subject", "My Test Message");
        $emailMessage->setValue("body", "<p>My Body</p>");
        $emailMessage->setValue("sent_from", "Test User <test@myaereuscom>");
        $emailMessage->setValue("send_to", "Another User <test2@myaereuscom>");
        $emailMessage->setValue("cc", "Copy User <test3@myaereuscom>");
        $emailMessage->setValue("bcc", "Blind User <test4@myaereuscom>");

        $mailMessage = $emailMessage->toMailMessage();

        // Test headers
        $headers = $mailMessage->getHeaders();
        $this->assertTrue($headers->has("subject"));
        $this->assertTrue($headers->has("to"));
        $this->assertTrue($headers->has("cc"));
        $this->assertTrue($headers->has("bcc"));

        // Test body
        $body = $mailMessage->getBody();
        $parts = $body->getParts();
        $this->assertContains("My Body", $parts[0]->getContent());
        $this->assertContains("<p>My Body</p>", $parts[0]->getContent());
    }

    /**
     * Test convert a EmailMessageEntity into a Mail/Message that is mime encoded
     */
    public function testToMailMimeMessageAttachment()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $emailMessage = $entityLoader->create("email_message");
        $emailMessage->setValue("subject", "My Test Message");
        $emailMessage->setValue("body", "<p>My Body</p>");
        $emailMessage->setValue("sent_from", "Test User <test@myaereuscom>");
        $emailMessage->setValue("send_to", "Another User <test2@myaereuscom>");

        // Add an attachment
        $fileSystem = $this->account->getServiceManager()->get("Netric/FileSystem/FileSystem");
        $file = $fileSystem->createFile("%tmp%", "testfile.txt", true);
        $fileSystem->writeFile($file, "Textual Data");
        $this->testEntities[] = $file;
        $emailMessage->addMultiValue("attachments", $file->getId(), $file->getName());


        $mailMessage = $emailMessage->toMailMessage();

        // Test attachments
        $body = $mailMessage->getBody();
        $parts = $body->getParts();
        $this->assertContains(
            "Textual Data",
            // 0 = body, 1 = file
            $parts[1]->getRawContent()
        );
        $this->assertEquals("testfile.txt", $parts[1]->getFileName());
        $this->assertEquals("application/octet-stream", $parts[1]->getType());
        $this->assertEquals(Mime\Mime::ENCODING_BASE64, $parts[1]->getEncoding());
    }

    /**
 * Test importing a complex mime Mail\Message into an EmailEntity
 */
    public function testFromMailMessage()
    {
        $message = new Mail\Message();
        $message->setEncoding('UTF-8');
        $message->setSubject("Test Email");
        $message->addFrom("test@myaereus.com");
        $message->addTo("test2@myaereus.com");
        $message->addTo("test3@myaereus.com");
        $message->addCc("test4@myaereus.com");
        $message->addBcc("test5@myaereus.com");

        // HTML part
        $htmlPart = new Mime\Part("<p>My Body</p>");
        $htmlPart->setEncoding(Mime\Mime::ENCODING_QUOTEDPRINTABLE);
        $htmlPart->setType(Mime\Mime::TYPE_HTML);
        $htmlPart->setCharset("UTF-8");

        // Plain text part
        $textPart = new Mime\Part("My Body");
        $textPart->setEncoding(Mime\Mime::ENCODING_QUOTEDPRINTABLE);
        $textPart->setType(Mime\Mime::TYPE_TEXT);
        $textPart->setCharset("UTF-8");

        // Create a multipart/alternative message for the text and html parts
        $bodyMessage = new Mime\Message();
        $bodyMessage->addPart($textPart);
        $bodyMessage->addPart($htmlPart);

        // Create mime message to wrap both the body and any attachments
        $mimeMessage = new Mime\Message();

        // Add text & html alternatives to the mime message wrapper
        $bodyPart = new Mime\Part($bodyMessage->generateMessage());
        $bodyPart->setType(Mime\Mime::MULTIPART_ALTERNATIVE);
        $bodyPart->setBoundary($bodyMessage->getMime()->boundary());
        $mimeMessage->addPart($bodyPart);

        // Add attachments to the mime message
        $attachment = new Mime\Part("attachment-content");
        $attachment->setType(Mime\Mime::TYPE_TEXT);
        $attachment->setFileName("myfile.txt");
        $attachment->setDisposition(Mime\Mime::DISPOSITION_ATTACHMENT);
        $attachment->setEncoding(Mime\Mime::ENCODING_BASE64);
        $mimeMessage->addPart($attachment);

        // Add the message to the mail/Message and return
        $message->setBody($mimeMessage);

        // Now import this message into entity
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $emailMessage = $entityLoader->create("email_message");
        $emailMessage->fromMailMessage($message);

        // Test values
        $this->assertEquals("Test Email", $emailMessage->getValue("subject"));
        $this->assertEquals(
            "<test2@myaereus.com>,<test3@myaereus.com>",
            $emailMessage->getValue("send_to")
        );
        $this->assertEquals("<test4@myaereus.com>", $emailMessage->getValue("cc"));
        $this->assertEquals("<test5@myaereus.com>", $emailMessage->getValue("bcc"));
        $this->assertEquals("<p>My Body</p>", $emailMessage->getValue("body"));
        $this->assertEquals("html", $emailMessage->getValue("body_type"));

        // Check attachments
        $fileSystem = $this->account->getServiceManager()->get("Netric/FileSystem/FileSystem");
        $attachments = $emailMessage->getValue("attachments");
        $file = $fileSystem->openFileById($attachments[0]);
        $this->assertEquals("attachment-content", $fileSystem->readFile($file));
        $this->testEntities[] = $file;
    }

    /**
     * Test importing a simple text Mail\Message into an entity
     */
    public function testFromMailMessage_Plain()
    {
        $message = new Mail\Message();
        $message->setEncoding('UTF-8');
        $message->setSubject("Test Email");
        $message->addFrom("test@myaereus.com");
        $message->addTo("test2@myaereus.com");
        $message->getHeaders()->addHeaderLine("content-type", Mime\Mime::TYPE_TEXT);

        // Add the message to the mail/Message and return
        $message->setBody("My Body");

        // Now import this message into entity
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $emailMessage = $entityLoader->create("email_message");
        $emailMessage->fromMailMessage($message);

        // Test values
        $this->assertEquals("Test Email", $emailMessage->getValue("subject"));
        $this->assertEquals("<test2@myaereus.com>", $emailMessage->getValue("send_to"));
        $this->assertEquals("My Body", $emailMessage->getValue("body"));
        $this->assertEquals("plain", $emailMessage->getValue("body_type"));
    }

    /**
     * Test importing a simple text Mail\Message into an entity
     */
    public function testFromMailMessage_Html()
    {
        $message = new Mail\Message();
        $message->setEncoding('UTF-8');
        $message->setSubject("Test Email");
        $message->addFrom("test@myaereus.com");
        $message->addTo("test2@myaereus.com");
        $message->getHeaders()->addHeaderLine("content-type", Mime\Mime::TYPE_HTML);

        // Add the message to the mail/Message and return
        $message->setBody("<p>My Body</p>");

        // Now import this message into entity
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $emailMessage = $entityLoader->create("email_message");
        $emailMessage->fromMailMessage($message);

        // Test values
        $this->assertEquals("Test Email", $emailMessage->getValue("subject"));
        $this->assertEquals("<test2@myaereus.com>", $emailMessage->getValue("send_to"));
        $this->assertEquals("<p>My Body</p>", $emailMessage->getValue("body"));
        $this->assertEquals("html", $emailMessage->getValue("body_type"));
    }

    public function testDiscoverThread()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");

        // Create first message - this makes a new thread
        $email1 = $entityLoader->create("email_message");
        $email1->setValue("message_id", "utest-" . rand());
        $email1->setValue("subject", "test message 1");
        $email1->setValue("owner_id", $this->user->getId());
        $entityLoader->save($email1);
        $this->testEntities[] = $email1;

        // Make sure we created a new thread
        $this->assertNotEmpty($email1->getValue("thread"));

        // Now create a second message, simulating a reply to
        $email2 = $entityLoader->create("email_message");
        $email2->setValue("in_reply_to", $email1->getValue("message_id"));
        $email2->setValue("subject", "test message 2");
        $email2->setValue("owner_id", $this->user->getId());
        $entityLoader->save($email2);
        $this->testEntities[] = $email2;

        // Make sure it discovered the thread
        $this->assertEquals($email1->getValue("thread"), $email2->getValue("thread"));

    }

    public function testOnBeforeSave()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");

        // Create first message - this makes a new thread
        $email = $entityLoader->create("email_message");

        // Run through onBeforeSave and make sure it worked
        $email->onBeforeSave($this->account->getServiceManager());

        // Should have generated a message Id
        $this->assertNotEmpty($email->getValue("message_id"));

        // Should have created a new thread
        $this->assertNotEmpty($email->getValue("thread"));

        // Should have set num_attachments to 0
        $this->assertEquals(0, $email->getValue("num_attachments"));

        // cleanup thread
        $thread = $entityLoader->get("email_thread", $email->getValue("thread"));
        $this->testEntities[] = $thread;
    }

    public function testOnAfterSave_Delete()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");

        // Create first message - this makes a new thread
        $email1 = $entityLoader->create("email_message");
        $email1->setValue("message_id", "utest-" . rand());
        $email1->setValue("owner_id", $this->user->getId());
        $entityLoader->save($email1);
        $this->testEntities[] = $email1;

        // Now create a second message, simulating a reply to and attach
        $email2 = $entityLoader->create("email_message");
        $email2->setValue("in_reply_to", $email1->getValue("message_id"));
        $email2->setValue("owner_id", $this->user->getId());
        $entityLoader->save($email2);
        $this->testEntities[] = $email2;

        $entityLoader->clearCache("email_thread", $email1->getValue("thread"));
        $thread = $entityLoader->get("email_thread", $email1->getValue("thread"));

        // Should have 2 messages in the queue
        $this->assertEquals(2, $thread->getValue("num_messages"));

        // Delete one of the messages
        $entityLoader->delete($email2);

        // Should have decremented num_messages but not deleted the thread
        $entityLoader->clearCache("email_thread", $email1->getValue("thread"));
        $thread = $entityLoader->get("email_thread", $email1->getValue("thread"));
        $this->assertEquals(1, $thread->getValue("num_messages"));
        $this->assertFalse($thread->isDeleted());

        // Delete the last message
        $entityLoader->delete($email1);

        // Should have decremented num_messages but not deleted the thread
        $entityLoader->clearCache("email_thread", $email1->getValue("thread"));
        $thread = $entityLoader->get("email_thread", $email1->getValue("thread"));
        $this->assertTrue($thread->isDeleted());
    }

    public function testOnBeforeDeleteHard()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $emailMessage = $entityLoader->create("email_message");

        // Add an attachment
        $fileSystem = $this->account->getServiceManager()->get("Netric/FileSystem/FileSystem");
        $file = $fileSystem->createFile("%tmp%", "testfile.txt", true);
        $fileSystem->writeFile($file, "Textual Data");
        $this->testEntities[] = $file;

        // Set the raw file id
        $emailMessage->setValue("file_id", $file->getId(), $file->getName());
        $entityLoader->save($emailMessage);

        // Cache file id for later testing
        $fileId = $file->getId();

        // Purge the email message and make sure the file goes with it
        $entityLoader->delete($emailMessage, true);
        $this->assertNull($fileSystem->openFileById($fileId));
    }

    public function testGetHtmlBody()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $emailMessage = $entityLoader->create("email_message");

        // Add a plain text message
        $emailMessage->setValue("body_type", EmailMessageEntity::BODY_TYPE_PLAIN);
        $emailMessage->setValue("body", "my\nmessage");

        // Test
        $expected = "my<br />\nmessage";
        $this->assertEquals($expected, $emailMessage->getHtmlBody());
    }

    public function testGetPlainBody()
    {
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $emailMessage = $entityLoader->create("email_message");

        // Add a plain text message
        $emailMessage->setValue("body_type", EmailMessageEntity::BODY_TYPE_HTML);
        $emailMessage->setValue("body", "<style>.test{padding:0;}</style>my<br />message");

        // Test
        $expected = "my\nmessage";
        $this->assertEquals($expected, $emailMessage->getPlainBody());
    }
}
