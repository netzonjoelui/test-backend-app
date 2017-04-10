<?php
/**
 * Unit tests for AntMail sync
 *
 * Future IMAP notes: - Handle this at a later time after the POP3 sync has been tested
 * because first we have to implement some better and more generic sync procedures.
 *
 * It looks like sync only downloads new messages, but does not update existing messages. For instance, if I 
 * download a message to Netric with the sync, and also connect to the backend with Outlook, mark the message 
 * in IMAP as read in outlook, then that message will never be marked as read in Netric because after the 
 * initial sync the message is no longer connected.
 *
 * 	Here is an overview of the sync process:
 * 	a. Sync remote mailboxes to local groupings
 * 	b. Sync local grouping to remote folders
 *	c. For each mailbox/grouping, sync new messages and changed messages from remote to the local store
 *
 *	Here is how I would test folder/grouping sync in the unit test:
 *	a. Create a subfolder of inbox on the imap store using the backend
 *	b. Sync and check to see if that subgroup exists in the local store
 * 	c. Create a subgroup of inbox in the local store
 *	d. Sync and check to see if the subfolder exists on the remote imap store
 *	e. Delete the folder from the remote imap store
 *	f. Sync and check to make sure the subgroup was deleted locally
 *	g. Delete a subgroup in the local store
 *	h. Synch and check to make sure the subfolder was deleted from the imap store
 *
 *	Here is how I would test message sync in the unit test:
 *	a. Add a temp message to the imap backend (you might need to create a function for this)
 *	b. Sync and make sure the message is in the local store
 *	c. Mark the message on the backend as read
 *	d. Sync and make sure the message in the local store is marked as read
 *	e. Delete the message on the backend
 *	f. Sync and make sure the message in the local store was deleted
 */

//require_once 'PHPUnit/Autoload.php';

// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');    
require_once(dirname(__FILE__).'/../../../lib/AntMail/Sync.php');
require_once(dirname(__FILE__).'/../../../lib/AntMail/Account.php');
require_once(dirname(__FILE__).'/../../../lib/AntMail/Backend.php');
require_once(dirname(__FILE__).'/../../../lib/WorkerMan.php');


class AntMail_SyncTest extends PHPUnit_Framework_TestCase
{
	var $ant = null;
	var $user = null;
	var $host = null;
	var $username = null;
	var $password = null;
	var $type = null;
	var $emailAccount = null;
	var $aid = null;

	/**
	 * Temp cache to hold supression setting
	 * 
	 * @var bool
	 */
	protected $supress = false;
	
	/**
	 * Setup unit test
	 */
	protected function setUp() 
	{
		$this->ant = new ANT();
		$this->user = new AntUser($this->ant->dbh, -1); // -1 = administrator
		$this->host = AntConfig::getInstance()->email['backend_host'];
		$this->type = "test";
		$this->port = 465;

		$this->emailAccount = new AntMail_Account($this->ant->dbh, null, $this->user);
		$this->emailAccount->name = "UnitTest Test EmailAccount";
		$this->emailAccount->type = "test";
		$this->emailAccount->username = $this->username;
		$this->emailAccount->password = $this->password;
		$this->emailAccount->host = $this->host;
        $this->emailAccount->userId = $this->user->id;
		$this->aid = $this->emailAccount->save();
		
		$this->markTestSkipped('Cannot test since imap server is not setup.');
	}

	/**
	 * Cleanup
	 */
	protected function tearDown()
	{
		if ($this->emailAccount)
			$this->emailAccount->remove();
	}

