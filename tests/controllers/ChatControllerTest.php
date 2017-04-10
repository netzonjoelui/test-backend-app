<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../controllers/ChatController.php');


class ChatControllerTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;
    var $backend = null;

    function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
        
        $this->friendName = "testFriend";
        $this->friendServer = "localhost";
    }

    function tearDown() 
    {
    }

    /*function getTests()
    {        
        return array("testAntChat");        
    }*/
    
    /**
    * Test ANT Chat - testAntChat($params)
    function testAntChat()
    {
        ob_start();
        // instantiate controllers
        $chatController = new ChatController($this->ant, $this->user);
        
        $params['friendName'] = $this->friendName . "@" . $this->friendServer;
        $result = $chatController->addFriend($params);
        $friendId = $result['id'];
        $this->assertTrue($friendId > 0);
        
        $params['filterId'] = $friendId;        
        $result = $chatController->getFriendList($params);
        $friend = $result[0];
        //$this->assertEquals($friend['id'], $friendId);
        $this->assertEquals($friend['friend_name'], $this->friendName);
        $this->assertEquals($friend['friend_server'], $this->friendServer);
        
        $result = $chatController->getUserDetails();
        $this->assertEquals($result['user_id'], $this->user->id);
        
        $params['friendId'] = $friendId;
        $result = $chatController->deleteFriend($params);
        $this->assertEquals($result['retVal'], 1);
        
        ob_get_contents();
        ob_end_clean();
    }
    */
    
    /**
    * Test ANT Chat - testChatMessage($params)
    */
    function testChatMessage()
    {
        ob_start();
        // instantiate controllers
        $chatController = new ChatController($this->ant, $this->user);
        $message = "testMessage";
        $params['message'] = $message;
        $params['friendName'] = $this->friendName;
        $params['friendServer'] = $this->friendServer;
        
        $result = $chatController->saveMessage($params);
        $currentTimestamp = $result['currentTimestamp'];
        $messageId = $result['messageId'];
        $this->assertEquals($result['message'], $message);
        $this->assertTrue($messageId > 0);
        $this->assertTrue($currentTimestamp <= time());
        
        $params['lastMessageTs'] = $currentTimestamp - 30;
        $result = $chatController->getMessage($params);
        $friendMessage = $result[0];
        $this->assertEquals($friendMessage['message'], $message);
        //$this->assertEquals($friendMessage['id'], $messageId);
        $this->assertEquals($friendMessage['messageTimestamp'], $currentTimestamp);
        //$this->assertEquals($friendMessage['user_id'], $this->user->id);
        
        ob_get_contents();
        ob_end_clean();
    }
    
    /**
    * Test ANT Chat - testChatSession($params)
    */
    function testChatSession()
    {
        ob_start();
        // instantiate controllers
        $chatController = new ChatController($this->ant, $this->user);
        $sessionType = "isTyping";
        $sessionValue = "false";
        $params['type'] = $sessionType;
        $params['value'] = $sessionValue;
        $params['friendName'] = $this->friendName;
        $params['friendServer'] = $this->friendServer;
        
        $result = $chatController->saveChatSession($params);        
        $this->assertEquals($result['retVal'], 1);
        $this->assertEquals($result['isTyping'], "false");
        
        ob_get_contents();
        ob_end_clean();
    }
    
    /**
    * Test ANT Chat - countFriendOnline($params)
    */
    function testCountFriendOnline()
    {
        ob_start();
        // instantiate controllers
        $chatController = new ChatController($this->ant, $this->user);
        
        $params = array();
        $result = $chatController->countFriendOnline($params);
        $this->assertEquals($result['retVal'], 1);
        $this->assertTrue($result['onlineCount'] >= 0);
        
        ob_get_contents();
        ob_end_clean();
    }
}
