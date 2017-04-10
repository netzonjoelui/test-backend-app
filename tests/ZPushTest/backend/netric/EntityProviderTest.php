<?php
/**
 * Test the the custom netric backend for ActiveSync
 */
namespace ZPushTest\backend\netric;

use Netric\Entity\Recurrence\RecurrencePattern;
use PHPUnit_Framework_TestCase;

// Add all z-push required files
require_once("z-push.includes.php");

// Include config
require_once(dirname(__FILE__) . '/../../../../config/zpush.config.php');

// Include backend classes
require_once('backend/netric/netric.php');
require_once('backend/netric/entityprovider.php');

class EntityProviderTest extends PHPUnit_Framework_TestCase
{
    /**
     * Handle to account
     *
     * @var \Netric\Account\Account
     */
    private $account = null;

    /**
     * Test user
     *
     * @var \Netric\Entity\ObjType\UserEntity
     */
    private $user = null;

    /**
     * Common constants used
     *
     * @cons string
     */
    const TEST_USER = "test_auth";
    const TEST_USER_PASS = "testpass";

    /**
     * Entity provider for converting entities to and from SyncObjects
     *
     * @var \EntityProvider
     */
    private $provider = null;

    /**
     * Test entities to cleanup
     *
     * @var \Netric\Entity\EntityInterface[]
     */
    private $testEntities = array();

    /**
     * Loader for opening, saving, and deleting entities
     *
     * @var \Netric\EntityLoader
     */
    private $entityLoader = null;

    /**
     * Test calendar
     *
     * @var \Netric\Entity\Entity
     */
    private $testCalendar = null;

    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();

        $this->account = \NetricTest\Bootstrap::getAccount();

        // Setup entity datamapper for handling users
        $dm = $this->account->getServiceManager()->get("Entity_DataMapper");

        // Make sure old test user does not exist
        $query = new \Netric\EntityQuery("user");
        $query->where('name')->equals(self::TEST_USER);
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $res = $index->executeQuery($query);
        for ($i = 0; $i < $res->getTotalNum(); $i++)
        {
            $user = $res->getEntity($i);
            $dm->delete($user, true);
        }

        // Create a test user
        $loader = $this->account->getServiceManager()->get("EntityLoader");
        $user = $loader->create("user");
        $user->setValue("name", self::TEST_USER);
        $user->setValue("password", self::TEST_USER_PASS);
        $user->setValue("active", true);
        $dm->save($user);
        $this->user = $user;
        $this->testEntities[] = $user; // cleanup automatically

        // Get the entityLoader
        $this->entityLoader = $this->account->getServiceManager()->get("EntityLoader");

        // Create inbox mailbox for testing
        $groupingsLoader = $this->account->getServiceManager()->get("EntityGroupings_Loader");
        $groupings = $groupingsLoader->get("email_message", "mailbox_id", array("user_id"=>$user->getId()));
        if (!$groupings->getByName("Inbox")) {
            $inbox = $groupings->create("Inbox");
            $inbox->user_id = $user->getId();
            $groupings->add($inbox);
            $groupingsLoader->save($groupings);
        }

        // Create a calendar for the user to test
        $calendar = $this->entityLoader->create("calendar");
        $calendar->setValue("name", "UTest provider");
        $calendar->setValue("user_id", $this->user->getId());
        $this->entityLoader->save($calendar);
        $this->testEntities[] = $calendar;
        $this->testCalendar = $calendar;

