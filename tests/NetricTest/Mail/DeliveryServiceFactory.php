<?php
namespace NetricTest\Mail;

use PHPUnit_Framework_TestCase;

class DeliveryServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sl = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\Mail\DeliveryService',
            $sl->get("Netric/Mail/DeliveryService")
        );
    }
}
