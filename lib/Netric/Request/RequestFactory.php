<?php
/**
 * Factory used to initialize the current request
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Request;

use Netric\ServiceManager;
use Netric\Console\Console;

/**
 * Create a request object
 *
 * @package Netric\Request
 */
class RequestFactory implements ServiceManager\ApplicationServiceFactoryInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\ServiceLocatorInterface $sl ServiceLocator for injecting dependencies
     * @return RequestInterface
     */
    public function createService(ServiceManager\ServiceLocatorInterface $sl)
    {
        if (Console::isConsole()) { 
            return new ConsoleRequest();
        }

        return new HttpRequest();
    }
}
