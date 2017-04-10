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
require_once('backend/netric/importchangesnetric.php');

class ImportChangesNetricTest extends PHPUnit_Framework_TestCase
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

    /**
     * Importer to test
     *
     * @var \ImportChangesNetric
     */
    private $importer = null;

    /**
     * Get the tests ready to run
     */
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

        $this->importer = new \ImportChangesNetric(
            $this->log,
            $this->collection,
            $this->entityProvider,
            $this->folderId
        );
    }

    public function testLoadConflicts()
    {
        $this->assertTrue($this->importer->LoadConflicts(null, null));
    }

    public function testImportMessageChange()
    {
        // Have the entity provider return a fake object id
        $this->entityProvider->method('saveSyncObject')->willReturn(123);

        // Have the entity provider return a fake entity state for the fake object
        $syncStat = array('id'=>123, 'flags'=>0, 'mod'=>2);
        $this->entityProvider->method('getEntityStat')->willReturn($syncStat);

        // Have the entity provider return a SyncTask
        $syncTask = new \SyncTask();
        $syncTask->flags = 0;
        $syncTask->subject = "test task";
        $this->importer->ImportMessageChange(123, $syncTask);

        // Check to make sure the sync worked
        $this->assertEquals(array($syncStat), $this->importer->GetState());
    }

    public function testImportMessageDeletion()
    {
        // Assume deleting the entity works
        $this->entityProvider->method('deleteEntity')->willReturn(true);

        // Have the entity provider return a fake entity state for the fake object
        $syncStat = array('id'=>123, 'flags'=>0, 'mod'=>2);
        $this->entityProvider->method('getEntityStat')->willReturn($syncStat);

        $this->assertTrue($this->importer->ImportMessageDeletion(123));
    }

    public function testImportMessageReadFlag()
    {
        // Assume deleting the entity works
        $this->entityProvider->method('markEntitySeen')->willReturn(true);

        $this->assertTrue($this->importer->ImportMessageReadFlag(123, 0));
    }

    public function testImportMessageMove()
    {
        $this->entityProvider->method('moveEntity')->willReturn(true);
        $this->assertTrue($this->importer->ImportMessageMove(123, 'newfoldertest'));
    }

    public function testImportFolderChange()
    {
        // Have the entity provider return a fake object id
        $this->entityProvider->method('saveSyncFolder')->willReturn(123);

        // Have the entity provider return a SyncTask
        $syncFolder = new \SyncFolder();
        $syncFolder->serverid = 123;
        $syncFolder->parentid = null;
        $syncFolder->displayname = "My Tasks";
        $syncFolder->type = SYNC_FOLDER_TYPE_TASK;
        $this->importer->ImportFolderChange($syncFolder);

        // Check to make sure the sync worked
        $syncStat = array('id'=>123, 'flags'=>0, 'mod'=>"My Tasks", "parent"=>null);
        $this->assertEquals(array($syncStat), $this->importer->GetState());
    }

    public function testImportFolderDeletion()
    {
        // Assume deleting the folder works
        $this->entityProvider->method('deleteFolder')->willReturn(true);

        // Have the provider return a folder
        $syncFolder = new \SyncFolder();
        $syncFolder->serverid = 123;
        $syncFolder->parentid = null;
        $syncFolder->displayname = "OtherFolder";
        $syncFolder->type = SYNC_FOLDER_TYPE_OTHER;
        $this->entityProvider->method('getFolder')->willReturn($syncFolder);

        $this->assertTrue($this->importer->ImportFolderDeletion(123));
    }
}