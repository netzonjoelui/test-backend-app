<?php
/**
 * Netric importer handles importing changes from the mobile device into netric
 *
 * The reason all the files are lowercase in here is because that is the z-push standard
 * so we stick with it to be consistent.
 */
$zPushRoot = dirname(__FILE__) ."/../../";

// Interfaces we are extending
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
 * Handle importing changes from the remote device to netric
 *
 * This is our local importer. Tt receives data from the PDA, for contents and hierarchy changes.
 * It must therefore receive the incoming data and convert it into netric objects.
 */
class ImportChangesNetric extends ChangesNetric implements IImportChanges
{
    /**
     * The unique id of the folder we are synchronizing
     *
     * @var string
     */
    private $folderId = false;

    /**
     * EntitySync collection that keeps track of changes
     *
     * @var \Netric\EntitySync\Collection\CollectionInterface
     */
    private $collection = null;

    /**
     * Netric log
     *
     * @var Netric\Log
     */
    private $log = null;

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

    // Import interface functions
    // ==================================================================================

    /**
     * Loads objects which are expected to be exported with the state
     * Before importing/saving the actual message from the mobile, a conflict detection should be done
     *
     * @param ContentParameters         $contentparameters
     * @param string                    $state
     *
     * @access public
     * @return boolean
     * @throws StatusException
     */
    public function LoadConflicts($contentparameters, $state)
    {
        return true;
    }

    /**
     * Imports a single message
     *
     * @param string        $id
     * @param SyncObject    $message
     *
     * @access public
     * @return boolean/string               failure / id of message
     * @throws StatusException
     */
    public function ImportMessageChange($id, $message)
    {
        // Do nothing if it is in a dummy folder
        if ($this->folderId == SYNC_FOLDER_TYPE_DUMMY) {
            throw new StatusException(
                sprintf(
                    "ImportChangesNetric->ImportMessageChange('%s'): can not be done on a dummy folder",
                    $id
                ),
                SYNC_STATUS_SYNCCANNOTBECOMPLETED
            );
        }

        if($id) {
            // See if there's a conflict
            $conflict = $this->isConflict("change", $this->folderId, $id);

            // Update client state if this is an update
            $change = array();
            $change["id"] = $id;
            $change["mod"] = 0; // dummy, will be updated later if the change succeeds
            $change["parent"] = $this->folderId;
            $change["flags"] = (isset($message->read)) ? $message->read : 0;
            $this->updateState("change", $change);

            if($conflict && $this->flags == SYNC_CONFLICT_OVERWRITE_PIM) {
                // in these cases the status SYNC_STATUS_CONFLICTCLIENTSERVEROBJECT should be returned, so the mobile client can inform the end user
                throw new StatusException(
                    sprintf(
                        "ImportChangesNetric->ImportMessageChange('%s','%s'): Conflict detected. Data from PIM will be dropped! Server overwrites PIM. User is informed.",
                        $id,
                        get_class($message)),
                    SYNC_STATUS_CONFLICTCLIENTSERVEROBJECT,
                    null,
                    LOGLEVEL_INFO
                );
            }

        }

        $id = $this->provider->saveSyncObject($this->folderId, $id, $message);

        if(!$id) {
            throw new StatusException(
                sprintf(
                    "ImportChangesNetric->ImportMessageChange('%s','%s'): unknown error in backend",
                    $id,
                    get_class($message)
                ),
                SYNC_STATUS_SYNCCANNOTBECOMPLETED
            );
        }

        // Record the state of the message
        $stat = $this->provider->getEntityStat($this->folderId, $id);
        $this->updateState("change", $stat);

        /*
         * Log that we imported this to prevent an infinite loop where we
         * import, then it exports, then we re-import etc...
         * Both the remote (first param) and the local (third param) IDs are the same
         */
        $this->collection->logImported($id, $stat['mod'], $stat['id'], $stat['mod']);

        ZLog::Write(LOGLEVEL_INFO, "ImportChangesNetric->ImportMessageChange: $id, {$this->folderId} imported");

        return $id;

    }

    /**
     * Imports a deletion. This may conflict if the local object has been modified
     *
     * @param string        $id
     *
     * @access public
     * @return boolean
     * @throws StatusException
     */
    public function ImportMessageDeletion($id)
    {
        // Do nothing if it is in a dummy folder
        if ($this->folderId == SYNC_FOLDER_TYPE_DUMMY) {
            throw new StatusException(
                sprintf(
                    "ImportChangesNetric->ImportMessageDeletion('%s'): can not be done on a dummy folder",
                    $id
                ),
                SYNC_STATUS_SYNCCANNOTBECOMPLETED
            );
        }

        // See if there's a conflict
        $conflict = $this->isConflict("delete", $this->folderId, $id);

        // Update device state
        $change = array();
        $change["id"] = $id;
        $this->updateState("delete", $change);

        /*
         * If there is a conflict, and the server 'wins', then return without performing the change
         * this will cause the exporter to 'see' the overriding item as a change, and send it back to the PIM
         */
        if($conflict && $this->flags == SYNC_CONFLICT_OVERWRITE_PIM) {
            ZLog::Write(LOGLEVEL_INFO, sprintf("ImportChangesNetric->ImportMessageDeletion($id): Conflict detected. Data from PIM will be dropped! Object was deleted."));
            return false;
        }

        // By not passing any local info (rest of params) we are purging
        $this->collection->logImported($id);

        return $this->provider->deleteEntity($this->folderId, $id);
    }

