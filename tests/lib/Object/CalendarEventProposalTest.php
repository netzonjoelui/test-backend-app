<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/CalendarEventProposal.php');

class CAntObject_CalendarEventProposalTest extends PHPUnit_Framework_TestCase
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
		$this->user = new AntUser($this->dbh, USER_SYSTEM);
	}

	/**
	 * Test sending inviations
	 */
	public function testSendInvitations()
	{
		$event = CAntObject::factory($this->dbh, "calendar_event_proposal", null, $this->user);
		$event->setValue("name", "testSendInvitations");
		$eid = $event->save();

		// Add non-email member
		$mem1 = CAntObject::factory($this->dbh, "member", null, $this->user);
		$mem1->setValue("name", "Evnt Member 1"); // non email or customer, should not send anything
		$mem1->save();
		$event->setMValue("attendees", $mem1->id);

		// Add a customer email
		$cust = CAntObject::factory($this->dbh, "customer", null, $this->user);
		$cust->setValue("name", "Customer Test Inv");
		$cust->setValue("email", "testSendInvitations@testSendInvitations.org");
		$cust->save();
		$mem2 = CAntObject::factory($this->dbh, "member", null, $this->user);
		$mem2->setValue("name", "Evnt Member 2");
		$mem2->setValue("obj_member", "customer:" . $cust->id);
		$mem2->save();
		$event->setMValue("attendees", $mem2->id);

		// Save members to event
		$event->save();

		// Now test sending invitations
		$event->testMode = true; // repress actual emails from being sent
		$numSent = $event->sendInvitations("attendees");
		$this->assertEquals($numSent, 1); // only the email should have been sent to customer
		$this->assertEquals($cust->getValue("email"), $event->testModeBuf[0]);

		// Call again with onlyupdates, meaning, only new members which will be 0
		$numSent = $event->sendInvitations("attendees", true);
		$this->assertEquals($numSent, 0); // only the email should have been sent to customer

		// Cleanup
		$event->removeHard();
		$mem1->removeHard();
		$cust->removeHard();
		$mem2->removeHard();
	}
}
