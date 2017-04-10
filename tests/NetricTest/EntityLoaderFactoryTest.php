<?php

namespace NetricTest;

use Netric;

use PHPUnit_Framework_TestCase;

class EntityLoaderFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\EntityLoader',
            $sm->get('EntityLoader')
        );

        $this->assertInstanceOf(
            'Netric\EntityLoader',
            $sm->get('Netric\EntityLoader')
        );
    }
}