<?php
/**
 * Service factory for the Forms
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\BrowserView;

use Netric\ServiceManager;

/**
 * Create a new BrowserView service for getting and saving forms
 *
 * @package Netric\FileSystem
 */
class BrowserViewServiceFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return FileSystem
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $dbh = $sl->get("Netric/Db/Db");
        $config = $sl->get("Netric/Config/Config");
        $defLoader = $sl->get("Netric/EntityDefinitionLoader");
        $settings = $sl->get('Netric/Settings/Settings');
        return new BrowserViewService($dbh, $config, $defLoader, $settings);
    }
}
