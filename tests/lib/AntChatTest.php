<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/AntChat.php');

/**
 * test suite for AntChat class
 */
class AntChatTest extends PHPUnit_Framework_TestCase
{
	var $obj = null;
	var $dbh = null;
	var $user = null;
    var $antchat = null;
    var $friendName = null;
    var $teamId = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
        $this->antchat = new AntChat($this->dbh, $this->user);
        
        $this->friendName = "andy.bernard";
        $this->teamId = 1;
	}
	
	function tearDown() 
	{
	}
    
    function getTests()
    {        
        return array("testGetFriendList");        
    }

    /**
     * Test if adding of chat friend is functioning properly
     */
	function testAddFriend() 
	{        
        $args['friendName'] = "simpletest@localhost";
        
        $retVal = $this->antchat->addFriend($args);
        
        $this->assertEquals($retVal['retVal'], 1);
        
        // delete the added friend sample.
        unset($args);
        $args['friendId'] = $retVal['id'];
        
        $retVal = $this->antchat->deleteFriend($args);
        
        $this->assertEquals($retVal['retVal'], 1);
	}
    
    /**
     * Test if adding of chat friend thats currently in the same team
     * It will return -1, if the chat friend already in same team
     */
    function testAddFriendTeam() 
    {           
        $args['friendName'] = $this->friendName;
        $args['teamId'] = $this->teamId;
        
        $retVal = $this->antchat->addFriend($args);
        
        $this->assertEquals($retVal['retVal'], 1);
    }
    
    /**
     * Test if retreiving friend list is functioning properly
     */
    function testGetFriendList()
    {
        /*$retVal = $this->antchat->getFriendList();        
        $this->assertTrue(count($retVal) > 0);*/
    }
    
    /**
     * Test if retreiving current user details is functioning properly
     */
    function testGetUserDetails()
    {
        $retVal = $this->antchat->getUserDetails();
        $this->assertEquals($retVal['retVal'], 1);
    }
    
    /**
     * Test if saving of chat messages is functioning properly
     */
    function testSaveMessage()
    {
        $args['message'] = rawurlencode("simpletest message.");
        $args['friendName'] = $this->friendName;
        
        $retVal = $this->antchat->saveMessage($args);
        
        $this->assertEquals($retVal['message'], $args['message']);
        
        // delete message after test
        unset($args);
        $args['messageId'] = $retVal['messageId'];
        
        $retVal = $this->antchat->removeOldMessage($args);
        
        $this->assertEquals($retVal['retVal'], 1);
    }
    
    /**
     * Test if retreiving of friend chat messages inline chat client is functioning properly
     */
    function testGetMessageInline()
    {
        $args['friendName'] = $this->friendName;
        $args['chatPopup'] = false;
        
        $retVal = $this->antchat->getMessage($args);
        
        if(is_array($retVal))
            $this->assertNotNull($retVal[0]['messageTimestamp']);
        else        
            $this->assertEquals($retVal, 1);
    }
    
    /**
     * Test if retreiving of friend chat messages in chat client popup is functioning properly
     */
    function testGetMessagePopup()
    {
        $args['friendName'] = $this->friendName;
        $args['chatPopup'] = true;
        
        $retVal = $this->antchat->getMessage($args);
        
        if(is_array($retVal))
            $this->assertNotNull($retVal[0]['timestamp']);
        else        
            $this->assertEquals($retVal, 1);
    }

    /**
     * Test if retreiving of new chat messages is functioning properly
     */    
    function testGetNewMessages()
    {
        $retVal = $this->antchat->getNewMessages();
        
		$this->assertTrue(is_array($retVal));
        if(count($retVal))
            $this->assertNotNull($retVal[0]['user_name']);
    }
    
    /**
     * Test if saving of chat session (user is typing) is functioning properly
     */    
    function testSaveChatSessionIsTyping()
    {
        $args['friendName'] = $this->friendName;        
        $args['type'] = "isTyping";
        $args['value'] = "false";
        
        $retVal = $this->antchat->saveChatSession($args);
        
        
        $this->assertEquals($args['value'], $retVal[$args['type']]);
    }
    
    /**
     * Test if saving of chat session (chat client popup) is functioning properly
     */    
    function testSaveChatSessionIsPopup()
    {
        $args['friendName'] = $this->friendName;        
        $args['type'] = "isPopup";
        $args['value'] = "false";
        
        $retVal = $this->antchat->saveChatSession($args);
        
        
        $this->assertEquals($args['value'], $retVal[$args['type']]);
    }
    
    /**
     * Test if saving of chat session (chat friend availablity) is functioning properly
     */    
    function testSaveChatSessionIsOnline()
    {
        $args['friendName'] = $this->friendName;        
        $args['type'] = "isOnline";
        $args['value'] = "false";
        
        $retVal = $this->antchat->saveChatSession($args);
        
        $this->assertEquals($args['value'], $retVal[$args['type']]);
    }
    
    /**
     * Test if retrieving of chat sessions is functioning properly
     */    
    function testGetChatSession()
    {
        $args['friendName'] = $this->friendName;        
        $retVal = $this->antchat->getChatSession($args);
        
        $this->assertEquals($retVal[0]['retVal'], 1);
    }
    
    /**
     * Test if counting of friends online is functioning properly
     */    
    function testCountFriendOnline()
    {        
        $args['teamId'] = $this->teamId;
        $retVal = $this->antchat->countFriendOnline($args);

        $this->assertNotNull($retVal['retVal']);
    }
}
