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

class CAntObject_EmailThreadTest extends PHPUnit_Framework_TestCase
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
    
	/*
    function getTests()
    {        
        return array("testSend");        
    }    
	 */
    
	/**
	 * Test moving threads
	 *
	 * @group testMove
	 */
	public function testMove()
	{
		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);

		// Create a test group
		$groupData = $email->addGroupingEntry("mailbox_id", "Unit Test Group", "e3e3e3");

		$email->setGroup("Inbox");
		$email->setHeader("Subject", "Test Message");
		$email->setHeader("From", "sky.stebnicki@aereus.com");
		$email->setHeader("To", "foo@bar.com");
		$email->setBody("my test email");
		$mid = $email->save();
		$this->assertTrue($mid > 0);
		$inboxId = $email->getValue("mailbox_id");
		$threadId = $email->getValue("thread");
		unset($email);

		// Reopen and move the thread
		$thread = CAntObject::factory($this->dbh, "email_thread", $threadId, $this->user);
		$thread->move($groupData['id'], $inboxId);
		unset($thread);

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
	 * Test moving threads to and from the trash
	 */
	public function testMoveToTrash()
	{
		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$email->setGroup("Inbox");
		$email->setHeader("Subject", "Test Message");
		$email->setHeader("From", "sky.stebnicki@aereus.com");
		$email->setHeader("To", "foo@bar.com");
		$email->setBody("my test email");
		$mid = $email->save();
		$this->assertTrue($mid > 0);
		$inboxId = $email->getValue("mailbox_id");
		$threadId = $email->getValue("thread");

		// Reopen and move the thread to trash
		$thread = CAntObject::factory($this->dbh, "email_thread", $threadId, $this->user);
		$trashGroup = $thread->getGroupingEntryByName("mailbox_id", "Trash");
		$thread->move($trashGroup['id']);
		unset($thread);

		// Reopen and test group
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals($email->getValue("f_deleted"), 't');

		$thread = CAntObject::factory($this->dbh, "email_thread", $email->getValue("thread"), $this->user);
		$this->assertEquals($thread->getValue("f_deleted"), 't'); 

		// Now try moving out of the trash
		$thread = CAntObject::factory($this->dbh, "email_thread", $threadId, $this->user);
		$thread->move($inboxId, $trashGroup['id']);
		unset($thread);

		// Reopen and test group
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals($email->getValue("f_deleted"), 'f');
		$this->assertEquals($email->getValue("mailbox_id"), $inboxId);

		$thread = CAntObject::factory($this->dbh, "email_thread", $email->getValue("thread"), $this->user);
		$this->assertTrue($thread->getMValueExists("mailbox_id", $inboxId));

		// Cleanup
		$email->removeHard();
	}

	/**
	 * Test removing threads
	 */
	public function testRemove()
	{
		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$email->setGroup("Inbox");
		$email->setHeader("Subject", "Test Message");
		$email->setHeader("From", "sky.stebnicki@aereus.com");
		$email->setHeader("To", "foo@bar.com");
		$email->setBody("my test email");
		$mid = $email->save();
		$this->assertTrue($mid > 0);
		$threadId = $email->getValue("thread");

		// Open thread and remove
		$thread = CAntObject::factory($this->dbh, "email_thread", $threadId, $this->user);
		$thread->remove();
		unset($thread);

		// Reopen and test deleted status
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals($email->getValue("f_deleted"), 't');

		$thread = CAntObject::factory($this->dbh, "email_thread", $email->getValue("thread"), $this->user);
		$this->assertEquals($thread->getValue("f_deleted"), "t");
		
		// Cleanup
		$thread->removeHard();
	}

	/**
	 * Test unremoving threads
	 */
	public function testUnremove()
	{
		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$email->setGroup("Inbox");
		$email->setHeader("Subject", "Test Message");
		$email->setHeader("From", "sky.stebnicki@aereus.com");
		$email->setHeader("To", "foo@bar.com");
		$email->setBody("my test email");
		$mid = $email->save();
		$this->assertTrue($mid > 0);
		$threadId = $email->getValue("thread");

		// Open thread and remove
		$thread = CAntObject::factory($this->dbh, "email_thread", $threadId, $this->user);
		$thread->remove();
		unset($thread);

		// Reopen and test deleted status
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals($email->getValue("f_deleted"), 't');

		$thread = CAntObject::factory($this->dbh, "email_thread", $email->getValue("thread"), $this->user);
		$this->assertEquals($thread->getValue("f_deleted"), "t"); 

		// Now unremove and test
		$thread->unremove();

		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals($email->getValue("f_deleted"), 'f');

		$thread = CAntObject::factory($this->dbh, "email_thread", $email->getValue("thread"), $this->user);
		$this->assertEquals($thread->getValue("f_deleted"), 'f');

		
		// Cleanup
		$thread->removeHard();
	}

	/**
	 * Test marking a thread as read
	 */
	public function testMarkRead()
	{
		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$email->setGroup("Inbox");
		$email->setHeader("Subject", "Test Message");
		$email->setHeader("From", "sky.stebnicki@aereus.com");
		$email->setHeader("To", "foo@bar.com");
		$email->setBody("my test email");
		$email->setValue("flag_seen", 'f');
		$mid = $email->save();
		$this->assertTrue($mid > 0);
		$threadId = $email->getValue("thread");

		// Open thread and remove
		$thread = CAntObject::factory($this->dbh, "email_thread", $threadId, $this->user);
		$this->assertEquals($thread->getValue('f_seen'), 'f'); // Make sure thread was created inheritiing the flag of the email
		$thread->markRead();
		unset($thread);

		// Reopen and test deleted status
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals($email->getValue("flag_seen"), 't');

		$thread = CAntObject::factory($this->dbh, "email_thread", $email->getValue("thread"), $this->user);
		$this->assertEquals($thread->getValue("f_seen"), 't');
		
		// Cleanup
		$thread->removeHard();
	}

	/**
	 * Test marking a thread as spam
	 */
	public function testMarkSpam()
	{
		$email = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$email->setGroup("Inbox");
		$email->setHeader("Subject", "Test Message");
		$email->setHeader("From", "sky.stebnicki@aereus.com");
		$email->setHeader("To", "foo@bar.com");
		$email->setBody("my test email");
		$mid = $email->save();
		$this->assertTrue($mid > 0);
		$threadId = $email->getValue("thread");

		// Open thread and remove
		$thread = CAntObject::factory($this->dbh, "email_thread", $threadId, $this->user);
		$thread->markSpam();

		// Reopen and test deleted status
		$email = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals($email->getValue("flag_spam"), 't');
		
		// Cleanup
		$thread->removeHard();
	}
}
