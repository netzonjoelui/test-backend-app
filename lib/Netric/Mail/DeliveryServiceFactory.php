<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Mail;

use Netric\EntitySync\Collection\CollectionFactory;
use Netric\ServiceManager;

/**
 * Create a service for delivering mail
 */
class DeliveryServiceFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return DeliveryService
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $user = $sl->getAccount()->getUser();
        $entityLoader = $sl->get("EntityLoader");
        $groupingsLoader = $sl->get("Netric/EntityGroupings/Loader");
        $log = $sl->get("Log");
        $index = $sl->get("EntityQuery_Index");
        $fileSystem = $sl->get("Netric/FileSystem/FileSystem");

        return new DeliveryService(
            $log,
            $entityLoader,
            $groupingsLoader,
            $index,
            $fileSystem
        );
    }
}
