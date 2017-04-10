<?php
/**
 * Construct the settings service
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Settings;

use Netric\ServiceManager;

/**
 * Create a new settings service
 *
 * @package Netric\FileSystem
 */
class SettingsFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return FileSystem
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $dbh = $sl->get("Db");
        $cache = $sl->get("Cache");
        $account = $sl->getAccount();
        return new Settings($dbh, $account, $cache);
    }
}
