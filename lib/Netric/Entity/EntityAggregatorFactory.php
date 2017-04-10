<?php
/**
 * Service factory for the EntityAggregator
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity;

use Netric\ServiceManager;

/**
 * Create a new EntityAggregator service for updating aggregates
 *
 * @package Netric\FileSystem
 */
class EntityAggregatorFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return FileSystem
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $entityLoader = $sl->get("Netric/EntityLoader");
        $entityIndex = $sl->get("Netric/EntityQuery/Index/Index");

        return new EntityAggregator($entityLoader, $entityIndex);
    }
}
