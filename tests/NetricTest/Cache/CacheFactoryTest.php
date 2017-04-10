<?php

namespace NetricTest\Cache;

use Netric;

use PHPUnit_Framework_TestCase;

class CacheFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->assertInstanceOf(
            'Netric\Cache\AlibCache',
            $sm->get('Cache')
        );

        $this->assertInstanceOf(
            'Netric\Cache\AlibCache',
            $sm->get('Netric\Cache\Cache')
        );
    }
}