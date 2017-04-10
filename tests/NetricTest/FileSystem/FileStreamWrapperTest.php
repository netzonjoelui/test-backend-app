<?php
/**
 * Test that we can wrap a file
 */
namespace NetricTest\FileSystem;

use Netric\FileSystem\FileSystem;
use Netric\FileSystem\FileStreamWrapper;
use Netric\Entity\DataMapperInterface;
use Netric\EntityQuery;
use Netric\EntityLoader;
use Netric\Entity\ObjType;

use PHPUnit_Framework_TestCase;

class FileStreamWrapperTest extends PHPUnit_Framework_TestCase
{
    /**
     * Reference to account running for unit tests
     *
     * @var \Netric\Account\Account
     */
    private $account = null;

    /**
     * Get FileSystem
     *
     * @var FileSystem
     */
    private $fileSystem = null;

    /**
     * Entity loader
     *
     * @var EntityLoader
     */
    private $entityLoader = null;


    /**
     * Test files to cleanup
     *
     * @var ObjType\FileEntity[]
     */
    private $testFiles = array();

    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $sl = $this->account->getServiceManager();

        $this->fileSystem = $sl->get("Netric/FileSystem/FileSystem");
        $this->entityLoader = $sl->get("EntityLoader");
    }

    /**
     * Clean-up and test files
     */
    protected function tearDown()
    {
        foreach ($this->testFiles as $file) {
            $this->fileSystem->deleteFile($file, true);
        }
    }

    /**
     * Create a test file to work with
     *
     * @param string $name Name of the test file to create
     * @return \Netric\Entity\EntityInterface
     */
    private function createTestFile($name = "streamtest.txt")
    {
        $file = $this->entityLoader->create("file");
        $file->setValue("name", $name);
        $this->entityLoader->save($file);
        $this->testFiles[] = $file;
        return $file;
    }

    /**
     * Check if we can read from a file using standard PHP streams
     */
    public function testRead()
    {
        $data = "my test contents";

        // Create a test file and write to it
        $testFile = $this->createTestFile();
        $bytesWritten = $this->fileSystem->writeFile($testFile, $data);
        $this->assertNotEquals(-1, $bytesWritten);

        // Now open a stream and read from it one byte at a time
        $buf = "";
        $stream = FileStreamWrapper::open($this->fileSystem, $testFile);
        while (!feof($stream))
        {
            $ch = fread($stream, 1);
            $buf .= $ch;
        }
        $this->assertEquals($buf, $data);
    }

    /**
     * Make sure the context works with simultaneous reads from different files
     */
    public function testRead_Multi()
    {
        $data = "my test contents";
        $data2 = "second test contents";

        // Create a test files and write to them
        $testFile = $this->createTestFile("streamtest1.txt");
        $this->fileSystem->writeFile($testFile, $data);
        $testFile2 = $this->createTestFile("streamtest2.txt");
        $this->fileSystem->writeFile($testFile2, $data2);

        // Open them both at once
        $stream1 = FileStreamWrapper::open($this->fileSystem, $testFile);
        $stream2 = FileStreamWrapper::open($this->fileSystem, $testFile2);

        // Read through stream 1
        $buf = "";
        while (!feof($stream1))
        {
            $ch = fread($stream1, 1);
            $buf .= $ch;
        }
        $this->assertEquals($buf, $data);

         // Read through stream 2
        $buf = "";
        while (!feof($stream2))
        {
            $ch = fread($stream2, 1);
            $buf .= $ch;
        }
        $this->assertEquals($buf, $data2);
    }
}