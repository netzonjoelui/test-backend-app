<?php
/**
 * Service factory for the Netric Db
 *
 * @author Marl Tumulak <marl.tumulak@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Db;

use Netric\ServiceManager;

/**
 * Create a Netric Db service
 */
class DbFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return DbInterface
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $config = $sl->get("Config");
        $db = new Pgsql($config->db["host"], $sl->getAccount()->getDatabaseName(), $config->db["user"], $config->db["password"]);
        $db->setSchema("acc_" . $sl->getAccount()->getId());

        return $db;
    }
}
