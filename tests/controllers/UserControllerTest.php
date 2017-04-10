<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/Controller.php');
require_once(dirname(__FILE__).'/../../controllers/UserController.php');


class UserConrollerTest extends PHPUnit_Framework_TestCase
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
    }

    function tearDown() 
    {
    }

    /*function getTests()
    {        
        return array("testSaveGroups");
    }*/

    /**
    * Test ANT User - getAuthString()
    */
    function testGetAuthString()
    {
        // instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        $result = $userController->getAuthString();
        $this->assertTrue(count($result) > 0);
    }
    
    /**
    * Test ANT User - groupAdd($params)
    */
    function testGroupAdd()
    {
        // instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        $params['name'] = "UnitTest GroupName";
        $groupId = $userController->groupAdd($params);         
        $this->assertTrue($groupId > 0);
        
        // clear data
        $params['gid'] = $groupId;
        $result = $userController->groupDelete($params);
        $this->assertEquals($result, 1);
    }
    
    /**
    * Test ANT User - groupDelete($params)
    */
    function testGroupDelete()
    {
        // instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        // create group data
        $params['name'] = "UnitTest GroupName";
        $groupId = $userController->groupAdd($params);         
        $this->assertTrue($groupId > 0);
        
        // test group delete
        $params['gid'] = $groupId;
        $result = $userController->groupDelete($params);
        $this->assertEquals($result, 1);
    }
    
    /**
    * Test ANT User - saveUser($params)
    */
    function testSaveUser()
    {
        // Clean Unit Test User First
        $this->dbh->Query("delete from users where name like '%unittestusername%';");
        
        // instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        // test save user
		$params = array();
        $params['userName'] = "UnitTestUserName";
        $params['name'] = "UnitTestUserName";
        $params['password'] = "UnitTest UserPassword";
        $params['active'] = "t";
        $userId = $userController->saveUser($params);
        $this->assertTrue($userId > 0);
        
        // retrieve user using AntObject
        $obj = new CAntObject($this->dbh, "user", $userId, $this->user);
        $this->assertEquals($obj->getValue("name"), strtolower($params['name']));
        $this->assertEquals($obj->getValue("active"), "t");
        
        // clear data
        $obj->removeHard();
    }
    
    /**
    * Test ANT User - saveUserWiz($params)
    */
    function testSaveUserWiz()
    {
        // Clean Unit Test User First
        $this->dbh->Query("delete from users where name like '%unittestusername%';");
        
        // instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        // create user data
        $params['userName'] = "UnitTestUserName";
        $params['name'] = "UnitTestUserName";
        $params['password'] = "UnitTest UserPassword";
        $params['active'] = "t";
        $userId = $userController->saveUser($params);
        $this->assertTrue($userId > 0);
        
        // test save user wizard
        unset($params);
        $params['uid'] = $userId;
        $result = $userController->saveUserWiz($params);
        $this->assertTrue($result > 0);
        $this->assertEquals($result, $userId);
        
        // retrieve user using AntObject
        $obj = new CAntObject($this->dbh, "user", $userId, $this->user);
        
        // clear data
        $obj->removeHard();
    }
    
    /**
    * Test ANT User - userDelete($params)
    */
    function testUserDelete()
    {
        // Clean Unit Test User First
        $this->dbh->Query("delete from users where name like '%unittestusername%';");
        
        // instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        // create user data
        $params['userName'] = "UnitTestUserName";
        $params['name'] = "UnitTestUserName";
        $params['password'] = "UnitTest UserPassword";
        $params['active'] = "t";
        $userId = $userController->saveUser($params);
        $this->assertTrue($userId > 0);
        
        // test user delete
        $params['uid'] = $userId;
        $result = $userController->userDelete($params);
        $this->assertTrue($result > 0);
        $this->assertEquals($result, 1);
                
        // retrieve user using AntObject
        $obj = new CAntObject($this->dbh, "user", $userId, $this->user);
        $this->assertEquals($obj->getValue("name"), strtolower($params['name']));        
        
        // clear data
        $obj->removeHard();
    }
    
    
    /**
    * Test ANT User - saveGroups($params)
    */
    function testSaveGroups()
    {
        // Clean Unit Test User First
        $this->dbh->Query("delete from users where name like '%unittestusername%';");
        
        // instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        // create user data
        $params['userName'] = "UnitTestUserName";
        $params['name'] = "UnitTestUserName";
        $params['password'] = "UnitTest UserPassword";
        $params['active'] = "t";
        $userId = $userController->saveUser($params);
        $this->assertTrue($userId > 0);
        
        // create group 1
        $params['name'] = "UnitTest GroupName1";
        $groupId1 = $userController->groupAdd($params);         
        $this->assertTrue($groupId1 > 0);
        
        // create group 2
        $params['name'] = "UnitTest GroupName1";
        $groupId2 = $userController->groupAdd($params);         
        $this->assertTrue($groupId2 > 0);
        
        // test save Group
        $params['uid'] = $userId;
        $params['groups'] = array($groupId1, $groupId2);
        $result = $userController->saveGroups($params);
        $this->assertTrue(is_array($result));
        $this->assertEquals($groupId1, $result[0]);
        $this->assertEquals($groupId2, $result[1]);
        
        // clear data
        $params['gid'] = $groupId1;
        $result = $userController->groupDelete($params);
        $this->assertEquals($result, 1);
        
        $params['gid'] = $groupId2;
        $result = $userController->groupDelete($params);
        $this->assertEquals($result, 1);
                
        // clear user data
        $obj = new CAntObject($this->dbh, "user", $userId, $this->user);
        $obj->removeHard();
    }
    
    /**
    * Test ANT User - saveGroups($params)
    */
    function testTeamAdd()
    {
        // instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        $params['parent_id'] = 1;
        $params['name'] = "UnitTest TeamName";
        $tid = $userController->teamAdd($params);
        $this->assertTrue($tid > 0);
        
        // clear data
        $params['tid'] = $tid;
        $result = $userController->teamDelete($params);
        $this->assertEquals($result, 1);
    }
    
    /**
    * Test ANT User - teamDelete($params)
    */
    function testTeamDelete()
    {
        // instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        // create team data
        $params['parent_id'] = 1;
        $params['name'] = "UnitTest TeamName";        
        $tid = $userController->teamAdd($params);
        $this->assertTrue($tid > 0);
        
        // test team delete
        $params['tid'] = $tid;
        $result = $userController->teamDelete($params);
        $this->assertEquals($result, 1);
    }
    
    /**
    * Test ANT User - login($params)
    */ 
    function testLogin()
    {
        // Clean Unit Test User First
        $this->dbh->Query("delete from users where name like '%unittestusername%';");
        
        //  instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        // create user data
        $params['userName'] = "UnitTestUserName";
        $params['name'] = "UnitTestUserName";
        $params['password'] = "UnitTest UserPassword";
        $params['active'] = "t";
        $userId = $userController->saveUser($params);
        $this->assertTrue($userId > 0);
        
        // test login
        $result = $userController->login($params);
        $this->assertTrue($result > 0);
        //$this->assertEquals($result, $userId);
        
        // clear user data
        $obj = new CAntObject($this->dbh, "user", $userId, $this->user);
        $obj->removeHard();
    }
    
    /**
    * Test ANT User - getGroups($params)
    */ 
    function testGetGroups()
    {
        //  instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        $result = $userController->getGroups(array());
        $this->assertTrue(is_array($result));
    }
    
    /**
    * Test ANT User - getUser($params)
    */ 
    function testGetUsers()
    {
        //  instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        $params['profile'] = 1;
        $result = $userController->getUsers($params);
        $this->assertTrue(is_array($result));
    }
    
    /**
    * Test ANT User - getThemes()
    */ 
    function testGetThemes()
    {
        //  instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        $result = $userController->getThemes();
        $this->assertTrue(is_array($result));
    }
    
    /**
    * Test ANT User - getTimezones()
    */ 
    function testGetTimezones()
    {
        //  instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        $result = $userController->getTimezones();
        $this->assertTrue(is_array($result));
    }
    
    /**
    * Test ANT User - getCarriers()
    */ 
    function testGetCarriers()
    {
        //  instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        $result = $userController->getCarriers();
        $this->assertTrue(is_array($result));
    }
    
    /**
    * Test ANT User - getTeams()
    */ 
    function testGetTeams()
    {
        //  instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        $result = $userController->getTeams(array());
        $this->assertTrue(is_array($result));
    }

    /**
     * Test ANT User - getUpdateStream()
     */ 
    function testGetUpdateStream()
    {
        //  instantiate controller
        $userController = new UserController($this->ant, $this->user);
        $userController->debug = true;
        
        $result = $userController->getUpdateStream(array("forceReturn"=>true));
        $this->assertTrue(is_array($result));
    }
}
