<?php
/**
 * Db implementation of module DataMapper test
 */
namespace NetricTest\Account\Module\DataMapper;

use Netric\Account\Module\DataMapper;

class DataMapperDbTest extends AbstractDataMapperTests
{
    /**
     * Get Db implementation of DataMapper
     *
     * @return DataMapper\DataMapperInterface
     */
    public function getDataMapper()
    {
        $account = \NetricTest\Bootstrap::getAccount();

        $sl = $account->getServiceManager();
        $db = $sl->get("Db");
        $config = $sl->get("Config");

        // Setup a user for testing
        $user = $account->getUser();

        return new DataMapper\DataMapperDb($db, $config, $user);
    }
}