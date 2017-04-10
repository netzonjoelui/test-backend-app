<?php
/**
 * Factory used to initialize the netric filesystem filestore
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\FileSystem\FileStore;

use Netric\ServiceManager;
use MogileFs;

/**
 * Create a file system storage service that uses aereus network storage
 */
class MogileFileStoreFactory implements ServiceManager\AccountServiceLocatorInterface
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
        $tmpPath = $config->data_path . "/" . "tmp";

        // Set the port
        $port = ($config->files->port) ? $config->files->port : 7001;

        // Establish mogile connection
        $mfsClient = new MogileFs();
        $mfsClient->connect($config->files->server, $port, $config->files->account);

        return new MogileFileStore(
            $accountId,
            $mfsClient,
            $dataMapper,
            $tmpPath
        );
    }
}
