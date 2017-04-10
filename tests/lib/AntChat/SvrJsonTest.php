<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/RpcSvr.php');
require_once(dirname(__FILE__).'/../../../lib/AntChat/SvrJson.php');

class AntChat_SvrJsonTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;
    var $svr = null;
    var $friendName = null;
    var $teamId = null;

    function setUp() 
    {
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
        
        //$this->svr = new AntChat_SvrJson($this->ant, $this->user);
        $this->svr = new RpcSvr($this->ant, $this->user);
        $this->svr->testMode = true;
        $this->svr->setClass("AntChat_SvrJson");

        
        $this->friendName = "andy.bernard";
        $this->teamId = 1;
    }
    
    function tearDown() 
    {
    }

    /**
     * Test if adding of chat friend is functioning properly
     */
    function testAddFriend() 
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'addFriend';
        $_REQUEST['friendName'] = 'simpletest@localhost';
        
        $retVal = $this->svr->run();        
        $this->assertEquals($retVal['retVal'], 1);
        
        // delete the added friend sample.
        unset($_REQUEST);
        $_REQUEST['function'] = 'deleteFriend';
        $_REQUEST['friendId'] = $retVal['id'];
        
        $retVal = $this->svr->run();                
        $this->assertEquals($retVal['retVal'], 1);
    }
    
    /**
     * Test if adding of chat friend thats currently in the same team
     * It will return -1, if the chat friend already in same team
     */
    function testAddFriendTeam() 
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'addFriend';
        $_REQUEST['friendName'] = $this->friendName;
        $_REQUEST['teamId'] = $this->teamId;
        
        $retVal = $this->svr->run();
        $this->assertEquals($retVal['retVal'], 1);
    }
    
    /**
     * Test if retreiving friend list is functioning properly
     */
    function testGetFriendList()
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'getFriendList';        
                
        $retVal = $this->svr->run();
        $this->assertTrue(count($retVal) > 0);
    }
    
    /**
     * Test if retreiving current user details is functioning properly
     */
    function testGetUserDetails()
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'getUserDetails';        
        
        $retVal = $this->svr->run();
        $this->assertEquals($retVal['retVal'], 1);
    }
    
    /**
     * Test if saving of chat messages is functioning properly
     */
    function testSaveMessage()
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'saveMessage';        
        $_REQUEST['message'] = rawurlencode("simpletest message.");
        $_REQUEST['friendName'] = $this->friendName;
        
        $retVal = $this->svr->run();
        $this->assertEquals($retVal['message'], $_REQUEST['message']);
        
        // delete message after test
        unset($_REQUEST);
        
        $_REQUEST['function'] = 'removeOldMessage';
        $_REQUEST['messageId'] = $retVal['messageId'];
        
        $retVal = $this->svr->run();
        $this->assertEquals($retVal['retVal'], 1);
    }
    
    /**
     * Test if retreiving of friend chat messages inline chat client is functioning properly
    function testGetMessageInline()
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'getMessage';        
        $_REQUEST['chatPopup'] = false;
        $_REQUEST['friendName'] = $this->friendName;

        $retVal = $this->svr->run();
        if($retVal[0]['id'] > 0)
            $this->assertNotNull($retVal[0]['messageTimestamp']);
        else
            $this->assertEquals($retVal['retVal'], 1);
    }
     */
    
    /**
     * Test if retreiving of friend chat messages in chat client popup is functioning properly
    function testGetMessagePopup()
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'getMessage';        
        $_REQUEST['chatPopup'] = true;
        $_REQUEST['friendName'] = $this->friendName;
        
        $retVal = $this->svr->run();
        if($retVal[0]['id'] > 0)
            $this->assertNotNull($retVal[0]['messageTimestamp']);
        else
            $this->assertEquals($retVal['retVal'], 1);
    }
     */
    
    /**
     * Test if retreiving of new chat messages is functioning properly
    function testGetNewMessages()
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'getNewMessages';        
        
        $retVal = $this->svr->run();        
        $this->assertEquals($retVal['retVal'], 1);        
    }
     */    
    
    /**
     * Test if saving of chat session (user is typing) is functioning properly
     */    
    function testSaveChatSessionIsTyping()
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'saveChatSession';
        $_REQUEST['friendName'] = $this->friendName;
        $_REQUEST['type'] = "isTyping";
        $_REQUEST['value'] = "false";
        
        $retVal = $this->svr->run();
        $this->assertEquals($_REQUEST['value'], $retVal['isTyping']);
    }
    
    /**
     * Test if saving of chat session (chat client popup) is functioning properly
     */    
    function testSaveChatSessionIsPopup()
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'saveChatSession';
        $_REQUEST['friendName'] = $this->friendName;
        $_REQUEST['type'] = "isPopup";
        $_REQUEST['value'] = "false";
        
        $retVal = $this->svr->run();
        $this->assertEquals($_REQUEST['value'], $retVal['isPopup']);
    }
    
    /**
     * Test if saving of chat session (chat friend availablity) is functioning properly
     */    
    function testSaveChatSessionIsOnline()
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'saveChatSession';
        $_REQUEST['friendName'] = $this->friendName;
        $_REQUEST['type'] = "isOnline";
        $_REQUEST['value'] = "false";
        
        $retVal = $this->svr->run();
        $this->assertEquals($_REQUEST['value'], $retVal['isOnline']);
    }
    
    /**
     * Test if retrieving of chat sessions is functioning properly
     */    
    function testGetChatSession()
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'getChatSession';
        $_REQUEST['friendName'] = $this->friendName;
        
        $retVal = $this->svr->run();
        $this->assertEquals($retVal[0]['retVal'], 1);
    }
    
    /**
     * Test if counting of friends online is functioning properly
    function testCountFriendOnline()
    {
        global $_REQUEST;

        $_REQUEST['function'] = 'countFriendOnline';
        $_REQUEST['teamId'] = $this->teamId;
        
        $retVal = $this->svr->run();
        $this->assertNotNull($retVal[0]['friend_online']);
    }
     */    
}
