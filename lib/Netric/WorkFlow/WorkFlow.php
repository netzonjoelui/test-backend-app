<?php
/**
 * WorkFlow
 *
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */

namespace Netric\WorkFlow;

use Netric\EntityQuery\Where;
use Netric\WorkFlow\Action\ActionFactory;
use Netric\WorkFlow\Action\ActionInterface;

/**
 * Class describing a single workflow
 */
class WorkFlow
{
    /**
     * Events that can trigger work flows
     */
    const EVENT_CREATE = 'create';
    const EVENT_UPDATE = 'update';
    const EVENT_DELETE = 'delete';

    /**
     * Units of time for relative times
     *
     * @var const
     */
    const TIME_UNIT_MINUTE = 1;
    const TIME_UNIT_HOUR = 2;
    const TIME_UNIT_DAY = 3;
    const TIME_UNIT_WEEK = 4;
    const TIME_UNIT_MONTH = 5;
    const TIME_UNIT_YEAR = 6;

    /**
     * Unique ID of this workflow
     *
     * @var int
     */
    private $id = null;

    /**
     * Textual name
     *
     * @var string
     */
    private $name = "";

    /**
     * Detailed description
     *
     * @var string
     */
    private $notes = "";

    /**
     * The object type this workflow is interacting with
     *
     * @var string
     */
    private $objType = "";

    /**
     * Flag to determine if the current workflow is active or not
     *
     * @var bool
     */
    private $active = true;

    /**
     * Flag to indicate if this workflow should be triggered when an entity is created
     *
     * @var bool
     */
    private $onCreate = false;

    /**
     * Flag to indicate if this workflow should be triggered when an entity is updated
     *
     * @var bool
     */
    private $onUpdate = false;

    /**
     * Flag to indicate if this workflow should be triggered when an entity is deleted
     *
     * @var bool
     */
    private $onDelete = false;

    /**
     * Flag to indicate if this workflow should be run daily looking for matches
     *
     * @var bool
     */
    private $onDaily = false;

    /**
     * Only allow one instance of this workflow at a time per entity
     *
     * @var bool
     */
    private $singleton = true;

    /**
     * WorkFlow can be manually started
     *
     * @var bool
     */
    private $allowManual = true;

    /**
     * Only start WorkFlow if the conditions were previously unmet before the trigger event
     *
     * This can be used to assure that a WorkFlow is not fired over and over by checking to
     * assure prior to the event that triggered this WorkFlow the entity was not qualified for
     * this workflow to run. This would prevent a case where a user hits save 10 times resulting
     * in 10 notifications going out to the same user.
     *
     * @var bool
     */
    private $onlyOnConditionsUnmet = true;

    /**
     * Array of actions to run
     *
     * @var ActionInterface[]
     */
    private $actions = array();

    /**
     * Actions that were previously saved with IDs but removed
     *
     * @var ActionInterface[]
     */
    private $removedActions = array();

    /**
     * Array of query conditions
     *
     * These are used to filter whether or not an entity is qualified for this WorkFlow
     *
     * @var Where[]
     */
    private $conditions = array();

    /**
     * Factory for creating actions
     *
     * @var ActionFactory
     */
    private $actionFactory = null;

    /**
     * Revision id incremented every time this workflow is saved
     *
     * @var int
     */
    private $revision = 0;

    /**
     * Time this workflow was last run
     *
     * @var \DateTime
     */
    private $lastRun = null;

    /**
     * Construct the workflow object and set dependencies
     *
     * @param ActionFactory $actionFactory
     */
    public function __construct(ActionFactory $actionFactory)
    {
        $this->actionFactory = $actionFactory;
    }

