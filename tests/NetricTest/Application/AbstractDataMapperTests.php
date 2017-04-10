<?php
namespace NetricTest\Application;

use Netric\Application\DataMapperInterface;
use Netric\Account\Account;
use Netric\Application\Application;
use Netric\Config\ConfigLoader;
use PHPUnit_Framework_TestCase;

abstract class AbstractDataMapperTests extends PHPUnit_Framework_TestCase
{
    /**
     * Application object to test
     *
     * @var Application
     */
    private $application = null;

    /**
     * Test accounts created that should be deleted
     *
     * @var array
     */
    private $testAccountIds = [];

    public $config = null;

    /**
     * A name we can use for creating test accounts
     */
    const TEST_ACCOUNT_NAME = 'unit_test_account';

    /**
     * A test domaon
     */
    const TEST_EMAIL_DOMAIN = 'unittest.com';

    protected function setUp()
    {
        $configLoader = new ConfigLoader();
        $applicationEnvironment = (getenv('APPLICATION_ENV')) ? getenv('APPLICATION_ENV') : "production";

        // Setup the new config
        $this->config = $configLoader->fromFolder(__DIR__ . "/../../../config", $applicationEnvironment);
        $this->application = new Application($this->config);

        // Clean-up the test account in case it was left hanging on a previous failure
        $dataMapper = $this->getDataMapper();
        $account = new Account($this->application);
        if ($dataMapper->getAccountByName(self::TEST_ACCOUNT_NAME, $account)) {
            $dataMapper->deleteAccount($account->getId());
        }
    }

    protected function tearDown()
    {
        $dataMapper = $this->getDataMapper();

        foreach ($this->testAccountIds as $accountId)
        {
            $dataMapper->deleteAccount($accountId);
        }
    }

    /**
     * Get an implementation specific DataMapper
     *
     * @param string $optDbName Optional different name to use for the database
     * @return DataMapperInterface
     */
    abstract protected function getDataMapper($optDbName = null);

    /**
     * This is a cleanup method that we need done mantually in the datamapper driver
     *
     * We do not want to expose this in the application datamapper since the
     * application database should NEVER be deleted. So we leave it up to each
     * drive to manually delete or drop a temp/test database.
     *
     * @param string $dbName The name of the database to drop
     */
    abstract protected function deleteDatabase($dbName);

    public function testCreateAccount()
    {
        $dataMapper = $this->getDataMapper();
        $aid = $dataMapper->createAccount(self::TEST_ACCOUNT_NAME);
        $this->testAccountIds[] = $aid;
        $error = ($aid === -1) ? $dataMapper->getLastError()->getMessage() : "";
        // Make sure we did not get a 0 which is failure
        $this->assertNotEquals(0, $aid, $error);
    }

    public function testDeleteAccount()
    {
        $dataMapper = $this->getDataMapper();
        $aid = $dataMapper->createAccount(self::TEST_ACCOUNT_NAME);

        // Now delete it
        $this->assertTrue($dataMapper->deleteAccount($aid));

        // Make sure we cannot open it now
        $account = new Account($this->application);
        $this->assertFalse($dataMapper->getAccountById($aid, $account));
    }

    /**
     * Make sure we can create an application database
     */
    public function testCreateDatabase()
    {
        $dataMapper = $this->getDataMapper("testsystemdb");
        $this->assertTrue($dataMapper->createDatabase());

        // Cleanup
        $this->deleteDatabase("testsystemdb");
    }

    /**
     * If we call create database on an existing database, it should return true
     */
    public function testCreateDatabase_Existing()
    {
        $dataMapper = $this->getDataMapper();
        $this->assertTrue($dataMapper->createDatabase());
    }

