<?php

namespace NetricTest;

use Netric;

use PHPUnit_Framework_TestCase;

class EntityDefinitionLoaderFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\EntityDefinitionLoader',
            $sm->get('EntityDefinitionLoader')
        );

        $this->assertInstanceOf(
            'Netric\EntityDefinitionLoader',
            $sm->get('Netric\EntityDefinitionLoader')
        );
    }
}