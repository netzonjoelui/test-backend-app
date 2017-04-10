<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Controller.php');

class CAntObject_ContentFeedPostTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;

	/**
	 * Initialize some common variables
	 */
    public function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
    }
    
    /**
     * Make sure the publish flag gets set if the status = Published
     */
    public function testPublish()
    {
        $obj = CAntObject::factory($this->dbh, "content_feed_post", null, $this->user);

		// Get the id of the "Published" grouping
		$publishedGrp = $obj->getGroupingEntryByName("status_id", "Published");

		// Set values
        $obj->setValue("title", "Test Post");
        $obj->setValue("data", "Test Post Content");
        $obj->setValue("f_publish", "f"); // will be changed
        $obj->setValue("status_id", $publishedGrp['id']);
        $postId = $obj->save();

		// Test value of object to make sure f_publish flag was set to true
		$this->assertEquals($obj->getValue("f_publish"), 't');
        
        // Cleanup
        $obj->removeHard();
    }
}
