<?php
/**
 * Factory used to initialize the netric filesystem
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\FileSystem;

use Netric\ServiceManager;

/**
 * Create a file system service
 * @package Netric\FileSystem
 */
class FileSystemFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return FileSystem
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $fileStore = $sl->get('Netric\FileSystem\FileStore\FileStore');
        $user = $sl->getAccount()->getUser();
        $entityLoader = $sl->get("Netric/EntityLoader");
        $dataMapper = $sl->get("Netric/Entity/DataMapper/DataMapper");
        $entityIndex = $sl->get("Netric/EntityQuery/Index/Index");

        return new FileSystem($fileStore, $user, $entityLoader, $dataMapper, $entityIndex);
    }
}
