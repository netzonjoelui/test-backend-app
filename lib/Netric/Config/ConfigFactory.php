<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Config;

use Netric\ServiceManager\ApplicationServiceFactoryInterface;
use Netric\ServiceManager\ServiceLocatorInterface;

/**
 * Handle setting up the config service
 */
class ConfigFactory implements ApplicationServiceFactoryInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceLocatorInterface $sl ServiceLocator for injecting dependencies
     * @return Config
     */
    public function createService(ServiceLocatorInterface $sl)
    {
        $applicationEnvironment = (getenv('APPLICATION_ENV')) ? getenv('APPLICATION_ENV') : "production";
        $config = ConfigLoader::fromFolder(
            __DIR__ . "/../../../config",
            $applicationEnvironment
        );

        // If for any reason config is not set then we should fail here
        if ($config == null) {
            throw new \RuntimeException("Config returned null, this should never happen");
        }

        return $config;
    }
}
