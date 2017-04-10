<?php
namespace NetricTest\Mail;

use PHPUnit_Framework_TestCase;

class SenderServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sl = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\Mail\SEnderService',
            $sl->get("Netric/Mail/SenderService")
        );
    }
}
