<?php
/*
 * Demo factory used for testing
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\ServiceManager\Test;

use Netric\ServiceManager;

/**
 * Class used to demonstrate loading a service through the ServiceManager
 */
class ServiceFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param \Netric\ServiceManager\ServiceLocatorInterface $sl ServiceLocator for injecting dependencies
     * @return mixed Initailized service object
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        return new Service();
    }
}
