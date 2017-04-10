<?php
/**
 * Provides extensions for the Project object
 *
 * @author Marl Tumulak <marl.tumulak@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Entity\ObjType;

use Netric\ServiceManager\AccountServiceManagerInterface;
use Netric\Entity\Entity;
use Netric\Entity\EntityInterface;
use Netric\EntityLoader;
use Netric\EntityQuery;
use Netric\EntityQuery\Index\IndexInterface;

/**
 * Project represents a single project entity
 */
class ProjectEntity extends Entity implements EntityInterface
{
    /**
     * Entity index for running queries against
     *
     * @var IndexInterface
     */
    private $indexInterface = null;

    /**
     * The loader for a specific entity
     *
     * @var EntityLoader
     */
    private $entityLoader = null;

    /**
     * Class constructor
     *
     * @param EntityDefinition $def The definition of this type of object
     * @param EntityLoader $entityLoader The loader for a specific entity
     * @param IndexInterface $index IndexInterface for running queries against
     */
    public function __construct(&$def, EntityLoader $entityLoader, IndexInterface $indexInterface)
    {
        parent::__construct($def);

        $this->entityLoader = $entityLoader;
        $this->indexInterface = $indexInterface;
    }

    /**
     * Callback function used for derrived subclasses
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeSave(AccountServiceManagerInterface $sm)
    {
    }

    /**
     * Callback function used for derrived subclasses
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onAfterSave(AccountServiceManagerInterface $sm)
    {
    }

    /**
     * Called right before the entity is purged (hard delete)
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeDeleteHard(AccountServiceManagerInterface $sm)
    {
    }

    /**
     * Perform a clone of the project entity to another project
     *
     * @param Entity $toEntity The entity that we are cloning to
     */
    public function cloneTo(Entity $toEntity)
    {
        // Get the id of $toEntity since ::cloneTo() will set the $toEntity's id to null. We assign it back later after cloning this project
        $toEntityId = $toEntity->getId();

        // Perform the shallow copy of fields
        parent::cloneTo($toEntity);

        // Assign back the $toEntity Id since it was set to null when cloning this project using ::cloneTo().
        $toEntity->setId($toEntityId);

        // Query the tasks of this project entity
        $query = new EntityQuery("task");
        $query->where('project')->equals($this->getId());

        // Execute query and get num results
        $res = $this->indexInterface->executeQuery($query);
        $num = $res->getNum();

        // Loop through each task of this project entity
        for ($i = 0; $i < $num; $i++) {
            $task = $res->getEntity($i);

            // Create a new task to be cloned
            $toTask = $this->entityLoader->create("task");

            $task->cloneTo($toTask);

            // Move task to the project entity we are cloning
            $toTask->setValue("project", $toEntityId);

            // Save the task
            $this->entityLoader->save($toTask);
        }
    }
}

