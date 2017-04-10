<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\ServiceManager;

/**
 * Service factories are classes that handle the construction of complex/cumbersome services
 *
 * We just need to make sure that account or application interfaces extend this so the
 * service locator knows if the factory is a service locator factory.
 *
 * It will check if ($factory Instanceof ServiceFactoryInterface) before calling
 * ::create to instantiate the service.
 */
interface ServiceFactoryInterface
{
}