    public function testCreateEmailDomain()
    {
        $dataMapper = $this->getDataMapper();
        $aid = $dataMapper->createAccount(self::TEST_ACCOUNT_NAME);
        $this->testAccountIds[] = $aid;
        $ret = $dataMapper->createEmailDomain($aid, self::TEST_EMAIL_DOMAIN);
        $dataMapper->deleteEmailDomain($aid, self::TEST_EMAIL_DOMAIN);
        $this->assertTrue($ret);
    }

    public function testDeleteEmailDomain()
    {
        $dataMapper = $this->getDataMapper();
        $aid = $dataMapper->createAccount(self::TEST_ACCOUNT_NAME);
        $this->testAccountIds[] = $aid;
        $dataMapper->createEmailDomain($aid, self::TEST_EMAIL_DOMAIN);
        $this->assertTrue($dataMapper->deleteEmailDomain($aid, self::TEST_EMAIL_DOMAIN));
    }

    public function testCreateUoUpdateEmailAlias()
    {
        $dataMapper = $this->getDataMapper();
        $aid = $dataMapper->createAccount(self::TEST_ACCOUNT_NAME);
        $this->testAccountIds[] = $aid;

        // TODO: When a constraint is added to check if the domain exists, we'll
        // need to create a test domain here before creating the alias.

        // Test insert
        $ret = $dataMapper->createOrUpdateEmailAlias(
            $aid,
            'address@' . self::TEST_EMAIL_DOMAIN,
            'someotheraddress@test.com'
        );
        $this->assertTrue($ret);

        // Test Update
        $ret = $dataMapper->createOrUpdateEmailAlias(
            $aid,
            'address@' . self::TEST_EMAIL_DOMAIN,
            'someotheraddress@test.com,andanother@test.com'
        );
        $this->assertTrue($ret);

        // Make sure delete succeeds which tells us we created it correctly
        $this->assertTrue($dataMapper->deleteEmailAlias($aid, 'address@' . self::TEST_EMAIL_DOMAIN));
    }

    public function testDeleteEmailAlias()
    {
        $dataMapper = $this->getDataMapper();
        $aid = $dataMapper->createAccount(self::TEST_ACCOUNT_NAME);
        $this->testAccountIds[] = $aid;

        $dataMapper->createOrUpdateEmailAlias(
            $aid,
            'testdelete@' . self::TEST_EMAIL_DOMAIN,
            'someotheraddress@test.com'
        );

        $this->assertTrue(
            $dataMapper->deleteEmailAlias($aid, 'testdelete@' . self::TEST_EMAIL_DOMAIN)
        );
    }

    public function testCreateOrUpdateEmailuser()
    {
        $dataMapper = $this->getDataMapper();
        $aid = $dataMapper->createAccount(self::TEST_ACCOUNT_NAME);
        $this->testAccountIds[] = $aid;

        // TODO: When a constraint is added to check if the domain exists, we'll
        // need to create a test domain here before creating the user.

        // Test insert
        $ret = $dataMapper->createOrUpdateEmailUser(
            $aid,
            'address@' . self::TEST_EMAIL_DOMAIN,
            'password'
        );

        // Test Update
        $ret = $dataMapper->createOrUpdateEmailUser(
            $aid,
            'address@' . self::TEST_EMAIL_DOMAIN,
            'password2'
        );
        $this->assertTrue($ret);

        // Make sure delete succeeds which tells us we created it correctly
        $this->assertTrue($dataMapper->deleteEmailUser($aid, 'address@' . self::TEST_EMAIL_DOMAIN));
    }

    public function testDeleteEmailUser()
    {
        $dataMapper = $this->getDataMapper();
        $aid = $dataMapper->createAccount(self::TEST_ACCOUNT_NAME);
        $this->testAccountIds[] = $aid;

        $dataMapper->createOrUpdateEmailUser(
            $aid,
            'testdelete@' . self::TEST_EMAIL_DOMAIN,
            'password'
        );

        $this->assertTrue(
            $dataMapper->deleteEmailUser($aid, 'testdelete@' . self::TEST_EMAIL_DOMAIN)
        );
    }
}