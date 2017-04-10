<?php
/**
 * WorkFlow manager service
 *
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\WorkFlow;

use Netric\EntityQuery\Index\IndexInterface;
use Netric\EntityQuery;
use Netric\EntityQuery\Results;
use Netric\WorkFlow\Action\ActionInterface;
use Netric\Entity\EntityInterface;
use Netric\WorkFlow\DataMapper\DataMapperInterface;
use Netric\Log;
use Netric\Error\AbstractHasErrors;

/**
 * Workflow service
 */
class WorkFlowManager extends AbstractHasErrors
{
    /**
     * WorkFlow DataMapper
     *
     * @var DataMapperInterface
     */
    private $workFlowDataMapper = null;

    /**
     * Entity index for queries
     *
     * @var IndexInterface
     */
    private $entityIndex = null;

    /**
     * Logger interface
     *
     * @var Log
     */
    private $log = null;

    /**
     * Set dependencies and construct the manager
     *
     * @param DataMapperInterface $workFlowDataMapper
     * @param IndexInterface $index The query index interface
     * @param Log $log Netric log
     */
    public function __construct(DataMapperInterface $workFlowDataMapper, IndexInterface $index, Log $log)
    {
        $this->workFlowDataMapper = $workFlowDataMapper;
        $this->entityIndex = $index;
        $this->log = $log;
    }

    /**
     * Start WorkFlows for an entity based on an action
     *
     * @param EntityInterface $entity The entity being acted on
     * @param string $event One of WorkFlow::EVENT_
     */
    public function startWorkFlows(EntityInterface $entity, $event)
    {
        $objType = $entity->getDefinition()->getObjType();

        // Get active WorkFlows for this entity
        $workFlows = $this->workFlowDataMapper->getWorkFlows($objType, true, $event);
        foreach ($workFlows as $workFlow)
        {
            // Check if conditions match
            if ($this->workFlowConditionsMatch($workFlow, $entity))
            {
                $this->startWorkFlowInstance($workFlow, $entity);
            }
        }
    }

    /**
     * Run and execute WorkFlows that run periodically (daily)
     */
    public function runPeriodicWorkFlows()
    {
        // Get all daily workFlows
        $workFlows = $this->workFlowDataMapper->getWorkFlows(null, true, "daily");
        foreach ($workFlows as $workFlow)
        {
            $results = $this->getEntitiesThatMatchConditions($workFlow);
            $num = $results->getTotalNum();
            for ($i = 0; $i < $num; $i++)
            {
                $entity = $results->getEntity($i);
                $this->startWorkFlowInstance($workFlow, $entity);
            }

            // Update last run to make sure we don't run it over and over
            $workFlow->setLastRun();
            $this->workFlowDataMapper->save($workFlow);

            // Log what just happened
            $this->log->info("Ran periodic WorkFlow " . $workFlow->getId() . " on $num entities");
        }
    }

    /**
     * Force starting a WorkFlow against an entity
     *
     * @param EntityInterface $entity Entity to execute WorkFlow against
     * @param int $wid The unique id of the WorkFlow to start
     * @throws \RuntimeException if there are any problems
     */
    public function startWorkflowById(EntityInterface $entity, $wid)
    {
        $workFlow = $this->workFlowDataMapper->getById($wid);

        if (!$workFlow)
        {
            throw new \RuntimeException("WorkFlow $wid does not exist");
        }

        if ($workFlow->getObjType() != $entity->getDefinition()->getObjType())
        {
            throw new \RuntimeException(
                "WorkFlow id $wid only runs against objType '" . $workFlow->getObjType() . "'" .
                " and '" . $entity->getDefinition()->getObjType() . "' was passed"
            );
        }

        $this->startWorkFlowInstance($workFlow, $entity);
    }

    /**
     * Run actions that were scheduled by a workflow instance
     */
    public function runScheduledActions()
    {
        /*
         * Get array of instances and actions that are scheduled to run on
         * or before this moment.
         */
        $scheduled = $this->workFlowDataMapper->getScheduledActions();
        foreach ($scheduled as $queued)
        {
            $workFlowInstance = $queued['instance'];
            $action = $queued['action'];
            $this->executeAction($action, $workFlowInstance, true);
        }

        // Log what just happened
        $this->log->info("Found and executed " . count($scheduled) . " scheduled actions");
    }

    /**
     * Get a WorkFlow by id
     *
     * @param id $id
     * @return WorkFlow
     */
    public function getWorkFlowById($id)
    {
        return $this->workFlowDataMapper->getById($id);
    }

    /**
     * Get all workflows
     *
     * @param string $objType Get the object type
     * @return WorkFlow[]
     */
    public function getWorkFlows($objType = null)
    {
        return $this->workFlowDataMapper->getWorkFlows($objType);
    }