    public function testSyncMailbox_Test()
    {
        // Instantiate test backend which has 2 messages added by default
        $syncObj = new AntMail_Sync($this->ant->dbh, $this->user);
        $mailObj = CAntObject::factory($this->ant->dbh, "email_message", null, $this->user);
        $mailbox = $mailObj->getGroup("Inbox");

        $this->emailAccount->type = "test";
        $aid = $this->emailAccount->save();
        $this->assertTrue($aid > 0);

        $backend = $this->emailAccount->getBackend();
        $this->assertTrue($backend != null);

        // Sync and make sure two messages (in test backend) are added to the local ANT store in the inbox
        $syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
        $this->assertEquals(2, count($syncMessages));
        $openMid = $syncMessages[count($syncMessages)-1];
        $this->assertNotNull($openMid); // Email Object Id

        // Sync again and make sure already synchronized messages were not saved again
        $syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
        $this->assertEquals(count($syncMessages), 0);

        // Delete a message from the local ANT store
        $msgObj = CAntObject::factory($this->ant->dbh, "email_message", $openMid, $this->user);
        $ret = $msgObj->remove();
        $this->assertTrue($ret); // make sure message exists and was removed

        // Check and make sure the message was deleted on the server
        $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
        $currentNumMessages = $backend->getNumMessages();
        $this->assertEquals(1, $currentNumMessages);

        // Now delete the last message on the server directly and sync and make sure it is deleted locally
        $list = $backend->getMessageList();
        $backend->deleteMessage($list[count($list)-1]['uid'], "Inbox");
        $syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
        $this->assertEquals(count($syncMessages), 1);

        // Sync again and make sure already synchronized messages were not saved again
        $syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
        $this->assertEquals(count($syncMessages), 0);

        // Final Cleanup
        $msgObj->removeHard();
    }
	
	/**
	 * Test POP3 sync
	 *
	 * @group testSyncMailbox_Pop3
	 *
	public function testSyncMailbox_Pop3()
	{
		// Instantiate 
		$syncObj = new AntMail_Sync($this->ant->dbh, $this->user);
        $mailObj = CAntObject::factory($this->ant->dbh, "email_message", null, $this->user);
		$mailbox = $mailObj->getGroup("Inbox");
		
		$this->emailAccount->type = "pop3";
		$aid = $this->emailAccount->save();
        $this->assertTrue($aid > 0);

		$backend = $this->emailAccount->getBackend();
		$this->assertTrue($backend != null);

		// Put a message in the pop3 store
		$numBeforeDeliver = $backend->getNumMessages();
		$email = new Email($this->host, null, null, $this->port);
		$email->ignoreSupression = true;
		$email->send(array("administrator@test.netricos.com"), array("From"=>"administrator@aereus.com", "Subject"=>"testSyncMailbox_Pop3"), "Test");
		$this->waitUntilMessageDelivered($numBeforeDeliver, $backend); // Give the pop3 email class time to refresh the email list
		$numOnServer = $backend->getNumMessages();

		// Sync and make sure the message is added to the local ANT store in the inbox
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$this->assertTrue(count($syncMessages)>0);
		$openMid = $syncMessages[count($syncMessages)-1];        
        $this->assertTrue($openMid > 0); // Email Object Id

		// Sync again and make sure already synchronized messages were not saved again
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$this->assertEquals(count($syncMessages), 0);
        
		// Delete the message from the local ANT store
		$msgObj = CAntObject::factory($this->ant->dbh, "email_message", $openMid, $this->user);
		$msgObj->debug = true; // force immediate processing of backend deletion
		$ret = $msgObj->remove();
		//echo "MID: $openMid\n";
		$this->assertTrue($ret); // make sure message exists and was removed
		$syncObj->debug = true;
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
        $backend->commit(); // make sure deleted messages are expunged

		// 5. Check and make sure the message is missing from the POP3 store
		$currentNumMessages = $backend->getNumMessages();
		$this->assertEquals($numOnServer, $currentNumMessages + 1);

		// Now delete a message on the server directly and sync and make sure it is NOT deleted locally
		// because pop3 is not a two-way sync
		$list = $backend->getMessageList();
		$ret = $backend->deleteMessage($list[count($list)-1]['uid'], "Inbox");
		$numAfterDelete = $backend->getNumMessages();
		$this->assertEquals($numAfterDelete + 1, $currentNumMessages); // Should be one less after delete
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$this->assertEquals(count($syncMessages), 0); // should ignore

		// Final Cleanup
		$msgObj->removeHard();
	}
     */

