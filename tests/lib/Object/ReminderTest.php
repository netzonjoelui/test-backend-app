<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Reminder.php');

class CAntObject_ReminderTest extends PHPUnit_Framework_TestCase
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
	 * Test tsExecute calculation based on a field
	 */
	public function testSetTsExecByField()
	{
		// First create a test event
		$evnt = CAntObject::factory($this->dbh, "calendar_event", null, $this->user);
		$evnt->setValue("name", "testSetTsExecByField event");
		$evnt->setValue("ts_start", "1/1/2013 9:00:00 AM");
		$evnt->setValue("ts_end", "1/1/2013 10:00:00 AM");
		$eid = $evnt->save();

		// Test calculation
		$rem = new CAntObject_Reminder($this->dbh, null, $this->user);
		$rem->setValue("obj_reference", "calendar_event:$eid");
		$rem->setValue("interval", 30);
		$rem->setValue("interval_unit", "minutes");
		$rem->setValue("field_name", "ts_start");
		$ret = $rem->setTsExecByField();
		$this->assertTrue($ret);
		$this->assertEquals(strtotime($rem->getValue("ts_execute")), strtotime("1/1/2013 8:30:00 AM"));

		// Cleanup
		$evnt->removeHard();
		$rem->removeHard();
	}

	/**
	 * Test delay update
	 */
	public function testDelay()
	{
		// First create a test event
		$evnt = CAntObject::factory($this->dbh, "calendar_event", null, $this->user);
		$evnt->setValue("name", "testSetTsExecByField event");
		$evnt->setValue("ts_start", "1/1/2013 9:00:00 AM");
		$evnt->setValue("ts_end", "1/1/2013 10:00:00 AM");
		$eid = $evnt->save();

		// Test calculation
		$rem = new CAntObject_Reminder($this->dbh, null, $this->user);
		$rem->setValue("obj_reference", "calendar_event:$eid");
		$rem->setValue("interval", 30);
		$rem->setValue("interval_unit", "minutes");
		$rem->setValue("field_name", "ts_start");
		$ret = $rem->setTsExecByField();
		$this->assertTrue($ret);
		$rit = $rem->save();

		// Now delay and test
		$rem->delay(strtotime("1/1/2013 10:30:00 AM"));
		// make sure it did not revert to fieldName calc
		$this->assertEquals(strtotime($rem->getValue("ts_execute")), strtotime("1/1/2013 10:30:00 AM"));

		// Cleanup
		$evnt->removeHard();
		$rem->removeHard();
	}
}
