<?php
/**
 * Test entity case class
 */
namespace NetricTest\Entity\ObjType;

use Netric\Entity;
use Netric\Entity\ObjType\UserEntity;
use PHPUnit_Framework_TestCase;

class CaseTest extends PHPUnit_Framework_TestCase
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
        $def = $this->account->getServiceManager()->get("EntityDefinitionLoader")->get("case");
        $entity = $this->account->getServiceManager()->get("EntityFactory")->create("case");
        $this->assertInstanceOf("\\Netric\\Entity\\ObjType\\CaseEntity", $entity);
    }

    public function testOnAfterSave()
    {
        $sm = $this->account->getServiceManager();
        $entityLoader = $sm->get("EntityLoader");

        $case = $sm->get("EntityFactory")->create("case");
        $case->setValue("title", 'Test Case');
        $case->setValue("owner_id", UserEntity::USER_CURRENT);
        $case->onAfterSave($sm);

        // Cleanup
        $entityLoader->delete($case, true);
    }
}