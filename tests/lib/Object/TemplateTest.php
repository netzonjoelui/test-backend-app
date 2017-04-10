<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Template.php');

class AntObject_TemplateTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;
	var $ant = null;

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
		
		$this->markTestSkipped('Depricated.');
	}

	/**
	 * Saving & loading
	 */
	public function TODO_testSave()
	{
		// TODO: test save and load of variable types
		// TODO: we need to handle fkey_multi and object_multi fields
	}

	/**
	 * Generate a basic object
	 */
	public function testCreateObject()
	{
		$temp = new AntObject_Template($this->dbh, "project", null, $this->user);
		$temp->setValue("name", "My Template Project");

		// Create a new object from the template
		$newProj = $temp->createObject();
		$this->assertEquals("My Template Project", $newProj->getValue("name"));

		// Use the template to apply to an existing object
		$existProj = CAntObject::factory($this->dbh, "project", null, $this->user);
		$existProj->setValue("name", "My Alternate Title");
		$temp->createObject($existProj);
		$this->assertEquals("My Alternate Title", $existProj->getValue("name"));

		// Cleanup
		$newProj->removeHard();
		$existProj->removeHard();
	}

	/**
	 * Generate object with object references
	 */
	public function testCreateObjectRefereces()
	{
		$temp = new AntObject_Template($this->dbh, "project", null, $this->user);
		$temp->setValue("name", "My Template Project");
		$temp->setValue("date_deadline", "1/1/2013");

		// Add a task object reference template that is due 1 day after the project starts
		$tempTask = new AntObject_Template($this->dbh, "task", null, $this->user);
		$tempTask->setValue("name", "My First Template Task");
		$tempTask->setValue("deadline", "=1:days:after:project.date_deadline");
		$temp->addObjectReferenced($tempTask, "project");

		// Create a new object from the template
		$newProj = $temp->createObject();
		$this->assertEquals("My Template Project", $newProj->getValue("name"));

		// Make sure a task was created for this project
		$olist = new CAntObjectList($this->dbh, "task", $this->user);
		$olist->addCondition("and", "project", "is_equal", $newProj->id);
		$olist->getObjects();
		$this->assertEquals(1, $olist->getNumObjects());

		// Now make sure the task has the correct deadline based on the start date of the project
		$task = $olist->getObject(0);
		$this->assertEquals("01/02/2013", date("m/d/Y", strtotime($task->getValue("deadline"))));

		// Cleanup
		$newProj->removeHard();
		$task->removeHard();
	}

	/**
	 * Test testProcessValueTime
	 */
	public function testProcessValueTime()
	{
		$task = CAntObject::factory($this->dbh, "task", null, $this->user);
		$task->setValue("name", "testProcessValueTime");
		$task->setValue("start_date", "1/1/2013");

		$tempTask = new AntObject_Template($this->dbh, "task", null, $this->user);

		// today
		$this->assertEquals($tempTask->processValueTime("=0:days:after:", $task, true), date("m/d/Y"));

		// +1 day
		$this->assertEquals($tempTask->processValueTime("=1:days:after:start_date", $task, true), "01/02/2013");

		// -1 day
		$this->assertEquals($tempTask->processValueTime("=1:days:before:start_date", $task, true), "12/31/2012");

		// Finally, dereference a project value to see if the date is correct
		$proj = CAntObject::factory($this->dbh, "project", null, $this->user);
		$proj->setValue("name", "testProcessValueTime");
		$proj->setValue("date_deadline", "12/31/2013");
		$pid = $proj->save(false);
		$task->setValue("project", $pid);
		$task->save(false);

		// +1 day after project deadline
		$this->assertEquals($tempTask->processValueTime("=1:days:after:project.date_deadline", $task, true), "01/01/2014");

		// Cleanup
		$proj->removeHard();
		$task->removeHard();
	}
}
