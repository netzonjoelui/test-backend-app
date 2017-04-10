<?php
/**
 * Test entity activity class
 */
namespace NetricTest\Entity\ObjType;

use Netric\Entity;
use PHPUnit_Framework_TestCase;

class FileTest extends PHPUnit_Framework_TestCase
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
     * Test files
     *
     * @var Entity\ObjType\FileEntity[]
     */
    private $testFiles = array();

    /**
     * Entity DataMapper for creating, updating, and deleting files entities
     *
     * @var Entity\DataMapperInterface
     */
    private $entityDataMapper = null;


    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->entityDataMapper = $this->account->getServiceManager()->get("Entity_DataMapper");
        $this->user = $this->account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);
    }

    /**
     * Clean-up and test files
     */
    protected function tearDown()
    {
        foreach ($this->testFiles as $file)
        {
            if ($file->getId())
                $this->entityDataMapper->delete($file, true);
        }
    }

    /**
     * Test dynamic factory of entity
     */
    public function testFactory()
    {
        $entity = $this->account->getServiceManager()->get("EntityFactory")->create("file");
        $this->assertInstanceOf('\Netric\Entity\ObjType\FileEntity', $entity);
    }

    /**
     * Verity that hard deleting a file purges from the file store
     */
    public function testOnDeleteHard()
    {
        $fileStore = $this->account->getServiceManager()->get("Netric/FileSystem/FileStore/FileStore");

        // Create a new file & upload data
        $loader = $this->account->getServiceManager()->get("EntityLoader");
        $file = $loader->create("file");
        $file->setValue("name", "test.txt");
        $this->entityDataMapper->save($file);
        $this->testFiles[] = $file;;

        // Write data to the file
        $fileStore->writeFile($file, "my test data");
        $this->assertTrue($fileStore->fileExists($file));

        // Open a copy to check the store later since the DataMapper will zero out $file
        $fileCopy = $loader->create("file");
        $this->entityDataMapper->getById($fileCopy, $file->getId());

        // Purge the file -- second param is a delete hard param
        $this->entityDataMapper->delete($file, true);

        // Test to make sure the data was deleted
        $this->assertFalse($fileStore->fileExists($fileCopy));
    }

}