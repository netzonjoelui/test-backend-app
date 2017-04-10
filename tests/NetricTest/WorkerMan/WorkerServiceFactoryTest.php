<?php
namespace NetricTest\WorkerMan;

use PHPUnit_Framework_TestCase;

class WorkerServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getApplication()->getServiceManager();
        $this->assertInstanceOf(
            'Netric\WorkerMan\WorkerService',
            $sm->get('Netric\WorkerMan\WorkerService')
        );
    }
}