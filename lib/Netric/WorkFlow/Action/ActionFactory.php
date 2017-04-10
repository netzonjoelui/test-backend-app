<?php
/**
 * Factory creates work flow actions
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\WorkFlow\Action;

use Netric\ServiceManager\AccountServiceManagerInterface;
use Netric\WorkFlow\Action\Exception\ActionNotFoundException;

/**
 * Get a new action instance based on a name
 *
 * @package Netric\FileSystem
 */
class ActionFactory
{
    /**
     * Service manager used to load dependencies
     *
     * @var AccountServiceManagerInterface
     */
    private $serviceManager = null;

    /**
     * Class constructor
     *
     * @param AccountServiceManagerInterface $sl ServiceLocator implementation for injecting dependencies
     */
    public function __construct(AccountServiceManagerInterface $sl)
    {
        $this->serviceManager = $sl;
    }

    /**
     * Create a new action based on a name
     *
     * @param string $type The name of the type of action
     * @return ActionInterface
     * @throws ActionNotFoundException if the $type is not a valid action
     * @throws \InvalidArgumentException If the caller tries to send an empty string for type
     */
    public function create($type)
    {
        $action = null;

        if (!$type)
            throw new \InvalidArgumentException("Cannot call create with an empty type param");

        /*
         * First convert object name to file name - camelCase with upper case first.
         * Example: 'test' becomes 'Test'
         * Example: 'my_action' becomes 'MyAction'.
         */
        $className = ucfirst($type);
        if (strpos($type, "_") !== false)
        {
            $parts = explode("_", $className);
            $className = "";
            foreach ($parts as $word)
                $className .= ucfirst($word);
        }

        // Every action must have a factory
        $className = "\\Netric\\WorkFlow\\Action\\". $className . "ActionFactory";

        // Use factory if it exists
        if (class_exists($className))
        {
            $action = $className::create($this->serviceManager);
        }
        else
        {
            throw new ActionNotFoundException("Action factory $className could not be found");
        }

        return $action;
    }
}
