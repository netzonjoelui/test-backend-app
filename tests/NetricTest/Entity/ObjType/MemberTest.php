<?php
/**
 * Test entity member class
 */
namespace NetricTest\Entity\ObjType;

use Netric\Entity;
use Netric\Entity\ObjType\UserEntity;
use PHPUnit_Framework_TestCase;

class MemberTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tennant account
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
     * Test dynamic factory of entity
     */
    public function testFactory()
    {
        $def = $this->account->getServiceManager()->get("EntityDefinitionLoader")->get("member");
        $entity = $this->account->getServiceManager()->get("EntityFactory")->create("member");
        $this->assertInstanceOf("\\Netric\\Entity\\ObjType\\MemberEntity", $entity);
    }
}