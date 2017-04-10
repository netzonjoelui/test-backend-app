<?php
/**
 * Our implementation of a ServiceLocator pattern
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\ServiceManager;

use Netric;

/**
 * Class for constructing, caching, and finding services by name
 */
class ApplicationServiceManager extends AbstractServiceManager
{
    /**
     * Map a name to a class factory
     *
     * The target will be appended with 'Factory' so
     * "test" => "Netric/ServiceManager/Test/Service",
     * will load
     * Netric/ServiceManager/Test/ServiceFactory
     *
     * Use these sparingly because it does obfuscate from the
     * client what classes are being loaded.
     *
     * @var array
     */
    protected $invokableFactoryMaps = array(
        // Application config
        "Config" => "Netric/Config/Config",
    );
}
