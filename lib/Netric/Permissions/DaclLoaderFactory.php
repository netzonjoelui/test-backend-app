<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Permissions;

use Netric\ServiceManager;

/**
 * Create a DaclLoader
 */
class DaclLoaderFactory implements ServiceManager\ApplicationServiceFactoryInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\ServiceLocatorInterface $sl ServiceLocator for injecting dependencies
     * @return DaclLoader
     */
    public function createService(ServiceManager\ServiceLocatorInterface $sl)
    {
        $entityLoader = $sl->get("EntityLoader");
        return new DaclLoader($entityLoader);
    }
}
