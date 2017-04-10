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
 * Create a new WaitConditionAction
 */
class WaitConditionActionFactory
{
    /**
     * Create a new action based on a name
     *
     * @param AccountServiceManagerInterface $serviceLocator For loading dependencies
     * @return ActionInterface
     */
    static public function create(AccountServiceManagerInterface $serviceLocator)
    {
        // Return a new WaitConditionAction
        $entityLoader = $serviceLocator->get("EntityLoader");
        $actionFactory = new ActionFactory($serviceLocator);
        $workFlowDataMapper = $serviceLocator->get("Netric/WorkFlow/DataMapper/DataMapper");
        return new WaitConditionAction($entityLoader, $actionFactory, $workFlowDataMapper);
    }
}
