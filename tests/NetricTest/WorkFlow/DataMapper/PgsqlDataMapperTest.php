<?php
/**
 * Test the Pgsql DataMapper for WorkFlows
 */
namespace NetricTest\WorkFlow\DataMapper;

use Netric\WorkFlow\DataMapper\PgsqlDataMapper;
use Netric\WorkFlow\Action\ActionFactory;

class PgsqlDataMapperTest extends AbstractDataMapperTests
{
    public function getDataMapper()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $db = $sm->get("Db");
        $actionFactory = new ActionFactory($sm);
        $entityLoader = $sm->get("EntityLoader");
        $entityIndex = $sm->get("EntityQuery_Index");
        return new PgsqlDataMapper($db, $actionFactory, $entityLoader, $entityIndex);
    }
}
