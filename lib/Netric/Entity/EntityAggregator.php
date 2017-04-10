<?php
/**
 * Handle aggregating values for entities
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity;

use Netric\EntityDefinition;
use Netric\EntityLoader;
use Netric\EntityGroupings;
use Netric\EntityQuery;
use Netric\EntityQuery\Aggregation;
use Netric\EntityQuery\Index\IndexInterface;


/**
 * Class for handing entity aggregates
 *
 * If an entity has an 'aggregates' property in the
 * definition to means that one field is used to update
 * an aggregate of a related entity.
 *
 * For example:
 * A 'product_review' entity has a field called 'rating'
 * which will update the avg rating of the 'product' entity
 * the the review is concerning.
 *
 * Another example:
 * A 'task' entity has a 'cost_actual' field which is updated (sum)
 * every time a new 'time' entity is created logging hours referencing
 * a 'task' entity.
 *
 * In the future this may also be used for entity OLAP cubes, but not for now.
 */
class EntityAggregator
{
    /**
     * Handle to the entity loader for creating and loading entities
     *
     * @var EntityLoader
     */
    private $entityLoader = null;

    /**
     * Index for running queries
     *
     * @var IndexInterface
     */
    private $entityIndex = null;

    /**
     * Constructor
     *
     * @param EntityLoader $entityLoader To load and save entities
     * @param IndexInterface $entityIndex The query entities
     */
    public function __construct(EntityLoader $entityLoader, IndexInterface $entityIndex)
    {
        $this->entityLoader = $entityLoader;
        $this->entityIndex = $entityIndex;
    }

    /**
     * Update any aggregates related to this entity
     *
     * @param Entity $entity
     */
    public function updateAggregates(Entity $entity)
    {
        $def = $entity->getDefinition();
        $aggregates = $def->aggregates;

        // Check to see if this entity has any aggregates
        if (count($aggregates) < 0)
        {
            return;
        }

        // Loop through each aggregate and update as necessary
        foreach ($aggregates as $agg)
        {

            $field = $def->getField($agg->field);
            $referencedId = $entity->getValue($agg->field);

            if ($referencedId && $field->type == "object" && $field->subtype)
            {
                // Create a new query to aggregate against
                $query = new EntityQuery($def->getObjType());

                // Make sure we are referencing the same entity
                $query->where($agg->field)->equals($entity->getValue($agg->field));

                // Initialize an aggregate
                $queryAgg = null;

                // Create new aggregate based on type
                switch ($agg->type)
                {
                    case 'sum':
                        $queryAgg = new Aggregation\Sum("update");
                        break;

                    case 'avg':
                        $queryAgg = new Aggregation\Avg("update");
                        break;
                }

                // If aggregate was valid (and created), then update referenced entity
                if ($queryAgg)
                {
                    // Set the field we are calculating on
                    $queryAgg->setField($agg->calcField);

                    // Add the aggregate to the quer
                    $query->addAggregation($queryAgg);

                    // Get value of aggregate
                    $aggValue = $this->entityIndex->executeQuery($query)->getAggregation("update");

                    // Update $agg['refField'] of referenced entity
                    $entityToUpdate = $this->entityLoader->get($field->subtype, $referencedId);
                    $entityToUpdate->setValue($agg->refField, $aggValue);
                    $this->entityLoader->save($entityToUpdate);
                }
            }
        }
    }
}