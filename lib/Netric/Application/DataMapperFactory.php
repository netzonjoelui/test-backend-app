<?php
/**
 * Service factory for the Application datamapper
 *
 * @author Marl Tumulak <marl.tumulak@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Application;

use Netric\ServiceManager;

/**
 * Create a new Application DataMapper service
 */
class DataMapperFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return DataMapperInterface
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $config = $sl->get("Netric/Config/Config");
        return new DataMapperPgsql($config->db->host, $config->db->sysdb, $config->db->user, $config->db->password  );
    }
}
