<?php
/**
 * Test the SchemDataMapper service factory
 */
namespace NetricTest\Application\Schema;

use PHPUnit_Framework_TestCase;

class SchemaDataMapperFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\Application\Schema\SchemaDataMapperInterface',
            $sm->get('Netric\Application\Schema\SchemaDataMapper')
        );
    }
}