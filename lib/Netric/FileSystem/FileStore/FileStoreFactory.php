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
class FileStoreFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return FileStoreInterface
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $config = $sl->get('Netric\Config\Config');
        $store = $config->get('files')->get('store');

        $fileStore = 'Netric\FileSystem\FileStore';

        if($store === "mogile")
            $fileStore .= '\MogileFileStore';
        else
            $fileStore .= '\LocalFileStore';

        return $sl->get($fileStore);
    }
}
