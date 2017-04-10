<?php
/**
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\WorkFlow\Action;

use Netric\Entity\EntityInterface;
use Netric\EntityLoader;
use Netric\EntityQuery\Index\IndexInterface;
use Netric\EntityQuery;
use Netric\WorkFlow\WorkFlowInstance;

/**
 * Action used to check if conditions match for the entity being acted on
 *
 * Params in the 'data' field:
 *
 * 'conditions' => [
 *      [
 *          'blogic'=>EntityQuery\Where::COMBINED_BY_*,
 *          'field_name'='name_of_entity_field',
 *          'operator'=>EntityQuery\Where::OPERATOR_*
 *          'value'=>'The value the operator is comparing with'
 *      ]
 * ]
 */
class CheckConditionAction extends AbstractAction implements ActionInterface
{
    /**
     * Entity index for running queries against
     *
     * @var IndexInterface
     */
    private $entityIndex = null;

    /**
     * Get dependencies
     *
     * @param EntityLoader $entityLoader
     * @param ActionFactory $actionFactory
     * @param IndexInterface $entityIndex
     */
    public function __construct(EntityLoader $entityLoader, ActionFactory $actionFactory, IndexInterface $entityIndex)
    {
        $this->entityIndex = $entityIndex;

        // Should always call the parent constructor for base dependencies
        parent::__construct($entityLoader, $actionFactory);
    }

    /**
     * Execute this action
     *
     * @param WorkFlowInstance $workflowInstance The workflow instance we are executing in
     * @return bool true on success, false on failure
     */
    public function execute(WorkFlowInstance $workflowInstance)
    {
        $entity = $workflowInstance->getEntity();

        // Entity must be saved to meet conditions
        if (!$entity->getId())
            return false;

        // Get merged params
        $params = $this->getParams($entity);

        // We use the index for checking if conditions match since it contains all the condition logic
        $query = new EntityQuery($entity->getDefinition()->getObjType());

        // Add the entity as a condition to see if it meets the criteria
        $query->where("id")->equals($entity->getId());

        // Query deleted if the entity is deleted
        if ($entity->isDeleted())
            $query->andWhere("f_deleted")->equals(true);

        if (isset($params['conditions']) && is_array($params['conditions']))
        {
            foreach ($params['conditions'] as $cond)
            {
                $query->andWhere($cond['field_name'], $cond['operator'], $cond['value']);
            }
        }

        // Get results
        $result = $this->entityIndex->executeQuery($query);
        return ($result->getNum()) ? true : false;
    }
}
