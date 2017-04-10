<?php
/**
 * Test the LocalFileStoreFactory service
 */
namespace NetricTest\FileSystem;

use Netric;
use PHPUnit_Framework_TestCase;

class LocalFileStoreFactoryTest extends PHPUnit_Framework_TestCase
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
            $sl->get('Netric/FileSystem/FileStore/LocalFileStore')
        );
    }
}