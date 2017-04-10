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

class ReminderAction_SmsTest extends PHPUnit_Framework_TestCase 
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
		// Make sure carrier and mobile are set
		$oldPhoneMobile = $this->user->getValue("phone_mobile");
		$oldPhoneMobileCarrier = $this->user->getValue("phone_mobile_carrier");
		$this->user->setValue("phone_mobile", "1111111111");
		$this->user->setValue("phone_mobile_carrier", "@txt.att.net");
		$this->user->save();

		// Create calendar event
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
		$rem->setValue("action_type", 'email');
		$remId = $rem->save();
		$this->assertNotEquals($remId, false);

		// Test email action
		$act = new ReminderAction_Sms($rem);
		$act->testMode = true;
        $emailVars = $act->execute();

		// Send to should have come from the current user
		$this->assertEquals($emailVars['headers']['To'], "1111111111@txt.att.net");
		$this->assertEquals($emailVars['headers']['Subject'], "Reminder - Event: " . $event->getValue("name"));

        // Open and make sure we are marked as executed
		$rem = new CAntObject_Reminder($this->dbh, $remId, $this->user);
		$this->assertEquals($rem->getValue("f_executed"), 'f');

		// Cleanup
		$rem->remove();
		$event->removeHard();

		// Reset phone for user
		$this->user->setValue("phone_mobile", $oldPhoneMobile);
		$this->user->setValue("phone_mobile_carrier", $oldPhoneMobileCarrier);
		$this->user->save();
	}
}

