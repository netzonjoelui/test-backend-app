<?php
/**
 * Test recurrence for entities
 */
namespace NetricTest\Entity\Recurrence;

use Netric\Entity\Recurrence\RecurrencePattern;
use PHPUnit_Framework_TestCase;

class RecurrencePatternTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Tennant account
     * 
     * @var \Netric\Account\Account
     */
    private $account = null;
    
    /**
     * Administrative user
     * 
     * @var \Netric\User
     */
    private $user = null;
    

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->user = $this->account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);
	}

	public function testConstructor()
	{
		$pattern = new RecurrencePattern();
		$this->assertInstanceof('\Netric\Entity\Recurrence\RecurrencePattern', $pattern);
	}

	public function testSetDayOfWeek()
	{
		$rp = new RecurrencePattern();
		$rp->setDayOfWeek(RecurrencePattern::WEEKDAY_SUNDAY, true);
		$this->assertNotEquals(0, $rp->getDayOfWeekMask() & RecurrencePattern::WEEKDAY_SUNDAY);
		$this->assertEquals(0, $rp->getDayOfWeekMask() & RecurrencePattern::WEEKDAY_MONDAY);

		// Flip days and test again
		$rp->setDayOfWeek(RecurrencePattern::WEEKDAY_MONDAY, true);
		$rp->setDayOfWeek(RecurrencePattern::WEEKDAY_SUNDAY, false);
		$this->assertNotEquals(0, $rp->getDayOfWeekMask() & RecurrencePattern::WEEKDAY_MONDAY);
		$this->assertEquals(0, $rp->getDayOfWeekMask() & RecurrencePattern::WEEKDAY_SUNDAY);
	}

	public function testGetNextStart_Daily()
	{
		$rp = new RecurrencePattern();
		$rp->setRecurType(RecurrencePattern::RECUR_DAILY);
		$rp->setInterval(1);
		$rp->setDateStart(new \DateTime("1/1/2010"));
		$rp->setDateEnd( new \DateTime("3/1/2010"));
		
		// First instance should be today
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/01/2010"));

		// Next instance should be tomorrow
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/02/2010"));
		
		// Change interval to skip a day and rewind to set
		$rp->setInterval(2);
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/04/2010"));

		// Call again should skip another day
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/06/2010"));
	}

	public function testGetNextStart_Weekly()
	{
		$rp = new RecurrencePattern();
		$rp->setRecurType(RecurrencePattern::RECUR_WEEKLY);
		$rp->setInterval(1);
		$rp->setDateStart(new \DateTime("1/2/2011")); // First sunday
		$rp->setDateEnd( new \DateTime("1/15/2011"));
		$rp->setDayOfWeek(RecurrencePattern::WEEKDAY_SUNDAY, true);
		$rp->setDayOfWeek(RecurrencePattern::WEEKDAY_WEDNESDAY, true);

		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/02/2011")); // Sun
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/05/2011")); // Wed
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/09/2011")); // Sun
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/12/2011")); // Wed

		// Next should fail because it is beyond the endDate
		$tsNext = $rp->getNextStart();
		$this->assertFalse($tsNext);
	}

	public function testGetNextStart_Monthly()
	{
		$rp = new RecurrencePattern();
		$rp->setRecurType(RecurrencePattern::RECUR_MONTHLY);
		$rp->setInterval(1);
		$rp->setDayOfMonth(1);
		$rp->setDateStart(new \DateTime("1/1/2011")); // First sunday

		// Should be the first of each month
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/01/2011"));
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("02/01/2011"));
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("03/01/2011"));

		// Skip over non-existant dates
		$rp = new RecurrencePattern();
		$rp->setRecurType(RecurrencePattern::RECUR_MONTHLY);
		$rp->setInterval(1);
		$rp->setDayOfMonth(30);
		$rp->setDateStart(new \DateTime("1/1/2011")); // First sunday

		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/30/2011"));
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("03/30/2011")); // Should skip of ver 2/30 because does not exist
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("04/30/2011"));

	}

	public function testGetNextStart_MonthNth()
	{
		$rp = new RecurrencePattern();
		$rp->setRecurType(RecurrencePattern::RECUR_MONTHNTH);
		$rp->setDayOfWeek(RecurrencePattern::WEEKDAY_SUNDAY, true);
		$rp->setInterval(1);
		$rp->setInstance(RecurrencePattern::NTH_4TH); // The 4th Sunday of each month
		$rp->setDayOfMonth(1);
		$rp->setDateStart(new \DateTime("1/1/2011")); // First sunday

		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/23/2011")); // The 4th Sunday in January
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("02/27/2011")); // The 4th Sunday in February

		// Test last
		$rp = new RecurrencePattern();
		$rp->setRecurType(RecurrencePattern::RECUR_MONTHNTH);
		$rp->setDayOfWeek(RecurrencePattern::WEEKDAY_SUNDAY, true);
		$rp->setInterval(1);
		$rp->setInstance(RecurrencePattern::NTH_LAST); // The last sunday
		$rp->setDayOfMonth(1);
		$rp->setDateStart(new \DateTime("1/1/2011")); // First sunday

		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/30/2011")); // The last Sunday in January
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("02/27/2011")); // The last Sunday in February
	}

	public function testGetNextStart_Yearly()
	{
		$rp = new RecurrencePattern();
		$rp->setRecurType(RecurrencePattern::RECUR_YEARLY);
		$rp->setInterval(1);
		$rp->setDayOfMonth(8);
		$rp->setMonthOfYear(10);
		$rp->setDateStart(new \DateTime("1/1/2011")); // First sunday
		$rp->setDateEnd(new \DateTime("1/1/2013")); // First sunday

		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("10/08/2011"));
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("10/08/2012"));
		$tsNext = $rp->getNextStart();
		$this->assertFalse($tsNext); // Past the dateEnd
	}
	
	public function testGetNextStart_YearNth()
	{
		$rp = new RecurrencePattern();
		$rp->setRecurType(RecurrencePattern::RECUR_YEARNTH);
		// The 4th Sunday of January
		$rp->setInstance(RecurrencePattern::NTH_4TH);
		$rp->setDayOfWeek(RecurrencePattern::WEEKDAY_SUNDAY, true);
		$rp->setMonthOfYear(1);
		$rp->setInterval(1);

		$rp->setDateStart(new \DateTime("1/1/2011"));
		$rp->setDateEnd(new \DateTime("1/1/2013"));

		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/23/2011"));
		$tsNext = $rp->getNextStart();
		$this->assertEquals($tsNext, new \DateTime("01/22/2012"));
		$tsNext = $rp->getNextStart();
		$this->assertFalse($tsNext); // Past the dateEnd
	}

	public function testToAndFromArray()
	{
		$import = array(
			"id" => 123,
            "recur_type" => RecurrencePattern::RECUR_MONTHLY,
			"interval" => 2,
			"instance" => RecurrencePattern::NTH_1ST,
			"day_of_month" => 1, // 1st
			"month_of_year" => 1, // 1-12 = January
			"day_of_week_mask" => RecurrencePattern::WEEKDAY_FRIDAY | RecurrencePattern::WEEKDAY_MONDAY,
			"date_start" => "2015-01-01",
			"date_end" => "2015-02-01",
			"f_active" => true,
			"obj_type" => "task",
			"first_entity_id" => 444,
			"date_processed_to" => "2015-03-01",
			"field_date_start" => "deadline",
			"field_date_end" => "deadline",
			"field_time_start" => "ts_start",
			"field_time_end" => "ts_end",
			"ep_locked" => time(),
		);

		$recur = new RecurrencePattern();
		$recur->fromArray($import);

		// Convert back to an array and test
		$exported = $recur->toArray();

		$this->assertEquals($import['id'], $exported['id']);
		$this->assertEquals($import['recur_type'], $exported['recur_type']);
        $this->assertEquals($import['interval'], $exported['interval']);
		$this->assertEquals($import['instance'], $exported['instance']);
		$this->assertEquals($import['day_of_month'], $exported['day_of_month']);
		$this->assertEquals($import['month_of_year'], $exported['month_of_year']);
		$this->assertEquals($import['day_of_week_mask'], $exported['day_of_week_mask']);
		$this->assertEquals($import['date_start'], $exported['date_start']);
		$this->assertEquals($import['date_end'], $exported['date_end']);
		$this->assertEquals($import['f_active'], $exported['f_active']);
		$this->assertEquals($import['obj_type'], $exported['obj_type']);
		$this->assertEquals($import['first_entity_id'], $exported['first_entity_id']);
		$this->assertEquals($import['date_processed_to'], $exported['date_processed_to']);
		$this->assertEquals($import['field_date_start'], $exported['field_date_start']);
		$this->assertEquals($import['field_date_end'], $exported['field_date_end']);
		$this->assertEquals($import['field_time_start'], $exported['field_time_start']);
		$this->assertEquals($import['field_time_end'], $exported['field_time_end']);
		$this->assertEquals($import['ep_locked'], $exported['ep_locked']);
	}

	/**
	 * @group recur
	 */
	/*
	function testRecur() 
	{
		$dbh = $this->dbh;
		
		$obj = new CAntObject($dbh, "task", null, $this->user);
		$obj->setValue("name", "My Recurring Task");
		$obj->setValue("start_date", "1/1/2011");
		$obj->setValue("deadline", "1/1/2011");
		$rp = $obj->getRecurrencePattern();
		$rp->type = RECUR_DAILY;
		$rp->interval = 1;
		$rp->dateStart = "1/1/2011";
		$rp->dateEnd = "3/1/2011";
		$obj->save();

		$this->assertNotNull($rp->id);

		$created = $rp->createInstances("1/3/2011");
		$this->assertEquals($created, 2); // should create two additional objects

		$obj->remove();

		// Make sure recurrence pattern is flagged inactive
		$this->assertEquals($dbh->GetNumberRows($dbh->Query("select * from object_recurrence where id='".$rp->id."' and f_active is false")), 1);

		$obj->remove();

		// Make sure recurrence pattern is purged
		$this->assertEquals($dbh->GetNumberRows($dbh->Query("select * from object_recurrence where id='".$rp->id."'")), 0);


		// Now test using CAntObjectList::checkForRecurrence
		$obj = new CAntObject($dbh, "task", null, $this->user);
		$obj->setValue("name", "My Recurring Task");
		$obj->setValue("start_date", "1/1/2011");
		$obj->setValue("deadline", "1/1/2011");
		$rp = $obj->getRecurrencePattern();
		$rp->type = RECUR_DAILY;
		$rp->interval = 1;
		$rp->dateStart = "1/1/2011";
		$rp->dateEnd = "1/3/2011";
		$oid = $obj->save();

		$this->assertNotNull($rp->id);

		$objList = new CAntObjectList($dbh, "task", $this->user);
		$objList->addCondition("and", "start_date", "is_less", "5/1/2011"); // Should fire create instances
		$objList->getObjects();

		// Make sure there are three recurring tasks created
		$this->assertEquals(3, $dbh->GetNumberRows($dbh->Query("select id from project_tasks where recurrence_pattern='".$rp->id."'")));

		// Cleanup
		$rp->removeSeries();
		$obj->removeHard();

		// Test with an event due to using timestamps rather than dates
		$obj = new CAntObject($dbh, "calendar_event", null, $this->user);
		$obj->setValue("name", "My Recurring Event");
		$obj->setValue("ts_start", "1/5/2011 09:00 AM");
		$obj->setValue("ts_end", "1/5/2011 10:00 AM");
		$rp = $obj->getRecurrencePattern();
		$rp->type = RECUR_WEEKLY;
		$rp->interval = 1;
		$rp->dateStart = "1/5/2011"; // First Wednesday
		$rp->dateEnd = "1/19/2011"; // Third Wednesday
		$rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_WEDNESDAY;
		$obj->save();

		$this->assertNotNull($rp->id);

		$objList = new CAntObjectList($dbh, "calendar_event", $this->user);
		$objList->addCondition("and", "ts_start", "is_greater_or_equal", "1/1/2011"); // Should fire create instances
		$objList->addCondition("and", "ts_start", "is_less_or_equal", "5/1/2011"); // Should fire create instances
		$objList->getObjects();

		// Make sure there are three recurring tasks created
		$this->assertEquals(3, $dbh->GetNumberRows($dbh->Query("select id from calendar_events where recurrence_pattern='".$rp->id."'")));

		// Cleanup
		$rp->removeSeries();
		$obj->removeHard();
	}
	*/
}
