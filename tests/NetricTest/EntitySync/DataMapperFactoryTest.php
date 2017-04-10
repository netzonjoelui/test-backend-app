<?php

namespace NetricTest\EntitySync;

use Netric;

use PHPUnit_Framework_TestCase;

class DataMapperFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\EntitySync\DataMapperPgsql',
            $sm->get('EntitySync_DataMapper')
        );

        $this->assertInstanceOf(
            'Netric\EntitySync\DataMapperPgsql',
            $sm->get('Netric\EntitySync\DataMapper')
        );
    }
}