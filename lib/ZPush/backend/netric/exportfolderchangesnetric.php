<?php
/**
 * Netric exporter handles exporting folder changes from netric to the device
 *
 * The reason all the files are lowercase in here is because that is the z-push standard
 * so we stick with it to be consistent.
 */
$zPushRoot = dirname(__FILE__) ."/../../";

// Interfaces we are extending
require_once($zPushRoot . 'lib/interface/iexportchanges.php');

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
 * Handle exporting folder changes from netric to the device
 */
class ExportFolderChangeNetric extends ChangesNetric implements IExportChanges
{
    /**
     * The current step we are processing
     *
     * @var int
     */
    private $step = 0;

    /**
     * Imporer
     *
     * @var ImportChangesNetric
     */
    private $importer = null;

    /**
     * Netric log
     *
     * @var Netric\Log
     */
    private $log = null;

    /**
     * Array of changes to import
     *
     * @var array('id', 'type'=>'change'|'delete', 'flags', 'mod')
     */
    private $changes = array();

    /**
     * Constructor
     *
     * @param Netric\Log $log Logger for recording what is going on
     * @param EntityProvider $entityProvider Write and read entities from netric
     */
    public function __construct(
        Netric\Log $log,
        EntityProvider $entityProvider
    )
    {
        $this->log = $log;
        $this->provider = $entityProvider;
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
        ZLog::Write(LOGLEVEL_DEBUG, "InitializeExporter Initializing");
        $this->changes = array();
        $this->step = 0;
        $this->importer = $importer;

        // Get folder hierarchy
        $folders = $this->provider->getAllFolders();

        // Convert the folders to stats
        $hierarchy = array();
        foreach ($folders as $folder) {
            $hierarchy[] = array(
                "id" => $folder->serverid,
                "flags" => 0,
                "mod" => $folder->displayname
            );
        }

        // Get a diff of any changes made compared to the state from last sync
        $this->changes = $this->getDiffTo($hierarchy);

        ZLog::Write(LOGLEVEL_INFO, "ExportFolderChangeNetric:InitializeExporter Got hierarchy with " . count($this->changes) . " changes");

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
        if($this->step < count($this->changes))  {
            $change = $this->changes[$this->step];

            switch($change["type"])
            {
                case "change":
                    $folder = $this->provider->getFolder($change["id"]);

                    // The folder was apparently deleted between the time we changed and now
                    if(!$folder) {
                        throw new StatusException("The folder {$change['id']} could not be opened");
                    }

                    if($this->flags & BACKEND_DISCARD_DATA || $this->importer->ImportFolderChange($folder))
                    {
                        $this->updateState(
                            $change["type"],
                            array(
                                "type" => $change['type'],
                                "parent" => $folder->parentid,
                                "id" => $change['id'],
                                "mod" => $folder->displayname
                            )
                        );
                    }
                    break;

                case "delete":
                    if($this->flags & BACKEND_DISCARD_DATA || $this->importer->ImportFolderDeletion($change["id"]))
                    {
                        // Delete action only requires id in the stat data
                        $this->updateState(
                            $change["type"],
                            array("id" => $change['id'])
                        );
                    }
                    break;
                default:
                    // Not supported
                    throw new StatusException("Sync type {$change['type']} not supported");

            }


            $this->step++;

            return array(
                "steps" => count($this->changes),
                "progress" => $this->step
            );
        } else {
            return false;
        }

    }

    /**----------------------------------------------------------------------------------------------------------
     * DiffState specific stuff
     */

    /**
     * Comparing function used for sorting of the differential engine
     *
     * @param array $a
     * @param array $b
     * @return boolean
     */
    static public function RowCmp($a, $b) {
        // TODO implement different comparing functions
        return $a["id"] < $b["id"] ? 1 : -1;
    }

    /**
     * Differential mechanism compares the current syncState to the sent $new
     *
     * This is only used for folder hierarchy since we have to combine them
     * and no single netric sync collection will contain all the changes.
     *
     * @param array $new
     * @return array
     */
    private function getDiffTo($new)
    {
        $changes = array();

        // Sort both arrays in the same way by ID
        usort($this->syncState, array("ExportFolderChangeNetric", "RowCmp"));
        usort($new, array("ExportFolderChangeNetric", "RowCmp"));

        $inew = 0;
        $iold = 0;

        // Get changes by comparing our list of messages with
        // our previous state
        while(1) {
            $change = array();

            if($iold >= count($this->syncState) || $inew >= count($new))
                break;

            if($this->syncState[$iold]["id"] == $new[$inew]["id"]) {
                // Both messages are still available, compare flags and mod
                if(isset($this->syncState[$iold]["flags"]) && isset($new[$inew]["flags"]) && $this->syncState[$iold]["flags"] != $new[$inew]["flags"]) {
                    // Flags changed
                    $change["type"] = "flags";
                    $change["id"] = $new[$inew]["id"];
                    $change["flags"] = $new[$inew]["flags"];
                    $changes[] = $change;
                }

                if($this->syncState[$iold]["mod"] != $new[$inew]["mod"]) {
                    $change["type"] = "change";
                    $change["id"] = $new[$inew]["id"];
                    $changes[] = $change;
                }

                $inew++;
                $iold++;
            } else {
                if($this->syncState[$iold]["id"] > $new[$inew]["id"]) {
                    // Message in state seems to have disappeared (delete)
                    $change["type"] = "delete";
                    $change["id"] = $this->syncState[$iold]["id"];
                    $changes[] = $change;
                    $iold++;
                } else {
                    // Message in new seems to be new (add)
                    $change["type"] = "change";
                    $change["flags"] = SYNC_NEWMESSAGE;
                    $change["id"] = $new[$inew]["id"];
                    $changes[] = $change;
                    $inew++;
                }
            }
        }

        while($iold < count($this->syncState)) {
            // All data left in 'syncstate' have been deleted
            $change["type"] = "delete";
            $change["id"] = $this->syncState[$iold]["id"];
            $changes[] = $change;
            $iold++;
        }

        while($inew < count($new)) {
            // All data left in new have been added
            $change["type"] = "change";
            $change["flags"] = SYNC_NEWMESSAGE;
            $change["id"] = $new[$inew]["id"];
            $changes[] = $change;
            $inew++;
        }

        return $changes;
    }
}
