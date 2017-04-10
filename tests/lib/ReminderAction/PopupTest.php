<?php
// Test Framework
//require_once 'PHPUnit/Autoload.php';
//require_once(dirname(__FILE__).'/../simpletest/autorun.php');

// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Reminder.php');

class ReminderAction_PopupTest extends PHPUnit_Framework_TestCase 
{
	var $ant = null;
	var $user = null;
	var $dbh = null;
	var $dbhSys = null;

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, USER_SYSTEM); // -1 = administrator
	}
	
	/**
	 * Test save
	 */
	public function testExecute()
	{
		$event = CAntObject::factory($this->dbh, "calendar_event", null, $this->user);
		$event->setValue("ts_start", "1/1/2014 12:00 PM");
		$event->setValue("ts_end", "1/1/2014 1:00 PM");
		$event->setValue("name", "My Test Event");
		$eid = $event->save();

		// Create reminder for event
		$rem = new CAntObject_Reminder($this->dbh, null, $this->user);
		$rem->setValue("obj_reference", "calendar_event:$eid");
		$rem->setValue("interval", 30);
		$rem->setValue("interval_unit", "minutes");
		$rem->setValue("ts_execute", date("Y-m-d h:i:s A", (time() - 10)));
		$rem->setValue("f_executed", 'f');
		$rem->setValue("action_type", 'popup');
		$remId = $rem->save();
		$this->assertNotEquals($remId, false);

		// Test email action
		$act = new ReminderAction_Popup($rem);
		$act->testMode = true;
        $notification = $act->execute();

		// Send to should have come from the current user
		$this->assertEquals($notification->getValue("owner_id"), $this->user->id);
		$this->assertEquals($notification->getValue("f_popup"), 't');
		$this->assertTrue(strlen($notification->getValue("description"))>0);
		$this->assertTrue(strlen($notification->getValue("name"))>0);

		// Cleanup
		$rem->remove();
		$event->removeHard();
	}
}
