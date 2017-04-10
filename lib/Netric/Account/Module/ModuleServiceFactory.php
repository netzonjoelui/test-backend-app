<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Account\Module;

use Netric\ServiceManager;

/**
 * Create a module service
 */
class ModuleServiceFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return ModuleService
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $dataMapper = $sl->get('Netric/Account/Module/DataMapper/DataMapper');

        return new ModuleService($dataMapper);
    }
}
