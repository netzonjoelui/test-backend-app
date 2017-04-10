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
require_once(dirname(__FILE__).'/../../controllers/InfocenterController.php');


class InfocenterControllerTest extends PHPUnit_Framework_TestCase
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
        return array("testGroupSetColor");        
    }*/

    /**
    * Test ANT Infocenter - saveDocument($params)
    */
    function testSaveDocument()
    {
        // Instantiate IC controller
        $icController = new InfocenterController($this->ant, $this->user);
        $icController->debug = true;
        
        $params['body'] = "UnitTest IcBody";
        $params['title'] = "UnitTest IcTitle";
        $params['keywords'] = "UnitTest IcKeywords";
        
        // test insert a document
        $docid = $icController->saveDocument($params);
        $this->assertTrue($docid > 0);
        
        // test update the new document 
        $params['docid'] = $docid;
        $ret = $icController->saveDocument($params);
        $this->assertTrue($ret > 0);
        $this->assertEquals($ret, $docid);
        
        // clear data
        $ret = $icController->deleteDocument($params);
        $this->assertTrue($ret > 0);
        $this->assertEquals($ret, $docid);
    }
    
    /**
    * Test ANT Infocenter - deleteDocument($params)
    */
    function testDeleteDocument()
    {
        // Instantiate IC controller
        $icController = new InfocenterController($this->ant, $this->user);
        $icController->debug = true;
        
        $params['body'] = "UnitTest IcBody";
        $params['title'] = "UnitTest IcTitle";
        $params['keywords'] = "UnitTest IcKeywords";
        
        // Create a document
        $docid = $icController->saveDocument($params);
        $this->assertTrue($docid > 0);
        
        // test delete document
        $params['docid'] = $docid;
        $ret = $icController->deleteDocument($params);
        $this->assertTrue($ret > 0);
        $this->assertEquals($ret, $docid);
    }
    
    /**
    * Test ANT Infocenter - deleteDocument($params)
    */
    function testDocumentGetTitle()
    {
        // Instantiate IC controller
        $icController = new InfocenterController($this->ant, $this->user);
        $icController->debug = true;
        
        $params['body'] = "UnitTest IcBody";
        $params['title'] = "UnitTest IcTitle";
        $params['keywords'] = "UnitTest IcKeywords";
        
        // Create a document
        $docid = $icController->saveDocument($params);
        $this->assertTrue($docid > 0);
        
        $params['docid'] = $docid;
        
        // test get title
        $title = $icController->documentGetTitle($params);
        $this->assertTrue(count($title) > 0);
        $this->assertEquals($title, $params['title']);
        
        // clear data
        
        $ret = $icController->deleteDocument($params);
        $this->assertTrue($ret > 0);
        $this->assertEquals($ret, $docid);
    }
    
    /**
    * Test ANT Infocenter - groupAdd($params)
    */
    function testGroupAdd()
    {
        // Instantiate IC controller
        $icController = new InfocenterController($this->ant, $this->user);
        $icController->debug = true;
        
        $params['name'] = "UnitTest GroupName";
        $params['color'] = "eeeeee";
        
        $gid = $icController->groupAdd($params);
        $this->assertTrue($gid > 0);
        
        // clear data
        $params['gid'] = $gid;
        $ret = $icController->groupDelete($params);
        $this->assertTrue($ret > 0);
        $this->assertEquals($gid, $ret);
    }
    
    /**
    * Test ANT Infocenter - groupDelete($params)
    */
    function testGroupDelete()
    {
        // Instantiate IC controller
        $icController = new InfocenterController($this->ant, $this->user);
        $icController->debug = true;
        
        $params['name'] = "UnitTest GroupName";
        $params['color'] = "eeeeee";
        
        // create group data
        $gid = $icController->groupAdd($params);
        $this->assertTrue($gid > 0);
        
        // test group delete
        $params['gid'] = $gid;
        $ret = $icController->groupDelete($params);
        $this->assertTrue($ret > 0);
        $this->assertEquals($gid, $ret);
    }
    
    /**
    * Test ANT Infocenter - groupRename($params)
    */
    function testGroupRename()
    {
        // Instantiate IC controller
        $icController = new InfocenterController($this->ant, $this->user);
        $icController->debug = true;
        
        $params['name'] = "UnitTest GroupName";
        $params['color'] = "eeeeee";
        
        // create group data
        $gid = $icController->groupAdd($params);
        $this->assertTrue($gid > 0);
        
        $params['gid'] = $gid;
        
        // test group rename
        $params['name'] = "UnitTest GroupRename";
        $name = $icController->groupRename($params);
        $this->assertTrue(count($name) > 0);
        $this->assertEquals($name, $params['name']);
        
        // clear data        
        $ret = $icController->groupDelete($params);
        $this->assertTrue($ret > 0);
        $this->assertEquals($gid, $ret);
    }
    
    /**
    * Test ANT Infocenter - groupSetColor($params)
    */
    function testGroupSetColor()
    {
        // Instantiate IC controller
        $icController = new InfocenterController($this->ant, $this->user);
        $icController->debug = true;
        
        $params['name'] = "UnitTest GroupName";
        $params['color'] = "eeeeee";
        
        // create group data
        $gid = $icController->groupAdd($params);
        $this->assertTrue($gid > 0);
        
        $params['gid'] = $gid;
        
        // test group set color
        $params['color'] = "333333";
        $color = $icController->groupSetColor($params);
        $this->assertTrue(count($color) > 0);
        $this->assertEquals($color, $params['color']);
        
        // clear data        
        $ret = $icController->groupDelete($params);
        $this->assertTrue($ret > 0);
        $this->assertEquals($gid, $ret);
    }
}
