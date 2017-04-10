<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/CAntObjectList.php');
require_once(dirname(__FILE__).'/../../lib/WorkFlow.php');
require_once(dirname(__FILE__).'/../../email/email_functions.awp');
require_once(dirname(__FILE__).'/../../community/feed_functions.awp');
require_once(dirname(__FILE__).'/../../lib/Controller.php');
require_once(dirname(__FILE__).'/../../controllers/ContentController.php');


class ContentControllerTest extends PHPUnit_Framework_TestCase
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
        return array("testFeedAddField");
    }*/

    /**
    * Test ANT Content - feedAddField($params)
    */
    function testFeedAddField()
    {
		$params = array();
        // instantiate content controller
        $contentController = new ContentController($this->ant, $this->user);
        $contentController->debug = true;
        
        // create xml feed
        $obj = new CAntObject($this->dbh, "content_feed", null, $this->user);
        $obj->setValue("title", "UnitTest FeedTitle");
        $fid = $obj->save();
        $this->assertTrue($fid > 0);
        
        $params['name'] = "UnitTest_FeedName";
        $params['type'] = "int";
        $params['fid'] = $fid;
        
        $result = $contentController->feedAddField($params);
        $this->assertEquals($result, 1);
        
        // test the field thoroughly
        $result = $contentController->feedGetFields($params);
        $this->assertTrue(is_array($result));
        $this->assertEquals($result[0]['type'], $params['type']);
        $this->assertEquals($result[0]['title'], $params['name']);
        $this->assertTrue($result[0]['id'] > 0);
        
        // clean data
        unset($params);
        $params["dfield"] = $result[0]['name'];
        $contentController->feedDeleteField($params);
        $obj->removeHard();
        
        // Manually delete the app_object_type_fields for feedname_feed
        $query = "delete from app_object_type_fields where name like 'unittest_feedname_feed_id%'";
        $this->dbh->Query($query);
    }
    
    /**
    * Test ANT Content - feedGetFields($params)
    */
    function testFeedGetFields()
    {
        // instantiate content controller
        $contentController = new ContentController($this->ant, $this->user);
        $contentController->debug = true;
        
        // create xml feed
        $obj = new CAntObject($this->dbh, "content_feed", null, $this->user);
        $obj->setValue("title", "UnitTest FeedTitle");
        $fid = $obj->save();
        
        $params['name'] = "UnitTest_FeedName";
        $params['type'] = "int";
        $params['fid'] = $fid;
        
        // create feed field
        $result = $contentController->feedAddField($params);
        $this->assertEquals($result, 1);
        
        // test feed get fields
        $result = $contentController->feedGetFields($params);
        $this->assertTrue(is_array($result));
        $this->assertEquals($result[0]['type'], $params['type']);
        $this->assertEquals($result[0]['title'], $params['name']);
        $this->assertTrue($result[0]['id'] > 0);
        
        // clean data
        unset($params);
        $params["dfield"] = $result[0]['name'];
        $contentController->feedDeleteField($params);
        $obj->removeHard();        
    }
    
    /**
    * Test ANT Content - feedPostPublish($params)
    */
    function testFeedPostPublish()
    {
        // instantiate content controller
        $contentController = new ContentController($this->ant, $this->user);
        $contentController->debug = true;
        
        // create xml feed
        $obj = new CAntObject($this->dbh, "content_feed", null, $this->user);
        $obj->setValue("title", "UnitTest FeedTitle");
        $fid = $obj->save();
        
        // test feed post publish
        $params['fid'] = $fid;
        $result = $contentController->feedPostPublish($params);
        $this->assertEquals($result, 1);
        
        // clean data        
        $obj->removeHard();        
    }

    /**
    * Test ANT Content - groupSetColor($params)
    */    
    function testGroupSetColor()
    {
        // instantiate content controller
        $contentController = new ContentController($this->ant, $this->user);
        $contentController->debug = true;
        
        // create group data
        $params['name'] = "UnitTest GroupName";
        $params['color'] = "eeeeee";
        $gid = $contentController->groupAdd($params);
        $this->assertTrue($gid > 0);
        
        // test group set color
        unset($params);
        $params['gid'] = $gid;
        $params['color'] = "cccccc";
        $result = $contentController->groupSetColor($params);
        $this->assertEquals($result, $params['color']);
        
        // clean data        
        $result = $contentController->groupDelete($params);
        $this->assertEquals($result, $gid);
    }
    
    /**
    * Test ANT Content - groupRename($params)
    */
    function testGroupRename()
    {
        // instantiate content controller
        $contentController = new ContentController($this->ant, $this->user);
        $contentController->debug = true;
        
        // create group data
        $params['name'] = "UnitTest GroupName";
        $params['color'] = "eeeeee";
        $gid = $contentController->groupAdd($params);
        $this->assertTrue($gid > 0);
        
        // test group Rename
        unset($params);
        $params['gid'] = $gid;
        $params['name'] = "UnitTest GroupRename";
        $result = $contentController->groupRename($params);
        $this->assertEquals($result, $params['name']);
        
        // clean data        
        $result = $contentController->groupDelete($params);
        $this->assertEquals($result, $gid);
    }
    
    /**
    * Test ANT Content - groupDelete($params)
    */
    function testGroupDelete()
    {
        // instantiate content controller
        $contentController = new ContentController($this->ant, $this->user);
        $contentController->debug = true;
        
        // create group data
        $params['name'] = "UnitTest GroupName";
        $params['color'] = "eeeeee";
        $gid = $contentController->groupAdd($params);
        $this->assertTrue($gid > 0);
        
        // test group delete
        $params['gid'] = $gid;
        $result = $contentController->groupDelete($params);
        $this->assertEquals($result, $gid);
    }

    /**
    * Test ANT Content - groupDelete($params)
    */    
    function testGroupAdd()
    {
        // instantiate content controller
        $contentController = new ContentController($this->ant, $this->user);
        $contentController->debug = true;
                
        $params['name'] = "UnitTest GroupName";
        $params['color'] = "eeeeee";
        $gid = $contentController->groupAdd($params);
        $this->assertTrue($gid > 0);
        
        // clear data
        $params['gid'] = $gid;
        $result = $contentController->groupDelete($params);
        $this->assertEquals($result, $gid);
    }
    
    /**
    * Test ANT Content - feedAddCategory($params)
    */    
    function testFeedAddCategory()
    {
        // instantiate content controller
        $contentController = new ContentController($this->ant, $this->user);
        $contentController->debug = true;
        
        // create xml feed
        $obj = new CAntObject($this->dbh, "content_feed", null, $this->user);
        $obj->setValue("title", "UnitTest FeedTitle");
        $fid = $obj->save();
        
        $params['name'] = "UnitTest FeedCategory";        
        $params['fid'] = $fid;
        $dcatId = $contentController->feedAddCategory($params);
        $this->assertTrue($dcatId > 0);
        
        // clean data
        $params['dcat'] = $dcatId;
        $result = $contentController->feedDeleteCategory($params);
        $this->assertEquals($result, 1);
        $obj->removeHard();
    }
    
    /**
    * Test ANT Content - feedAddCategory($params)
    */    
    function testFeedDeleteCategory()
    {
        // instantiate content controller
        $contentController = new ContentController($this->ant, $this->user);
        $contentController->debug = true;
        
        // create xml feed
        $obj = new CAntObject($this->dbh, "content_feed", null, $this->user);
        $obj->setValue("title", "UnitTest FeedTitle");
        $fid = $obj->save();
        
        $params['name'] = "UnitTest FeedCategory";        
        $params['fid'] = $fid;
        
        // create feed field
        $dcatId = $contentController->feedAddCategory($params);
        $this->assertTrue($dcatId > 0);
        
        // test feed delete category
        $params['dcat'] = $dcatId;
        $result = $contentController->feedDeleteCategory($params);
        $this->assertEquals($result, 1);
        
        // clean data
        $obj->removeHard();
    }
    
    /**
    * Test ANT Content - feedAddCategory($params)
    */    
    function testFeedGetCategories()
    {
        // instantiate content controller
        $contentController = new ContentController($this->ant, $this->user);
        $contentController->debug = true;
        
        // create xml feed
        $obj = new CAntObject($this->dbh, "content_feed", null, $this->user);
        $obj->setValue("title", "UnitTest FeedTitle");
        $fid = $obj->save();
        
        $params['name'] = "UnitTest FeedCategory";        
        $params['fid'] = $fid;
        
        // create feed field
        $dcatId = $contentController->feedAddCategory($params);
        $this->assertTrue($dcatId > 0);
        
        // test feed get categories
        $result = $contentController->feedGetCategories($params);
        $this->assertTrue(is_array($result));
        $this->assertEquals($result[0]['id'], $dcatId);
        $this->assertEquals($result[0]['name'], $params['name']);
        
        // clean data
        $params['dcat'] = $dcatId;
        $result = $contentController->feedDeleteCategory($params);
        $this->assertEquals($result, 1);
        $obj->removeHard();
    }
}
