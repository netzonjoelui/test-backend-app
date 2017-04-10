<?php
/**
 * Test the EntityFactory service factory
 */
namespace NetricTest\Entity;

use Netric;
use PHPUnit_Framework_TestCase;

class EntityFactoryFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->assertInstanceOf('Netric\Entity\EntityFactory', $sm->get('EntityFactory'));
    }
}