    /**
     * Load from a data array
     *
     * @param array $data
     */
    public function fromArray($data)
    {
        if (isset($data['id']))
            $this->id = $data['id'];

        if (isset($data['name']))
            $this->name = $data['name'];

        if (isset($data['notes']))
            $this->notes = $data['notes'];

        if (isset($data['obj_type']))
            $this->objType = $data['obj_type'];

        if (isset($data['revision']))
            $this->revision = $data['revision'];

        if (isset($data['active']) && is_bool($data['active']))
            $this->active = $data['active'];

        if (isset($data['on_create']) && is_bool($data['on_create']))
            $this->onCreate = $data['on_create'];

        if (isset($data['on_update']) && is_bool($data['on_update']))
            $this->onUpdate = $data['on_update'];

        if (isset($data['on_delete']) && is_bool($data['on_delete']))
            $this->onDelete = $data['on_delete'];

        if (isset($data['on_daily']) && is_bool($data['on_daily']))
            $this->onDaily = $data['on_daily'];

        if (isset($data['singleton']) && is_bool($data['singleton']))
            $this->singleton = $data['singleton'];

        if (isset($data['allow_manual']) && is_bool($data['allow_manual']))
            $this->allowManual = $data['allow_manual'];

        if (isset($data['only_on_conditions_unmet']) && is_bool($data['only_on_conditions_unmet']))
            $this->onlyOnConditionsUnmet = $data['only_on_conditions_unmet'];

        if (isset($data['last_run']))
        {
            if ($data['last_run'])
                $this->lastRun = new \DateTime($data['last_run']);
            else
                $this->lastRun = null;

        }

        // Load conditions
        if (isset($data['conditions']) && is_array($data['conditions']))
        {
            // Reset any existing conditions
            $this->conditions = array();

            // Now add to local conditions property
            foreach ($data['conditions'] as $condData)
            {
                $where = new Where();
                $where->fromArray($condData);

                if (!$where->operator)
                    throw new \RuntimeException("Tried to add a bad cond: " . var_export($data['conditions'], true));

                $this->conditions[] = $where;
            }
        }

        // Load actions
        if (isset($data['actions']) && is_array($data['actions']))
        {
            /*
             * Queue current actions for deletion since we are setting all actions
             * and not just adding actions it is assumed anything missing from $data['actions']
             * has been deleted since the last save.
             *
             * When $this->addAction is called below it will remove it from the removedActions
             * queue to keep it from being deleted on the next save
             */
            foreach ($this->actions as $actionToRemove)
            {
                $this->removeAction($actionToRemove);
            }

            // Now add to local actions array
            foreach ($data['actions'] as $actData)
            {
                $action = $this->actionFactory->create($actData['type']);
                $action->fromArray($actData);
                $this->addAction($action);
            }
        }
    }

    /**
     * Convert this WorkFlow into an associative array
     *
     * @return array
     */
    public function toArray()
    {
        $ret = array(
            "id" => $this->id,
            "name" => $this->name,
            "notes" => $this->notes,
            "obj_type" => $this->objType,
            "revision" => $this->revision,
            "active" => $this->active,
            "on_create" => $this->onCreate,
            "on_update" => $this->onUpdate,
            "on_delete" => $this->onDelete,
            "on_daily" => $this->onDaily,
            "singleton" => $this->singleton,
            "allow_manual" => $this->allowManual,
            "last_run" => ($this->lastRun) ? $this->lastRun->format("Y-m-d H:i:s T") : null,
            "only_on_conditions_unmet" => $this->onlyOnConditionsUnmet,
        );

        // Set conditions
        $ret['conditions'] = array();
        foreach ($this->conditions as $where)
        {
            $ret['conditions'][] = $where->toArray();
        }

        // Set actions
        $ret['actions'] = array();
        foreach ($this->actions as $action)
        {
            $ret['actions'][] = $action->toArray();
        }

        return $ret;
    }

    /**
     * Get the id of this workflow
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the id of this workflow
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the object type
     *
     * @return string
     */
    public function getObjType()
    {
        return $this->objType;
    }

    /**
     * Get the name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the object type
     *
     * @param string $objType
     */
    public function setObjType($objType)
    {
        $this->objType = $objType;
    }

    /**
     * Get the revision number
     *
     * @return int
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Set the revision number of this workflow
     *
     * @param int
     */
    public function setRevision($revision)
    {
        $this->revision = $revision;
    }

    /**
     * Add a Where condition to filter which entities this workflow will act on
     *
     * @param Where $condition
     */
    public function addCondition(Where $condition)
    {
        $this->conditions[] = $condition;
    }


