<?php
/**
 * Test the FileStoreFactory service
 *
 * This is used to construct a specific FileStoreInterface based on system configuration.
 */
namespace NetricTest\FileSystem;

use Netric;
use PHPUnit_Framework_TestCase;

class FileStoreFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * Reference to account running for unit tests
     *
     * @var \Netric\Account\Account
     */
    private $account = null;


    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
    }

    public function testCreateService()
    {
        $sl = $this->account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\FileSystem\FileStore\FileStoreInterface',
            $sl->get('Netric/FileSystem/FileStore/FileStore')
        );
    }
}