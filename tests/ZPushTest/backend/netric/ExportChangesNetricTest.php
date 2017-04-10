<?php
namespace ZPushTest\backend\netric;

use PHPUnit_Framework_TestCase;

// Add all z-push required files
require_once("z-push.includes.php");

// Include config
require_once(dirname(__FILE__) . '/../../../../config/zpush.config.php');

// Include backend classes
require_once('backend/netric/netric.php');
require_once('backend/netric/exportchangesnetric.php');
require_once('backend/netric/entityprovider.php');

class ExportChangesNetricTest extends PHPUnit_Framework_TestCase
{
    /**
     * Logger interface
     *
     * @var \Netric\Log
     */
    private $log = null;

    /**
     * Sync collection mock d
     *
     * @var \Netric\EntitySync\Collection\CollectionInterface
     */
    private $collection = null;

    /**
     * Entity provider mock
     *
     * @var \EntityProvider
     */
    private $entityProvider = null;


    /**
     * Folder id for testing
     *
     * For the netric backend in z-push, the folder id is a string with two parts:
     * [obj_type]-[id] so if we are referincing an email grouping with an id if 1,
     * it would look like 'email_message-1'.
     *
     * @var string
     */
    private $folderId = null;

    protected function setUp()
    {
        $this->log = $this->getMockBuilder('\Netric\Log')
                          ->disableOriginalConstructor()
                          ->getMock();

        $this->collection = $this->getMockBuilder(
            '\Netric\EntitySync\Collection\CollectionInterface'
        )->getMock();

        $this->entityProvider = $this->getMockBuilder('\EntityProvider')
                                     ->disableOriginalConstructor()
                                     ->getMock();

        $this->folderId = \EntityProvider::FOLDER_TYPE_TASK . "-test";
    }

    public function testInitializeExporter()
    {
        $exporter = new \ExportChangeNetric(
            $this->log,
            $this->collection,
            $this->entityProvider,
            $this->folderId
        );

        // Create an in-memory importer
        $importer = new \ChangesMemoryWrapper();

        // Initialize
        $ret = $exporter->InitializeExporter($importer);
        $this->assertTrue($ret);
    }

    public function testGetChangeCount()
    {
        $exporter = new \ExportChangeNetric(
            $this->log,
            $this->collection,
            $this->entityProvider,
            $this->folderId
        );

        // Create an in-memory importer
        $importer = new \ChangesMemoryWrapper();

        // Create fake sync data
        $syncStats = array(
            array(
                "id" => 1,
                "action" => 'change',
                "commit_id" => 2,
            )
        );
        $this->collection->method('getExportChanged')->willReturn($syncStats);

        // Initialize
        $exporter->InitializeExporter($importer);

        // Make sure the changes have been registered
        $this->assertEquals(1, $exporter->GetChangeCount());
    }

    public function testSynchronize()
    {
        // Have the entity provider return a SyncTask
        $syncTask = new \SyncTask();
        $syncTask->flags = 0;
        $syncTask->subject = "test task";
        $this->entityProvider->method('getSyncObject')->willReturn($syncTask);

        // Create exporter
        $exporter = new \ExportChangeNetric(
            $this->log,
            $this->collection,
            $this->entityProvider,
            $this->folderId
        );

        // Create an in-memory importer
        $importer = new \ChangesMemoryWrapper();

        // Create fake sync data
        $syncStats = array(
            array(
                "id" => 1,
                "action" => 'change',
                "commit_id" => 2,
            ),
            array(
                "id" => 3,
                "action" => 'delete',
                "commit_id" => 4,
            )
        );
        $this->collection->method('getExportChanged')->willReturn($syncStats);

        // Config by passing a state that assumed we had previously imported id 3
        $exporter->Config(array(array('id' => 3, 'flags' => 0, 'mod' => 2,)));

        // Initialize
        $exporter->InitializeExporter($importer);

        // Synchronize - first pass should get change
        $result = $exporter->Synchronize();

        // If a change is made it should return steps and progress
        $this->assertEquals(array("steps" => 2, "progress" => 1), $result);

        // Synchronize again - should not get delete
        $result = $exporter->Synchronize();

        // If a change is made it should return steps and progress
        $this->assertEquals(array("steps" => 2, "progress" => 2), $result);

        // Make sure the importer was sent the change for id 1 from the collection
        $this->assertTrue($importer->IsChanged($syncStats[0]['id']));

        // Make sure the importer was sent the delete from id 3 from the collection
        $this->assertTrue($importer->IsDeleted($syncStats[1]['id']));

        // Calling Synchronize  a second time should return false since there are no changes
        $this->assertFalse($exporter->Synchronize());

        // Check that the state was updated
        $expectedState = array(
            array (
                'type' => 'change',
                'id' => 1,
                'flags' => 0,
                'mod' => 2,
            )
        );
        $this->assertEquals($expectedState, $exporter->GetState());
    }
}