        // Setup the provider service
        $this->provider = new \EntityProvider($this->account, $this->user);
    }

    /**
     * Cleanup
     */
    protected function tearDown()
    {
        foreach ($this->testEntities as $entity) {
            $this->entityLoader->delete($entity, true);
        }
    }

    /**
     * Make sure we get the contact folder
     */
    public function testGetContactFolders()
    {
        // Get folder hierarchy
        $folders = $this->provider->getContactFolders();

        $found = false;

        foreach ($folders as $folder) {
            if ($folder->serverid == \EntityProvider::FOLDER_TYPE_CONTACT . "-" . "my") {
                $found = true;
            }
        }

        // Make sure our folders existed
        $this->assertTrue($found);
    }

    /**
     * Check if we can get task folders
     */
    public function testGetTaskFolders()
    {
        // Get folder hierarchy
        $folders = $this->provider->getTaskFolders();

        $found = false;

        foreach ($folders as $folder) {
            if ($folder->serverid == \EntityProvider::FOLDER_TYPE_TASK . "-" . "my") {
                $found = true;
            }
        }

        // Make sure our folders existed
        $this->assertTrue($found);
    }

    /**
     * Check that we export calendars as SyncFolders
     */
    public function testGetCalendarFolders()
    {
        // Add a calendar for the user
        $entityLoader = $this->entityLoader;
        $calendar = $entityLoader->create("calendar");
        $calendar->setValue("name", "a test calendar");
        $calendar->setValue("user_id", $this->user->getId());
        $entityLoader->save($calendar);

        // Queue for cleanup
        $this->testEntities[] = $calendar;

        // Get calendars for this user
        $folders = $this->provider->getCalendarFolders();

        $found = false;
        foreach ($folders as $folder) {
            if ($folder->serverid == \EntityProvider::FOLDER_TYPE_CALENDAR . "-" . $calendar->getId()) {
                $found = true;
            }
        }

        // Test result
        $this->assertTrue($found);
    }

    /**
     * Test getting email folder_id groupings as folders
     */
    public function testGetEmailFolders()
    {
        // Add a mail folder for the user
        $sm = $this->account->getServiceManager();
        $entityGroupingsLoader = $sm->get("EntityGroupings_Loader");
        $groupings = $entityGroupingsLoader->get("email_message", "mailbox_id", array("user_id"=>$this->user->getId()));
        $newGroup = $groupings->create();
        $newGroup->name = "utttest mailbox";
        $newGroup->user_id = \Netric\Entity\ObjType\UserEntity::USER_SYSTEM;
        $groupings->add($newGroup);
        $entityGroupingsLoader->save($groupings);
        $savedGroup = $groupings->getByName("utttest mailbox");

        // Get groupings as folders
        $folders = $this->provider->getEmailFolders();

        $found = false;
        foreach ($folders as $folder) {
            if ($folder->serverid == \EntityProvider::FOLDER_TYPE_EMAIL . "-" . $savedGroup->id) {
                $found = true;
            }
        }

        // Cleanup first
        $groupings->delete($savedGroup->id);

        // Test result
        $this->assertTrue($found);
    }

    /**
     * Test getting note groupings as folders
     */
    public function testGetNoteFolders()
    {
        /*
         * Below is depricated since we no longer use groupings as folders
         *
        // Add a note grouping for the user
        $sm = $this->account->getServiceManager();
        $entityGroupingsLoader = $sm->get("EntityGroupings_Loader");
        $groupings = $entityGroupingsLoader->get("note", "groups", array("user_id"=>$this->user->getId()));
        $newGroup = $groupings->create();
        $newGroup->name = "utttest note folder";
        $newGroup->user_id = \Netric\Entity\ObjType\UserEntity::USER_SYSTEM;
        $groupings->add($newGroup);
        $entityGroupingsLoader->save($groupings);
        $savedGroup = $groupings->getByName("utttest note folder");

        // Get groupings as folders
        $folders = $this->provider->getNoteFolders();

        $found = false;
        $allNotesFound = false;
        foreach ($folders as $folder) {
            if ($folder->serverid == \EntityProvider::FOLDER_TYPE_NOTE. "-" . $savedGroup->id) {
                $found = true;
            } else if ($folder->serverid == \EntityProvider::FOLDER_TYPE_NOTE. "-all") {
                $allNotesFound = true;
            }
        }

        // Cleanup first
        $groupings->delete($savedGroup->id);

        // Test result
        $this->assertTrue($found);
        $this->assertTrue($allNotesFound);
        */

        // Get folder hierarchy
        $folders = $this->provider->getNoteFolders();

        $found = false;

        foreach ($folders as $folder) {
            if ($folder->serverid == \EntityProvider::FOLDER_TYPE_NOTE . "-" . "my") {
                $found = true;
            }
        }

        // Make sure our folders existed
        $this->assertTrue($found);
    }

    /**
     * Make sure we can get a task
     */
    public function testGetSyncObject_Task()
    {
        $task = $this->entityLoader->create("task");
        $task->setValue("name", "My Unit Test Task");
        $task->setValue("user_id", $this->user->getId());
        $task->setValue("start_date", date("m/d/Y"));
        $task->setValue("date_completed", date("m/d/Y"));
        $task->setValue("deadline", date("m/d/Y"));
        $tid = $this->entityLoader->save($task);

        // Queue for cleanup
        $this->testEntities[] = $task;

        $syncTask = $this->provider->getSyncObject(
            \EntityProvider::FOLDER_TYPE_TASK,
            $tid,
            new \ContentParameters()
        );

        $this->assertEquals($syncTask->subject, $task->getValue("name"));
        $this->assertEquals($syncTask->startdate, $task->getValue('start_date'));
        $this->assertEquals($syncTask->datecompleted, $task->getValue('date_completed'));
        $this->assertEquals($syncTask->duedate, $task->getValue('deadline'));
    }

    /**
     * Make sure we can get a contact
     */
    public function testGetSyncObject_Contact()
    {
        $contact = $this->entityLoader->create("contact_personal");
        $contact->setValue("first_name", "John");
        $contact->setValue("last_name", "Doe");
        $contact->setValue("user_id", $this->user->getId());
        $cid = $this->entityLoader->save($contact);

        // Queue for cleanup
        $this->testEntities[] = $contact;

        $syncContact = $this->provider->getSyncObject(
            \EntityProvider::FOLDER_TYPE_CONTACT,
            $cid,
            new \ContentParameters()
        );

        $this->assertEquals($syncContact->firstname, $contact->getValue("first_name"));
        $this->assertEquals($syncContact->lastname, $contact->getValue('last_name'));
    }

    /**
     * Make sure we can get a calendar
     */
    public function testGetSyncObject_Appointment()
    {
        // Create a new calendar for this event
        $calendar = $this->entityLoader->create("calendar");
        $calendar->setValue("name", "UT_TEST_CALENDAR");
        $calid = $this->entityLoader->save($calendar);
        $this->testEntities[] = $calendar;

        // Create an event
        $event = $this->entityLoader->create("calendar_event");
        $event->setValue("name", "UnitTest Event");
        $event->setValue("ts_start", "10/8/2011 2:30 PM");
        $event->setValue("ts_end", "10/8/2011 3:30 PM");
        $event->setValue("calendar", $calid);
        $cid = $this->entityLoader->save($event);

        // Queue for cleanup
        $this->testEntities[] = $event;

        $syncEvent= $this->provider->getSyncObject(
            \EntityProvider::FOLDER_TYPE_CALENDAR . "-" . $calid,
            $cid,
            new \ContentParameters()
        );

        $this->assertEquals($syncEvent->subject, $event->getValue("name"));
    }

    /**
     * Make sure we can get an email
     */
    public function testGetSyncObject_Email()
    {
        $email = $this->entityLoader->create("email_message");
        $email->setValue("subject", "A test message");
        $email->setValue("sent_from", "sky@stebnicki.net");
        $eid = $this->entityLoader->save($email);

        // Queue for cleanup
        $this->testEntities[] = $email;

        $syncMessage = $this->provider->getSyncObject(
            \EntityProvider::FOLDER_TYPE_EMAIL,
            $eid,
            new \ContentParameters()
        );

        $this->assertEquals($syncMessage->subject, $email->getValue("subject"));
        $this->assertEquals($syncMessage->from, $email->getValue('sent_from'));
    }

    public function testSaveSyncObject_Task()
    {
        $task = new \SyncTask();
        $task->subject = "UnitTest TaskName";
        $task->startdate = strtotime("11/17/2016");
        $task->duedate = strtotime("11/18/2016");
        $id = $this->provider->saveSyncObject(\EntityProvider::FOLDER_TYPE_TASK . "-my", null, $task);
        $this->assertNotNull($id);

        // Open and check the data
        $entity = $this->entityLoader->get("task", $id);
        $this->testEntities[] = $entity;
        $this->assertEquals($task->subject, $entity->getValue("name"));
        $this->assertGreaterThan(0, $entity->getValue("user_id"));
        $this->assertEquals(date("Y-m-d", $task->startdate), date("Y-m-d", $entity->getValue("start_date")));

        // Save changes to existing
        $task->subject = "UnitTest TaskName - edited";
        $this->provider->saveSyncObject(\EntityProvider::FOLDER_TYPE_TASK . "-my", $id, $task);

        // Test the new value
        $openedEntity = $this->entityLoader->get("task", $id);
        $this->assertEquals($task->subject, $openedEntity->getValue("name"));
    }

    public function testSaveSyncObject_Email()
    {
        $emailMailboxes = $this->provider->getEmailFolders();

        $mail = new \SyncMail();
        $mail->subject = "test";
        $id = $this->provider->saveSyncObject($emailMailboxes[0]->serverid, null, $mail);
        $this->assertNotNull($id);

        // Open and check the data
        $entity = $this->entityLoader->get("email_message", $id);
        $this->testEntities[] = $entity;
        $this->assertEquals($mail->subject, $entity->getValue("subject"));
        $this->assertGreaterThan(0, $entity->getValue("owner_id"));

        // Save changes to existing
        $mail->subject = "test - edited";
        $this->provider->saveSyncObject($emailMailboxes[0]->serverid, $id, $mail);

        // Test the new value
        $openedEntity = $this->entityLoader->get("email_message", $id);
        $this->assertEquals($mail->subject, $openedEntity->getValue("subject"));
    }

    public function testSaveSyncObject_Appointment()
    {
        $folderIds = $this->provider->getCalendarFolders();

        // Play with timezones to make sure it is working as designed
        $cur_tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $app = new \SyncAppointment();
        $app->timezone = base64_encode(\TimezoneUtil::GetSyncBlobFromTZ(\TimezoneUtil::GetFullTZ()));
        $app->starttime = strtotime("1/1/2011 10:11 PM");
        $app->endtime = strtotime("1/1/2011 11:11 PM");
        $app->subject = "New async unit test event";
        $app->uid = 'unittestevnt1';
        $app->location = 'My House';
        $app->recurrence = new \SyncRecurrence();
        $app->alldayevent = null;
        //$app->reminder = null;
        //$app->attendees = null;
        $app->body = "Notes here";
        //$app->exceptions = null;
        $app->recurrence->type = 1; // weekly
        $app->recurrence->interval = 1; // Every week
        $app->recurrence->dayofweek = $app->recurrence->dayofweek | RecurrencePattern::WEEKDAY_WEDNESDAY;
        $app->recurrence->until = strtotime("3/1/2011");

        $eid = $this->provider->saveSyncObject($folderIds[0]->serverid, null, $app);
        $this->assertNotNull($eid);

        // Test timezone by making the local timezone New York -5 hours
        date_default_timezone_set('America/New_York');

        // Open and check the data
        $entity = $this->entityLoader->get("calendar_event", $eid);
        $this->testEntities[] = $entity;
        $this->assertEquals($entity->getValue("name"), $app->subject);
        // Because we changed timezones, the times should be -5 hours  in EST
        $this->assertEquals($app->starttime, $entity->getValue("ts_start"));
        $this->assertEquals(date("Y-m-d h:i a T", $app->starttime), date("Y-m-d h:i a T", $entity->getValue("ts_start")));
        $this->assertEquals(date("Y-m-d h:i a T", $app->endtime), date("Y-m-d h:i a T", $entity->getValue("ts_end")));

        // Check recurrence
        $recur = $entity->getRecurrencePattern();
        $this->assertNotNull($recur);
        $this->assertEquals($recur->getRecurType(), RecurrencePattern::RECUR_WEEKLY);
        $this->assertEquals($recur->getDateEnd()->getTimestamp(), strtotime("3/1/2011"));
        $this->assertEquals($recur->getDayOfWeekMask(), RecurrencePattern::WEEKDAY_WEDNESDAY);

        // Cleanup
        date_default_timezone_set($cur_tz);
    }

    public function testSaveSyncObject_Contact()
    {
        $contact = new \SyncContact();
        $contact->firstname = "test";
        $contact->lastname = "contact";
        $id = $this->provider->saveSyncObject(\EntityProvider::FOLDER_TYPE_CONTACT . "-my", null, $contact);
        $this->assertNotNull($id);

        // Open and check the data
        $entity = $this->entityLoader->get("contact_personal", $id);
        $this->testEntities[] = $entity;
        $this->assertEquals($contact->firstname, $entity->getValue("first_name"));
        $this->assertEquals($contact->lastname, $entity->getValue("last_name"));
        $this->assertGreaterThan(0, $entity->getValue("user_id"));

        // Save changes to existing
        $contact->firstname = "test - edited";
        $this->provider->saveSyncObject(\EntityProvider::FOLDER_TYPE_CONTACT . "-my", $id, $contact);

        // Test the new value
        $openedEntity = $this->entityLoader->get("contact_personal", $id);
        $this->assertEquals($contact->firstname, $openedEntity->getValue("first_name"));
    }

    public function testSaveSyncObject_Note()
    {
        // Add a grouping to use
        $sm = $this->account->getServiceManager();
        $entityGroupingsLoader = $sm->get("EntityGroupings_Loader");
        $groupings = $entityGroupingsLoader->get("note", "groups", array("user_id"=>$this->user->getId()));
        $newGroup = $groupings->create();
        $newGroup->name = "utttest";
        $newGroup->user_id = \Netric\Entity\ObjType\UserEntity::USER_SYSTEM;
        $groupings->add($newGroup);
        $entityGroupingsLoader->save($groupings);
        $savedGroup = $groupings->getByName("utttest");

        $note = new \SyncNote();
        $note->subject = "A Unit Test Note";
        $note->asbody = new\SyncBaseBody();
        $note->asbody->type = SYNC_BODYPREFERENCE_HTML;
        $note->asbody->data = "<p>My Body</p>";
        $note->categories = array($savedGroup->name);
        $id = $this->provider->saveSyncObject(\EntityProvider::FOLDER_TYPE_NOTE . "-my", null, $note);
        $this->assertNotNull($id);

        // Open and check the data
        $entity = $this->entityLoader->get("note", $id);
        $this->testEntities[] = $entity;

        // Cleanup before testing
        $groupings->delete($savedGroup->id);

        // Test values
        $this->assertNotEmpty($entity->getValue("user_id"));
        $this->assertEquals('html', $entity->getValue("body_type"));
        $this->assertEquals($note->asbody->data, $entity->getValue("body"));
        $this->assertEquals($note->categories, array("utttest"));

        // Save changes without setting body type and meta data
        $note->asbody = "<p>My Edited Body</p>";
        $this->provider->saveSyncObject(\EntityProvider::FOLDER_TYPE_NOTE . "-my", $id, $note);

        // Test the new value
        $openedEntity = $this->entityLoader->get("note", $id);
        $this->assertEquals('plain', $entity->getValue("body_type"));
        $this->assertEquals($note->asbody, $entity->getValue("body"));;
    }

    public function testMoveEntity_Email()
    {
        // Create drafts mailbox for testing - Inbox is already added in $this->setUp
        $groupingsLoader = $this->account->getServiceManager()->get("EntityGroupings_Loader");
        $groupings = $groupingsLoader->get("email_message", "mailbox_id", array("user_id"=>$this->user->getId()));
        if (!$groupings->getByName("Drafts")) {
            $inbox = $groupings->create("Drafts");
            $inbox->user_id = $this->user->getId();
            $groupings->add($inbox);
            $groupingsLoader->save($groupings);
        }

        $grpInbox = $groupings->getByName("Inbox");
        $grpDrafts = $groupings->getByName("Drafts");

        $entity = $this->entityLoader->create("email_message");
        $entity->setValue("mailbox_id", $grpDrafts->id);
        $id = $this->entityLoader->save($entity);
        $this->testEntities[] = $entity;

        $ret = $this->provider->moveEntity(
            $id,
            \EntityProvider::FOLDER_TYPE_EMAIL . "-" . $grpDrafts->id,
            \EntityProvider::FOLDER_TYPE_EMAIL . "-" . $grpInbox->id
        );
        $this->assertTrue($ret);

        $loadedEntity = $this->entityLoader->get("email_message", $id);
        $this->assertEquals($grpInbox->id, $loadedEntity->getValue("mailbox_id"));
    }

    public function testMoveEntity_Appointment()
    {
        $calendar1 = $this->testCalendar;

        // Create a second calendar - first is created in setUp
        $calendar2 = $this->entityLoader->create("calendar");
        $calendar2->setValue("name", "UTest provider 2");
        $calendar2->setValue("user_id", $this->user->getId());
        $this->entityLoader->save($calendar2);
        $this->testEntities[] = $calendar2;

        $entity = $this->entityLoader->create("calendar_event");
        $entity->setValue("calendar", $calendar1->getId());
        $id = $this->entityLoader->save($entity);
        $this->testEntities[] = $entity;

        $ret = $this->provider->moveEntity(
            $id,
            \EntityProvider::FOLDER_TYPE_CALENDAR . "-" . $calendar1->getId(),
            \EntityProvider::FOLDER_TYPE_CALENDAR . "-" . $calendar2->getId()
        );
        $this->assertTrue($ret);

        $loadedEntity = $this->entityLoader->get("calendar_event", $id);
        $this->assertEquals($calendar2->getId(), $loadedEntity->getValue("calendar"));
    }

    /**
     * Make sure entities that do not support moves are not moved
     */
    public function testMoveEntity_Unsupported()
    {
        $this->assertFalse(
            $this->provider->moveEntity(
                1,
                \EntityProvider::FOLDER_TYPE_NOTE . "-my",
                \EntityProvider::FOLDER_TYPE_NOTE . "-new"
            )
        );

        $this->assertFalse(
            $this->provider->moveEntity(
                1,
                \EntityProvider::FOLDER_TYPE_CONTACT . "-my",
                \EntityProvider::FOLDER_TYPE_CONTACT . "-new"
            )
        );

        $this->assertFalse(
            $this->provider->moveEntity(
                1,
                \EntityProvider::FOLDER_TYPE_TASK . "-my",
                \EntityProvider::FOLDER_TYPE_TASK . "-new"
            )
        );
    }

    public function testGetEntityStat()
    {
        $entity = $this->entityLoader->create("calendar_event");
        $entity->setValue("name", "test event for stats");
        $entity->setValue("calendar", $this->testCalendar->getId());
        $id = $this->entityLoader->save($entity);
        $this->testEntities[] = $entity;

        $stat = $this->provider->getEntityStat(
            \EntityProvider::FOLDER_TYPE_CALENDAR . "-" . $this->testCalendar->getId(),
            $id
        );

        $this->assertEquals($id, $stat['id']);
        $this->assertGreaterThan(1, $stat['mod']);
    }

    public function testMarkEntitySeen()
    {
        // Get folders, at least one will be there because we created Inbox in $this->setUp
        $emailFolders = $this->provider->getEmailFolders();
        // Mailboxes are stored in '[obj_type]-[id]' format so get the id beflow
        $folderParts = explode("-", $emailFolders[0]->serverid);
        $mailboxId = $folderParts[1];

        $entity = $this->entityLoader->create("email_message");
        $entity->setValue("flag_seen", false);
        $entity->setValue("mailbox_id", $mailboxId);
        $id = $this->entityLoader->save($entity);
        $this->testEntities[] = $entity;

        $ret = $this->provider->markEntitySeen(
            $emailFolders[1]->serverid,
            $id,
            true
        );
        $this->assertTrue($ret);

        $loadedEntity = $this->entityLoader->get("email_message", $id);
        $this->assertTrue($loadedEntity->getValue("flag_seen"));
    }

    public function testDeleteEntity()
    {
        // Get folders, at least one will be there because we created Inbox in $this->setUp
        $emailFolders = $this->provider->getEmailFolders();
        // Mailboxes are stored in '[obj_type]-[id]' format so get the id beflow
        $folderParts = explode("-", $emailFolders[0]->serverid);
        $mailboxId = $folderParts[1];

        $entity = $this->entityLoader->create("email_message");
        $entity->setValue("subject", "testDeleteEntity in provider");
        $entity->setValue("mailbox_id", $mailboxId);
        $id = $this->entityLoader->save($entity);
        $this->testEntities[] = $entity;

        $ret = $this->provider->deleteEntity(
            $emailFolders[1]->serverid,
            $id
        );
        $this->assertTrue($ret);

        $loadedEntity = $this->entityLoader->get("email_message", $id);
        $this->assertTrue($loadedEntity->getValue("f_deleted"));
    }

    public function testDeleteFolder()
    {
        // Create a grouping to delete
        $groupingsLoader = $this->account->getServiceManager()->get("EntityGroupings_Loader");
        $groupings = $groupingsLoader->get("email_message", "mailbox_id", array("user_id"=>$this->user->getId()));
        $group = $groupings->getByName("Test");
        if (!$group) {
            $group = $groupings->create("Test");
            $group->user_id = $this->user->getId();
            $groupings->add($group);
            $groupingsLoader->save($groupings);
        }

        $ret = $this->provider->deleteFolder(\EntityProvider::FOLDER_TYPE_EMAIL . '-' . $group->id);
        $this->assertTrue($ret);
    }

    public function testSaveSyncFolder()
    {
        // We do not currently support adding a folder
    }

    public function testGetFolder()
    {
        // Get folders, at least one will be there because we created Inbox in $this->setUp
        $emailFolders = $this->provider->getEmailFolders();
        $first = $this->provider->getFolder($emailFolders[0]->serverid);
        $this->assertEquals($first, $emailFolders[0]);

        // Try with 'my' static folder
        $folderId = \EntityProvider::FOLDER_TYPE_TASK . '-my';
        $this->assertEquals(
            $folderId,
            $this->provider->getFolder($folderId)->serverid
        );
    }

    public function testGetAllFolders()
    {
        // Get folder hierarchy
        $folders = $this->provider->getAllFolders();

        $foundNote = false;
        $foundTask = false;
        $foundContact = false;
        $foundCalendar = false;
        $foundEmail = false;

        foreach ($folders as $folder) {
            if ($folder->serverid == \EntityProvider::FOLDER_TYPE_TASK . "-" . "my") {
                $foundTask = true;
            }
            if ($folder->serverid == \EntityProvider::FOLDER_TYPE_CONTACT . "-" . "my") {
                $foundContact = true;
            }
            else {
                // Test all other types that do not have static folders
                $parts = explode("-", $folder->serverid);
                switch ($parts[0])
                {
                    case \EntityProvider::FOLDER_TYPE_EMAIL:
                        $foundEmail = true;
                        break;
                    case \EntityProvider::FOLDER_TYPE_CALENDAR:
                        $foundCalendar = true;
                        break;
                    case \EntityProvider::FOLDER_TYPE_NOTE:
                        $foundNote = true;
                        break;
                }
            }
        }

        // Make sure our folders existed
        $this->assertTrue($foundNote);
        $this->assertTrue($foundTask);
        $this->assertTrue($foundContact);
        $this->assertTrue($foundCalendar);
        $this->assertTrue($foundEmail);
    }

}
