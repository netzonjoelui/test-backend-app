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
 * Create a file system storage service
 */
class LocalFileStoreFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return LocalFileStore
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $config = $sl->get("Netric/Config/Config");
        $dataPath = $config->data_path;
        $accountId = $sl->getAccount()->getId();
        $dataMapper = $sl->get("Netric/Entity/DataMapper/DataMapper");

        return new LocalFileStore($accountId, $dataPath, $dataMapper);
    }
}
