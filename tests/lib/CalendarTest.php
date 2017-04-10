<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/aereus.lib.php/antapi.php');
require_once(dirname(__FILE__).'/../../lib/Calendar.php');
require_once(dirname(__FILE__).'/../../lib/Object/CalendarEvent.php');
//require_once(dirname(__FILE__).'/../../lib/parsers/imc/CimcCalendar.php');

class CalendarTest extends \PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh; 
		$this->user = $this->ant->getUser(USER_SYSTEM);
	}
	
	function tearDown() 
	{
		//@unlink('/temp/test.log');	
	}

	/**
	 * Test to make sure ical invitations are property formatted
	 */
	function testIcalInv()
	{
		// Create a new calendar event for testing
		$event = new CAntObject_CalendarEvent($this->dbh, null, $this->user);
		$event->setValue("name", "UnitTest Event");
		$event->setValue("ts_start", "10/8/2011 2:30 PM");
		$event->setValue("ts_end", "10/8/2011 3:30 PM");
		$event->addAttendee("adminitrator@aereus.com", "Meeting Organizer");
		$eid = $event->save();

		// Make sure event saved
		$this->assertTrue($eid>0);

		// Now get the attendee id
		$att = $event->getAttendee(0);
		$this->assertTrue($att->id > 0);

		// Test ical
		$ical = $event->createIcalBody($att->id, "test@aereus.com");
		$icalPar = new CImcCalendar();
		$icalPar->parseText($ical);
		$this->assertTrue($icalPar->getNumEvents() > 0);

		// Cleanup
		$event->removeHard();
		$att->removeHard();
	}
}
