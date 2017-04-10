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

class CAntObject_CommentTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;

    function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
    }
    
    function tearDown() 
    {
    }
    
    
    /*
    function getTests()
    {        
        return array("testReportObject");
    }
     */
    
    /**
     * @deprecated We now use the new entity notifier system (Netric\Entity\Notifier)
     *
     * Test Comment Notify
     *
    function testCommentNotify()
    {
		// First cleanup if user already exists
		$list = new CAntObjectList($this->dbh, "user", $this->user);
		$list->addCondition("and", "name", "is_equal", "unitTestUser123");
		$list->getObjects();
		if ($list->getNumObjects()>0)
		{
			$usr = $list->getObject(0);
			$usr->removeHard();
		}

        // Create Test User
        $userObj = CAntObject::factory($this->dbh, "user", null, $this->user);
        $userObj->setValue("name", "unitTestUser123");
        $userObj->setValue("full_name", "unitTestFullName");
        $userId = $userObj->save();
        $this->assertTrue($userId > 0);
        
        // Associate Email To the User
        $emailController = new EmailController($this->ant, $this->user);
        $emailController->debug = true;
        $params = array("active" => "t", "displayName" => "Unit Test Email", "emailAddress" => "antunittest@gmail.com", "fullName" => "unitTestFullName", "uid" => $userId);
        $result = $emailController->saveDefaultEmail($params);
        $this->assertTrue($result > 0);
        unset($result);
        
        // Create Test Task
        $taskObj = CAntObject::factory($this->dbh, "task", null, $this->user);
        $taskObj->setValue("name", "unitTestTask");
        $taskId = $taskObj->save();
        $this->assertTrue($taskId > 0);
        
        // Add Comments in task
        $commentObj = CAntObject::factory($this->dbh, "comment", null, $this->user);
        $commentObj->testMode = true;
        $commentObj->setMValue("associations", "task:$taskId");
        $commentObj->setMValue("obj_reference", "task:$taskId");
        $commentObj->setMValue("comments[]", "0");
        $commentObj->setValue("notify", "user:$userId");
        $commentObj->setValue("comment", "Unit Test Comment");
        $commentObj->setValue("owner_id", $userId);        
        $commentObj->setValue("obj_reference", "task:$taskId");
        $commentId = $commentObj->save();
        $this->assertTrue($commentId > 0);
        $this->assertEquals($commentObj->testModeBuf["sent"]["by"]['id'], $this->user->id);
        $this->assertEquals($commentObj->testModeBuf["sent"]["from"], $this->user->fullName);
        $this->assertEquals($commentObj->testModeBuf["reference"]["object"]->id, $taskId);
        $this->assertEquals($commentObj->testModeBuf["sendTo"]["eml"], "antunittest@gmail.com");
        $this->assertEquals($commentObj->testModeBuf["status"], "sent");
        
        // Clean Data
        $commentObj->removeHard();
        $taskObj->removeHard();
        $userObj->removeHard();
    }
    */

    /**
     * Make sure that users added to the 'notify' field are copied to followers (newer code)
     *
     * This is largely copied from above and not checked too carefully since we will be
     * deleting this code completely pretty soon once we move all extended CAntObject(s)
     * over to the new Entity system.
     */
    public function testNotifyMovedToFollowers()
    {
        // First cleanup if user already exists
        $list = new CAntObjectList($this->dbh, "user", $this->user);
        $list->addCondition("and", "name", "is_equal", "unitTestUser123");
        $list->getObjects();
        if ($list->getNumObjects()>0)
        {
            $usr = $list->getObject(0);
            $usr->removeHard();
        }

        // Create Test User
        $userObj = CAntObject::factory($this->dbh, "user", null, $this->user);
        $userObj->setValue("name", "unitTestUser123");
        $userObj->setValue("full_name", "unitTestFullName");
        $userId = $userObj->save();
        $this->assertTrue($userId > 0);

        // Associate Email with the User
        $emailController = new EmailController($this->ant, $this->user);
        $emailController->debug = true;
        $params = array("active" => "t", "displayName" => "Unit Test Email", "emailAddress" => "antunittest@gmail.com", "fullName" => "unitTestFullName", "uid" => $userId);
        $result = $emailController->saveDefaultEmail($params);
        $this->assertTrue($result > 0);
        unset($result);

        // Create Test Task
        $taskObj = CAntObject::factory($this->dbh, "task", null, $this->user);
        $taskObj->setValue("name", "unitTestTask");
        $taskId = $taskObj->save();
        $this->assertTrue($taskId > 0);

        // Add Comments in task
        $commentObj = CAntObject::factory($this->dbh, "comment", null, $this->user);
        $commentObj->setMValue("associations", "task:$taskId");
        $commentObj->setMValue("obj_reference", "task:$taskId");
        $commentObj->setValue("notify", "user:$userId");
        $commentObj->setValue("comment", "Unit Test Comment");
        $commentObj->setValue("owner_id", $userId);
        $commentObj->setValue("obj_reference", "task:$taskId");
        $commentId = $commentObj->save();

        // Make sure that followers is set to the test user
        $followers = $commentObj->getValue("followers");
        $this->assertTrue(in_array($userId, $followers));

        // Clean Data
        $commentObj->removeHard();
        $taskObj->removeHard();
        $userObj->removeHard();
    }
}
