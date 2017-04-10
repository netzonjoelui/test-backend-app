<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\ServiceManager;

/**
 * ServiceManager implementation for managing plugins
 *
 * Automatically registers an initializer which should be used to verify that
 * a plugin instance is of a valid type. Additionally, allows plugins to accept
 * an array of options for the constructor, which can be used to configure
 * the plugin when retrieved. Finally, enables the allowOverride property by
 * default to allow registering factories, aliases, and invokables to take
 * the place of those provided by the implementing class.
 */
abstract class AbstractPluginManager
{
    /**
     * List of classes that can be invoked directly without a factory
     *
     * This is similar to the maps above, except a factory is not needed.
     * In order for a class to be invokable however, it must, by definition,
     * be invokable without any constructor arguments since the service manager
     * will simply to a new on the class name and return a singleton instance.
     *
     * The target will NOT be appended with 'Factory' so
     * "test_invokable" => "Netric/ServiceManager/Test/Service",
     * will create a new
     * Netric/ServiceManager/Test/Service
     *
     * @var array
     */
    protected $invokableClasses = array(
        "test_invokable" => 'Netric\ServiceManager\Test\Service',
    );

    /**
     * Validate the plugin
     *
     * Checks that the filter loaded is either a valid callback or an instance
     * of FilterInterface.
     *
     * @param  mixed $plugin
     * @return void
     * @throws Exception\RuntimeException if invalid
     */
    abstract public function validatePlugin($plugin);

    /**
     * Retrieve a service from the manager by name
     *
     * Allows passing an array of options to use when creating the instance.
     * get will use these and pass them to the instance
     * constructor if not null and a non-empty array.
     *
     * @param  string $name
     * @param  array $options
     *
     * @return object
     *
     * @throws Exception\ServiceNotFoundException
     */
    public function get($name, $options = [])
    {
        $plugin = false;

        // First check to see if $sServiceName has been mapped to a factory
        $className = $this->invokableClasses[$name];

        // Try to load the service and allow exception to be thrown if not found
        if ($className)
        {
            if (class_exists($className))
            {
                $plugin = (count($options)) ? new $className($options) : new $className();
            }
            else
            {
                throw new Exception\ServiceNotFoundException(sprintf(
                    '%s: A plugin by the name "%s" was not found and could not be instantiated.',
                    get_class($this) . '::' . __FUNCTION__,
                    $className
                ));
            }
        }

        // TODO: Cache?

        return $plugin;
    }
}