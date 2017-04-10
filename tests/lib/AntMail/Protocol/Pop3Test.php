<?php
//require_once 'PHPUnit/Autoload.php';

// ANT Includes
require_once(dirname(__FILE__).'/../../../../lib/AntConfig.php');
require_once('lib/AntMail/Protocol/Pop3.php');

class AntMail_Protocol_Pop3Test extends PHPUnit_Framework_TestCase
{
	var $host = null;
	var $username = null;
	var $password = null;
	
	function setUp() 
	{
		$this->host = AntConfig::getInstance()->email['backend_host'];
		$this->username = "administrator@test.netricos.com";
		$this->password = "Password1";
		$this->port = 465;
		
		$this->markTestSkipped('Cannot test since imap server is not setup.');
	}
	
	/**
	 * Authentication test
	 */
	public function testAuthenticate()
	{
		$popObj = new AntMail_Protocol_Pop3($this->host, $this->username, $this->password);
		$this->assertEquals($popObj->authStatus, true);
	}

	/**
	 * Test get message list
	 */
	public function testGetMessageList()
	{ 
		// Put a message on the server for testing -- this will be deleted in the last unit test
        $email = new Email($this->host, null, null, $this->port);
		$email->ignoreSupression = true;
		$email->send(array("administrator@test.netricos.com"), array("From"=>"administrator@aereus.com", "Subject"=>"testGetMessageList"), "Test");
		sleep(1); // Give the pop3 email class time to refresh the email list

		$popObj = new AntMail_Protocol_Pop3($this->host, $this->username, $this->password);
		$messages = $popObj->getMessageList();
		$this->assertNotEquals($messages, false); // false on failure
		$this->assertTrue(count($messages)>0);
		$this->assertNotEquals($messages[0]['uid'], null);
		$this->assertNotEquals($messages[0]['msgno'], null);
	}

	/**
	 * Test get full message
	 */
	public function testGetFullMessage()
	{
		$popObj = new AntMail_Protocol_Pop3($this->host, $this->username, $this->password);
		$messages = $popObj->getMessageList();
		$this->assertNotEquals($messages, false); // false on failure
		$this->assertTrue(count($messages)>0);

		$msg = $popObj->getFullMessage($messages[0]['msgno']);
		$this->assertNotEquals($msg, false); // false on failure
		$this->assertTrue(sizeof($msg)>0);
	}

	/**
	 * Test delete message
	 */
	public function testDeleteMessage()
	{
		/*
		// Put a message on the server for testing -- this will be deleted in the last unit test
        $email = new Email($this->host);
		$email->ignoreSupression = true;
		$email->send(array("administrator@test.netricos.com"), array("From"=>"administrator@aereus.com", "Subject"=>"testDeleteMessage"), "Test");
		sleep(3); // Give the pop3 email class time to refresh the email list
		 */

		$popObj = new AntMail_Protocol_Pop3($this->host, $this->username, $this->password);
		$messages = $popObj->getMessageList();
		$this->assertNotEquals($messages, false); // false on failure
		$this->assertTrue(count($messages)>0);

		$popObj->deleteMessage($messages[count($messages)-1]['uid']);
		$messagesNow = $popObj->getMessageList();
		$this->assertNotEquals(count($messages), count($messagesNow));
	}
}
