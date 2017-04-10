<?php
/**
 * Service factory for Log
 *
 * @author Marl Tumulak <marl.tumulak@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric;

use Netric\ServiceManager;

/**
 * Create a Log service
 */
class LogFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return Log
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        return $sl->getAccount()->getApplication()->getLog();
    }
}
