<?php
/**
 * Our base implementation of a ServiceLocator pattern
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\ServiceManager;

use Netric\Application\Application;

/**
 * Class for constructing, caching, and finding services by name
 */
abstract class AbstractServiceManager implements ServiceLocatorInterface
{
    /**
     * Cached services that have already been constructed
     *
     * @var array
     */
    protected $loadedServices = array();

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
        // Test service map
        "test" => "Netric/ServiceManager/Test/Service"
    );

    /**
     * Optional parent used to walk up a tree
     *
     * This could be used in cases where the Applicaiton has a base
     * set of services it can load, but the account service manager
     * has it's own account specific services.
     *
     * @var ServiceLocatorInterface
     */
    protected $parentServiceLocator = null;

    /**
     * Handle to the running application
     *
     * @var Application
     */
    protected $application = null;

    /**
     * Class constructor
     *
     * We are private because the class must be a singleton to assure resources
     * are initialized only once.
     *
     * @param Application $application Handle to the running application
     * @param ServiceLocatorInterface $parentServiceLocator Optional parent for walking a tree
     */
    public function __construct(
        Application $application,
        ServiceLocatorInterface $parentServiceLocator = null
    )
    {
        $this->application = $application;
        $this->parentServiceLocator = $parentServiceLocator;
    }

    /**
     * Get account instance of the running application
     *
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Get a service by name
     *
     * @param string $serviceName
     * @return mixed The service object and false on failure
     */
    public function get($serviceName)
    {
        return $this->initializeServiceByFactory($serviceName, true);
    }

    /**
     * Clear all loaded services causing the factories to be called again
     */
    public function clearLoadedServices()
    {
        $this->loadedServices = [];
    }

    /**
     * Attempt to initialize a service by loading a factory
     *
     * @param string $serviceName The class name of the service to load
     * @param bool $bCache Flag to enable caching this service for future requests
     * @throws Exception\ServiceNotFoundException Could not autoload factory for named service
     * @return mixed Service instance if loaded, null if class not found
     */
    private function initializeServiceByFactory($serviceName, $bCache=true)
    {
        // First check to see if $sServiceName has been mapped to a factory
        $serviceName = $this->getInvokableTarget($serviceName);

        // Normalise the serviceName
        $serviceName = $this->normalizeClassPath($serviceName);

        // First check to see if the service was already loaded
        if ($this->isLoaded($serviceName))
            return $this->loadedServices[$serviceName];

        // Check the parent if it was already loaded
        if ($this->parentServiceLocator) {
            if ($this->parentServiceLocator->isLoaded($serviceName)) {
                return $this->parentServiceLocator->get($serviceName);
            }
        }

        // Get actual class name by appending 'Factory' and normalizing slashes
        $classPath = $this->getServiceFactoryPath($serviceName);

        // Load the the service for the first time
        $service = null;

        // Try to load the service and allow exception to be thrown if not found
        if ($classPath) {
            if (class_exists($classPath)) {
                $factory = new $classPath();
            } else {
                throw new Exception\ServiceNotFoundException(sprintf(
                    '%s: A service by the name "%s" was not found and could not be instantiated.',
                    get_class($this) . '::' . __FUNCTION__,
                    $classPath
                ));
            }


            if ($factory Instanceof ServiceFactoryInterface)
            {
                $service = $factory->createService($this);
            }
            else
            {
                throw new Exception\ServiceNotFoundException(sprintf(
                    '%s: The factory interface must implement Netric/ServiceManager/AccountServiceLocatorInterface.',
                    get_class($this) . '::' . __FUNCTION__,
                    $classPath
                ));
            }
        }

        // Cache for future calls
        if ($bCache)
        {
            $this->loadedServices[$serviceName] = $service;
        }

        return $service;
    }

    /**
     * Normalize class path
     *
     * @param string $classPath The unique name of the service to load
     * @return string Autoloader friendly class path
     */
    private function normalizeClassPath($classPath)
    {
        // Replace forward slash with backslash
        $classPath = str_replace('/', '\\', $classPath);

        // If class begins with "\Netric" then remove the first slash because it is not needed
        if ("\\Netric" == substr($classPath, 0 , strlen("\\Netric")))
        {
            $classPath = substr($classPath, 1);
        }

        return $classPath;
    }

    /**
     * Try to locate service loading factory from the service path
     *
     * @param string $sServiceName The unique name of the service to load
     * @return string|bool The real path to the service factory class, or false if class not found
     */
    private function getServiceFactoryPath($sServiceName)
    {
        // Append Factory to the service name, then try to load using the initialized autoloaders
        $sClassPath = $sServiceName . "Factory";
        return $sClassPath;
    }

    /**
     * Check to see if a name is mapped to a real namespaced class
     *
     * @param string $sServiceName The potential service name alias
     * @return string If a map exists the rename the service to the real path, otherwise return the alias
     */
    private function getInvokableTarget($sServiceName)
    {
        if (isset($this->invokableFactoryMaps[$sServiceName]))
        {
            $sServiceName = $this->invokableFactoryMaps[$sServiceName];
        }

        return $sServiceName;
    }

    /**
     * Check to see if a service is already loaded
     *
     * @param string $serviceName
     * @return bool true if service is loaded and cached, false if it needs to be instantiated
     */
    public function isLoaded($serviceName)
    {
        if (isset($this->loadedServices[$serviceName]) && $this->loadedServices[$serviceName] != null)
            return true;
        else
            return false;
    }
}