    /**
     * Imports a change in 'read' flag
     * This can never conflict
     *
     * @param string        $id
     * @param int           $flags
     *
     * @access public
     * @return boolean
     * @throws StatusException
     */
    public function ImportMessageReadFlag($id, $flags)
    {
        // Do nothing if it is a dummy folder
        if ($this->folderId == SYNC_FOLDER_TYPE_DUMMY)
            throw new StatusException(sprintf("ImportChangesNetric->ImportMessageReadFlag('%s','%s'): can not be done on a dummy folder", $id, $flags), SYNC_STATUS_SYNCCANNOTBECOMPLETED);

        // Update client state
        $change = array();
        $change["id"] = $id;
        $change["flags"] = $flags;
        $this->updateState("flags", $change);

        if (!$this->provider->markEntitySeen($this->folderId, $id, $flags)) {
            throw new StatusException(
                sprintf(
                    "ImportChangesNetric->ImportMessageReadFlag('%s','%s'): Error, unable to mark message seen",
                    $id,
                    $flags
                ),
                SYNC_STATUS_OBJECTNOTFOUND
            );
        }

        /*
         * Log that we imported this to prevent an infinite loop where we
         * import, then it exports, then we re-import etc...
         * Both the remote (first param) and the local (third param) IDs are the same
         */
        $stat = $this->provider->getEntityStat($this->folderId, $id);
        $this->collection->logImported($id, $stat['mod'], $stat['id'], $stat['mod']);

        return true;
    }

    /**
     * Imports a move of a message. This occurs when a user moves an item to another folder
     *
     * @param string $id The id of the message to move
     * @param string $newfolder Destination folder
     * @return bool true on success, false on failure
     * @throws StatusException If there was an unexpected error
     */
    public function ImportMessageMove($id, $newfolder)
    {
        // Don't move messages from or to a dummy folder (GetHierarchy compatibility)
        if ($this->folderId == SYNC_FOLDER_TYPE_DUMMY || $newfolder == SYNC_FOLDER_TYPE_DUMMY) {
            throw new StatusException(
                sprintf(
                    "ImportChangesNetric->ImportMessageMove('%s'): can not be done on a dummy folder",
                    $id
                ),
                SYNC_MOVEITEMSSTATUS_CANNOTMOVE
            );
        }

        $ret = $this->provider->moveEntity($id, $this->folderId, $newfolder);

        if ($ret) {
            /*
             * Log that we imported this to prevent an infinite loop where we
             * import, then it exports, then we re-import etc...
             * Both the remote (first param) and the local (third param) IDs are the same
             */
            $stat = $this->provider->getEntityStat($this->folderId, $id);
            $this->collection->logImported($id, $stat['mod'], $stat['id'], $stat['mod']);

            ZLog::Write(LOGLEVEL_INFO, "ImportChangesNetric->ImportMessageMove: $id from {$this->folderId} to {$newfolder}");

        }

        return $ret;
    }


    /**----------------------------------------------------------------------------------------------------------
     * Methods to import hierarchy
     */

    /**
     * Imports a change on a folder
     *
     * @param object        $folder         SyncFolder
     *
     * @access public
     * @return boolean/string               status/id of the folder
     * @throws StatusException
     */
    public function ImportFolderChange($folder)
    {
        $id = $folder->serverid;
        $parent = $folder->parentid;
        $displayname = $folder->displayname;
        $type = $folder->type;

        //do nothing if it is a dummy folder
        if ($parent == SYNC_FOLDER_TYPE_DUMMY) {
            throw new StatusException(
                sprintf("ImportChangesNetric->ImportFolderChange('%s'): can not be done on a dummy folder",
                    $id
                ),
                SYNC_FSSTATUS_SERVERERROR
            );
        }

        $id = $this->provider->saveSyncFolder($parent, $id, $displayname, $type);

        if($id) {
            $change = array();
            $change["id"] = $id;
            $change["mod"] = $displayname;
            $change["parent"] = $parent;
            $change["flags"] = 0;
            $this->updateState("change", $change);
        }

        return $id;
    }

    /**
     * Imports a folder deletion
     *
     * @param string        $id
     * @param string        $parent id
     *
     * @access public
     * @return boolean/int  success/SYNC_FOLDERHIERARCHY_STATUS
     * @throws StatusException
     */
    public function ImportFolderDeletion($id, $parent = false)
    {
        // Do nothing if it is a dummy folder
        if ($parent == SYNC_FOLDER_TYPE_DUMMY) {
            throw new StatusException(
                sprintf(
                    "ImportChangesNetric->ImportFolderDeletion('%s','%s'): can not be done on a dummy folder",
                    $id,
                    $parent
                ),
                SYNC_FSSTATUS_SERVERERROR
            );
        }

        // Check the type and make sure we do not delete a system folder
        $folder = $this->provider->getFolder($id);
        if (isset($folder->type) && Utils::IsSystemFolder($folder->type)) {
            throw new StatusException(
                sprintf(
                    "ImportChangesNetric->ImportFolderDeletion('%s','%s'): Error deleting system/default folder",
                    $id,
                    $parent
                ),
                SYNC_FSSTATUS_SYSTEMFOLDER
            );
        }

        $ret = $this->provider->deleteFolder($id, $parent);
        if (!$ret) {
            throw new StatusException(
                sprintf(
                    "ImportChangesNetric->ImportFolderDeletion('%s','%s'): can not be done on a dummy folder",
                    $id,
                    $parent
                ),
                SYNC_FSSTATUS_FOLDERDOESNOTEXIST
            );
        }

        $change = array();
        $change["id"] = $id;

        $this->updateState("delete", $change);

        return true;
    }
}
