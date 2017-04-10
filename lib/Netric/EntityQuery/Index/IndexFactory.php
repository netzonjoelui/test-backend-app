<?php
/**
 * Service factory for the EntityQuery Index
 *
 * @author Marl Tumulak <marl.tumulak@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\EntityQuery\Index;

use Netric\ServiceManager;

/**
 * Create a EntityQuery Index service
 */
class IndexFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return DbInterface
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        return new Pgsql($sl->getAccount());
    }
}
