<?php
/**
 * Service factory for the ActivityLog
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity;

use Netric\ServiceManager;

/**
 * Factory for constructing an activity log service
 */
class ActivityLogFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return FileSystem
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $entityLoader = $sl->get("EntityLoader");
        $groupingsLoader = $sl->get("EntityGroupings_Loader");
        $currentUser = $sl->getAccount()->getUser();

        return new ActivityLog($entityLoader, $groupingsLoader, $currentUser);
    }
}
