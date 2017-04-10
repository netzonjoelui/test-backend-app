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

class CAntObject_ProjectTest extends PHPUnit_Framework_TestCase
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
    public function testCloneObjectReferences()
    {
		// Create orginal object
        $proj1 = CAntObject::factory($this->dbh, "project", null, $this->user);
		$proj1->setValue("name", "test obj reference");
		$proj1->setValue("date_deadline", "1/1/2013");
		$pid_1 = $proj1->save();

		// Add task to project 1
        $task = CAntObject::factory($this->dbh, "task", null, $this->user);
		$task->setValue("name", "test obj reference");
		$task->setValue("deadline", "1/7/2013"); // 1 week later
		$task->setValue("project", $pid_1);
		$tid = $task->save();

		// Create a new project and clone the refernces
        $proj2 = CAntObject::factory($this->dbh, "project", null, $this->user);
		$proj2->setValue("name", "test obj reference 2");
		$proj2->setValue("date_deadline", "2/1/2013");
		$pid_2 = $proj2->save();

		// Clone the task from the first
		$proj2->cloneObjectReferences($pid_1);

		// Get the new task
		$list = new CAntObjectList($this->dbh, "task", $this->user);
		$list->addCondition("and", "project", "is_equal", $pid_2);
		$list->getObjects();
		$newTask = $list->getObject(0);
		$this->assertEquals(date("m/d/Y", strtotime($newTask->getValue("deadline"))), "02/07/2013");
        
        // Cleanup
		$task->removeHard();
		$newTask->removeHard();
        $proj2->removeHard();
        $proj1->removeHard();
    }
}
