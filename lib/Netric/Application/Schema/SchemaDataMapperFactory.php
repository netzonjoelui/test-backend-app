<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2016 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Application\Schema;

use Netric\ServiceManager\AccountServiceManagerInterface;
use Netric\ServiceManager\AccountServiceLocatorInterface;

/**
 * Create the default DataMapper for account schemas
 */
class SchemaDataMapperFactory implements AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return SchemaDataMapperInterface
     */
    public function createService(AccountServiceManagerInterface $sl)
    {
        $dbh = $sl->get("Netric/Db/Db");
        $schemaDefinition = include(__DIR__ . "/../../../../data/schema/account.php");
        return new SchemaDataMapperPgsql($dbh, $schemaDefinition);
    }
}
