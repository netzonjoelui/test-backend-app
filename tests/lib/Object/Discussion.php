<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Controller.php');
require_once(dirname(__FILE__).'/../../../controllers/EmailController.php');

class CAntObject_DiscussionTest extends PHPUnit_Framework_TestCase
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
    * Test discussion Notify
    */
    public function testDiscussionNotify()
    {
        $obj = CAntObject::factory($this->dbh, "discussion", null, $this->user);
        $obj->testMode = true;
        $obj->setValue("notify", "user:" . $this->user->id);
        $obj->setValue("name", "Unit Test Discussion Name");
        $obj->setValue("message", "Unit Test Discussion");
        $discussionId = $obj->save();
        $this->assertTrue($discussionId > 0);
        $this->assertEquals($obj->getValue("name"), "Unit Test Discussion Name");
        $this->assertEquals($obj->getValue("message"), "Unit Test Discussion");
        
        // Cleanup
        $obj->removeHard();
    }
}
