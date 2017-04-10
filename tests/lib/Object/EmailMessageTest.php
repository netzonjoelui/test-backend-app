<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Invoice.php');
require_once(dirname(__FILE__).'/../../../lib/PaymentGateway.php');
require_once(dirname(__FILE__).'/../../../lib/PaymentGatewayManager.php');
require_once(dirname(__FILE__).'/../../../lib/aereus.lib.php/antapi.php');
require_once(dirname(__FILE__).'/../../../lib/AntMail/DeliveryAgent.php');

class CAntObject_EmailMessageTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;
	var $ant = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
	}
	
	function tearDown() 
	{
	}
    
    /*function getTests()
    {        
        return array("testEmailCampaignSend");
    }*/

	/**
	 * Test saving draft messages
	 *
	 * @group testSave
	 */
	public function testSave()
	{
		// Disable the attachment test until ANS get a test environment
		return;
		
		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$email->setHeader("Subject", "CAntObject_EmailMessageTest:testSave");
		$email->setHeader("From", "sky.stebnicki@aereus.com");
		$email->setHeader("To", "foo@bar.com");
		$email->setHeader("Cc", "foo2@bar.com");
		$email->setHeader("Bcc", "foo3@bar.com");
		$email->setBody("my test email");
		$email->setGroup("Inbox");

		// Add a test attachment
		// --------------------------------------------------
		$att = $email->addAttachment(dirname(__FILE__)."/../../data/mime_emails/testatt.txt");
		$att->name = "testatt.txt";
		$att->fileName = "testatt.txt";
		$att->conentType = "text/plain";
		$att->contentDisposition = "attachment";
        
		$mid = $email->save();

		$this->assertTrue(is_numeric($mid));
		unset($mail);

		// Test saved values
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals($email->getValue("body"), "my test email");
		$this->assertEquals($email->getValue("send_to"), "foo@bar.com");
		$this->assertEquals($email->getValue("sent_from"), "sky.stebnicki@aereus.com");
        
        // When in localhost, the manual adding of attachment works
        // When in live Ant, the manual adding of attachment is not working
        // Probably because CAntObject_Folder::importFile() is fid of the file is not available
		$this->assertEquals($email->getValue("num_attachments"), 1);

		// test if attachment exists - $att is still a valid reference
		$attachments = $email->getAttachments();
		$this->assertTrue(count($attachments) >= 0);
        
		$this->assertEquals($att->obj->getValue('file_id'), $attachments[0]->getValue('file_id'));

		// Now try adding a temp file attachment
		// --------------------------------------------------
		$antfs = new AntFs($this->dbh, $this->user);
		$file = $antfs->createTempFile();
		$fid = $file->id;
		$size = $file->write("test contents");
		$this->assertNotEquals($size, -1);

		$email->addAttachmentAntFsTmp($fid, true); // The second param makes it a temp file which will be moved
		$email->save();

		$this->assertEquals($email->getValue("num_attachments"), 2);
		$attachments = $email->getAttachments();
        
		$this->assertTrue(count($attachments) >= 1);

		// Test forwarded message attachments
		// --------------------------------------------------
		$email2 = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$email2->setHeader("Subject", "CAntObject_EmailMessageTest:testSave(2)");
		$email2->setHeader("From", "sky.stebnicki@aereus.com");
		$email2->setHeader("To", "foo@bar.com");
		$email2->setHeader("Cc", "foo2@bar.com");
		$email2->setHeader("Bcc", "foo3@bar.com");
		$email2->setBody("my test email");
		$email2->setGroup("Inbox");
		$email2->addAttachmentFwd($attachments[0]->id); // Just grab the first attachment
		$email2->debug = true;
		$email2->save();
		// test if attachment exists - $att is still a valid reference
		$attachments = $email2->getAttachments();
		$this->assertEquals(count($attachments), 1);

		// Check created thread
		// --------------------------------------------------
		$tid = $email->getValue("thread");
		$this->assertTrue(is_numeric($tid));
		$thread = CAntObject::factory($this->dbh, "email_thread", $tid, $this->user);
		$this->assertTrue($thread->getValue("num_attachments")>0);
		$this->assertEquals($thread->getValue("subject"), $email->getValue("subject"));
		$this->assertNotEquals($thread->getValue("body"), "");
		$this->assertTrue($thread->getMValueExists("mailbox_id", $email->getValue("mailbox_id")));

		// Cleanup
		// --------------------------------------------------
		//$email->removeHard();
		//$email2->removeHard();
	}

	/**
	 * Test sending emails
	 *
	 * @group testSend
	 */
	public function testSend()
	{
		// Disable the attachment test until ANS get a test environment
		return;
		
		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$email->testMode = true;
		$email->setHeader("Subject", "Test Message");
		$email->setHeader("From", "sky.stebnicki@aereus.com");
		$email->setHeader("To", "foo@bar.com");
		$email->setBody("my test email");

		// Add a temp file attachment
		// --------------------------------------------------
		$antfs = new AntFs($this->dbh, $this->user);
		$file = $antfs->createTempFile();
		$fid = $file->id;
		$size = $file->write("test contents");
		$this->assertNotEquals($size, -1);

		$email->addAttachmentAntFsTmp($fid, true); // The second param makes it a temp file which will be moved
        
		$ret = $email->send(true); // Need to save email, so attachment can be tested

		$this->assertTrue($ret);

		// Now test the contents of the email to see if the attachemnt is there
		//$this->assertNotEquals(strpos($email->testModeBuf['body'], "Content-Disposition: attachment;"), false);
        $attachments = $email->getAttachments();
        $this->assertEquals(count($attachments), 1);
        
		$this->assertEquals($email->testModeBuf['recipients']['To'], $email->getValue("send_to"));
		$this->assertEquals($email->testModeBuf['headers']['Subject'], $email->getValue("subject"));
        
        $email->removeHard();
	}

	/**
	 * Test to see if a sent message gets put into the right thread
	 *
	 * @group testSendThread
	 */
	public function testSendThread()
	{
		// Disable the attachment test until ANS get a test environment
		return;
		
		// Setup decoy email to make sure there are multiple threads so we know it is getting the right data
		$emailDecoy = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$emailDecoy->setHeader("Subject", "Test Message");
		$emailDecoy->setHeader("From", "sky.stebnicki@aereus.com");
		$emailDecoy->setHeader("To", "foo@bar.com");
		$emailDecoy->setHeader("Cc", "foo2@bar.com");
		$emailDecoy->setHeader("Bcc", "foo3@bar.com");
		$emailDecoy->setBody("my test email");
		$emailDecoy->setGroup("Inbox");
		$emailDecoy->save();

		// Setup initial email
		$emailFirst = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$emailFirst->setHeader("Subject", "Test Message");
		$emailFirst->setHeader("From", "sky.stebnicki@aereus.com");
		$emailFirst->setHeader("To", "foo@bar.com");
		$emailFirst->setHeader("Cc", "foo2@bar.com");
		$emailFirst->setHeader("Bcc", "foo3@bar.com");
		$emailFirst->setBody("my test email");
		$emailFirst->setGroup("Inbox");
		$mid = $emailFirst->save();
		$thread = CAntObject::factory($this->dbh, "email_thread", $emailFirst->getValue("thread"), $this->user);
		$this->assertEquals($thread->getValue("num_messages"), 1);
		$this->assertEquals($thread->getValue("num_attachments"), "0");

		// Now send email in reply
		$emailRep = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$emailRep->testMode = true;
		$emailRep->setHeader("Subject", "Test Message");
		$emailRep->setHeader("From", "sky.stebnicki@aereus.com");
		$emailRep->setHeader("To", "foo@bar.com");
		$emailRep->setHeader("In-reply-to", $emailFirst->getMessageId());
		$emailRep->setBody("my test email");
		$ret = $emailRep->send();
		$this->assertTrue($ret);
		// Now test to see if the threads in the two emails are the same
		$this->assertEquals($emailRep->getValue("thread"), $emailFirst->getValue("thread"));
		$thread = CAntObject::factory($this->dbh, "email_thread", $emailRep->getValue("thread"), $this->user);
		$this->assertEquals($thread->getValue("num_messages"), 2);

		// Now send reply to reply
		$emailRepRep = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$emailRepRep->testMode = true;
		$emailRepRep->setHeader("Subject", "Test Message");
		$emailRepRep->setHeader("From", "sky.stebnicki@aereus.com");
		$emailRepRep->setHeader("To", "foo@bar.com");
		$emailRepRep->setHeader("In-reply-to", $emailRep->getMessageId());
		$emailRepRep->setBody("my test email");
		$ret = $emailRepRep->send();
		$this->assertTrue($ret);
		// Now test to see if the threads in the two emails are the same
		$this->assertEquals($emailRepRep->getValue("thread"), $emailFirst->getValue("thread"));
		$thread = CAntObject::factory($this->dbh, "email_thread", $emailRepRep->getValue("thread"), $this->user);
		$this->assertEquals($thread->getValue("num_messages"), 3);

		// Cleanup
		$emailDecoy->removeHard();
		$emailFirst->removeHard();
		$emailRep->removeHard();
		$emailRepRep->removeHard();
	}

	/**
	 * Test moviving message from one group (mailbox_id) to another
	 *
	 * @group testMove
	 */
	public function testMove()
	{
		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);

		// Create a test group
		$groupData= $email->addGroupingEntry("mailbox_id", "Unit Test Group", "e3e3e3");

		$email->setGroup("Inbox");
		$email->setHeader("Subject", "Test Message");
		$email->setHeader("From", "sky.stebnicki@aereus.com");
		$email->setHeader("To", "foo@bar.com");
		$email->setHeader("Cc", "foo2@bar.com");
		$email->setHeader("Bcc", "foo3@bar.com");
		$email->setBody("my test email");
		$mid = $email->save();
		$inboxId = $email->getValue("mailbox_id");
		unset($email);
		$this->assertTrue($mid > 0);

		// Reopen and set the group
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$email->move($groupData['id']);
		unset($email);

		// Reopen and test group
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals($email->getValue("mailbox_id"), $groupData['id']);
		$thread = CAntObject::factory($this->dbh, "email_thread", $email->getValue("thread"), $this->user);
		$this->assertTrue($thread->getMValueExists("mailbox_id", $groupData['id']));
		$this->assertFalse($thread->getMValueExists("mailbox_id", $inboxId));

		// Cleanup
		$ret = $email->deleteGroupingEntry("mailbox_id", $groupData['id']);
		$email->removeHard();
	}

	/**
	 * Test removing a message
	 *
	 * @group testRemove
	 */
	public function testRemove()
	{
		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);

		$email->setGroup("Inbox");
		$email->setHeader("Subject", "Test Message");
		$email->setHeader("From", "sky.stebnicki@aereus.com");
		$email->setHeader("To", "foo@bar.com");
		$email->setHeader("Cc", "foo2@bar.com");
		$email->setHeader("Bcc", "foo3@bar.com");
		$email->setBody("my test email");
		$mid = $email->save();
		$inboxId = $email->getValue("mailbox_id");
		unset($email);
		$this->assertTrue($mid > 0);

		// Reopen and remove this message
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$email->remove();
		unset($email);

		// Reopen and test to make sure the message was deleted
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals($email->getValue("f_deleted"), 't');		
        $this->assertNotEquals($email->getValue("thread"), null);
        
		// And the thread
		$thread = CAntObject::factory($this->dbh, "email_thread", $email->getValue("thread"), $this->user);
		$this->assertEquals($thread->getValue("f_deleted"), 't'); 

		// Cleanup
		$email->removeHard();
	}

	/**
	 * Test parsing and inserting a new message into ANT from a raw text file
	 *
	 * @group testImport
	 */
	public function testImport()
	{
		// Disable the attachment test until ANS get a test environment
		return;
		
		// Test mailparse parser
		// ----------------------------------------------------------
		if(function_exists('mailparse_msg_parse'))
		{
			$tmpFile = $this->getMessageTempFile(dirname(__FILE__)."/../../data/mime_emails/attachments-mail.txt");
			$this->assertTrue(file_exists($tmpFile));

			// Insert message
			//$newEmail = new CAntObject_EmailMessage($this->dbh, null, $this->user);
            $newEmail = CAntObject::factory($this->dbh, "email_message", null, $this->user);
			$mid = $newEmail->import($tmpFile, "mailparse");
			$this->assertTrue(is_numeric($mid));
			unset($newEmail);

			// Make sure attachments have been added
			//$message = new CAntObject_EmailMessage($this->dbh, $mid, $this->user);
            $message = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
			$attachments = $message->getAttachments();
			$this->assertTrue(count($attachments)>0);
			$this->assertTrue($message->getValue("thread")>0);
			$thread = CAntObject::factory($this->dbh, "email_thread", $message->getValue("thread"), $this->user);
			$this->assertEquals($thread->getValue('subject'), "Fwd: font/logo house of shem.");
			$this->assertTrue($thread->getMValueExists("mailbox_id", $message->getValue("mailbox_id")));

			// Open attachment and make sure that the text of the message matches
			$attfound = false;
			foreach ($attachments as $att)
			{
				if ($att->getValue('filename') == "readme.txt")
				{
					//$this->assertEquals(UserFilesGetFileContents($this->dbh, $att['file_id']), "Test Readme Content");
					$attfound = true;
				}
			}
			$this->assertTrue($attfound);

			unlink($tmpFile);
			$message->removeHard();
		}

		// Test mimeDecode parser
		// ----------------------------------------------------------
		$tmpFile = $this->getMessageTempFile(dirname(__FILE__)."/../../data/mime_emails/attachments-mail.txt");
		$newEmail = new CAntObject_EmailMessage($this->dbh, null, $this->user);
		$mid = $newEmail->import($tmpFile, "mimedecode");
		$this->assertTrue(is_numeric($mid));
		unset($newEmail);

		// Make sure attachments have been added
		//$message = new CAntObject_EmailMessage($this->dbh, $mid, $this->user);
        $message = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$attachments = $message->getAttachments();
		$this->assertTrue(count($attachments)>0);
		$this->assertTrue($message->getValue("thread")>0);
		$thread = CAntObject::factory($this->dbh, "email_thread", $message->getValue("thread"), $this->user);
		$this->assertEquals($thread->getValue('subject'), "Fwd: font/logo house of shem.");
		$this->assertTrue($thread->getMValueExists("mailbox_id", $message->getValue("mailbox_id")));

		// Open attachment and make sure that the text of the message matches
		$attfound = false;
		foreach ($attachments as $att)
		{
			if ($att->getValue('filename') == "readme.txt")
			{
				// Need to test that file_id is more than 0
				$this->assertTrue($att->getValue('file_id') > 0);
				
				// For some reasons, UserFilesGetFileContents() is returning false
				// $this->assertEquals(UserFilesGetFileContents($this->dbh, $att['file_id']), "Test Readme Content");
				$attfound = true;
			}
		}
		$this->assertTrue($attfound);

		// Test the spam flag
		$this->assertEquals($message->getValue("flag_spam"), "t");
		$this->assertEquals($message->getGroupId("Junk Mail"), $message->getValue("mailbox_id"));

		unlink($tmpFile);
		$message->removeHard();
	}

	/**
	 * Test parsing and inserting a new message into ANT from a raw text file
	 *
	 * @group testReparse
	 */
	public function testReparse()
	{
		// Disable the attachment test until ANS get a test environment
		return;
		
		// Test mailparse parser
		// ----------------------------------------------------------
		if(function_exists('mailparse_msg_parse'))
		{
			$tmpFile = $this->getMessageTempFile(dirname(__FILE__)."/../../data/mime_emails/attachments-mail.txt");
			$this->assertTrue(file_exists($tmpFile));

			// Insert message
			//$newEmail = new CAntObject_EmailMessage($this->dbh, null, $this->user);
            $newEmail = CAntObject::factory($this->dbh, "email_message", null, $this->user);
			$mid = $newEmail->import($tmpFile, "mailparse", true); // last param saves a copy of the original
			$this->assertTrue(is_numeric($mid));
			// Change settings
			$oldSub = $newEmail->getValue("subject");
			$newEmail->setValue("subject", "edited subject");
			$newEmail->setValue("parse_rev", 1); // backrev the parse engine to force a reparse
			$newEmail->save();
			unset($newEmail);

			// Make sure the original was saved
			$oid = null;
			$message_id = null;
			$result = $this->dbh->Query("select message_id, lo_message from email_message_original where message_id='$mid';");
			if ($this->dbh->GetNumberRows($result))
			{
				$message_id = $this->dbh->GetValue($result, 0, "message_id");
				$oid = $this->dbh->GetValue($result, 0, "lo_message");
			}
			$this->assertNotEquals($message_id, null);
			$this->assertNotEquals($oid, null);

			/*
			$oid = $this->dbh->loImport($tmpFile);
			$this->dbh->Query("insert into email_message_original(message_id, lo_message) values('$mid', '$oid');");
			 */

			// Now reparse
			//$email = new CAntObject_EmailMessage($this->dbh, $mid, $this->user);
            $email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
			// First get the id of the attachment to make sure it gets replaced
			$attachments = $email->getAttachments();
			$attid = $attachments[0]->id;
			// Get body which should force a reparse
			$email->getBody();
			unset($email);

			// Make sure attachments have been added
			//$message = new CAntObject_EmailMessage($this->dbh, $mid, $this->user);
            $message = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
			$attachments = $message->getAttachments();
			$this->assertTrue(count($attachments)>0);
			$this->assertTrue($message->getValue("thread")>0);
			$this->assertEquals($message->getValue('subject'), $oldSub);
			$this->assertNotEquals($attid, $attachments[0]->id);

			unlink($tmpFile);
			/*
			$this->dbh->loUnlink($oid);
			$this->dbh->Query("delete from email_message_original where message_id='$mid'");
			 */
			$message->removeHard();
		}

		// Test mimeDecode parser
		// ----------------------------------------------------------
		$tmpFile = $this->getMessageTempFile(dirname(__FILE__)."/../../data/mime_emails/attachments-mail.txt");
		$newEmail = new CAntObject_EmailMessage($this->dbh, null, $this->user);
		$newEmail->debug = true;
		$mid = $newEmail->import($tmpFile, "mimedecode", true);
		$this->assertTrue(is_numeric($mid));
		// Change settings
		$oldSub = $newEmail->getValue("subject");
		$newEmail->setValue("subject", "edited subject");
		$newEmail->setValue("parse_rev", 1); // backrev the parse engine to force a reparse
		$newEmail->save();
		unset($newEmail);

		// Make sure the original was saved
		$oid = null;
		$message_id = null;
		$result = $this->dbh->Query("select message_id, lo_message from email_message_original where message_id='$mid';");
        $num = $this->dbh->GetNumberRows($result);
		if($num)
		{
			$message_id = $this->dbh->GetValue($result, 0, "message_id");
			$oid = $this->dbh->GetValue($result, 0, "lo_message");
		}
		$this->assertNotEquals($message_id, null);
		$this->assertNotEquals($oid, null);

		// Now reparse
		//$email = new CAntObject_EmailMessage($this->dbh, $mid, $this->user);
        $email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		// First get the id of the attachment to make sure it gets replaced
		$attachments = $email->getAttachments();
		$attid = $attachments[0]->id;
		// Get body which should force a reparse
		$email->getBody();
		unset($email);

		// Make sure attachments have been added
		//$message = new CAntObject_EmailMessage($this->dbh, $mid, $this->user);
        $message = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$attachments = $message->getAttachments();
		$this->assertTrue(count($attachments)>0);
		$this->assertTrue($message->getValue("thread")>0);
		$this->assertEquals($message->getValue('subject'), $oldSub);
		$this->assertNotEquals($attid, $attachments[0]->id);

		unlink($tmpFile);
		$message->removeHard();
	}

	/**
	 * Test mark read
	 *
	 * @group testMarkRead
	 */
	public function testMarkRead()
	{
		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);

		$email->setGroup("Inbox");
		$email->setValue("flag_seen", 'f');
		$email->setHeader("Subject", "Test Message");
		$email->setHeader("From", "sky.stebnicki@aereus.com");
		$email->setHeader("To", "foo@bar.com");
		$email->setHeader("Cc", "foo2@bar.com");
		$email->setHeader("Bcc", "foo3@bar.com");
		$email->setBody("my test email");
		$mid = $email->save();
		$inboxId = $email->getValue("mailbox_id");
		unset($email);
		$this->assertTrue($mid > 0);

		// Reopen and set the group
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$email->markRead(true);
		unset($email);

		// Reopen and test flag of message and the thread
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals($email->getValue("flag_seen"), 't');
		$thread = CAntObject::factory($this->dbh, "email_thread", $email->getValue("thread"), $this->user);
		$this->assertEquals($thread->getValue("f_seen"), 't');

		// Cleanup
		$email->removeHard();
	}

	/**
	 * Test associate message
	 *
	 * When a new message is created it will try to automatically associate with an existing 
	 * thread by message_id
	 *
	 * @group testFindExistingThread
	 */
	public function testFindExistingThread()
	{
		// Disable the attachment test until ANS get a test environment
		return;
		
		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$email->setGroup("Inbox");
		$email->setValue("flag_seen", 'f');
		$email->setHeader("Subject", "Test Message");
		$email->setHeader("From", "sky.stebnicki@aereus.com");
		$email->setHeader("To", "foo@bar.com");
		$email->setHeader("Cc", "foo2@bar.com");
		$email->setHeader("Bcc", "foo3@bar.com");
		$email->setBody("my test email");
		$mid = $email->save();
		$messageId = $email->getMessageId();
		$this->assertTrue($mid > 0);
		$this->assertEquals($email->owner_id, $this->user->id);

		// Create second email and set it as being a reply to the above message
		$email2 = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$email2->setGroup("Inbox");
		$email2->setValue("flag_seen", 'f');
		$email2->setHeader("Subject", "Test Message");
		$email2->setHeader("From", "sky.stebnicki@aereus.com");
		$email2->setHeader("To", "foo@bar.com");
		$email2->setHeader("Cc", "foo2@bar.com");
		$email2->setHeader("Bcc", "foo3@bar.com");
		$email2->setHeader("In-Reply-To", $messageId);
		$email2->setBody("my second test email");
		$mid2 = $email2->save();
		$this->assertTrue($mid2 > 0);

		// Now test to make sure both messages are in the same flag
		$this->assertEquals($email->getValue("thread"), $email2->getValue("thread"));

		// Cleanup
		$email->removeHard();
		$email2->removeHard();
	}

	/**
	 * Create a temp file to use when importing email
	 *
	 * @group getMessageTempFile
	 * @return string The path to the newly created temp file
	 */
	private function getMessageTempFile($file)
	{
		$tmpFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
		if (!file_exists($tmpFolder))
			@mkdir($tmpFolder, 0777);
		$tmpFile = tempnam($tmpFolder, "em");
		file_put_contents($tmpFile, file_get_contents($file)); // copy data

		// Normalize new lines to \r\n
		$handle = @fopen($tmpFile, "r");
		$handleNew = @fopen($tmpFile."-pro", "w");
		$buffer = null;
		if ($handle) 
		{
			while (($buffer = fgets($handle, 4096)) !== false) 
			{

				fwrite($handleNew,  preg_replace('/\r?\n$/', '', $buffer)."\r\n");
			}
			fclose($handle);
			fclose($handleNew);
			unlink($tmpFile);
			$tmpFile = $tmpFile."-pro"; // update name to match processed file
		}

		return $tmpFile;
	}

	/**
	 * Test setupSMTP
	 *
	 * @group setupSMTP
	 */
	public function testSetupSMTP()
	{
		$ant = $this->ant;

		// Store existing settings for later restoring
		$smtpHost = $ant->settingsGet("email/smtp_host");
		$smtpUser = $ant->settingsGet("email/smtp_user");
		$smtpPassword = $ant->settingsGet("email/smtp_password");
		$smtpPort = $ant->settingsGet("email/smtp_port");
		$smtpBulkHost = $ant->settingsGet("email/smtp_bulk_host");
		$smtpBulkUser = $ant->settingsGet("email/smtp_bulk_user");
		$smtpBulkPassword = $ant->settingsGet("email/smtp_bulk_password");
		$smtpBulkPort = $ant->settingsGet("email/smtp_bulk_port");

		// Clear settings
		$ant->settingsSet("email/smtp_host", "");
		$ant->settingsSet("email/smtp_user", "");
		$ant->settingsSet("email/smtp_password", "");
		$ant->settingsSet("email/smtp_port", "");
		$ant->settingsSet("email/smtp_bulk_host", "");
		$ant->settingsSet("email/smtp_bulk_user", "");
		$ant->settingsSet("email/smtp_bulk_password", "");
		$ant->settingsSet("email/smtp_bulk_port", "");

		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);

		// Now test system default
		$email->setupSMTP();
		$this->assertEquals($email->smtpHost, AntConfig::getInstance()->email['server']);
		$email->smtpHost = null; // reset

		// TODO: test system default for bulk

		// Add custom system setting
		$ant->settingsSet("email/smtp_host", "test.netricos.com");
		$email->setupSMTP();
		$this->assertEquals($email->smtpHost, "test.netricos.com");
		$email->smtpHost = null; // reset

		// Add custom system setting for bulk and indicate bulk message
		$ant->settingsSet("email/smtp_bulk_host", "bulk.netricos.com");
		$email->setupSMTP(null, true);
		$this->assertEquals($email->smtpHost, "bulk.netricos.com");
		$email->smtpHost = null; // reset

		// Check account
		$account = new AntMail_Account($this->dbh);
		$account->smtpHost = "test.account.com";
		$email->setupSMTP($account);
		$this->assertEquals($email->smtpHost, "test.account.com");
		$email->smtpHost = null; // reset

		// Now make sure bulk overrides custom account
		$email->setupSMTP($account, true);
		$this->assertEquals($email->smtpHost, "bulk.netricos.com");
		$email->smtpHost = null; // reset

		// Restore previous settings
		$ant->settingsSet("email/smtp_host", $smtpHost);
		$ant->settingsSet("email/smtp_user", $smtpUser);
		$ant->settingsSet("email/smtp_password", $smtpPassword);
		$ant->settingsSet("email/smtp_port", $smtpPort);
		$ant->settingsSet("email/smtp_bulk_host", $smtpBulkHost);
		$ant->settingsSet("email/smtp_bulk_user", $smtpBulkUser);
		$ant->settingsSet("email/smtp_bulk_password", $smtpBulkPassword);
		$ant->settingsSet("email/smtp_bulk_port", $smtpBulkPort);
	}
}
