<?php
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/RpcSvr.php');
require_once(dirname(__FILE__).'/../../lib/AntChat/SvrJson.php');
require_once(dirname(__FILE__).'/../../lib/Object/CalendarEvent.php');

// Async Libraries
define("ASYNC_PATH", dirname(__FILE__) . "/../../async/");
include_once(ASYNC_PATH . 'lib/exceptions/exceptions.php');
include_once(ASYNC_PATH . 'lib/utils/utils.php');
include_once(ASYNC_PATH . 'lib/utils/compat.php');
include_once(ASYNC_PATH . 'lib/utils/timezoneutil.php');
include_once(ASYNC_PATH . 'lib/core/zpushdefs.php');
include_once(ASYNC_PATH . 'lib/core/stateobject.php');
include_once(ASYNC_PATH . 'lib/core/interprocessdata.php');
include_once(ASYNC_PATH . 'lib/core/pingtracking.php');
include_once(ASYNC_PATH . 'lib/core/topcollector.php');
include_once(ASYNC_PATH . 'lib/core/loopdetection.php');
include_once(ASYNC_PATH . 'lib/core/asdevice.php');
include_once(ASYNC_PATH . 'lib/core/statemanager.php');
include_once(ASYNC_PATH . 'lib/core/devicemanager.php');
include_once(ASYNC_PATH . 'lib/core/zpush.php');
include_once(ASYNC_PATH . 'lib/core/zlog.php');
include_once(ASYNC_PATH . 'lib/core/paddingfilter.php');
include_once(ASYNC_PATH . 'lib/interface/ibackend.php');
include_once(ASYNC_PATH . 'lib/interface/ichanges.php');
include_once(ASYNC_PATH . 'lib/interface/iexportchanges.php');
include_once(ASYNC_PATH . 'lib/interface/iimportchanges.php');
include_once(ASYNC_PATH . 'lib/interface/isearchprovider.php');
include_once(ASYNC_PATH . 'lib/interface/istatemachine.php');
include_once(ASYNC_PATH . 'lib/core/streamer.php');
include_once(ASYNC_PATH . 'lib/core/streamimporter.php');
include_once(ASYNC_PATH . 'lib/core/synccollections.php');
include_once(ASYNC_PATH . 'lib/core/hierarchycache.php');
include_once(ASYNC_PATH . 'lib/core/changesmemorywrapper.php');
include_once(ASYNC_PATH . 'lib/core/syncparameters.php');
include_once(ASYNC_PATH . 'lib/core/bodypreference.php');
include_once(ASYNC_PATH . 'lib/core/contentparameters.php');
include_once(ASYNC_PATH . 'lib/wbxml/wbxmldefs.php');
include_once(ASYNC_PATH . 'lib/wbxml/wbxmldecoder.php');
include_once(ASYNC_PATH . 'lib/wbxml/wbxmlencoder.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncobject.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncbasebody.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncbaseattachment.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncmailflags.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncrecurrence.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncappointment.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncappointmentexception.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncattachment.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncattendee.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncmeetingrequestrecurrence.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncmeetingrequest.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncmail.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncnote.php');
include_once(ASYNC_PATH . 'lib/syncobjects/synccontact.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncfolder.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncprovisioning.php');
include_once(ASYNC_PATH . 'lib/syncobjects/synctaskrecurrence.php');
include_once(ASYNC_PATH . 'lib/syncobjects/synctask.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncoofmessage.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncoof.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncuserinformation.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncdeviceinformation.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncdevicepassword.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncitemoperationsattachment.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncsendmail.php');
include_once(ASYNC_PATH . 'lib/syncobjects/syncsendmailsource.php');
include_once(ASYNC_PATH . 'lib/default/backend.php');
include_once(ASYNC_PATH . 'lib/default/searchprovider.php');
include_once(ASYNC_PATH . 'lib/request/request.php');
include_once(ASYNC_PATH . 'lib/request/requestprocessor.php');
include_once(ASYNC_PATH . 'config.php');

