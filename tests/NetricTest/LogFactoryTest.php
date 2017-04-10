<?php

namespace NetricTest;

use Netric;

use PHPUnit_Framework_TestCase;

class LogFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\Log',
            $sm->get('Log')
        );

        $this->assertInstanceOf(
            'Netric\Log',
            $sm->get('Netric\Log')
        );
    }
}