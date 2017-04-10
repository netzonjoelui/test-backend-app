<?php
/**
 * Common Action functionality
 *
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\WorkFlow\Action;
use Netric\Entity\EntityInterface;
use Netric\WorkFlow\WorkFlowInstance;

/**
 * Base class for all actions
 */
interface ActionInterface
{
    /**
     * Execute this action
     *
     * @param WorkFlowInstance $workflowInstance The workflow instance we are executing in
     * @return bool true on success, false on failure
     */
    public function execute(WorkFlowInstance $workflowInstance);

    /**
     * Load action properties from an associated array
     *
     * @param array $data Associative array
     */
    public function fromArray(array $data);

    /**
     * Get properties of an action in an associated array
     *
     * @return array
     */
    public function toArray();

    /**
     * Set param
     *
     * @param string $name The unique name of the param to set
     * @param string $value The value of the given param
     */
    public function setParam($name, $value);

    /**
     * Get a param by name
     *
     * @param string $name The unique name of the param to get
     * @param EntityInterface $mergeWithEntity Optional entity to merge variables with
     * @return string
     */
    public function getParam($name, EntityInterface $mergeWithEntity = null);

    /**
     * Get id of action
     *
     * @return id
     */
    public function getId();

    /**
     * Set id of action
     *
     * @param int $id
     */
    public function setId($id);

    /**
     * Get child actions to remove
     *
     * @return ActionInterface[]
     */
    public function getRemovedActions();

    /**
     * Get child actions array
     *
     * @return ActionInterface[]
     */
    public function getActions();
}