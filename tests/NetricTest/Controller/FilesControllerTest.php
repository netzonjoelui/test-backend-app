<?php
/**
 * Test calling the files controller
 */
namespace NetricTest\Controller;

use Netric;
use Netric\Entity\ObjType;
use Netric\FileSystem\FileSystem;
use PHPUnit_Framework_TestCase;

class FilesControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Account used for testing
     *
     * @var \Netric\Account\Account
     */
    protected $account = null;

    /**
     * Controller instance used for testing
     *
     * @var \Netric\Controller\FilesController
     */
    protected $controller = null;

    /**
     * Test user
     *
     * @var \Netric\Entity\ObjType\UserEntity
     */
    private $user = null;

    /**
     * Get FileSystem
     *
     * @var FileSystem
     */
    private $fileSystem = null;

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

        // Create the controller
        $this->controller = new Netric\Controller\FilesController($this->account->getApplication(), $this->account);
        $this->controller->testMode = true;

        // Setup entity datamapper for handling users
        $dm = $sl->get("Entity_DataMapper");

        // Get FileSystem
        $this->fileSystem = $sl->get("Netric/FileSystem/FileSystem");
    }

    /**
     * Cleanup after a test runs
     */
    protected function tearDown()
    {
        // Clean-up test files
        foreach ($this->testFiles as $file)
        {
            $this->fileSystem->deleteFile($file);
        }

        // Delete all test folders in reverse order - in case they are children of each other
        $folders = array_reverse($this->testFolders);
        foreach ($folders as $folder)
        {
            $this->fileSystem->deleteFolder($folder);
        }
    }

    /**
     * Try uploading a file into the FileSystem through the controller
     */
    public function testUpload()
    {
        /*
         * Add fake uploaded files. In normal execution this would fail since
         * it would fail PHP's is_uploaded_file but whe controller->testMode is true
         * it bypasses that test.
         */

        // First copy to a temp file since we'll delete the temp in the upload function
        $sourceFile = __DIR__ . "/fixtures/files-upload-test.txt";
        $tempFile = __DIR__ . "/fixtures/files-upload-test-tmp.txt";
        copy($sourceFile, $tempFile);

        $req = $this->controller->getRequest();
        $testUploadedFiles = array(
            array("tmp_name"=>$tempFile, "name"=>"files-upload-test.txt")
        );
        $req->setParam("files", $testUploadedFiles);
        $req->setParam("path", "/testUpload");

        /*
         * Now upload the file which should import the temp file,
         * then delete it since it will normally be working with HTTP_POST uploads
         * adn we want it to cleanup as it finishes processing each file.
         */
        $ret = $this->controller->postUploadAction();

        // Results are returned in an array
        $this->assertFalse(isset($ret['error']), "Error: " . var_export($ret, true));
        $this->assertNotEquals(-1, $ret[0]); // error
        $this->assertTrue(isset($ret[0]['id']));
        $this->assertTrue(isset($ret[0]['name']));
        $this->assertTrue(isset($ret[0]['ts_updated']));

        // Make sure we cleaned up the temp file
        $this->assertFalse(file_exists($tempFile));

        // Set created folder so we make sure we purge it
        $this->testFolders[] = $this->fileSystem->openFolder("/testUpload");

        // Open the file and make sure it was uploaded correctly
        $file = $this->fileSystem->openFileById($ret[0]['id']);
        $this->testFiles[] = $file; // For tearDown Cleanup

        // Test file
        $this->assertEquals("files-upload-test.txt", $file->getValue("name"));
        $this->assertEquals(filesize($sourceFile), $file->getValue("file_size"));
        $this->assertEquals($this->account->getUser()->getId(), $file->getValue("owner_id"));
    }

    /**
     * Try uploading a single file into the FileSystem through the controller using the data from client side
     */
    public function testUploadFilesFromClient()
    {
        /*
         * Add fake uploaded files. In normal execution this would fail since
         * it would fail PHP's is_uploaded_file but whe controller->testMode is true
         * it bypasses that test.
         */

        // First copy to a temp file since we'll delete the temp in the upload function
        $sourceFile = __DIR__ . "/fixtures/files-upload-test.txt";
        $tempFile = __DIR__ . "/fixtures/files-upload-test-tmp.txt";
        copy($sourceFile, $tempFile);

        $req = $this->controller->getRequest();

        // We are using files array index, since this is the post data format sent by the client side
        $testUploadedFiles['files'] = array(
            "name" => "files-upload-test.txt",
            "tmp_name" => $tempFile,
            "type" => "text/plain",
            "size" => "100",
            "error" => 0
        );

        $req->setParam("files", $testUploadedFiles);
        $req->setParam("path", "/testUpload");

        /*
         * Now upload the file which should import the temp file,
         * then delete it since it will normally be working with HTTP_POST uploads
         * adn we want it to cleanup as it finishes processing each file.
         */
        $ret = $this->controller->postUploadAction();

        // Results are returned in an array
        $this->assertFalse(isset($ret['error']), "Error: " . var_export($ret, true));
        $this->assertNotEquals(-1, $ret[0]); // error
        $this->assertTrue(isset($ret[0]['id']));
        $this->assertTrue(isset($ret[0]['name']));
        $this->assertTrue(isset($ret[0]['ts_updated']));

        // Make sure we cleaned up the temp file
        $this->assertFalse(file_exists($tempFile));

        // Set created folder so we make sure we purge it
        $this->testFolders[] = $this->fileSystem->openFolder("/testUpload");

        // Open the file and make sure it was uploaded correctly
        $file = $this->fileSystem->openFileById($ret[0]['id']);
        $this->testFiles[] = $file; // For tearDown Cleanup

        // Test file
        $this->assertEquals("files-upload-test.txt", $file->getValue("name"));
        $this->assertEquals(filesize($sourceFile), $file->getValue("file_size"));
        $this->assertEquals($this->account->getUser()->getId(), $file->getValue("owner_id"));
    }

    /**
     * Try uploading 2 files into the FileSystem through the controller using the data from client side
     */
    public function testUploadFilesFromClientMultipleFiles()
    {
        /*
         * Add fake uploaded files. In normal execution this would fail since
         * it would fail PHP's is_uploaded_file but whe controller->testMode is true
         * it bypasses that test.
         */

        // First copy to a temp file since we'll delete the temp in the upload function
        $sourceFile = __DIR__ . "/fixtures/files-upload-test.txt";
        $tempFile = __DIR__ . "/fixtures/files-upload-test-tmp.txt";
        copy($sourceFile, $tempFile);

        // First copy to a temp file since we'll delete the temp in the upload function
        $sourceFile2 = __DIR__ . "/fixtures/files-upload-test2.txt";
        $tempFile2 = __DIR__ . "/fixtures/files-upload-test-tmp2.txt";
        copy($sourceFile2, $tempFile2);

        $req = $this->controller->getRequest();

        // We are using files array index, since this is the post data format sent by the client side
        $testUploadedFiles['files'] = array(
            "name" => array("files-upload-test.txt", "files-upload-test2.txt"),
            "tmp_name" => array($tempFile, $tempFile2),
            "type" => array("text/plain", "text/plain"),
            "size" => array("100", "100"),
            "error" => array(0, 0)
        );

        $req->setParam("files", $testUploadedFiles);
        $req->setParam("path", "/testUpload");

        /*
         * Now upload the file which should import the temp file,
         * then delete it since it will normally be working with HTTP_POST uploads
         * adn we want it to cleanup as it finishes processing each file.
         */
        $ret = $this->controller->postUploadAction();

        // Results are returned in an array
        $this->assertFalse(isset($ret['error']), "Error: " . var_export($ret, true));
        $this->assertNotEquals(-1, $ret[0]); // error
        $this->assertTrue(isset($ret[0]['id']));
        $this->assertTrue(isset($ret[0]['name']));
        $this->assertTrue(isset($ret[0]['ts_updated']));

        // Check the result for the second file
        $this->assertNotEquals(-1, $ret[1]); // error
        $this->assertTrue(isset($ret[1]['id']));
        $this->assertTrue(isset($ret[1]['name']));
        $this->assertTrue(isset($ret[1]['ts_updated']));

        // Make sure we cleaned up the temp file
        $this->assertFalse(file_exists($tempFile));
        $this->assertFalse(file_exists($tempFile2));

        // Set created folder so we make sure we purge it
        $this->testFolders[] = $this->fileSystem->openFolder("/testUpload");

        // Open the file and make sure it was uploaded correctly
        $file = $this->fileSystem->openFileById($ret[0]['id']);
        $this->testFiles[] = $file; // For tearDown

        // Clean up for the second file
        $file2 = $this->fileSystem->openFileById($ret[1]['id']);
        $this->testFiles[] = $file2; // For tearDown Cleanup Cleanup

        // Test file
        $this->assertEquals("files-upload-test.txt", $file->getValue("name"));
        $this->assertEquals(filesize($sourceFile), $file->getValue("file_size"));
        $this->assertEquals($this->account->getUser()->getId(), $file->getValue("owner_id"));

        // Test the second file
        $this->assertEquals("files-upload-test2.txt", $file2->getValue("name"));
    }
}