    /**
     * Save workflow
     *
     * @param WorkFlow $workFlow
     * @return bool true on success, false on failure
     */
    public function saveWorkFlow(WorkFlow $workFlow)
    {
        $ret = $this->workFlowDataMapper->save($workFlow);

        // Save error
        if (!$ret)
            $this->addErrorFromMessage($this->workFlowDataMapper->getLastError()->getMessage());

        return $ret;
    }

    /**
     * Test to see if an entity matches a set of WorkFlow conditions
     *
     * @param WorkFlow $workFlow The WorkFlow we would like to know about
     * @param EntityInterface $entity The entity we are checking against the WorkFlow conditions
     * @return bool true if the entity matches, or false if it does not
     */
    private function workFlowConditionsMatch(WorkFlow $workFlow, EntityInterface $entity)
    {
        // We use the index for checking if conditions match since it contains all the condition logic
        $query = new EntityQuery($entity->getDefinition()->getObjType());

        // Add the entity as a condition to see if it meets the criteria
        $query->where("id")->equals($entity->getId());

        // Query deleted if the entity is deleted
        if ($entity->isDeleted())
            $query->andWhere("f_deleted")->equals(true);

        /*
         * If the workflow has a onlyOnConditionsUnmet flag then we
         * need to check to see if any of the fields that match conditions
         * were changed (presumably to match the conditions) before we trigger the
         * workflow. This is useful in cases where we check if something like
         * task done='t' then send email, but if the user just hits save for notes
         * we don't want to send another email about it being completed.
         * However, if they update the task to mark it as incomplete for some reason,
         * then later complete it again, we do want to trigger the notification.
         */
        $conditionFieldChanged = false;

        // Get where conditions from the workflow
        $conditions = $workFlow->getConditions();
        foreach ($conditions as $cond)
        {
            $query->andWhere($cond->fieldName, $cond->operator, $cond->value);

            if ($entity->fieldValueChanged($cond->fieldName))
            {
                $conditionFieldChanged = true;
            }
        }

        // Get results
        $result = $this->entityIndex->executeQuery($query);
        $num = $result->getNum();

        // See comments above for $conditionFieldChanged variable explanation
        if($workFlow->isOnlyOnConditionsUnmet() && !$conditionFieldChanged)
            return false;

        // If we found the entity in the query we know it is a match
        if ($num)
            return true;

        // The entity was not found when checked against the workflow conditions
        return false;
    }

    /**
     * Start a workflow instance given a WorkFlow and an Entity
     *
     * @param WorkFlow $workFlow The WorkFlow we would like to run
     * @param EntityInterface $entity The entity we are running on
     */
    private function startWorkFlowInstance(WorkFlow $workFlow, EntityInterface $entity)
    {
        $workFlowInstance = new WorkFlowInstance($workFlow->getId(), $entity);
        $this->workFlowDataMapper->saveWorkFlowInstance($workFlowInstance);

        // Now execute first level of actions in the workflow
        $actions = $workFlow->getActions();
        foreach ($actions as $action)
        {
            $this->executeAction($action, $workFlowInstance);
        }
    }

    /**
     * Execute an action for a workflow instance
     *
     * @param ActionInterface $action The action to execute (including all children)
     * @param WorkFlowInstance $workFlowInstance The instance of the workflow being executed
     * @param bool $purgeScheduled If true clear the scheduled task on successfully executed
     */
    private function executeAction(ActionInterface $action, WorkFlowInstance $workFlowInstance, $purgeScheduled = false)
    {
        if ($action->execute($workFlowInstance))
        {
            // Log what just happened for troubleshooting
            $this->log->info("Executed action " . $action->getId() . " against instance " . $workFlowInstance->getId());

            // Delete any scheduled tasks if set
            if ($purgeScheduled)
                $this->workFlowDataMapper->deleteScheduledAction($workFlowInstance->getId(), $action->getId());

            // If action completed and returned true then run children
            $children = $action->getActions();
            foreach ($children as $childAction)
            {
                $this->executeAction($childAction, $workFlowInstance);
            }
        }
        else if ($action->getLastError())
        {
            // Log the error
            $this->log->error("Failed to execute " . $action->getId() . ": " . $action->getLastError()->getMessage());
        }
    }

    /**
     * Get results of entities that match the workflow conditions
     *
     * @param WorkFlow $workFlow The WorkFlow with conditions we use for finding entities
     * @return Results EntityQuery results from the query if found
     */
    private function getEntitiesThatMatchConditions(WorkFlow $workFlow)
    {
        // Query object types for this work flow
        $query = new EntityQuery($workFlow->getObjType());

        // Get where conditions from the workflow
        $conditions = $workFlow->getConditions();
        foreach ($conditions as $cond)
        {
            $query->andWhere($cond->fieldName, $cond->operator, $cond->value);
        }

        // Get results
        $result = $this->entityIndex->executeQuery($query);
        return $result;
    }
}