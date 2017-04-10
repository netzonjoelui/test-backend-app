<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\WorkFlow\Action;

use Netric\ServiceManager\AccountServiceManagerInterface;

/**
 * Factory to create a new AssignAction
 */
class AssignActionFactory
{
    /**
     * Construct new action
     *
     * @param AccountServiceManagerInterface $serviceLocator For loading dependencies
     * @return ActionInterface
     */
    static public function create(AccountServiceManagerInterface $serviceLocator)
    {
        // Return a new TestAction
        $entityLoader = $serviceLocator->get("EntityLoader");
        $actionFactory = new ActionFactory($serviceLocator);
        $groupingsLoader = $serviceLocator->get("EntityGroupings_Loader");
        $queryIndex = $serviceLocator->get("EntityQuery_Index");
        return new AssignAction($entityLoader, $actionFactory, $groupingsLoader, $queryIndex);
    }
}
