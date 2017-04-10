<?php
/**
 * Test saving files locally to disk
 */
namespace NetricTest\FileSystem\FileStore;

use Netric;
use PHPUnit_Framework_TestCase;
use Netric\FileSystem\FileStore;
use Netric\Entity\DataMapperInterface;

abstract class AbstractFileStoreTests extends PHPUnit_Framework_TestCase
{
    /**
     * Test files
     *
     * @var Netric\Entity\ObjType\FileEntity[]
     */
    private $testFiles = array();

    /**
     * Required for any FileStore implementation to constract and return a File Store
     *
     * @return FileStoreInterface
     */
    abstract protected function getFileStore();

    /**
     * Get the DataMapper for files
     *
     * @return DataMapperInterface
     */
    private function getEntityDataMapper()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        return $account->getServiceManager()->get("Entity_DataMapper");
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
     * Clean-up and test files
     */
    protected function tearDown()
    {
        $fileStore = $this->getFileStore();
        $dataMapper = $this->getEntityDataMapper();
        foreach ($this->testFiles as $file)
        {
            // Delete with this filestore since it may not
            if ($fileStore->fileExists($file))
            {
                $fileStore->deleteFile($file);
            }

            $dataMapper->delete($file, true);
        }
    }

    /**
     * Make sure we can write to a file
     */
    public function testWriteFile()
    {
        $fileStore = $this->getFileStore();
        $testFile = $this->createTestFile();

        $bytesWritten = $fileStore->writeFile($testFile, "test contents");
        $this->assertNotEquals(-1, $bytesWritten);
        $this->assertEquals($testFile->getValue("file_size"), $bytesWritten);
    }

    /**
     * Make sure we can write to a file
     */
    public function testWriteFileStream()
    {
        $fileStore = $this->getFileStore();
        $testFile = $this->createTestFile();

        // Create a temp stream
        $fileStream = tmpfile();
        fwrite($fileStream, "test contents");
        fseek($fileStream, 0);

        $bytesWritten = $fileStore->writeFile($testFile, $fileStream);
        $this->assertNotEquals(-1, $bytesWritten);
        $this->assertEquals($testFile->getValue("file_size"), $bytesWritten);

        fclose($fileStream);
    }

    /**
     * Make sure we can read the entire contents of a file
     */
    public function testReadFile()
    {
        $fileStore = $this->getFileStore();
        $testFile = $this->createTestFile();
        $content = "testReadFile Contents";

        $fileStore->writeFile($testFile, $content);

        $buf = $fileStore->readFile($testFile);
        $this->assertEquals($content, $buf);

    }

    /**
     * Test new file import
     */
    public function testUploadFile()
    {
        $fileStore = $this->getFileStore();
        $testFile = $this->createTestFile();
        $uploadFilePath = __DIR__ . "/fixtures/file-to-upload.txt";

        // Test importing a file into the FileSystem
        $ret = $fileStore->uploadFile($testFile, $uploadFilePath);
        $this->assertTrue($ret);

        // Try reading the file ato make sure data was imported
        $buf = $fileStore->readFile($testFile);

        // The contents of ./fixtures/file-to-upload.txt is: FileHasContent
        $this->assertEquals("FileHasContent", $buf);
    }

    /**
     * Make sure if I update a revision then it returns latest but keeps both versions
     */
    public function testUploadFileRevisions()
    {
        $fileStore = $this->getFileStore();
        $testFile = $this->createTestFile();
        $uploadFilePath = __DIR__ . "/fixtures/file-to-upload.txt";
        $uploadFile2Path = __DIR__ . "/fixtures/file-to-upload-2.txt";

        // Test importing a file into the FileSystem
        $ret = $fileStore->uploadFile($testFile, $uploadFilePath);
        $this->assertTrue($ret);

        /*
         * Try reading the file ato make sure data was imported
         * The contents of ./fixtures/file-to-upload.txt is: FileHasContent
         */
        $buf = $fileStore->readFile($testFile);
        $this->assertEquals("FileHasContent", $buf);

        // Re-import again with a new file
        $ret = $fileStore->uploadFile($testFile, $uploadFile2Path);
        $this->assertTrue($ret);

        /*
         * Try reading the file to make sure data was imported
         * The contents of ./fixtures/file-to-upload-2.txt is: FileHasContent2
         */
        $buf = $fileStore->readFile($testFile);
        $this->assertEquals("FileHasContent2", $buf);

        /*
         * Get all the revisions. There should be three:
         * 1 for original, 2 for first upload, 3 for second
         *
         * We will also load the second revision (index 1)
         * to make sure it is still the first file we uploaded
         * immediately after creating the file.
         */
        $dataMapper = $this->getEntityDataMapper();
        $files = $dataMapper->getRevisions("file", $testFile->getId());
        $keys = array_keys($files); // $fiels is an assoicative with key being revid
        $this->assertEquals(3, count($files));
        // The second revision (index 1) should be the first import
        $buf = $fileStore->readFile($files[$keys[1]]);
        // Make sure it read the first file
        $this->assertEquals("FileHasContent", $buf);
    }

    public function testDeleteFile()
    {
        $fileStore = $this->getFileStore();
        $testFile = $this->createTestFile();
        $uploadFilePath = __DIR__ . "/fixtures/file-to-upload.txt";
        $uploadFile2Path = __DIR__ . "/fixtures/file-to-upload-2.txt";

        // Upload two files, then make sure they are both deleted
        $fileStore->uploadFile($testFile, $uploadFilePath);
        $fileStore->uploadFile($testFile, $uploadFile2Path);

        // Delete the file - which will purge all revisions
        $this->assertTrue($fileStore->deleteFile($testFile));

        // Now loop through all revisions and make sure we purged them
        $dataMapper = $this->getEntityDataMapper();
        $files = $dataMapper->getRevisions("file", $testFile->getId());
        foreach ($files as $rev=>$file)
        {
            $this->assertFalse($fileStore->fileExists($file));
        }
    }

    /**
     * Make sure the fileExists fuction works as expected
     */
    public function testFileExists()
    {
        $fileStore = $this->getFileStore();
        $testFile = $this->createTestFile();

        // Now write an actual file and make sure it exists
        $fileStore->writeFile($testFile, "test contents");
        $this->assertTrue($fileStore->fileExists($testFile));

        // Delete it and then make sure fileExists returns false
        $fileStore->deleteFile($testFile);

        $this->assertFalse($fileStore->fileExists($testFile));
    }
}
