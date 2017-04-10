<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\ServiceManager;

/**
 * Service factories are classes that handle the construction of complex/cumbersome services
 */
interface ApplicationServiceFactoryInterface extends ServiceFactoryInterface
{
    /**
     * Service creation factory for the application - not account specific
     *
     * @param ServiceLocatorInterface $sl ServiceLocator for injecting dependencies
     * @return mixed Initialized service object
     */
    public function createService(ServiceLocatorInterface $sl);
}
