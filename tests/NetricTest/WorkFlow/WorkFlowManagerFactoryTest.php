<?php
/**
 * Test the WorkFlowManager factory
 */
namespace NetricTest\WorkFlow\DataMapper;

use PHPUnit_Framework_TestCase;

class WorkFlowManagerFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\WorkFlow\WorkFlowManager',
            $sm->get('Netric\WorkFlow\WorkFlowManager')
        );
    }
}