<?php
namespace NetricTest\WorkerMan;

use PHPUnit_Framework_TestCase;

class ConfigFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\Config\Config',
            $sm->get('Netric\Config\Config')
        );
    }
}