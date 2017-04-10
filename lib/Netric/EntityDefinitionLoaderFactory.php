<?php
/**
 * Service factory for the Entity Definition Loader
 *
 * @author Marl Tumulak <marl.tumulak@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric;

use Netric\ServiceManager;

/**
 * Create a Entity Definition Loader service
 */
class EntityDefinitionLoaderFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return EntityDefinitionLoader
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $dm = $sl->get("Netric/EntityDefinition/DataMapper/DataMapper");
        $cache = $sl->get("Netric/Cache/Cache");

        return new EntityDefinitionLoader($dm, $cache);
    }
}
