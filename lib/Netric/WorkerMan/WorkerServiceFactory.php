<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\WorkerMan;

use Netric\ServiceManager\ApplicationServiceFactoryInterface;
use Netric\ServiceManager\ServiceLocatorInterface;

/**
 * Handle setting up a worker service
 */
class WorkerServiceFactory implements ApplicationServiceFactoryInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceLocatorInterface $sl ServiceLocator for injecting dependencies
     * @return WorkerService
     */
    public function createService(ServiceLocatorInterface $sl)
    {
        $config = $sl->get('Netric\Config\Config');

        $queue = null;

        switch ($config->workers->queue) {
            case 'gearman':
                $queue = new Queue\Gearman($config->workers->server);
                break;
            default:
                throw new \RuntimeException("Worker queue not supported: " . $config->workers->queue);
                break;
        }

        return new WorkerService($sl->getApplication(), $queue);
    }
}