	/**
	 * Test IMAP sync
	 *
	 * @group testSyncMailbox_Imap
	 *
	public function testSyncMailbox_Imap()
	{
		// Instantiate 
		$syncObj = new AntMail_Sync($this->ant->dbh, $this->user);
        $mailObj = CAntObject::factory($this->ant->dbh, "email_message", null, $this->user);
		$mailbox = $mailObj->getGroup("Inbox");
		
		$this->emailAccount->type = "imap";
		$aid = $this->emailAccount->save();
        $this->assertTrue($aid > 0);

		$backend = $this->emailAccount->getBackend();
		$this->assertTrue($backend != null);

		// Put two messages on the server
		$email = new Email($this->host, null, null, $this->port);
		$email->ignoreSupression = true;
		$numBeforeDeliver = $backend->getNumMessages();
		$email->send(array("administrator@test.netricos.com"), array("From"=>"administrator@aereus.com", "Subject"=>"testSyncMailbox_Imap"), "Test");
		$this->waitUntilMessageDelivered($numBeforeDeliver, $backend); // Give the imap email class time to refresh the email list
		$numBeforeDeliver = $backend->getNumMessages();
		$email->send(array("administrator@test.netricos.com"), array("From"=>"administrator@aereus.com", "Subject"=>"testSyncMailbox_Imap"), "Test");
		$this->waitUntilMessageDelivered($numBeforeDeliver, $backend); // Give the imap email class time to refresh the email list
		$numOnServer = $backend->getNumMessages();

		// Sync and make sure the message is added to the local ANT store in the inbox
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$this->assertTrue(count($syncMessages)>0);
		$openMid = $syncMessages[count($syncMessages)-1];        
        $this->assertTrue($openMid > 0); // Email Object Id

		// Sync again and make sure already synchronized messages were not saved again
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$this->assertEquals(count($syncMessages), 0);
        
		// Delete the message from the local ANT store
		$msgObj = CAntObject::factory($this->ant->dbh, "email_message", $openMid, $this->user);
		$msgObj->debug = true; // force immediate processing of backend deletion
		$ret = $msgObj->remove();
		//echo "MID: $openMid\n";
		$this->assertTrue($ret); // make sure message exists and was removed
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);

		// Check and make sure the message was deleted on the server
		$currentNumMessages = $backend->getNumMessages();
		$this->assertEquals($numOnServer, $currentNumMessages + 1);

		// Now delete a message on the server directly and sync and make sure it is deleted locally
		$list = $backend->getMessageList();
		$ret = $backend->deleteMessage($list[count($list)-1]['uid'], "Inbox");
		$numAfterDelete = $backend->getNumMessages();
		$this->assertEquals($numAfterDelete + 1, $currentNumMessages); // Should be one less after delete
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$this->assertEquals(count($syncMessages), 1);

		// Final Cleanup
		$msgObj->removeHard();
	}
     * */

