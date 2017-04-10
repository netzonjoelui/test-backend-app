<?php
/**
 * Factory used to initialize the netric filesystem
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Authentication;

use Netric\ServiceManager;

/**
 * Create an authentication service
 *
 * @package Netric\Authentication
 */
class AuthenticationServiceFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return FileSystem
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $key = "GENERATEDSERVERSIDEKEY";
        $userIndex = $sl->get("Netric/EntityQuery/Index/Index");
        $userLoader = $sl->get("Netric/EntityLoader");
        $request = $sl->get("Netric/Request/Request");

        return new AuthenticationService($key, $userIndex, $userLoader, $request);
    }
}
