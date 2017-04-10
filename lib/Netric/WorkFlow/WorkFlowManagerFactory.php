<?php
/**
 * Factory used to start the WorkFLow internal service
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\WorkFlow;

use Netric\ServiceManager;

/**
 * Create a WorkFlow Management service
 *
 * @package Netric\FileSystem
 */
class WorkFlowManagerFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return FileSystem
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $user = $sl->getAccount()->getUser();
        $dataMapper = $sl->get("Netric/WorkFlow/DataMapper/DataMapper");
        $entityIndex = $sl->get("EntityQuery_Index");
        $log = $sl->get("Log");

        return new WorkFlowManager($dataMapper, $entityIndex, $log);
    }
}
