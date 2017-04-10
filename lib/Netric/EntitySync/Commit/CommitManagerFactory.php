<?php
/**
 * Service factory for the Entity Sync Commit Manager
 *
 * @author Marl Tumulak <marl.tumulak@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\EntitySync\Commit;

use Netric\ServiceManager;

/**
 * Create a Entity Sync Commit Manager service
 *
 * @package Netric\EntitySync\Commit\CommitManager
 */
class CommitManagerFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return CommitManager
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $dm = $sl->get("Netric/EntitySync/Commit/DataMapper/DataMapper");
        return new CommitManager($dm);
    }
}
