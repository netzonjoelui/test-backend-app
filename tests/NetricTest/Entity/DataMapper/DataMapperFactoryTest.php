<?php

namespace NetricTest\Entity\DataMapper;

use Netric;

use PHPUnit_Framework_TestCase;

class DataMapperFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\Entity\DataMapper\PgSql',
            $sm->get('Entity_DataMapper')
        );

        $this->assertInstanceOf(
            'Netric\Entity\DataMapper\PgSql',
            $sm->get('Netric\Entity\DataMapper\DataMapper')
        );
    }
}