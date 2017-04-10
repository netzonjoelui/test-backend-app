<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/Controller.php');
require_once(dirname(__FILE__).'/../../controllers/ContactController.php');


class ContactControllerTest extends PHPUnit_Framework_TestCase
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
        return array("testContactGetName");        
    }*/

    /**
    * Test ANT Contact - contactGetName($params)
    */
    function testContactGetName()
    {
        // instantiate controllers
        $contactController = new ContactController($this->ant, $this->user);
        $contactController->debug = true;
        
        $params['first_name'] = "TestUnit ContactName";
        
        // add contact data
        $obj = new CAntObject($this->dbh, "contact_personal", null, $this->user);
        $obj->setValue("first_name", $params['first_name']);
        $cid = $obj->save();
        $this->assertTrue($cid > 0);
        
        $params['cid'] = $cid;
        $cname = $contactController->contactGetName($params);
        $this->assertEquals($cname, $params['first_name']);

        // clear data
        $obj->removeHard();
    }
    
    /**
    * Test ANT Contact - syncCustomers($params)
    */
    function syncCustomers()
    {
        // TO DO
    }
    
    /**
    * Test ANT Contact - groupSetColor($params)
    */
    function testGroupSetColor()
    {
        // instantiate controllers
        $contactController = new ContactController($this->ant, $this->user);
        $contactController->debug = true;
        
        // add group here
        $params['name'] = "UnitTest PersonalGroup";
        $params['color'] = "eeeeee";
        $gid = $contactController->groupAdd($params);
        
        $params['gid'] = $gid;
        $params['color'] = "eeeeee";
        $ret = $contactController->groupSetColor($params);
        $this->assertTrue(count($ret) > 0);
        
        // clean data
        $ret = $contactController->groupDelete($params);
    }
    
    /**
    * Test ANT Contact - groupAdd($params)
    */
    function testGroupAdd()
    {
        // instantiate controllers
        $contactController = new ContactController($this->ant, $this->user);
        $contactController->debug = true;
                
        // test group add        
        $params['name'] = "UnitTest PersonalGroup";
        $params['color'] = "eeeeee";
        $gid = $contactController->groupAdd($params);
        $this->assertTrue(count($gid ) > 0);
        
        // clean data        
        $params['gid'] = $gid;
        $ret = $contactController->groupDelete($params);
    }
    
    /**
    * Test ANT Contact - groupRename($params)
    */
    function testGroupRename()
    {
        // instantiate controllers
        $contactController = new ContactController($this->ant, $this->user);
        $contactController->debug = true;
                
        $params['name'] = "UnitTest PersonalGroupRename";
        $params['color'] = "eeeeee";        
        // add group data first        
        $gid = $contactController->groupAdd($params);
        $this->assertTrue(count($gid ) > 0);
        
        $params['gid'] = $gid;        
        // test group rename
        $gname = $contactController->groupRename($params);
        $this->assertEquals($gname, $params['name']);
        
        // clean data                
        $ret = $contactController->groupDelete($params);
    }
    
    /**
    * Test ANT Contact - groupDelete($params)
    */
    function testGroupDelete()
    {
        // instantiate controllers
        $contactController = new ContactController($this->ant, $this->user);
        $contactController->debug = true;
                
        // add group data first
        $params['name'] = "UnitTest PersonalGroup";
        $params['color'] = "eeeeee";
        $gid = $contactController->groupAdd($params);
        $this->assertTrue(count($gid ) > 0);
        
        // test group delete
        $params['gid'] = $gid;
        $ret = $contactController->groupDelete($params);
    }
}
