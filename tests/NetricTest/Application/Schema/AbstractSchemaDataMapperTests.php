<?php
/**
 * Common tests for all schema DataMappers
 */
namespace NetricTest\Application\Schema;

use PHPUnit_Framework_TestCase;
use Netric\Application\Application;
use Netric\Application\Schema\SchemaDataMapperInterface;
use Netric\Application\Schema\SchemaProperty;

abstract class AbstractSchemaDataMapperTests extends PHPUnit_Framework_TestCase
{
    /**
     * Application object to test
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
     * Test account name
     *
     * @var const
     */
    const TEST_ACCOUNT_NAME = 'ut_schema_testr';

    protected function setUp()
    {
        $this->account = \NetricTest\Bootstrap::getAccount();
        $this->application = $this->account->getApplication();

        $this->deleteTestAccount();
    }

    protected function tearDown()
    {
        $this->deleteTestAccount();
    }

    private function deleteTestAccount()
    {
        // Cleanup if there's any test accounts
        $accountToDelete = $this->application->getAccount(null, self::TEST_ACCOUNT_NAME);
        if ($accountToDelete)
            $this->application->deleteAccount($accountToDelete->getName());
    }

    /**
     * Get the PostgreSQL DataMapper
     *
     * @param array $schemaDefinition
     * @param string $accountId THe account we will be managing the schema for
     * @return SchemaDataMapperInterface
     */
    abstract protected function getDataMapper(array $schemaDefinition, $accountId);

    /**
     * Test a created bucket by inserting data
     *
     * @param string $bucketName The name of the table/document/collection to save data to
     * @param array $data The data to insert and verify
     * @reutrn bool true if data could be inserted and read from the data store
     */
    abstract protected function insertIntoBucket($bucketName, array $data);

    /**
     * Make sure that a field with a primary key is set
     *
     * @param string $bucketName The name of the table/document/collection to test
     * @param strung|array $propertyOrProperties A property name or array of property names
     * @return bool true if the key exists
     */
    abstract protected function primaryKeyExists($bucketName, $propertyOrProperties);

    /**
     * Assure that there is an index on a given property for a bucket
     *
     * @param string $bucketName The name of the table/document/collection to test
     * @param string|array $propertyOrProperties A property name or array of property names
     * @return bool true if the index exists
     */
    abstract protected function indexExists($bucketName, $propertyOrProperties);

    /**
     * Test creating a brand new schema
     */
    public function testUpdate()
    {
        // Create a new account to update
        $account = $this->application->createAccount(self::TEST_ACCOUNT_NAME, "test@test.com", "password");

        // Create a test definition with all the goodies for testing
        $testDefinition = array(
            "unit_test_schema" => array(
                "PROPERTIES" => array(
                    'id'			=> array('type'=>SchemaProperty::TYPE_BIGSERIAL),
                    'name'			=> array('type'=>SchemaProperty::TYPE_CHAR_128),
                    'value'			=> array('type'=>SchemaProperty::TYPE_INT),
                    'some_unique'   => array('type'=>SchemaProperty::TYPE_CHAR_128)
                ),
                'PRIMARY_KEY'		=> 'id',
                "INDEXES" => array(
                    array('properties'=>array('name'))
                )
            ),
        );

        $dataMapper = $this->getDataMapper($testDefinition, $account->getId());
        $this->assertTrue($dataMapper->update($account->getId()), $dataMapper->getLastError());

        // Now test reading and writing data
        $data = array(
            "name" => 'my test value',
            "value" => 100,
        );
        $this->assertTrue($this->insertIntoBucket("unit_test_schema", $data), $dataMapper->getLastError());

        // Make sure the primary key was setup
        $this->assertTrue($this->primaryKeyExists("unit_test_schema", "id"));

        // Make sure the index was created on the 'name' property
        $this->assertTrue($this->indexExists("unit_test_schema", "name"));
    }
}
