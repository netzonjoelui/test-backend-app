<?php
/**
 * Test saving files locally to disk
 */
namespace NetricTest\FileSystem\FileStore;

use Netric;
use PHPUnit_Framework_TestCase;
use Netric\FileSystem\FileStore\LocalFileStore;

class LocalFileStoreTest extends AbstractFileStoreTests
{
    /**
     * Handle to a constructed LocalFiletore
     *
     * @var LocalFileStore
     */
    private $localFileStore = null;

    /**
     * The datamapth
     *
     * @var string
     */
    private $localPath = "";

    protected function setUp()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $accId = $account->getId();
        $dataPath = __DIR__ . "/tmp";
        $dataMapper = $sm->get("Entity_DataMapper");

        //$user = $this->account->getUser(\Netric\Entity\ObjType\UserEntity::USER_SYSTEM);

        $this->localFileStore = new LocalFileStore($accId, $dataPath, $dataMapper);

        $this->localPath = $dataPath;

        // Make directory if it does not exist
        if (!file_exists($this->localPath))
        {
            mkdir($this->localPath);
        }
    }

    protected function tearDown()
    {
        parent::tearDown();

        if (file_exists($this->localPath)) {
            $this->rrmdir($this->localPath);
        }
    }


    /**
     * Recursively delete a directory and all it's children
     *
     * @param string $dir The path fo the directory to recursively deleted
     */
    private function rrmdir($dir)
    {
        if (is_dir($dir))
        {
            $objects = scandir($dir);

            foreach ($objects as $object)
            {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir."/".$object))
                        $this->rrmdir($dir."/".$object);
                    else
                        unlink($dir."/".$object);
                }
            }

            rmdir($dir);
        }
    }

    /**
     * Required for any FileStore implementation to constract and return a File Store
     *
     * @return FileStoreInterface
     */
    protected function getFileStore()
    {
        return $this->localFileStore;
    }
}