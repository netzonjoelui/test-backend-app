<?php
require_once 'PHPUnit/Autoload.php';
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

class ImporterTest extends PHPUnit_Framework_TestCase
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
	 * Test device sending a change to to Netric
	 *
	 * @group testImportMessageChange
	 */
	public function testImportMessageChange()
	{
		$importer = new ImportChangesAnt($this->backend, "contacts_root");

		// Initialize and remove any leftover stats
		$importer->collection->fInitialized = true;
		$importer->collection->resetStats();
		$importer->collection->save(); // Save so fInitialized is saved for CAntObject::save below

		// Import new contact
		$contact = new SyncContact();
		$contact->firstname = "First";
		$contact->lastname = "Version";
		$cid = $importer->ImportMessageChange("", $contact);
		$this->assertTrue(is_numeric($cid));

		$obj = CAntObject::factory($this->dbh, "contact_personal", $cid, $this->user);
		$this->assertEquals($obj->getValue("first_name"), $contact->firstname);
		$this->assertEquals($obj->getValue("last_name"), $contact->lastname);

		// Now make changes to the contact and import
		$contact = new SyncContact();
		$contact->firstname = "Second";
		$contact->lastname = "Version";
		$cid = $importer->ImportMessageChange($cid, $contact);

		$obj = CAntObject::factory($this->dbh, "contact_personal", $cid, $this->user);
		$this->assertEquals($obj->getValue("first_name"), $contact->firstname);
		$this->assertEquals($obj->getValue("last_name"), $contact->lastname);

		// Make sure the newly imported contact was not added to outgoing stats
		// so we can prevent an ugly sync loop.
		$changes = $importer->collection->getChangedObjects();
		$this->assertEquals(0, count($changes));

		// Cleanup
		$obj->removeHard();
	}


	/**
	 * Test device deleting from Netric
	 *
	 * @group testImportMessageDeletion
	 */
	public function testImportMessageDeletion()
	{
		$importer = new ImportChangesAnt($this->backend, "contacts_root");

		// Initialize and remove any leftover stats
		$importer->collection->fInitialized = true;
		$importer->collection->resetStats();
		$importer->collection->save(); // Save so fInitialized is saved for CAntObject::save below

		// First import a new contact
		$contact = new SyncContact();
		$contact->firstname = "First";
		$contact->lastname = "Version";
		$cid = $importer->ImportMessageChange("", $contact);
		$this->assertTrue(is_numeric($cid));

		$obj = CAntObject::factory($this->dbh, "contact_personal", $cid, $this->user);
		$this->assertEquals($obj->getValue("first_name"), $contact->firstname);
		$this->assertEquals($obj->getValue("last_name"), $contact->lastname);

		// Now simulate device deleting the contact
		$ret = $importer->ImportMessageDeletion($cid);
		$this->assertTrue($ret);

		$obj = CAntObject::factory($this->dbh, "contact_personal", $cid, $this->user);
		$this->assertTrue($obj->isDeleted());

		// Make sure the newly dekete contact was not added to outgoing stats
		$changes = $importer->collection->getChangedObjects();
		$this->assertEquals(0, count($changes));

		// Cleanup
		$obj->remove();
	}

	/**
	 * Test marking a message as read
	 *
	 * @group ImportMessageReadFlag
	 */
	public function testImportMessageReadFlag()
	{
		$importer = new ImportChangesAnt($this->backend, "Inbox");
		$importer->collection->cutoffdate = strtotime("-1 day");

		// First create a dummy email message and put it in the inbox
		$obj = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$obj->setValue("subject", "Test message");
		$obj->setValue("flag_seen", 'f');
		$obj->setGroup("Inbox");
		$mid = $obj->save();

		// Initialize and remove any stats including the one we created above
		$importer->collection->fInitialized = true;
		$importer->collection->resetStats();
		$importer->collection->save(); // Save so fInitialized is saved for CAntObject::save below

		// Now simulate device marking this message as read
		$ret = $importer->ImportMessageReadFlag($mid);
		$this->assertTrue($ret);

		$obj = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals('t', $obj->getValue("flag_seen"));

		// Make sure the update is not circular in the stats
		$changes = $importer->collection->getChangedObjects();
		$this->assertEquals(0, count($changes));

		// Cleanup
		$obj->remove();
	}

	/**
	 * Test moving a message
	 *
	 * @group ImportMessageMove
	 */
	public function testImportMessageMove()
	{
		$importer = new ImportChangesAnt($this->backend, "Inbox");
		$importer->collection->cutoffdate = strtotime("-1 day");

		// First create a dummy email message and put it in the inbox
		$obj = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$obj->setValue("subject", "Test message");
		$obj->setValue("flag_seen", 'f');
		$obj->setGroup("Inbox");
		$mid = $obj->save();

		// Create additional mailbox
		$newMailbox = $obj->addGroupingEntry("mailbox_id", "Inbox/testImportMessageMove");

		// Initialize and remove any stats including the one we created above
		$importer->collection->fInitialized = true;
		$importer->collection->resetStats();
		$importer->collection->save(); // Save so fInitialized is saved for CAntObject::save below

		// Now simulate device moving this message
		$ret = $importer->ImportMessageMove($mid, "Inbox.testImportMessageMove");
		$this->assertTrue($ret);

		$obj = CAntObject::factory($this->dbh, "email_message", $mid, $this->user);
		$this->assertEquals($newMailbox['id'], $obj->getValue("mailbox_id"));

		// Make sure the update is not circular in the stats
		$changes = $importer->collection->getChangedObjects();
		$this->assertEquals(0, count($changes));

		// Cleanup
		$obj->deleteGroupingEntry("mailbox_id", $newMailbox['id']);
		$obj->removeHard();
	}
}
