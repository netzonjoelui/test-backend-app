<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/Email.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/Controller.php');
require_once(dirname(__FILE__).'/../../controllers/NotesController.php');


class NotesControllerTest extends PHPUnit_Framework_TestCase
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

    function getTests()
    {        
        return array("testGroupSetColor");        
    }    

    /**
    * Test ANT Notes - groupAdd($params)
    */
    function testGroupAdd()
    {
        // instantiate notes controller
        $notesController = new NotesController($this->ant, $this->user);
        $notesController->debug = true;
        
        $params['name'] = "UnitTest GroupName";
        $params['color'] = "eeeeee";        
        $gid = $notesController->groupAdd($params);
        $this->assertTrue($gid > 0);
        
        // clear data
        $params['gid'] = $gid;
        $ret = $notesController->groupDelete($params);
        $this->assertTrue($ret > 0);
        $this->assertEquals($ret, $gid);
    }
    
    /**
    * Test ANT Notes - groupDelete($params)
    */
    function testGroupDelete()
    {
        // instantiate notes controller
        $notesController = new NotesController($this->ant, $this->user);
        $notesController->debug = true;
        
        $params['name'] = "UnitTest GroupName";
        $params['color'] = "eeeeee";        
        
        // create group data
        $gid = $notesController->groupAdd($params);
        $this->assertTrue($gid > 0);
        
        // test group delete
        $params['gid'] = $gid;
        $ret = $notesController->groupDelete($params);
        $this->assertTrue($ret > 0);
        $this->assertEquals($ret, $gid);
    }
    
    /**
    * Test ANT Notes - groupSetColor($params)
    */
    function testGroupSetColor()
    {
        // instantiate notes controller
        $notesController = new NotesController($this->ant, $this->user);
        $notesController->debug = true;
        
        $params['name'] = "UnitTest GroupName";
        $params['color'] = "eeeeee";        
        
        // create group data
        $gid = $notesController->groupAdd($params);
        $this->assertTrue($gid > 0);
        
        $params['gid'] = $gid;
        
        // test group set color
        $params['color'] = "eeeeee";
        $ret = $notesController->groupSetColor($params);
        $this->assertTrue(count($ret) > 0);
        $this->assertEquals($ret, $params['color']);
        
        // clear data        
        $ret = $notesController->groupDelete($params);
        $this->assertTrue($ret > 0);
        $this->assertEquals($ret, $gid);
    }
}
?>