	/**
	 * Test moving a message with imap sync
	 *
	 * @group testSyncMailbox_ImapMove
	 *
	public function testSyncMailbox_ImapMove()
	{
		// Instantiate 
		$syncObj = new AntMail_Sync($this->ant->dbh, $this->user);
        $mailObj = CAntObject::factory($this->ant->dbh, "email_message", null, $this->user);
		$mailbox = $mailObj->getGroup("Inbox");
		$mailbox2 = $mailObj->addGroupingEntry("mailbox_id", "Test");
		
		$this->emailAccount->type = "imap";
		$aid = $this->emailAccount->save();
        $this->assertTrue($aid > 0);

		$backend = $this->emailAccount->getBackend();
		$this->assertTrue($backend != null);

		// Put a message in the imap store
		$numBeforeDeliver = $backend->getNumMessages();
		$email = new Email($this->host, null, null, $this->port);
		$email->ignoreSupression = true;
		$email->send(array("administrator@test.netricos.com"), array("From"=>"administrator@aereus.com", "Subject"=>"testSyncMailbox_Pop3"), "Test");
		$this->waitUntilMessageDelivered($numBeforeDeliver, $backend); // Give the imap email class time to refresh the email list
		$numOnServer = $backend->getNumMessages();

		// Sync and make sure the message is added to the local ANT store in the inbox
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$this->assertTrue(count($syncMessages)>0);
		$openMid = $syncMessages[count($syncMessages)-1];        
        $this->assertTrue($openMid > 0); // Email Object Id

		// Move the message to another group
		$msgObj = CAntObject::factory($this->ant->dbh, "email_message", $openMid, $this->user);
		$msgObj->move($mailbox2['id']);
		$thread = CAntObject::factory($this->ant->dbh, "email_thread", $msgObj->getValue("thread"), $this->user);

		// Check and make sure the message was deleted on the server mailbox
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$currentNumMessages = $backend->getNumMessages();
		$this->assertEquals($numOnServer, $currentNumMessages + 1);

		// Make sure it was not deleted locally
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$currentNumMessages = $backend->getNumMessages();
		$msgObj = CAntObject::factory($this->ant->dbh, "email_message", $openMid, $this->user);
		$this->assertNotEquals('t', $msgObj->getValue("f_deleted"));

		// Final Cleanup
		$msgObj->deleteGroupingEntry("mailbox_id", $mailbox2['id']);
		$msgObj->removeHard();
	}
     * */

	/**
	 * Make sure that deleting the thread results in deleting the message in imap
	 *
	 * @group testSyncMailbox_ImapMove
	 *
	public function testSyncMailbox_MarkSeen()
	{
		// Instantiate 
		$syncObj = new AntMail_Sync($this->ant->dbh, $this->user);
        $mailObj = CAntObject::factory($this->ant->dbh, "email_message", null, $this->user);
		$mailObj->setValue("flag_seen", 'f');
		$mailbox = $mailObj->getGroup("Inbox");
		$mailbox2 = $mailObj->addGroupingEntry("mailbox_id", "Test");
		
		$this->emailAccount->type = "imap";
		$aid = $this->emailAccount->save();
        $this->assertTrue($aid > 0);

		$backend = $this->emailAccount->getBackend();
		$this->assertTrue($backend != null);

		// Put a message in the imap store
		$numBeforeDeliver = $backend->getNumMessages();
		$email = new Email($this->host, null, null, $this->port);
		$email->ignoreSupression = true;
		$email->send(array("administrator@test.netricos.com"), array("From"=>"administrator@aereus.com", "Subject"=>"testSyncMailbox_Pop3"), "Test");
		$this->waitUntilMessageDelivered($numBeforeDeliver, $backend); // Give the imap email class time to refresh the email list
		$numOnServer = $backend->getNumMessages();

		// Sync and make sure the message is added to the local ANT store in the inbox
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$this->assertTrue(count($syncMessages)>0);
		$openMid = $syncMessages[count($syncMessages)-1];        
        $this->assertTrue($openMid > 0); // Email Object Id

		// Set seen flag to true
        $mailObj = CAntObject::factory($this->ant->dbh, "email_message", $openMid, $this->user);
		$mailObj->setValue("flag_seen", 't');
		$mailObj->save();
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);

		// Get messages from server and make sure the message is read
		$messages = $backend->getMessageList();
		$match = false;
		foreach ($messages as $msg)
		{
			if ($msg['uid'] == $mailObj->getValue("message_uid"))
			{
				if ($msg['seen'] == '1')
					$match = true;
			}
		}
		$this->assertTrue($match);


		// Final Cleanup
		$mailObj->removeHard();
	}*/

