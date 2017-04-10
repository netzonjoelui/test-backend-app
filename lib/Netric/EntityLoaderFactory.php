<?php
/**
 * Service factory for the Entity Loader
 *
 * @author Marl Tumulak <marl.tumulak@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric;

use Netric\ServiceManager;

/**
 * Create a Entity Loader service
 */
class EntityLoaderFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return EntityLoader
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $dm = $sl->get("Netric/Entity/DataMapper/DataMapper");
        $definitionLoader = $sl->get("Netric/EntityDefinitionLoader");

        return new EntityLoader($dm, $definitionLoader);
    }
}
