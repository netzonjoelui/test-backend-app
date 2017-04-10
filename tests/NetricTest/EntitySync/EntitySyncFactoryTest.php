<?php

namespace NetricTest\EntitySync;

use Netric;

use PHPUnit_Framework_TestCase;

class EntitySyncFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\EntitySync\EntitySync',
            $sm->get('EntitySync')
        );

        $this->assertInstanceOf(
            'Netric\EntitySync\EntitySync',
            $sm->get('Netric\EntitySync\EntitySync')
        );
    }
}