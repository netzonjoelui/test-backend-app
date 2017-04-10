<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015-2016 Aereus
 */
namespace Netric\ServiceManager;

use Netric\Application\Application;

/**
* Service factories are classes that handle the construction of complex/cumbersome services
*/
interface ServiceLocatorInterface
{
    /**
     * Get a service by name
     *
     * @param string $serviceName
     * @return mixed The service object and false on failure
     */
    public function get($serviceName);

    /**
     * Get the current running application
     *
     * @return Application;
     */
    public function getApplication();
}