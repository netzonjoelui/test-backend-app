<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2015-2016 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Application\Schema;

use Netric\Error\AbstractHasErrors;

/**
 * Common functions for all schema datamappers
 */
abstract class AbstractSchemaDataMapper extends AbstractHasErrors implements SchemaDataMapperInterface
{
    /**
     * Latest schema definition
     *
     * @var array
     */
    protected $schemaDefinition = [];

    /**
     * A data bucket is a table/document/collection or whatever the datastore calls it
     *
     * @param string $bucketName The name of the table/document/collection
     * @param array $bucketDefinition The definition of data that is stored in the bucket
     * @return bool true on success, false on failure with this->getLastError set
     */
    abstract protected function applyBucketDefinition($bucketName, array $bucketDefinition);

    /**
     * Make sure a database/namespace/schema or whatever the data store uses for multi tenancy exists
     *
     * @param int $accountId The unique account id to create a schema for
     * @return bool true on success, false on failure with $this->getLastError set
     */
    abstract protected function createSchemaIfNotExists($accountId);

    /**
     * Create the initial schema for an account
     *
     * @param int $accountId Optional account ID we are creating, otherwise assume system
     * @return bool true on success, false on failure
     */
    public function update($accountId = null)
    {
        // First make sure the schema exists
        if ($accountId)
        {
            /*
             * If no $accountId has been passed then we can assume this is updating the
             * system account. Otherwise, if an accountId has been passed we need to make
             * sure the schema/database for that specific account exists before we try
             * to update it.
             *
             * In the case of the main system Application database, it MUST be created prior
             * to running update on the schema. This is normally done by running
             * ./bin netric install from the command line if netric is running in stand alone mode
             * otherwise we can assume the administrator should have setup the application.
             */
            if (!$this->createSchemaIfNotExists($accountId))
            {
                return false;
            }
        }

        // Make sure the this->schemaDefinition is applied to the new schema
        if (!$this->processDefinition())
        {
            // Something went wrong, get more details with $this->getLastError
            return false;
        }


        // The new schema should be ready to go
        return true;
    }

    /**
     * Diff the actual schema against the definition to check for changes and apply them
     */
    protected function processDefinition()
    {
        foreach ($this->schemaDefinition as $bucketName=>$bucketDefinition)
        {
            if (!$this->applyBucketDefinition($bucketName, $bucketDefinition))
            {
                // Something went wrong stop
                throw new \RuntimeException("Could not process schema: " . $this->getLastError()->getMessage());
            }
        }
        return true;
    }
}