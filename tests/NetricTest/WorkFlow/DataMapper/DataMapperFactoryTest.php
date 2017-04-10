<?php
/**
 * Test the DataMapper factory for WorkFlow
 */
namespace NetricTest\WorkFlow\DataMapper;

use PHPUnit_Framework_TestCase;

class DataMapperFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\WorkFlow\DataMapper\DataMapperInterface',
            $sm->get('Netric\WorkFlow\DataMapper\DataMapper')
        );
    }
}