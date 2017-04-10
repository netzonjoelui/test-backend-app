<?php
//require_once 'PHPUnit/Autoload.php';

// ANT Includes
require_once(dirname(__FILE__).'/../../../../lib/AntConfig.php');
require_once('lib/AntMail/Protocol/Test.php');

class AntMail_Protocol_TestTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test get message list
     */
    public function testGetMessageList()
    {
        $popObj = new AntMail_Protocol_Test("localhost", "nousername", "nopass");
        $popObj->setup();
        $messages = $popObj->getMessageList();
        $this->assertNotEquals($messages, null); // false on failure
        $this->assertTrue(count($messages)>0);
        $this->assertNotEquals($messages[0]['uid'], null);
    }

    /**
     * Test get full message
     */
    public function testGetFullMessage()
    {
        $popObj = new AntMail_Protocol_Test("localhost", "nousername", "nopass");
        $popObj->setup();
        $messages = $popObj->getMessageList();
        $this->assertNotEquals($messages, null); // false on failure
        $this->assertTrue(count($messages)>0);

        $msg = $popObj->getFullMessage($messages[0]['msgno']);
        $this->assertTrue(sizeof($msg)>0);
    }

    /**
     * Test delete message
     */
    public function testDeleteMessage()
    {
        $popObj = new AntMail_Protocol_Test("localhost", "nousername", "nopass");
        $popObj->setup();
        $messages = $popObj->getMessageList();
        $numBefore = count($messages);
        $this->assertTrue($numBefore>0);

        $popObj->deleteMessage($messages[count($messages)-1]['uid']);
        $messagesNow = $popObj->getMessageList();
        $this->assertNotEquals($numBefore, count($messagesNow));
    }
}
