<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../controllers/ObjectListController.php');

class ObjectListControllerTest extends PHPUnit_Framework_TestCase
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
    
	/**
	 * Use the below function to limit the current test
	 */	
    /*function getTests()
    {        
        return array("testDeleteObjects");
    }*/
    
    /**
     * Test ObjectList::query action
     */
    function testQuery()
    {        
		// First create test object
		$obj = new CAntObject($this->ant->dbh, "customer", null, $this->user);
		$obj->setValue("first_name", "Test Customer");
		$cid = $obj->save();

        $params['obj_type'] = "customer";
        
        $objController = new ObjectListController($this->ant, $this->user);
		$objController->debug = true;
        $ret = $objController->query($params);
        $this->assertTrue(is_array($ret));

		// Test output
		$ret = json_decode($objController->debugOutputBuf);
		$this->assertTrue($ret->totalNum > 0);
		$this->assertTrue(count($ret->objects) > 0);

		// Cleanup
		$obj->removeHard();
    }

    /**
     * Test ObjectList::deleteObjects
     */
    /*function testDeleteObjects()
    {
		// Test deleting browseby
		$antfs = new AntFs($this->dbh, $this->user);
		$fldr = $antfs->openFolder("/test/deltest", true);
        // Should create the /test/deltest folder before opening folder.
		//$this->assertNotNull($fldr->id);

		$params['obj_type'] = "file";
		$params['browsebyfield'] = "folder_id";
		$params['objects'] = array("browse:".$fldr->id);
        
        $objController = new ObjectListController($this->ant, $this->user);
		$objController->debug = true;
        $ret = $objController->deleteObjects($params);
        $this->assertEquals("browse:".$fldr->id, $ret[0]);
		unset($fldr);

		$fldr = $antfs->openFolder("/test/deltest");
		$this->assertNull($fldr);
	}*/
}