	/**
	 * Test scenario where a message is marked as seen after it is marked as deleted
	 *
	public function testMarkSeenAfterDeleted()
	{
		// Instantiate 
		$syncObj = new AntMail_Sync($this->ant->dbh, $this->user);
        $mailObj = CAntObject::factory($this->ant->dbh, "email_message", null, $this->user);
		$mailObj->setValue("flag_seen", 'f');
		$mailbox = $mailObj->getGroup("Inbox");
		$mailbox2 = $mailObj->addGroupingEntry("mailbox_id", "Test");
		
		$this->emailAccount->type = "imap";
		$aid = $this->emailAccount->save();
        $this->assertTrue($aid > 0);

		$backend = $this->emailAccount->getBackend();
		$this->assertTrue($backend != null);

		// Put a message in the imap store
		$numBeforeDeliver = $backend->getNumMessages();
		$email = new Email($this->host, null, null, $this->port);
		$email->ignoreSupression = true;
		$email->send(array("administrator@test.netricos.com"), array("From"=>"administrator@aereus.com", "Subject"=>"testSyncMailbox_Pop3"), "Test");
		$this->waitUntilMessageDelivered($numBeforeDeliver, $backend); // Give the imap email class time to refresh the email list
		$numOnServer = $backend->getNumMessages();

		// Sync and make sure the message is added to the local ANT store in the inbox
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$this->assertTrue(count($syncMessages)>0);
		$openMid = $syncMessages[count($syncMessages)-1];        
        $this->assertTrue($openMid > 0); // Email Object Id

		// Set seen flag to true after deleting it
        $mailObj = CAntObject::factory($this->ant->dbh, "email_message", $openMid, $this->user);
		$mailObj->remove(); // create 'delete' sync stat action
		// Now mark it as seen and save which will create a 'change' stat action
		$mailObj->setValue("flag_seen", 't');
		$mailObj->save();
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);

		// Get messages from server and make sure the message was deleted
		$messages = $backend->getMessageList();
		$match = false;
		foreach ($messages as $msg)
		{
			if ($msg['uid'] == $mailObj->getValue("message_uid"))
				$match = true;
		}
		$this->assertFalse($match);

		// Final Cleanup
		$mailObj->removeHard();
	}*/

	/**
	 * Make sure that deleting the thread results in deleting the message in imap
	 *
	 * @group testSyncMailbox_ImapMove
	 *
	public function testSyncMailbox_ImapDeleteThread()
	{
		// Instantiate 
		$syncObj = new AntMail_Sync($this->ant->dbh, $this->user);
        $mailObj = CAntObject::factory($this->ant->dbh, "email_message", null, $this->user);
		$mailObj->setValue("flag_seen", 'f');
		$mailbox = $mailObj->getGroup("Inbox");
		$mailbox2 = $mailObj->addGroupingEntry("mailbox_id", "Test");
		
		$this->emailAccount->type = "imap";
		$aid = $this->emailAccount->save();
        $this->assertTrue($aid > 0);

		$backend = $this->emailAccount->getBackend();
		$this->assertTrue($backend != null);

		// Put a message in the imap store
		$numBeforeDeliver = $backend->getNumMessages();
		$email = new Email($this->host, null, null, $this->port);
		$email->ignoreSupression = true;
		$email->send(array("administrator@test.netricos.com"), array("From"=>"administrator@aereus.com", "Subject"=>"testSyncMailbox_Pop3"), "Test");
		$this->waitUntilMessageDelivered($numBeforeDeliver, $backend); // Give the imap email class time to refresh the email list
		$numOnServer = $backend->getNumMessages();

		// Sync and make sure the message is added to the local ANT store in the inbox
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$this->assertTrue(count($syncMessages)>0);
		$openMid = $syncMessages[count($syncMessages)-1];        
        $this->assertTrue($openMid > 0); // Email Object Id

		// Set seen flag which should cause a duplicate stat
		$mailObj->setValue("flag_seen", 't');
		$mailObj->save();

		// Delete the message by deleting the thread - another stat
		$msgObj = CAntObject::factory($this->ant->dbh, "email_message", $openMid, $this->user);
		$thread = CAntObject::factory($this->ant->dbh, "email_thread", $msgObj->getValue("thread"), $this->user);
		$thread->remove();

		// Check and make sure the message was deleted on the server mailbox
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$currentNumMessages = $backend->getNumMessages();
		$this->assertEquals($numOnServer, $currentNumMessages + 1);

		// Final Cleanup
		$msgObj->deleteGroupingEntry("mailbox_id", $mailbox2['id']);
		$msgObj->removeHard();
	}*/

