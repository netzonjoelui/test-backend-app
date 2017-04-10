<?php

namespace NetricTest\EntityDefinition\DataMapper;

use Netric;

use PHPUnit_Framework_TestCase;

class DataMapperFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\EntityDefinition\DataMapper\PgSql',
            $sm->get('EntityDefinition_DataMapper')
        );

        $this->assertInstanceOf(
            'Netric\EntityDefinition\DataMapper\PgSql',
            $sm->get('Netric\EntityDefinition\DataMapper\DataMapper')
        );
    }
}