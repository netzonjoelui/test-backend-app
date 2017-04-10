<?php
/**
 * Base class for modifying state changes
 *
 * In z-push a state is always returned from both imports and exports
 * to keep a log of what has already been sent and received from a device.
 *
 * z-push uses this state to detect errors, circular references and a host of other
 * useful sync things.
 *
 * This class handles keeping the state updated as an importer or exporter makes
 * changes to entities.
 *
 * The reason all the files are lowercase in here is because that is the z-push standard
 * so we stick with it to be consistent.
 */
$zPushRoot = dirname(__FILE__) ."/../../";

// Interfaces we are extending
require_once($zPushRoot . 'lib/interface/ichanges.php');

// Supporting files and exceptions
require_once($zPushRoot . 'lib/core/zpush.php');
require_once($zPushRoot . 'lib/request/request.php');
require_once($zPushRoot . 'lib/exceptions/authenticationrequiredexception.php');
require_once($zPushRoot . 'lib/exceptions/statusexception.php');

// Include netric autoloader for all netric libraries
require_once(dirname(__FILE__)."/../../../../init_autoloader.php");

/**
 * Simple diff of changes
 */
class ChangesNetric implements IChanges
{
    /**
     * Current sync state
     *
     * @var array(
     *          array(
     *              'type' => 'change'|'delete'|'flags',
     *              'id' => uniqueId,
     *              'mod' => revision,
     *              'flags' => int
     *          )
     *      )
     */
    protected $syncState = array();

    /**
     * Config flags
     *
     * @var int
     */
    protected $flags;

    /**
     * Parameters to contron the type of content we are synchronizing
     *
     * @var ContentParameters
     */
    protected $contentParameters = null;

    /**
     * Optional cutoff to limit past changes
     *
     * Example: only changes from the past 30 days.
     *
     * @var int Timestamp
     */
    protected $cutoffDate = null;

    /**
     * Provider used to read and write entities
     *
     * @var EntityProvider
     */
    protected $provider = null;

    /**
     * Initializes the state
     *
     * @param array|string $state Array of previously synchronized, "" if first time
     * @param int$flags
     * @return boolean true on success
     * @throws StatusException If we are passed a bad state
     */
    public function Config($state, $flags = 0)
    {
        if ($state == "")
            $state = array();

        if (!is_array($state))
            throw new StatusException("Invalid state", SYNC_FSSTATUS_CODEUNKNOWN);

        $this->syncState = $state;
        $this->flags = $flags;
        return true;
    }

    /**
     * Configures additional parameters used for content synchronization
     *
     * @param ContentParameters $contentParameters
     * @return void
     */
    public function ConfigContentParameters($contentParameters)
    {
        $this->contentParameters = $contentParameters;
        $this->cutoffDate = Utils::GetCutOffDate($contentParameters->GetFilterType());
    }

    /**
     * Returns state
     *
     * @return array
     * @throws StatusException
     */
    public function GetState() {
        if (!isset($this->syncState) || !is_array($this->syncState))
            throw new StatusException("DiffState->GetState(): Error, state not available", SYNC_FSSTATUS_CODEUNKNOWN, null, LOGLEVEL_WARN);

        return $this->syncState;
    }


    /**
     * Update the state to reflect changes
     *
     * @param string        $type of change
     * @param array         $change
     *
     *
     * @access protected
     * @return
     */
    protected function updateState($type, $change) {
        // Change can be a change or an add
        if($type == "change") {
            for($i=0; $i < count($this->syncState); $i++) {
                if($this->syncState[$i]["id"] == $change["id"]) {
                    $this->syncState[$i] = $change;
                    return;
                }
            }
            // Not found, add as new
            $this->syncState[] = $change;
        } else {
            for($i=0; $i < count($this->syncState); $i++) {
                // Search for the entry for this item
                if($this->syncState[$i]["id"] == $change["id"]) {
                    if($type == "flags") {
                        // Update flags
                        $this->syncState[$i]["flags"] = $change["flags"];
                    } else if($type == "delete") {
                        // Delete item
                        array_splice($this->syncState, $i, 1);
                    }
                    return;
                }
            }
        }
    }

    /**
     * Returns TRUE if the given ID conflicts with the given operation. This is only true in the following situations:
     *   - Changed here and changed there
     *   - Changed here and deleted there
     *   - Deleted here and changed there
     * Any other combination of operations can be done (e.g. change flags & move or move & delete)
     *
     * @param string        $type of change
     * @param string        $folderid
     * @param string        $id
     *
     * @access protected
     * @return
     */
    protected function isConflict($type, $folderid, $id) {
        $stat = $this->provider->getEntityStat($folderid, $id);

        if(!$stat) {
            // Message is gone
            if($type == "change")
                return true; // deleted here, but changed there
            else
                return false; // all other remote changes still result in a delete (no conflict)
        }

        foreach($this->syncState as $state) {
            if($state["id"] == $id) {
                $oldstat = $state;
                break;
            }
        }

        if(!isset($oldstat)) {
            // New message, can never conflict
            return false;
        }

        if($stat["mod"] != $oldstat["mod"]) {
            // Changed here
            if($type == "delete" || $type == "change")
                return true; // changed here, but deleted there -> conflict, or changed here and changed there -> conflict
            else
                return false; // changed here, and other remote changes (move or flags)
        }
    }

    /**
     * Check the previous syncState to determine if we have synchronized this item before
     *
     * @param int $id The unique id of the entity to check
     * @return bool true if we have synchronized before, otherwise false
     */
    protected function foundInSyncState($id) {
        foreach($this->syncState as &$item) {
            if ($item['id'] == $id) {
                return true;
            }
        }
        return false;
    }
}