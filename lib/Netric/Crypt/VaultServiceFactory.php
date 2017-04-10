<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Crypt;

use Netric\ServiceManager;

/**
 * Create a service for getting and setting secrets
 */
class VaultServiceFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return VaultService
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        return new VaultService();
    }
}
