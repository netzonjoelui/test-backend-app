<?php
/**
 * Test the NotifierFactory factory
 */
namespace NetricTest\Entity\Notifier;

use PHPUnit_Framework_TestCase;

class NotifierFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\Entity\Notifier\Notifier',
            $sm->get('Netric\Entity\Notifier\Notifier')
        );
    }
}