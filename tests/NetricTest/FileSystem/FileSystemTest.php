<?php
/**
 * Test the FileSystem service
 */
namespace NetricTest\FileSystem;

use Netric\FileSystem\FileSystem;
use Netric\Entity\DataMapperInterface;
use Netric\EntityQuery;
use Netric\EntityLoader;
use Netric\Entity\ObjType;

use PHPUnit_Framework_TestCase;

class FileSystemTest extends PHPUnit_Framework_TestCase
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
     * Entity DataMapper
     *
     * @var DataMapperInterface
     */
    private $dataMapper = null;

    /**
     * Entity loader
     *
     * @var EntityLoader
     */
    private $entityLoader = null;

    /**
     * Entity Query index for finding things
     *
     * @var EntityQuery\Index\IndexInterface
     */
    private $entityIndex = null;

    /**
     * Test folders to cleanup
     *
     * @var ObjType\Folder[]
     */
    private $testFolders = array();

    /**
     * Test files to cleanup
     *
     * @var ObjType\File[]
     */
    private $testFiles = array();

    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $sl = $this->account->getServiceManager();

        $this->fileSystem = $sl->get("Netric/FileSystem/FileSystem");
        $this->dataMapper = $sl->get("Entity_DataMapper");
        $this->entityLoader = $sl->get("EntityLoader");
        $this->entityIndex = $sl->get("EntityQuery_Index");
    }

    protected function tearDown()
    {
        // Clean-up test files
        foreach ($this->testFiles as $file)
        {
            $this->fileSystem->deleteFile($file, true);
        }

        // Delete all test folders in reverse order - in case they are children of each other
        $folders = array_reverse($this->testFolders);
        foreach ($folders as $folder)
        {
            $this->fileSystem->deleteFolder($folder, true);
        }
    }

    private function queueFolderForCleanup($folder)
    {
        if ($folder->getValue('name') == '/')
            throw new \Exception("You cannot delete root");

        $this->testFolders[] = $folder;
    }

    /**
     * Test to make sure the root folder is set on construction
     */
    public function testGetRootFolder()
    {
        $this->assertNotNull($this->fileSystem->getRootFolder());
    }

    /**
     * Test opening an existing folder by path
     */
    public function testOpenFolder()
    {
        // Create some test folders
        $rootFolder = $this->fileSystem->getRootFolder();

        // Cleanup first
        if ($this->fileSystem->openFolder("/testOpenSub"))
            $this->fileSystem->deleteFolder($this->fileSystem->openFolder("/testOpenSub"), true);

        // Create /testOpenSub
        $subFolder = $this->entityLoader->create("folder");
        $subFolder->setValue("name", "testOpenSub");
        $subFolder->setValue("parent_id", $rootFolder->getId());
        $this->dataMapper->save($subFolder);
        $this->queueFolderForCleanup($subFolder);

        // Create /testOpenSub/Child
        $childFolder = $this->entityLoader->create("folder");
        $childFolder->setValue("name", "Child");
        $childFolder->setValue("parent_id", $subFolder->getId());
        $this->dataMapper->save($childFolder);
        $this->queueFolderForCleanup($childFolder);

        // Try opening /testOpenSub
        $openedFolder = $this->fileSystem->openFolder("/testOpenSub");
        $this->assertNotNull($openedFolder);
        $this->assertEquals($openedFolder->getId(), $subFolder->getId());
        $this->assertEquals(
            $openedFolder->getValue("name"),
            $subFolder->getValue("name")
        );

        // Try opening /testOpenSub/Child
        $openedFolder = $this->fileSystem->openFolder("/testOpenSub/Child");
        $this->assertNotNull($openedFolder);
        $this->assertEquals($openedFolder->getId(), $childFolder->getId());
        $this->assertEquals(
            $openedFolder->getValue("name"),
            $childFolder->getValue("name")
        );
    }

    /**
     * Make sure that we can open a new folder and create it if missing
     */
    public function testOpenFolderCreateMissing()
    {
        $origChildId = null;

        // First delete test path if it exists
        $folder = $this->fileSystem->openFolder("/testOpenSubCreate/Child");
        if ($folder)
            $this->dataMapper->delete($folder);
        $folder = $this->fileSystem->openFolder("/testOpenSubCreate");
        if ($folder)
        {
            $origChildId = $folder->getId();
            $this->dataMapper->delete($folder);
        }

        // Now open the folder with create flag (second)
        $openedFolder = $this->fileSystem->openFolder("/testOpenSubCreate/Child", true);
        $this->assertNotNull($openedFolder);
        $this->assertNotEquals($origChildId, $openedFolder->getId());

        // Stash for cleanup
        $this->queueFolderForCleanup($this->fileSystem->openFolder("/testOpenSubCreate"));
        $this->queueFolderForCleanup($this->fileSystem->openFolder("/testOpenSubCreate/Child"));
    }

    /**
     * Test to make sure we get a folder by id
     */
    public function testOpenFolderById()
    {
        $testFolder = $this->fileSystem->openFolder("/testOpenFolderById", true);
        $this->queueFolderForCleanup($testFolder);
        $folderId = $testFolder->getId();

        // Try to re-open the above folder by id
        $sameFolder = $this->fileSystem->openFolderById($folderId);
        $this->assertNotNull($sameFolder);
        $this->assertEquals($folderId, $sameFolder->getId());
    }

    /**
     * Test importing a new file
     */
    public function testImportFile()
    {
        $fileToImport = __DIR__ . "/FileStore/fixtures/file-to-upload.txt";

        // Test importing a local file
        $importedFile = $this->fileSystem->importFile($fileToImport, "/testImportFile");
        $this->assertNotNull($importedFile);
        $this->assertEquals("file-to-upload.txt", $importedFile->getValue("name"));
        $this->assertEquals(filesize($fileToImport), $importedFile->getValue("file_size"));

        // Queue files for cleanup
        $this->testFiles[] = $importedFile;
        $this->queueFolderForCleanup($this->fileSystem->openFolder("/testImportFile"));
    }

    /**
     * Test opening a file by unique id
     */
    public function testOpenFileById()
    {
        $fileToImport = __DIR__ . "/FileStore/fixtures/file-to-upload.txt";
        $importedFile = $this->fileSystem->importFile($fileToImport, "/testOpenFileById");

        // Test opening the file straight from the fileSystem by id
        $openedFile = $this->fileSystem->openFileById($importedFile->getId());
        $this->assertNotNull($openedFile);
        $this->assertFalse(empty($openedFile->getId()));
        $this->assertEquals($importedFile->getId(), $openedFile->getId());

        // Queue files for cleanup
        $this->testFiles[] = $importedFile;
        $this->queueFolderForCleanup($this->fileSystem->openFolder("/testOpenFileById"));
    }

    public function testOpenFile()
    {

    }

    /**
     * Make sure we can convert bytes to human readable text like 1,000 to 1k
     */
    public function testGetHumanSize()
    {
        $this->assertEquals("999B", $this->fileSystem->getHumanSize(999));
        $this->assertEquals("1K", $this->fileSystem->getHumanSize(1000));
        $this->assertEquals("500K", $this->fileSystem->getHumanSize(500000));
        $this->assertEquals("1M", $this->fileSystem->getHumanSize(1000000));
        $this->assertEquals("5M", $this->fileSystem->getHumanSize(5000000));
        $this->assertEquals("1G", $this->fileSystem->getHumanSize(1000000000));
        $this->assertEquals("5G", $this->fileSystem->getHumanSize(5000000000));
        $this->assertEquals("1T", $this->fileSystem->getHumanSize(1000000000000));
    }

    /**
     * Test that we can verify the existence (and non-existence) of a folder path
     */
    public function testFolderExists()
    {
        $testPath = "/testFolderExists";
        $folder = $this->fileSystem->openFolder($testPath);
        if ($folder)
            $this->dataMapper->delete($folder);

        // Test a non-existent folder
        $this->assertFalse($this->fileSystem->folderExists($testPath));

        // Create the file now
        $folder = $this->fileSystem->openFolder($testPath, true);
        $this->queueFolderForCleanup($folder);

        // Test a existent folder
        $this->assertTrue($this->fileSystem->folderExists($testPath));
    }

    public function testFileExists()
    {
        $file = $this->fileSystem->fileExists("/", "missingfile.txt");
        $this->assertFalse($file);

        // Create the missing file
        $file = $this->fileSystem->createFile("/", "presentfile.txt", true);
        $this->testFiles[] = $file; // Cleanup
        $this->assertNotNull($file);
        $this->assertFalse(empty($file->getId()));
    }

    public function testDeleteFile()
    {
        $testFile = $this->entityLoader->create("file");
        $testFile->setValue("name", "myfile.txt");
        $this->dataMapper->save($testFile);
        $fileId = $testFile->getId();

        $ret = $this->fileSystem->deleteFile($testFile, true);
        $this->assertTrue($ret);

        // Make sure this does not exist any more
        $this->assertNull($this->fileSystem->openFileById($fileId));
    }

    public function testDeleteFolder()
    {
        // Create some test folders
        $rootFolder = $this->fileSystem->getRootFolder();

        // Cleanup first
        if ($this->fileSystem->openFolder("/testDeleteFolder"))
            $this->fileSystem->deleteFolder($this->fileSystem->openFolder("/testDeleteFolder"), true);

        // Create /testDeleteFolder
        $subFolder = $this->entityLoader->create("folder");
        $subFolder->setValue("name", "testDeleteFolder");
        $subFolder->setValue("parent_id", $rootFolder->getId());
        $this->dataMapper->save($subFolder);

        $ret = $this->fileSystem->deleteFolder($subFolder, true);
        $this->assertTrue($ret);

        // Make sure this does not exist any more
        $this->assertFalse($this->fileSystem->folderExists("/testDeleteFolder"));
    }

    public function testFileIsTemp()
    {
        $fldr = $this->fileSystem->openFolder("%tmp%", true);
        $this->assertNotNull($fldr->getId());

        // Third param is overwrite = true
        $file = $this->fileSystem->createFile("%tmp%", "test", true);
        $this->testFiles[] = $file;

        // Test
        $this->assertTrue($this->fileSystem->fileIsTemp($file));

        // Move then test again
        $fldr = $this->fileSystem->openFolder("/testFileIsTemp", true);
        $this->queueFolderForCleanup($fldr);
        $this->fileSystem->moveFile($file, $fldr);
        $this->assertFalse($this->fileSystem->fileIsTemp($file));
    }

    public function testCreateFile()
    {
        // Try making a new file
        $file = $this->fileSystem->createFile("%tmp%", "testCreateFile.txt", true);
        $this->testFiles[] = $file; // For cleanup
        $this->assertNotNull($file->getId());

        // Now try creating same file without overwrite - causes an error
        $file2 = $this->fileSystem->createFile("%tmp%", "testCreateFile.txt", false);
        $this->assertNull($file2);
        $this->assertNotNull($this->fileSystem->getLastError());

        // Overwrite the old file with a new one
        $file3 = $this->fileSystem->createFile("%tmp%", "testCreateFile.txt", true);
        $this->testFiles[] = $file3;
        $this->assertNotNull($file3->getId());
        $this->assertNotEquals($file->getId(), $file3->getId());
    }

    public function testMoveFile()
    {
        // Try making a new file
        $file = $this->fileSystem->createFile("%tmp%", "testFileMove.txt", true);
        $this->testFiles[] = $file; // For cleanup
        $this->assertNotNull($file->getId());

        $fldr = $this->fileSystem->openFolder("/testFileMove", true);
        $this->queueFolderForCleanup($fldr);
        $this->fileSystem->moveFile($file, $fldr);

        // Test to make sure it has moved
        $this->assertEquals($fldr->getId(), $file->getValue("folder_id"));
    }

    public function testWriteAndReadFile()
    {
        $testData = "test data";

        $file = $this->fileSystem->createFile("%tmp%", "testFileMove.txt", true);
        $retSizeUploaded = $this->fileSystem->writeFile($file, $testData);
        $this->assertGreaterThan(0, $retSizeUploaded, $this->fileSystem->getLastError());
        $this->assertEquals($testData, $this->fileSystem->readFile($file));
    }
}