    /**
     * Get conditions for this workflow
     *
     * @return Where[]
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Remove an action
     *
     * @param ActionInterface $action The action to remove
     * @returns bool true if removed, false if not found
     */
    public function removeAction(ActionInterface $action)
    {
        for ($i = 0; $i < count($this->actions); $i++)
        {
            if ($action === $this->actions[$i] ||
                ($action->getId() != null && $action->getId() === $this->actions[$i]->getId()))
            {
                array_splice($this->actions, $i, 1);

                // If previously saved then queue it to be purged on save
                if ($action->getId())
                {
                    $this->removedActions[] = $action;
                }

                return true;
            }
        }

        // Not found so nothing to remove
        return true;
    }

    /**
     * Add an action
     *
     * @param ActionInterface $actionToAdd
     */
    public function addAction(ActionInterface $actionToAdd)
    {
        // First make sure we didn't previously remove this action
        for ($i = 0; $i < count($this->removedActions); $i++)
        {
            if ($actionToAdd === $this->removedActions[$i] ||
                ($actionToAdd->getId() != null && $actionToAdd->getId() === $this->removedActions[$i]->getId()))
            {
                // Remove it from deletion queue, apparently the user didn't mean to delete it
                array_splice($this->removedActions, $i, 1);
            }
        }

        // Check if previously added
        $previouslyAddedAt = -1;
        for ($i = 0; $i < count($this->actions); $i++)
        {
            if ($actionToAdd->getId() &&
                $this->actions[$i]->getId() === $actionToAdd->getId())
            {
                $previouslyAddedAt = $i;
                break;
            }
        }

        // If this action was not previously added then push the new action, otherwise replace
        if ($previouslyAddedAt === -1)
            $this->actions[] = $actionToAdd;
        else
            $this->actions[$previouslyAddedAt] = $actionToAdd;
    }

    /**
     * Get actions to remove
     *
     * @return ActionInterface[]
     */
    public function getRemovedActions()
    {
        return $this->removedActions;
    }

    /**
     * Get actions array
     *
     * @return ActionInterface[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Flag to indicate this should only be executed if conditions were previously unmet
     *
     * When an event occurs in an enttiy that a workflow is listening for, it will first
     * check that the conditions for launching the event are met before triggering.
     * This options is useful if you want to indicate that a workflow should only be fired
     * if the conditions were not true before the entity was saved. This would keep something
     * like repeat emails from being triggered if the user saves 10x.
     *
     * return bool
     */
    public function isOnlyOnConditionsUnmet()
    {
        return $this->onlyOnConditionsUnmet;
    }

    /**
     * Set flag to only run workflow if condtions were previously unmet
     *
     * @param bool $value The flag value to set
     */
    public function setOnlyOnConditionsUnmet($value = true)
    {
        $this->onlyOnConditionsUnmet = $value;
    }

    /**
     * Set whether or not to trigger this WorkFlow when an entity updates
     *
     * @param bool $listen If true then trigger on the event
     */
    public function setOnUpdate($listen = true)
    {
        $this->onUpdate = $listen;
    }

    /**
     * Set whether or not to trigger this WorkFlow when an entity is created
     *
     * @param bool $listen If true then trigger on the event
     */
    public function setOnCreate($listen = true)
    {
        $this->onCreate = $listen;
    }

    /**
     * Set whether or not to trigger this WorkFlow when an entity is deleted
     *
     * @param bool $listen If true then trigger on the event
     */
    public function setOnDelete($listen = true)
    {
        $this->onDelete = $listen;
    }

    /**
     * Set whether we should check daily to see if anything meets the conditions of this WorkFlow
     *
     * @param bool $listen If true then trigger on the event
     */
    public function setOnDaily($listen = true)
    {
        $this->onDaily = $listen;
    }

    /**
     * Update the last run
     *
     * @param \DateTime $when If set use this date, otherwise just use 'now'
     */
    public function setLastRun(\DateTime $when = null)
    {
        if (!$when)
            $when = new \DateTime();

        $this->lastRun = $when;
    }
}