	/**
	 * Test sync of non-existent folder on imap
	 *
	 * @group testSyncMailbox_ImapFolderMissing
	 *
	public function testSyncMailbox_ImapFolderMissing()
	{
		// Instantiate 
		$syncObj = new AntMail_Sync($this->ant->dbh, $this->user);
        $mailObj = CAntObject::factory($this->ant->dbh, "email_message", null, $this->user);
		$mailbox = $mailObj->addGroupingEntry("mailbox_id", "testSyncMailbox_ImapFolderMissing");
		
		$this->emailAccount->type = "imap";
		$aid = $this->emailAccount->save();
        $this->assertTrue($aid > 0);

		$backend = $this->emailAccount->getBackend();
		$this->assertTrue($backend != null);

		// Sync but nothing should happen because this folder does not exist on the server
		$syncMessages = $syncObj->syncMailbox($mailbox["id"], $this->emailAccount);
		$this->assertEquals(count($syncMessages), 0);

		// Final Cleanup
		$mailObj->deleteGroupingEntry("mailbox_id", $mailbox['id']);
	}*/

	/**
	 * Test sync mailboxes
	 *
	 * @group testSyncMailboxes_Imap
	 *
	public function testSyncMailboxes_Imap()
	{
		// Instantiate 
		$syncObj = new AntMail_Sync($this->ant->dbh, $this->user);
        $mailObj = CAntObject::factory($this->ant->dbh, "email_message", null, $this->user);
		
		$this->emailAccount->type = "imap";
		$aid = $this->emailAccount->save();
        $this->assertTrue($aid > 0);

		$backend = $this->emailAccount->getBackend();
		$this->assertTrue($backend != null);

		// Cleanup if test mailbox already exists, then create it new
		$grp = $mailObj->getGroupingEntryByPath("mailbox_id", "Inbox/testSyncMailboxes_Imap");
		if ($grp)
			$mailObj->deleteGroupingEntry("mailbox_id", $grp['id']);
		$backend->deleteMailbox("Inbox/testSyncMailboxes_Imap");
		$backend->addMailbox("Inbox/testSyncMailboxes_Imap");
		
		// Sync - the local groupig should be crated
		$syncMessages = $syncObj->syncMailboxes($this->emailAccount);
		$grp = $mailObj->getGroupingEntryByPath("mailbox_id", "Inbox/testSyncMailboxes_Imap");
		$this->assertTrue(is_numeric($grp['id']));

		// Cleanup mailbox
		$mailObj->deleteGroupingEntry("mailbox_id", $grp['id']);
	}*/
	
	/**
	 * Wait until message was delivered
	 */
	public function waitUntilMessageDelivered($origcnt, $backend, $iteration=0)
	{        
		if ($iteration > 20)
			return false;

        $backend->commit(); // Refresh connection stats (make sure nothing is cached)
		$numOnServer = $backend->getNumMessages();
		if ($origcnt == $numOnServer)
		{
			sleep(1);
			return $this->waitUntilMessageDelivered($origcnt, $backend, ++$iteration);
		}

		return true;
	}
}
