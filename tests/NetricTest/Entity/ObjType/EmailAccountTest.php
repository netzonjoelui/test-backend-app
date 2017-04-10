<?php
/**
 * Test EmailAccount entity
 */
namespace NetricTest\Entity\ObjType;

use Netric\Entity;
use Netric\Crypt\BlockCipher;
use PHPUnit_Framework_TestCase;

class EmailAccountEntityTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tenant account
     *
     * @var \Netric\Account\Account
     */
    private $account = null;

    /**
     * Administrative user
     *
     * @var \Netric\User
     */
    private $user = null;


    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->user = $this->account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);
    }

    /**
     * Test factory
     */
    public function testFactory()
    {
        $entity = $this->account->getServiceManager()->get("EntityFactory")->create("email_account");
        $this->assertInstanceOf("\\Netric\\Entity\\ObjType\\EmailAccountEntity", $entity);
    }

    public function testOnBeforeSavePasswordEncrypt()
    {
        $entity = $this->account->getServiceManager()->get("EntityFactory")->create("email_account");
        $entity->setValue("address", "test@test.com");
        $entity->setValue("password", "test");

        // Simulate onBeforeSave
        $serviceManager = $this->account->getServiceManager();
        $entity->onBeforeSave($serviceManager);

        $vaultService = $serviceManager->get("Netric/Crypt/VaultService");

        $blockCypher = new BlockCipher($vaultService->getSecret("EntityEnc"));
        $encrypted = $blockCypher->encrypt("test");
        $this->assertEquals($encrypted, $entity->getValue("password"));

        // Test is decryped
        $decrypted = $blockCypher->decrypt($entity->getValue("password"));
        $this->assertEquals("test", $decrypted);

        // Make sure we don't encrypt it again
        $entity->resetIsDirty();
        $entity->onBeforeSave($serviceManager);
        $encrypted = $blockCypher->encrypt("test");
        $this->assertEquals($encrypted, $entity->getValue("password"));
    }
}
