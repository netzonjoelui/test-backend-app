<?php
/**
 * Netric exporter handles exporting entity changes to the device
 *
 * The reason all the files are lowercase in here is because that is the z-push standard
 * so we stick with it to be consistent.
 */
$zPushRoot = dirname(__FILE__) ."/../../";

// Interfaces
require_once($zPushRoot . 'lib/interface/iexportchanges.php');
require_once($zPushRoot . 'lib/interface/iimportchanges.php');

// Supporting files and exceptions
require_once($zPushRoot . 'lib/core/zpush.php');
require_once($zPushRoot . 'lib/request/request.php');
require_once($zPushRoot . 'lib/exceptions/authenticationrequiredexception.php');
require_once($zPushRoot . 'lib/exceptions/statusexception.php');

// Local backend files
require_once($zPushRoot . 'backend/netric/changesnetric.php');
require_once($zPushRoot . 'backend/netric/entityprovider.php');

// Include netric autoloader for all netric libraries
require_once(dirname(__FILE__)."/../../../../init_autoloader.php");

/**
 * Handle exporting changes from netric to the device
 */
class ExportChangeNetric extends ChangesNetric implements IExportChanges
{
    /**
     * The current step we are processing
     *
     * @var int
     */
    private $step = 0;

    /**
     * Importer to send changes to the device
     *
     * @var IImportChanges
     */
    private $importer = null;

    /**
     * Netric log
     *
     * @var Netric\Log
     */
    private $log = null;

    /**
     * EntitySync collection that keeps track of changes
     *
     * @var \Netric\EntitySync\Collection\CollectionInterface
     */
    private $collection = null;

    /**
     * Array of changes to import
     *
     * @var array('id', 'type'=>'change'|'delete', 'flags', 'mod')
     */
    private $changes = array();

    /**
     * The unique id of the folder we are synchronizing
     *
     * @var string
     */
    public $folderId = false;

    /**
     * Constructor
     *
     * @param Netric\Log $log Logger for recording what is going on
     * @param Netric\EntitySync\Collection\CollectionInterface $collection Track changes
     * @param EntityProvider $entityProvider Write and read entities from netric
     * @param string $folderId
     */
    public function __construct(
        Netric\Log $log,
        Netric\EntitySync\Collection\CollectionInterface $collection,
        EntityProvider $entityProvider,
        $folderId = null
    )
    {
        $this->log = $log;
        $this->collection = $collection;
        $this->provider = $entityProvider;
        $this->folderId = $folderId;
    }

    /**
     * Sets the importer where the exporter will sent its changes to
     *
     * This exporter should also be ready to accept calls after this
     *
     * @param object        &$importer      Implementation of IImportChanges
     *
     * @access public
     * @return boolean
     * @throws StatusException
     */
    public function InitializeExporter(&$importer)
    {
        $this->changes = array();
        $this->step = 0;
        $this->importer = $importer;

        if(!$this->folderId) {
            throw new StatusException("Tried to use ExportChangesNetric for hierarchy. Class should have been ExportFolderChangesnetric");
        }

        // Do nothing if it is a dummy folder
        if ($this->folderId != SYNC_FOLDER_TYPE_DUMMY)
        {
            // Check for cutoff date
            $cutoffDate = null;
            if ($this->cutoffDate) {
                $cutoffDate = new \DateTime();
                $cutoffDate->setTimestamp($this->cutoffDate);
            }

            // Second param does not fast-forward the collection so we have to do it manually
            $this->changes = $this->collection->getExportChanged(false, $cutoffDate);
        }

         ZLog::Write(LOGLEVEL_DEBUG, "ExportChangeNetric:InitializeExporter Initialized {$this->folderId} with " . count($this->changes) . " content changes");

        return true;
    }

    /**
     * Returns the amount of changes to be exported
     *
     * @access public
     * @return int
     */
    public function GetChangeCount()
    {
         ZLog::Write(LOGLEVEL_INFO, "ExportChangeNetric:GetChagesCount: returning " . count($this->changes) . " changes");
        return count($this->changes);
    }

    /**
     * Synchronizes a change to the configured importer
     *
     * @throws StatusException if a sync action is not supported
     * @return array|bool Array with status data if success, false if there are no changes
     */
    public function Synchronize()
    {
        // Get one of our stored changes and send it to the importer, store the new state it succeeds
        if($this->step < count($this->changes)) {
            $change = $this->changes[$this->step];

            switch($change["action"])
            {
                case "change":
                    // Note: because 'parseMessage' and 'statMessage' are two seperate
                    // calls, we have a chance that the message has changed between both
                    // calls. This may cause our algorithm to 'double see' changes.
                    $message = $this->provider->getSyncObject($this->folderId, $change["id"], $this->contentParameters);

                    /*
                     * If this message was never before sent to the device (not in $this->syncState)
                     * then we should set teh flags to a special 'NewMessage' flag which will change
                     * the tag name when sending results back to the device.
                     */
                    if (!$this->foundInSyncState($change['id'])) {
                        $message->flags = SYNC_NEWMESSAGE;
                    }

                    // Make sure the message is valid and has all the fields needed
                    if (!$message->Check(true)) {
                        ZLog::Write(LOGLEVEL_ERROR, "ExportChangeNetric->Synchronize: {$change['id']} is invalid syncObject");
                    }

                    if($message) {
                        if($this->flags & BACKEND_DISCARD_DATA || $this->importer->ImportMessageChange($change["id"], $message) == true) {
                            // Update the collection to set change to successfully exported
                            $this->collection->setLastCommitId($change['commit_id']);
                            $this->collection->logExported($change['id'], $change['commit_id']);
                             ZLog::Write(LOGLEVEL_DEBUG, "ExportChangeNetric->Synchronize exported change {$change['id']} commit " . $change['commit_id']);
                        }
                    } else {
                        // Looks like the message was deleted, do not try to export again
                        ZLog::Write(LOGLEVEL_WARN, "ExportChangeNetric->Synchronize: Could not load {$change['id']} for folder: " . $this->folderId);
                    }
                    break;

                case "delete":
                    // Entity was deleted
                    if ($this->foundInSyncState($change['id'])) {
                        if(
                            $this->flags & BACKEND_DISCARD_DATA ||
                            $this->importer->ImportMessageDeletion($change["id"]) == true
                        )
                        {

                            $this->collection->logExported($change['id'], null);
                            ZLog::Write(LOGLEVEL_INFO, "ExportChangeNetric->Synchronize: exported delete {$change['id']}");
                        }
                    } else {
                        ZLog::Write(LOGLEVEL_INFO, "ExportChangeNetric->Synchronize: stale in netric but never sent to device {$change['id']}");
                    }

                    break;
                default:
                    // Not supported
                    throw new StatusException("Sync action {$change['action']} not supported");
            }

            // Update syncStates with this change
            $this->updateState(
                $change["action"],
                array(
                    "type" => $change['action'],
                    "id" => $change['id'],
                    "flags"=> 0,
                    "mod" => (isset($change['commit_id'])) ? $change['commit_id'] : 0
                )
            );

            $this->step++;

             ZLog::Write(LOGLEVEL_INFO, "ExportChangeNetric->Synchronize: synchronized {$this->step} of " . count($this->changes));

            return array(
                "steps" => count($this->changes),
                "progress" => $this->step
            );
        } else {
            // No changes left, the's fast-forward to th
            return false;
        }
    }
}
