<?php
/**
 * Factory to create a new ConditionsMatchAcion
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\WorkFlow\Action;

use Netric\ServiceManager\AccountServiceManagerInterface;

/**
 * Create a new CheckConditionAction
 */
class CheckConditionActionFactory
{
    /**
     * Create a new action based on a name
     *
     * @param AccountServiceManagerInterface $serviceLocator For loading dependencies
     * @return ActionInterface
     */
    static public function create(AccountServiceManagerInterface $serviceLocator)
    {
        // Return a new CheckConditionAction
        $entityLoader = $serviceLocator->get("EntityLoader");
        $queryIndex = $serviceLocator->get("EntityQuery_Index");
        $actionFactory = new ActionFactory($serviceLocator);
        return new CheckConditionAction($entityLoader, $actionFactory, $queryIndex);
    }
}
