<?php
/**
 * Test managing a schema in PostgreSQL database
 */
namespace NetricTest\Application\Schema;

use Netric\Application\Application;
use Netric\Config\ConfigLoader;
use Netric\Db\Pgsql;
use Netric\Application\Schema\SchemaDataMapperPgsql;

class SchemaDataMapperPgsqlTest extends AbstractSchemaDataMapperTests
{
    /**
     * Handle to current database
     *
     * @var null
     */
    private $dbh = null;

    /**
     * Get the PostgreSQL DataMapper
     *
     * @param array $schemaDefinition
     * @param string $accountId THe account we will be managing the schema for
     * @return SchemaDataMapperPgsql
     */
    protected function getDataMapper(array $schemaDefinition, $accountId)
    {
        $configLoader = new \Netric\Config\ConfigLoader();
        $applicationEnvironment = (getenv('APPLICATION_ENV')) ? getenv('APPLICATION_ENV') : "production";

        // Setup the new config
        $config = $configLoader->fromFolder(__DIR__ . "/../../../../config", $applicationEnvironment);

        $pgsql = new Pgsql(
            $config->db['host'],
            $config->db['accdb'],
            $config->db['user'],
            $config->db['password']
        );

        // Set the schema we will be interacting with
        $pgsql->setSchema("acc_" . $accountId);

        $this->dbh = $pgsql;

        return new SchemaDataMapperPgsql($pgsql, $schemaDefinition);
    }

    /**
     * Test a created bucket by inserting data
     *
     * @param string $bucketName The name of the table/document/collection to save data to
     * @param array $data The data to insert and verify
     * @return bool true if data could be inserted and read from the data store
     */
    protected function insertIntoBucket($bucketName, array $data)
    {
        $columns = [];
        $values = [];
        foreach ($data as $colName=>$value)
        {
            $columns[] = $colName;
            $values[] = $value;
        }

        $sql = "INSERT INTO " . $bucketName . "(" . implode(',', $columns) . ")
                VALUES('" . implode("','", $values) . "')";

        // Return true if we were able to insert successfully
        return ($this->dbh->query($sql)) ? true : false;
    }

    /**
     * Make sure that a field with a primary key is set
     *
     * @param string $bucketName The name of the table/document/collection to test
     * @param string|array $propertyOrProperties A property name or array of property names
     * @return bool true if the key exists
     */
    protected function primaryKeyExists($bucketName, $propertyOrProperties)
    {
        return $this->dbh->isPrimaryKey($bucketName, $propertyOrProperties);
    }

    /**
     * Assure that there is an index on a given property for a bucket
     *
     * @param string $bucketName The name of the table/document/collection to test
     * @param string|array $propertyOrProperties A property name or array of property names
     * @return bool true if the index exists
     */
    protected function indexExists($bucketName, $propertyOrProperties)
    {
        return $this->dbh->indexExists($bucketName . "_" . $propertyOrProperties . "_idx");
    }
}
