<?php
/**
 * Test saving files remotely to ans
 */
namespace NetricTest\FileSystem\FileStore;

use Netric;
use Netric\FileSystem\FileStore\AnsFileStore;
use Netric\FileSystem\FileStore\FileStoreInterface;

/**
 * Running this test requires we have an ANS service running
 *
 * @group integration
 */
class AnsFileStoreTest extends AbstractFileStoreTests
{
    /**
     * Handle to a constructed LocalFiletore
     *
     * @var AnsFileStore
     */
    private $ansFileStore = null;

    /**
     * Temp path for saving files
     *
     * @var string
     */
    private $tmpPath = "";

    protected function setUp()
    {
        // For now we are just going to skip ANS since we're planning on removing it
        $this->markTestSkipped(
            'ANS is no longer supported in V2, we use mogile directly.'
        );

        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();

        $this->tmpPath = __DIR__ . "/tmp";

        $accId = $account->getId();
        $dataMapper = $sm->get("Entity_DataMapper");

        $config = $sm->get("Config");
        $ansServer = $config->alib['ans_server'];
        $ansAccount = $config->alib['ans_account'];
        $ansPassword = $config->alib['ans_password'];

        $this->ansFileStore = new AnsFileStore(
            $accId,
            $dataMapper,
            $ansServer,
            $ansAccount,
            $ansPassword,
            $this->tmpPath
        );

        // Make directory if it does not exist
        if (!file_exists($this->tmpPath))
        {
            mkdir($this->tmpPath);
        }
    }

    /**
     * Cleanup
     */
    protected function tearDown()
    {
        parent::tearDown();

        if (file_exists($this->tmpPath)) {
            $this->rrmdir($this->tmpPath);
        }
    }


    /**
     * Required for any FileStore implementation to constract and return a File Store
     *
     * @return FileStoreInterface
     */
    protected function getFileStore()
    {
        return $this->ansFileStore;
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
}