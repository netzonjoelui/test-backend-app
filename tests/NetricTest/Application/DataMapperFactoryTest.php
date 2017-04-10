<?php

namespace NetricTest\Application;

use Netric;

use PHPUnit_Framework_TestCase;

class DataMapperFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\Application\DataMapperPgsql',
            $sm->get('Application_DataMapper')
        );

        $this->assertInstanceOf(
            'Netric\Application\DataMapperPgsql',
            $sm->get('Netric\Application\DataMapper')
        );
    }
}