<?php
namespace NetricTest\Crypt;

use PHPUnit_Framework_TestCase;

class VaultServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sl = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\Crypt\VaultService',
            $sl->get("Netric/Crypt/VaultService")
        );
    }
}
