<?php
/**
 * Factory used to initialize the netric filesystem filestore
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\FileSystem\FileStore;

use Netric\ServiceManager;

/**
 * Create a file system storage service that uses aereus network storage
 */
class AnsFileStoreFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return LocalFileStore
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $accountId = $sl->getAccount()->getId();
        $dataMapper = $sl->get("Netric/Entity/DataMapper/DataMapper");

        $config = $sl->get("Netric/Config/Config");
        $ansServer = $config->alib->ans_server;
        $ansAccount = $config->alib->ans_account;
        $ansPassword = $config->alib->ans_password;

        $tmpPath = $config->data_path . "/" . "tmp";

        return new AnsFileStore($accountId, $dataMapper, $ansServer, $ansAccount, $ansPassword, $tmpPath);
    }
}
