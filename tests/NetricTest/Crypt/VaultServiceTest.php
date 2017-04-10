<?php
namespace NetricTest\Crypt;

use PHPUnit_Framework_TestCase;

class VaultServiceTest extends PHPUnit_Framework_TestCase
{
    public function testGetSecret()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sl = $account->getServiceManager();
        $vaultService = $sl->get("Netric/Crypt/VaultService");
        $this->assertNotEmpty($vaultService->getSecret("My Test Key"));
    }
}
