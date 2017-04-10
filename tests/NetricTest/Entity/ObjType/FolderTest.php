<?php
/**
 * Test entity activity class
 */
namespace NetricTest\Entity\ObjType;

use Netric\Entity;
use PHPUnit_Framework_TestCase;

class FolderTest extends PHPUnit_Framework_TestCase
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

    private function createTestFile()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $loader = $account->getServiceManager()->get("EntityLoader");
        $dataMapper = $this->getEntityDataMapper();

        $file = $loader->create("file");
        $file->setValue("name", "test.txt");
        $dataMapper->save($file);

        $this->testFiles[] = $file;

        return $file;
    }

    /**
     * Test dynamic factory of entity
     */
    public function testFactory()
    {
        $entity = $this->account->getServiceManager()->get("EntityFactory")->create("folder");
        $this->assertInstanceOf('\Netric\Entity\ObjType\FolderEntity', $entity);
    }
}