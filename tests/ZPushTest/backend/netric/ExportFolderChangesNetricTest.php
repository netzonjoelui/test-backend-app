<?php
namespace ZPushTest\backend\netric;

use PHPUnit_Framework_TestCase;

// Add all z-push required files
require_once("z-push.includes.php");

// Include config
require_once(dirname(__FILE__) . '/../../../../config/zpush.config.php');

// Include backend classes
require_once('backend/netric/netric.php');
require_once('backend/netric/entityprovider.php');

class ExportFolderChagesNetricTest extends PHPUnit_Framework_TestCase
{
    /**
     * Logger interface
     *
     * @var \Netric\Log
     */
    private $log = null;

    /**
     * Entity provider mock
     *
     * @var \EntityProvider
     */
    private $entityProvider = null;


    protected function setUp()
    {
        $this->log = $this->getMockBuilder('\Netric\Log')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityProvider = $this->getMockBuilder('\EntityProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testInitializeExporter()
    {
        $exporter = new \ExportFolderChangeNetric(
            $this->log,
            $this->entityProvider
        );

        // Create an in-memory importer
        $importer = new \ChangesMemoryWrapper();

        // Create fake folder to return
        $syncFolder = new \SyncFolder();
        $syncFolder->serverid = 'test';
        $syncFolder->displayname = "Test";
        $this->entityProvider->method('getAllFolders')->willReturn(array($syncFolder));

        // Initialize
        $ret = $exporter->InitializeExporter($importer);
        $this->assertTrue($ret);
    }

    public function testGetChangeCount()
    {
        $exporter = new \ExportFolderChangeNetric(
            $this->log,
            $this->entityProvider
        );

        // Create an in-memory importer
        $importer = new \ChangesMemoryWrapper();

        // Create fake folder to return
        $syncFolder = new \SyncFolder();
        $syncFolder->serverid = 'test';
        $syncFolder->displayname = "Test";
        $this->entityProvider->method('getAllFolders')->willReturn(array($syncFolder));

        // Initialize
        $exporter->InitializeExporter($importer);

        // Make sure the changes have been registered
        $this->assertEquals(1, $exporter->GetChangeCount());
    }

    public function testSynchronize()
    {
        // Have the entity provider return a SyncFolder
        $syncFolder = new \SyncFolder();
        $syncFolder->serverid = 'test';
        $syncFolder->displayname = "Test";
        $this->entityProvider->method('getFolder')->willReturn($syncFolder);

        // Create exporter
        $exporter = new \ExportFolderChangeNetric(
            $this->log,
            $this->entityProvider
        );

        // Create an in-memory importer
        $importer = new \ChangesMemoryWrapper();

        // Create fake folder to return for getAllFolders
        $syncFolder = new \SyncFolder();
        $syncFolder->serverid = 'test';
        $syncFolder->displayname = "Test";
        $this->entityProvider->method('getAllFolders')->willReturn(array($syncFolder));

        // Config by passing a state that assumed we had previously imported a different folder
        $syncFolderDelete = new \SyncFolder();
        $syncFolderDelete->serverid = 'testdel';
        $syncFolderDelete->displayname = "DelTest";
        $exporter->Config(array(array('id' => $syncFolderDelete->serverid, 'flags' => 0)));

        // Add to old state in the memory so it can delete it
        $importer->AddFolder($syncFolderDelete);

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

        // Make sure the importer delete was called for folder 'test'
        $this->assertTrue($importer->IsChanged($syncFolder));

        // Make sure the number of changes is right
        $this->assertEquals(2, $importer->GetChangeCount());

        // Calling Synchronize  a second time should return false since there are no changes
        $this->assertFalse($exporter->Synchronize());

        // Check that the state was updated
        $expectedState = array(
            array (
                'type' => 'change',
                'id' => 'test',
                'mod' => 'Test',
                'parent' => null
            )
        );
        $this->assertEquals($expectedState, $exporter->GetState());
    }
}