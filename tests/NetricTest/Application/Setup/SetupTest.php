<?php
/**
 * Test account setup functions
 */
namespace NetricTest\Application\Setup;

use Netric\Account\Account;
use Netric\Application\Application;
use Netric\Account\AccountIdentityMapper;
use Netric\Application\Setup\Setup;
use Netric\Application\Setup\AccountUpdater;
use PHPUnit_Framework_TestCase;

class SetupTest extends PHPUnit_Framework_TestCase
{
    /**
     * Account identity mapper used for testing
     *
     * @var AccountIdentityMapper
     */
    private $mapper = null;

    /**
     * Cache interface
     *
     * @var \Netric\Cache\CacheInterface
     */
    private $cache = null;

    /**
     * Account setup
     *
     * @var Setup
     */
    private $setup = null;

    /**
     * Application
     *
     * @var Application
     */
    private $application = null;

    /**
     * Account that the unit test is currently running under
     *
     * @var Account
     */
    private $account = null;

    /**
     * Setup each test
     */
    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->application = $this->account->getApplication();
        $serviceManager =$this->account->getServiceManager();

        $this->cache = $serviceManager->get("Cache");
        $dataMapper = $serviceManager->get("Application_DataMapper");

        $this->mapper = new AccountIdentityMapper($dataMapper, $this->cache);
        $this->setup = new Setup();
    }

    /**
     * Make sure we can initialize a new account
     */
    public function testSetupAccount()
    {
        // Cleanup if there's any left-overs from a failed test
        $accountToDelete = $this->mapper->loadByName("ut_setup", $this->application);
        if ($accountToDelete)
            $this->mapper->deleteAccount($accountToDelete);

        // Create a new test account
        $accountId = $this->mapper->createAccount("ut_setup");
        $account = $this->mapper->loadById($accountId, $this->application);

        // Run updates on the account
        $this->assertTrue($this->setup->setupAccount($account, "test@test.com", "password"));

        // Cleanup
        $this->mapper->deleteAccount($account);
    }

    /**
     * Make sure we can update an existing account to the latest version/revision
     */
    public function testUpdateAccount()
    {
        // Cleanup if there's any left-overs from a failed test
        $accountToDelete = $this->mapper->loadByName("ut_setup", $this->application);
        if ($accountToDelete)
            $this->mapper->deleteAccount($accountToDelete);

        // Create a new test account
        $accountId = $this->mapper->createAccount("ut_setup");
        $account = $this->mapper->loadById($accountId, $this->application);

        // Run updates on the account
        $updater = new AccountUpdater($account);
        $this->assertEquals($updater->getLatestVersion(), $this->setup->updateAccount($account));

        // Cleanup
        $this->mapper->deleteAccount($account);
    }

    /**
     * Test updating an application
     */
    public function testUpdateApplication()
    {
        $this->assertTrue($this->setup->updateApplication($this->application));
    }
}
