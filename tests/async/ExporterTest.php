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

class ExporterTest extends PHPUnit_Framework_TestCase
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
	 * Test initialize with a dummy importer
	 *
	 * @group testInitializeExporter
	 */
	public function testInitializeExporter()
	{
		$exporter = new ExportChangesAnt($this->backend, "Inbox"); //$this->backend->GetExporter("Inbox"); // contact
		$contentparameters = new ContentParameters();
		$exporter->ConfigContentParameters($contentparameters);

		// Create memory importer for testing
		$importer = new ChangesMemoryWrapper();

		// Initialize and remove any stats including the one we created above
		$exporter->collection->cutoffdate = strtotime("-1 day");
		$exporter->collection->fInitialized = true;
		$exporter->collection->resetStats();
		$exporter->collection->save(); // Save so fInitialized is saved for CAntObject::save below

		// Create a dummy email message
		$obj = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$obj->setValue("subject", "Test message");
		$obj->setValue("flag_seen", 'f');
		$obj->setValue("owner_id", $this->user->id);
		$obj->setGroup("Inbox");
		$mid = $obj->save();

		// Initialize exporter
		$ret = $exporter->InitializeExporter($importer);
		$this->assertTrue($ret);
		$this->assertEquals($exporter->GetChangeCount(), 1);

		// Cleanup
		$obj->removeHard();
	}

	/**
	 * Test initialize with a dummy importer for hierarchy
	 *
	 * @group testInitializeExporterHierarchy
	 */
	public function TODO_testInitializeExporterHierarchy()
	{
		$exporter = new ExportChangesAnt($this->backend, false); // folders
		$contentparameters = new ContentParameters();
		$exporter->ConfigContentParameters($contentparameters);

		// Create memory importer for testing
		$importer = new ChangesMemoryWrapper();

		// Initialize and remove any stats including the one we created above
		$exporter->collection->fInitialized = false;
		$exporter->collection->resetStats();

		// Initialize exporter
		$ret = $exporter->InitializeExporter($importer);
		$this->assertTrue($ret);
		$this->assertTrue($exporter->GetChangeCount() > 1);

		// Check and see if contacts_root is there (if it is then the others will be too)
		$found = false;
		foreach ($exporter->changes as $change)
		{
			if ($change['id'] == "contacts_root")
				$found = true;
		}
		$this->assertTrue($found);

		// Now initialize again but the collection should be initlaized and render zero changes
		$exporter->collection->resetStats();
		$ret = $exporter->InitializeExporter($importer);
		$this->assertTrue($ret);
		$this->assertEquals(0, $exporter->GetChangeCount());
	}

	/**
	 * Test synchronization of objects
	 *
	 * @group testSynchronize
	 */
	public function testSynchronize()
	{
		$exporter = new ExportChangesAnt($this->backend, "Inbox");
		$contentparameters = new ContentParameters();
		$exporter->ConfigContentParameters($contentparameters);

		// Create memory importer for testing
		$importer = new ChangesMemoryWrapper();

		// Initialize and remove any stats including the one we created above
		$exporter->collection->cutoffdate = strtotime("-1 day");
		$exporter->collection->fInitialized = true;
		$exporter->collection->resetStats();
		$exporter->collection->save(); // Save so fInitialized is saved for CAntObject::save below

		// Create a dummy email message
		$obj = CAntObject::factory($this->dbh, "email_message", null, $this->user);
		$obj->setValue("subject", "Test message");
		$obj->setValue("flag_seen", 'f');
		$obj->setValue("owner_id", $this->user->id);
		$obj->setGroup("Inbox");
		$mid = $obj->save();

		// Initialize exporter
		$ret = $exporter->InitializeExporter($importer);
		$progress = $exporter->Synchronize();
		$this->assertEquals(1, $progress["steps"]);
		$this->assertEquals(1, $progress["progress"]);
		$this->assertTrue($importer->IsChanged($mid));

		// Now delete the message
		$obj->remove();
		$ret = $exporter->InitializeExporter($importer);
		$progress = $exporter->Synchronize();
		$this->assertEquals(1, $progress["steps"]);
		$this->assertEquals(1, $progress["progress"]);
		$this->assertTrue($importer->IsDeleted($mid));

		// Cleanup
		$obj->remove();
	}

	/**
	 * Test synchronization of folders
	 *
	 * @group testSynchronizeHierarchy
	 */
	public function INPROGRESS_testSynchronizeHierarchy()
	{
		$exporter = new ExportChangesAnt($this->backend, false);
		$contentparameters = new ContentParameters();
		$exporter->ConfigContentParameters($contentparameters);
		$obj = CAntObject::factory($this->dbh, "email_message", null, $this->user);

		// Delete test grouping if it already exists
		$grp = $obj->getGroupingEntryByPath("mailbox_id", "Inbox/testSynchronize");
		$obj->deleteGroupingEntry("mailbox_id", $grp['id']);

		// Create memory importer for testing
		$importer = new ChangesMemoryWrapper();

		// Initialize and remove any stats including the one we created above
		$exporter->collection->cutoffdate = strtotime("-1 day");
		$exporter->collection->fInitialized = true;
		$exporter->collection->resetStats();
		$exporter->collection->save(); // Save so fInitialized is saved for CAntObject::save below

		// Create a new mailbox
		$grp = $obj->addGroupingEntry("mailbox_id", "Inbox/testSynchronize");

		// Initialize exporter
		$ret = $exporter->InitializeExporter($importer);
		$progress = $exporter->Synchronize();
		$this->assertEquals(1, $progress["steps"]);
		$this->assertEquals(1, $progress["progress"]);

		// Now delete the message
		$obj->deleteGroupingEntry("mailbox_id", $grp['id']);
		$ret = $exporter->InitializeExporter($importer);
		$progress = $exporter->Synchronize();
		$this->assertEquals(1, $progress["steps"]);
		$this->assertEquals(1, $progress["progress"]);
	}
}
