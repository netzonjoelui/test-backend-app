<?php
/**
 * Test the ActivityLog service factory
 */
namespace NetricTest\Settings;

use Netric\Entity;
use PHPUnit_Framework_TestCase;

class ActivityLogFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\Entity\ActivityLog',
            $sm->get('Netric\Entity\ActivityLog')
        );
    }
}