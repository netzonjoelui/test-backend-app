<?php
/**
 * Define expected functions for a WorkFlow DataMapper
 *
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\WorkFlow\DataMapper;

use Netric\Entity\EntityInterface;
use Netric\WorkFlow\WorkFlow;
use Netric\WorkFlow\WorkFlowInstance;
use Netric\Error\ErrorAwareInterface;

interface DataMapperInterface extends ErrorAwareInterface
{
    /**
     * Save a new or existing WorkFlow
     *
     * @param WorkFlow $workFlow The workflow to save
     * @return int|null The unique id if success, null on failure (call getLastError for details)
     * @throws \RuntimeException on critical unexpected error
     */
    public function save(WorkFlow $workFlow);

    /**
     * Delete an existing WorkFlow
     *
     * @param WorkFlow $workFlow The workflow to delete
     * @return true on success, false on failure with detauls in getLastError
     */
    public function delete(WorkFlow $workFlow);

    /**
     * Open a new workflow by id
     *
     * @param int $id The unique id of the workflow to load
     * @return WorkFlow|null Returns null if $id does not exist
     */
    public function getById($id);

    /**
     * Get a list of WorkFlows as an array
     *
     * @param string $objType If set only get for a specific object type
     * @param bool $onlyActive Only return active workflows, otherwise return all
     * @param string $filterEvent If set, only get workflows listening for a specific event
     * @return WorkFlow[] An array of WorkFlow objects or just an empty array if none found
     */
    public function getWorkFlows($objType = null, $onlyActive = true, $filterEvent = null);

    /**
     * Save an instance of a workflow
     *
     * @param WorkFlowInstance $workFlowInstance Instance to save
     * @return int id The unique id of the instance run
     */
    public function saveWorkFlowInstance(WorkFlowInstance $workFlowInstance);

    /**
     * Get a WorkFlowInstance by id
     *
     * @param int $workFlowInstanceId The unique id of the workflow instance running
     * @return WorkFlowInstance|null
     */
    public function getWorkFlowInstanceById($workFlowInstanceId);

    /**
     * Delete a workflow instance id
     *
     * This is only for admin really because an instance will almost always be set to completed
     * but never deleted since we want to maintain a record of the instance run.
     *
     * @param int $workFlowInstanceId
     * @throws \InvalidArgumentException if anything but a workFlowInstanceId is passed
     */
    public function deleteWorkFlowInstance($workFlowInstanceId);

    /**
     * Schedule an action to run at some time in the future
     *
     * @param int $workFlowInstanceId
     * @param int $actionId
     * @param \DateTime $executeTime
     * @return bool true on success, false on failure
     */
    public function scheduleAction($workFlowInstanceId, $actionId, \DateTime $executeTime);

    /**
     * Delete a scheduled action if set for a workflow instance and an action
     *
     * @param int $workFlowInstanceId
     * @param int $actionId
     * @return bool true on success, false on failure
     */
    public function deleteScheduledAction($workFlowInstanceId, $actionId);

    /**
     * Get a scheduled action time if set for a workflow instance and an action
     *
     * @param int $workFlowInstanceId
     * @param int $actionId
     * @return \DateTime|null
     */
    public function getScheduledActionTime($workFlowInstanceId, $actionId);

    /**
     * Get all actions scheduled to be executed on or before $toDate
     *
     * @param \DateTime $toDate
     * @return array(array("instance"=>WorkFlowInstance, "action"=>ActionInterface))
     */
    public function getScheduledActions(\DateTime $toDate = null);
}