<?php
//require_once 'PHPUnit/Autoload.php';

// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/AntMail/Backend.php');

class AntMail_BackendTest extends PHPUnit_Framework_TestCase
{
	var $ant = null;
	var $user = null;
	var $host = null;
	var $username = null;
	var $password = null;
	
	function setUp() 
	{
		$this->ant = new ANT();
		$this->user = new AntUser($this->ant->dbh, -1); // -1 = administrator
		
		$this->host = AntConfig::getInstance()->email['backend_host'];
		$this->username = "administrator@test.netricos.com";
		$this->password = "Password1";
		
		$this->markTestSkipped('Cannot test since imap server is not setup.');
	}
	
	/**
	 * Limit what tests are run
	 */
	/*function getTests()
	{        
		return array("testMailBackendPop");
	}*/
	
	/*
	 * Test Mail Backend Pop3
	 */
	function testMailBackendPop()
	{
		// Instantiate
		$mailObj = new AntMail_Backend("pop3", $this->host, $this->username, $this->password, 110);
		
		$this->assertEquals(get_class($mailObj->mailProtocol), "AntMail_Protocol_Pop3");
	}
	
	/*
	 * Test Mail Backend Imap
	 */
	function INPROGRESS_testMailBackendImap()
	{
		// Instantiate imap ant mail backend
		$mailObj = new AntMail_Backend("imap", $this->host, $this->username, $this->password);
		$this->assertTrue(is_object($mailObj->getMailProtocol()->imapCheck));
		
		// Send email to be used as test data
		$emailResult = $this->runSendTestEmail($username);
		
		// Get Inbox email lists 
		$result = $mailObj->getMessageList(); // Default Mailbox is Inbox
		
		if($emailResult) // If test email is sent succesful
			$messageIndex = 0;
		else // Lets use the first email as test data if test sent email fails
			$messageIndex = sizeof($result)-1;
			
		$uid = $result[$messageIndex]['uid'];
		$msgno = $result[$messageIndex]['msgno'];
		
		$this->assertTrue(is_array($result));
		$this->assertTrue(is_array($result[$messageIndex]));
		$this->assertTrue(!empty($result[$messageIndex]['date']));
		$this->assertTrue(strtotime($result[$messageIndex]['date']) > 0);
		$this->assertTrue($uid > 0);
		$this->assertTrue($msgno > 0);
		unset($result);
		
		// Mark as read the first email
		$result = $mailObj->markMessageRead(null, $msgno);
		$this->assertEquals($result, 1);
		unset($result);
		
		// Mark as flagged the first email
		$result = $mailObj->markMessageFlagged(null, $msgno);
		$this->assertTrue(in_array($msgno, $result));
		unset($result);
		
		// Delete only if the test email is sent
		if($emailResult)
		{
			// Test Delete Email
			$mailObj->deleteMessage($msgno);
			// TODO: Create a test here
		}
		
		// Test Add Mailbox
		// $result = $mailObj->addMailbox("[Gmail]/Drafts", "Gmail Drafts"); TODO: Retest this once function is completed            
		
		// Test Get mailboxes
		 $result = $mailObj->getMailboxes();
		 $this->assertTrue(is_array($result));
		 $this->assertFalse(empty($result[0]['name']));
		 unset($result);            
		
		// Test Delete Mailbox
		// $result = $mailObj->deleteMailbox($mailboxId); TODO: Retest this once function is completed
	}
	
	/**
	 * Helper routine to send test email
	 *
	 * @param AntApi_ObjectStore_* $store
	 * @param AntApi_Object $obj The object we are working with
	 */
	private function runSendTestEmail($email)
	{
		// Send email to be used as test data
		$headers = 'From: Marl Tumulak <marl.tumulak@aereus.com>' . "\r\n";
		$emailResult = @mail($email, 'Unit Test Email', "Unit Test Message Body");
		
		return $emailResult;
	}
}