// Add include path for backend includes
ini_set('include_path', ini_get('include_path') 
							. PATH_SEPARATOR . dirname(__FILE__)."/../../async"
							. PATH_SEPARATOR . dirname(__FILE__)."/../../async/include");

require_once(dirname(__FILE__).'/../../async/backend/ant/ant.php');

class BackendAntTest extends \PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;
	var $backend = null;

	/**
	 * Setup each test
	 */
    protected function setUp() 
    {
        $this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
		$this->backend = new BackendAnt();

		$this->backend->user = $this->user;
		$this->backend->username = $this->user->name;
		$this->backend->dbh = $this->ant->dbh;
		$this->backend->deviceId = "UnitTestDevice";
    }
    
    /**
     * Test login
     */
    public function testAntBackendLogin()
    {
		$ret = $this->backend->Logon("administrator", $this->ant->accountName, "Password1");
        $this->assertTrue($ret);
    }

    /**
     * Add a contact
     */
    public function testSaveContact()
    {
		$contact = new SyncContact();
		$contact->firstname = "UnitTestFirst";
		$contact->lastname = "UnitTestLast";
		$obj = $this->backend->saveContact("", $contact);
		$cid = $obj->id;

		// Make sure we have a new contact id
        $this->assertTrue(is_numeric($cid) && $cid > 0);

		// Make sure backend had fully populated contact
		$obj = new CAntObject($this->dbh, "contact_personal", $cid, $this->user);
		$this->assertEquals($obj->getValue("first_name"), $contact->firstname);
		$this->assertEquals($obj->getValue("last_name"), $contact->lastname);

		// Cleanup
		$obj->removeHard();
    }

	/**
     * Test adding a task
     */
    public function testSaveTask()
    {		
        $task = new SyncTask();                
        $task->subject = "UnitTest TaskName";        
        $task->startdate = strtotime("11/17/2011");
        $task->duedate = strtotime("11/18/2011");
        $obj = $this->backend->saveTask("", $task);
		$tid = $obj->id;

        // Make sure we have a new task id
        $this->assertTrue(is_numeric($tid) && $tid > 0);

        // Make sure backend had fully populated task
        $obj = new CAntObject($this->dbh, "task", $tid, $this->user);
        $this->assertEquals($obj->getValue("name"), $task->subject);
        $this->assertEquals(strtotime($obj->getValue("start_date")), $task->startdate);
        $this->assertEquals(strtotime($obj->getValue("deadline")), $task->duedate);

        // Cleanup
        $obj->removeHard();
	}

    /**
     * Test saving an appointment
     */
    public function testSaveAppointment()
	{
		// Set testing timezone
		$cur_tz = date_default_timezone_get();
		date_default_timezone_set('America/Los_Angeles'); // -8

		$app = new SyncAppointment();
		$app->timezone = base64_encode($this->backend->getSyncBlobFromTZ(TimezoneUtil::GetFullTZ()));
		$app->starttime = strtotime("1/1/2011 11:11 AM");
		$app->endtime = strtotime("1/1/2011 12:11 PM");
		$app->subject = "New async unit test event";
		$app->uid = 'unittestevnt1';
		$app->location = 'My House';
		$app->recurrence = new SyncRecurrence();
		$app->alldayevent = null;
		//$app->reminder = null;
		//$app->attendees = null;
		$app->body = "Notes here";
		//$app->exceptions = null;
		$app->recurrence->type = 1; // weekly
		$app->recurrence->interval = 1; // Every week
		$app->recurrence->dayofweek = $app->recurrence->dayofweek | WEEKDAY_WEDNESDAY; // Every wednesday
		$app->recurrence->until = strtotime("3/1/2011");

		$obj = $this->backend->saveAppointment("", $app);
		$eid = $obj->id;

		// Test timezone by making the local timezone New York +3 hours
		date_default_timezone_set('America/New_York'); // -5

		// Make sure we have a new event id
        $this->assertTrue(is_numeric($eid) && $eid > 0);

		// Test stat
		//$stat = $this->backend->StatMessage("calendar_root", $eid);
		//$this->assertEquals($stat['id'], $eid);

		// Make sure backend had fully populated contact
		$obj = new CAntObject_CalendarEvent($this->dbh, $eid, $this->user);
		$this->assertEquals($obj->getValue("name"), $app->subject);
		// Because we changed timezones, the times should be 3 hours later in EST
		$this->assertEquals(strtotime($obj->getValue("ts_start")), strtotime("01/01/2011 02:11 pm"));
		$this->assertEquals(strtotime($obj->getValue("ts_end")), strtotime("01/01/2011 03:11 pm"));

		// Check recurrence
		$recur = $obj->getRecurrencePattern();
		$this->assertEquals($recur->type, RECUR_WEEKLY);
		$this->assertEquals(strtotime($recur->dateEnd), strtotime("3/1/2011"));
		$this->assertEquals($recur->dayOfWeekMask, WEEKDAY_WEDNESDAY);

		// Cleanup
		$obj->removeHard();
		date_default_timezone_set($cur_tz);
    }

    

    /**
     * Test ANT backend - get event
     */
    public function testAntBackendGetEvent()
	{
		// Set testing timezone
		//$cur_tz = date_default_timezone_get();
		//date_default_timezone_set('America/Los_Angeles'); // -8

		$calid = GetDefaultCalendar($this->dbh, $this->user->id);

		// Create a new calendar event for testing
		$event = new CAntObject_CalendarEvent($this->dbh, null, $this->user);
		$event->setValue("name", "UnitTest Event");
		$event->setValue("ts_start", "10/8/2011 2:30 PM");
		$event->setValue("ts_end", "10/8/2011 3:30 PM");
		$event->setValue("calendar", $calid);
		$rp = $event->getRecurrencePattern();
		$rp->type = RECUR_MONTHLY;
		$rp->dayOfMonth = 1;
		$rp->interval = 1;
		$rp->dateStart = "1/1/2011";
		$rp->dateEnd = "11/1/2011";
		$eid = $event->save();

		// Get the event and make sure the recurrence is set right
		$syncEvent = $this->backend->getAppointment($eid);
		$this->assertEquals($syncEvent->subject, $event->getValue("name"));
		$this->assertEquals($syncEvent->recurrence->type, 2); // 2 = monthly
		$this->assertEquals($syncEvent->recurrence->dayofmonth , 1);

		// Cleanup
		$event->removeHard();
	}

    /**
     * Test ANT backend - get events
     */
    public function testAntBackendGetEvents()
	{
		$calid = GetDefaultCalendar($this->dbh, $this->user->id);

		// Create a new calendar event for testing
		$event = new CAntObject_CalendarEvent($this->dbh, null, $this->user);
		$event->setValue("name", "UnitTest Event");
		$event->setValue("ts_start", "10/8/2011 2:30 PM");
		$event->setValue("ts_end", "10/8/2011 3:30 PM");
		$event->setValue("calendar", $calid);
		$eid = $event->save();

		// Query events
		$events = $this->backend->GetMessageList("calendar_root");
		$this->assertTrue(count($events) > 0);

		// Cleanup
		$event->removeHard();
	}

    /**
     * Test ANT backend - get email message
     */
    public function testAntBackendGetMessage()
	{
		// Create new mail object and save it to ANT
		$obj = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$obj->setGroup("Inbox");
		$obj->setValue("flag_seen", 'f');
		$obj->setHeader("Subject", "UnitTest EmailSubject");
		$obj->setHeader("From", "UnitTestFrom@UnitTest.com");
		$obj->setHeader("To", "UnitTestSendTo@UnitTest.com");
		$obj->setBody("UnitTest EmailBody", "plain");
		$eid = $obj->save();

		$contentParams = new ContentParameters();
        $email = $this->backend->getEmail($eid, $contentParams);
        $this->assertEquals($obj->getValue("subject"), $email->subject);
        $this->assertEquals($obj->getValue("sent_from"), $email->from);        
        
        // Cleanup
        $obj->removeHard();
	}

    /**
     * Test ANT backend - get contacts list
     */
    public function testAntBackendGetContacts()
	{
        $contact = new SyncContact();
        $contact->firstname = "first_name";
        $contact->lastname = "last_name";
        $cid = $this->backend->saveContact("", $contact);
        
        $contacts = $this->backend->GetMessageList("contacts_root");
        $this->assertTrue(count($contacts) > 0);
        
        // Cleanup
        $obj = new CAntObject($this->dbh, "contact_personal", $cid, $this->user);
        $obj->removeHard();
	}

    /**
     * Test ANT backend - get tasks list
     */
    public function testAntBackendGetTasks()
	{		
        $task = new SyncTask();                
        $task->name = "UnitTest TaskName";        
        $task->startdate = strtotime("11/17/2011");
        $task->duedate = strtotime("11/18/2011");
        $obj = $this->backend->saveTask("", $task);
		$tid = $obj->id;
        
        $tasks = $this->backend->GetMessageList("tasks_root");
        $this->assertTrue(count($tasks) > 0);
        
        // Cleanup
        $obj->removeHard();
	}

    /**
     * Test ANT backend - get personal_contact
     */
    public function testAntBackendGetContact()
	{
        $contact = new SyncContact();
        $contact->firstname = "first_name";
        $contact->lastname = "last_name";
        $obj = $this->backend->saveContact("", $contact);
		$cid = $obj->id;

        // Query Tasks
        $syncContact = $this->backend->getContact($cid);
        $this->assertEquals($syncContact->firstname, $contact->firstname);
        $this->assertEquals($syncContact->lastname, $contact->lastname);
        
        // Cleanup
        $obj = new CAntObject($this->dbh, "contact_personal", $cid, $this->user);
        $obj->removeHard();
	}

    /**
     * Test ANT backend - get task 
     */
    public function testAntBackendGetTask()
	{
        $task = new SyncTask();                
        $task->subject = "UnitTest TaskName";        
        $task->startdate = strtotime("11/17/2011");
        $task->duedate = strtotime("11/18/2011");
        $obj = $this->backend->saveTask("", $task);
		$tid = $obj->id;

        // Query Tasks
        $syncTask = $this->backend->getTask($tid);
        $this->assertEquals($syncTask->subject, $task->subject);
        $this->assertEquals($syncTask->startdate, $task->startdate);
        $this->assertEquals($syncTask->duedate, $task->duedate);
        
        // Cleanup
        $obj->removeHard();
	}

	/**
     * Test ANT backend - stat event
     */
    public function testAntBackendStatEvent()
	{
		$calid = GetDefaultCalendar($this->dbh, $this->user->id);

		// Create a new calendar event for testing with default revision of 1
		$event = new CAntObject_CalendarEvent($this->dbh, null, $this->user);
		$event->setValue("name", "UnitTest Event");
		$event->setValue("ts_start", "10/8/2011 2:30 PM");
		$event->setValue("ts_end", "10/8/2011 3:30 PM");
		$event->setValue("calendar", $calid);
		$eid = $event->save();

		// Query events
		$stat = $this->backend->StatMessage("calendar_root", $eid);
		$this->assertEquals($stat['mod'], 1);

		// Cleanup
		$event->removeHard();
	}

	/**
     * Test ANT backend - stat contact 
     */
    public function testAntBackendStatContact()
	{
		// Todo - marl: test StatMessage("contacts_root", $contactid)
        $contact = new SyncContact();
        $contact->firstname = "UnitTestFirst";
        $contact->lastname = "UnitTestLast";
        $obj = $this->backend->saveContact("", $contact);
		$cid = $obj->id;
        
        // Test stat
        $stat = $this->backend->StatMessage("contacts_root", $cid);
        $this->assertEquals($stat['id'], $cid);
        
        // Cleanup
        $obj->removeHard();
	}

	/**
     * Test ANT backend - stat task
     */
    public function testAntBackendStatTask()
	{
		// Todo - marl: test StatMessage("tasks_root", $taskid)
        $task = new SyncTask();                
        $task->name = "UnitTest TaskName";        
        $task->startdate = "11/17/2011";
        $task->duedate = "11/18/2011";
        $obj = $this->backend->saveTask("", $task);
		$tid = $obj->id;

        // Test stat
        $stat = $this->backend->StatMessage("tasks_root", $tid);
        $this->assertEquals($stat['id'], $tid);
        
        // Cleanup
        $obj->removeHard();
	}

    /**
     * Test sending mail
	 *
	 * @group testAntBackendSendMail
     */
    public function testAntBackendSendMail()
	{

		$mimeMail = dirname(__FILE__)."/../data/mime_emails/attachments-mail.txt";
		$this->backend->testMode = true; // keep message from actually being sent
		$ret = $this->backend->SendMail($mimeMail);
		$this->assertTrue($ret);
	}

	/**
	 * Test the folder changes sync using objectsync collections
	 */
	public function testChangesSink()
	{
		$this->backend->ChangesSinkInitialize("Inbox");

		// Initialize and remove any stats and ignore existing messages
		$collection = $this->backend->getSyncCollection("Inbox");
		$collection->fastForwardToHead();

		// Get changes for Inbox - should be 0 because we reset above
		$changedFolders = $this->backend->ChangesSink(10);
		$this->assertEquals(0, count($changedFolders));

		// Create a dummy email message which will add the to stats
		$obj = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$obj->setValue("subject", "Test message");
		$obj->setValue("flag_seen", 'f');
		$obj->setValue("owner_id", $this->user->id);
		$obj->setGroup("Inbox");
		$mid = $obj->save();

		// Get changes for Inbox - should be 0 because we reset above
		$changedFolders = $this->backend->ChangesSink(10);
		$this->assertEquals(1, count($changedFolders));

		// Second call should equal 0 because stats got reset
		$changedFolders = $this->backend->ChangesSink(10);
		$this->assertEquals(0, count($changedFolders));

		// Cleanup
		$obj->removeHard();
	}

	/**
	 * Test the folder changes sync using objectsync collections
	 */
	public function testChangesSinkEvents()
	{
		$this->backend->ChangesSinkInitialize("calendar_root");

		// Initialize and remove any stats and ignore existing messages
		$collection = $this->backend->getSyncCollection("calendar_root");
		$collection->fastForwardToHead();

		// Get changes for calendar - should be 0 because we reset above
		$changedFolders = $this->backend->ChangesSink(10);
		$this->assertEquals(0, count($changedFolders));

		// Create a dummy email message which will add the to stats
		$obj = CAntObject::factory($this->dbh, "calendar_event", null, $this->user);
		$obj->setValue("name", "My Test Event");
		$obj->setValue("ts_start", date("m/d/Y") . " 12:00 PM");
		$obj->setValue("ts_end", date("m/d/Y") . " 01:00 PM");
		$mid = $obj->save();

		// Get changes for Inbox - should be 1
		$changedFolders = $this->backend->ChangesSink(10);
		$this->assertEquals(1, count($changedFolders));

		// Cleanup
		$obj->removeHard();
	}

	/**
	 * Test get message list for calendar events
	 */
	public function testGetMessageListAppointments()
	{
		// Create an event to make sure we have at least one
		$obj = CAntObject::factory($this->dbh, "calendar_event", null, $this->user);
		$obj->setValue("name", "My Test Event");
		$obj->setValue("ts_start", date("m/d/Y") . " 12:00 PM");
		$obj->setValue("ts_end", date("m/d/Y") . " 01:00 PM");
		$eid = $obj->save();

		// Get events
		$events = $this->backend->GetMessageList("calendar_root", time()); // second param cuts off to today
		$found = false;
		foreach ($events as $evt)
		{
			if ($evt["id"] == $eid)
				$found = true;
		}
		$this->assertTrue($found);

		// Cleanup
		$obj->removeHard();
	}
}
