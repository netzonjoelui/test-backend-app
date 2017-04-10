<?php
/**
 * Test an entity factory
 */
namespace NetricTest\Entity;

use Netric\Entity;
use PHPUnit_Framework_TestCase;

class EntityFactoryTest extends PHPUnit_Framework_TestCase
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
     * @var \Netric\Entity\EntityFactory
     */
    private $entityFactory = null;


    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $sm = $this->account->getServiceManager();
        $this->entityFactory = $sm->get("EntityFactory");
    }

    /**
     * Make sure we can get an extended object type
     */
    public function testCreateUser()
    {
        $user = $this->entityFactory->create("user");
        $this->assertInstanceOf("\\Netric\\Entity\\ObjType\\UserEntity", $user);
    }
}