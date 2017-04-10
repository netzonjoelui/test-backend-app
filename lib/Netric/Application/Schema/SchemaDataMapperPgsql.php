<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2015-2016 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Application\Schema;

use Netric\Db\Pgsql;
use Netric\Error\Error;

/**
 * PostgreSQL implementation of the schema DataMapper
 */
class SchemaDataMapperPgsql extends AbstractSchemaDataMapper
{
    /**
     * Handle to the database where the account is located
     *
     * @var Pgsql
     */
    private $dbh = null;

    /**
     * Construct this DataMapper
     *
     * @param Pgsql $dbh A handle to the PostgreSQL account database
     * @param array $schemaDefinition The latest schema definition
     */
    public function __construct(Pgsql $dbh, array $schemaDefinition)
    {
        $this->dbh = $dbh;
        $this->schemaDefinition = $schemaDefinition;
    }

    /**
     * Make sure a namespace/schema exists for this tenant
     *
     * @param int $accountId The unique account id to create a schema for
     * @return bool true on success, false on failure with $this->getLastError set
     */
    protected function createSchemaIfNotExists($accountId)
    {
        if (!$this->dbh->schemaExists("acc_" . $accountId))
        {
            if (!$this->dbh->query("CREATE SCHEMA acc_" . $accountId . ";", false))
            {
                // We failed for some reason
                $this->errors[] = new Error("Could not create schema: " . $this->dbh->getLastError());
                return false;
            }

            // Switch to the new schema
            $this->dbh->setSchema("acc_" . $accountId);
        }

        // Schema exists
        return true;
    }

    /**
     * Create and update a table for each data bucket
     *
     * A bucket is simply an abstract name for a table/collection/file/document
     * or whatever the particular data-store calls a collection of data with similar
     * properties.
     *
     * @param string $bucketName The name of the table/document/collection
     * @param array $bucketDefinition The definition of data that is stored in the bucket
     * @return bool true on success, false on failure with this->getLastError set
     */
    protected function applyBucketDefinition($bucketName, array $bucketDefinition)
    {
        $tableExists = $this->dbh->tableExists($bucketName);

        // Create or update columns
        // -----------------------------------------------

        /*
         * If this is a new table, we expect applyColumns to return true and add the
         * column definition string to $createColumns array so that the CREATE TABLE
         * statement below this can use the columns added to first create the table.
         *
         * If the table already exists then applyColumn will run an ALTER TABLE query.
         * This is done because creating the table all at once is about 2x as fast as
         * creating an empty table and altering it to add each column.
         */
        $createColumns = ($tableExists) ? false : array();

        // Loop through each column and either queue it to be added to a new table or alter existing
        foreach ($bucketDefinition['PROPERTIES'] as $columnName=>$columnDefinition) {
            if (!$this->applyColumn($bucketName, $columnName, $columnDefinition, $createColumns)) {
                // Something went wrong, leave and return an error
                $this->errors[] = new Error($this->dbh->getLastError());
                return false;
            }
        }

        // Create the table if it does not exist
        // ----------------------------------------
        if (is_array($createColumns)) {

            $sql = "CREATE TABLE IF NOT EXISTS $bucketName(" . implode(',', $createColumns) . ")";

            // Does this table inherit?
            if (isset($bucketDefinition['INHERITS']))
            {
                $sql .= " INHERITS (".$bucketDefinition['INHERITS'].")";
            }

            $sql .= ";";

            // Create the table
            if (!$this->dbh->query($sql)) {
                throw new \RuntimeException("Could not create table $bucketName: " . $this->dbh->getLastError());
            }
        }


        // Create primary key
        // -----------------------------------------------
        if (isset($bucketDefinition['PRIMARY_KEY']))
        {
           if (!$this->applyPrimaryKey($bucketName, $bucketDefinition['PRIMARY_KEY']))
           {
               // Something went wrong, leave and return an error
               $this->errors[] = new Error($this->dbh->getLastError());
               return false;
           }
        }

        // Create keys if supported by the database
        // -----------------------------------------------
        if (isset($bucketDefinition['KEYS']))
        {
            foreach ($bucketDefinition['KEYS'] as $keyData)
            {
                $this->applyForeignKey($bucketName, $keyData);
            }
        }

        // Create indexes
        // -----------------------------------------------
        if (isset($bucketDefinition['INDEXES']))
        {
            foreach ($bucketDefinition['INDEXES'] as $indexData)
            {
                $this->applyIndex($bucketName, $indexData);
            }
        }

        return true;
    }

    /**
     * Apply definition to a column
     *
     * @param $tableName
     * @param $columnName
     * @param array $columnDefinition
     * @param array|bool $createColumns If new table this will be an array to add statements to
     * @return bool true on success, false on failure
     */
    private function applyColumn($tableName, $columnName, array $columnDefinition, &$createColumns = false)
    {
        // Make sure the column names are not too long
        if (strlen($columnName) > 64)
            throw new \RuntimeException("Column name '$columnName' on table '$tableName' is too long.");

        if (isset($columnDefinition['default']) && $columnDefinition['default'] == 'auto_increment' && strlen($columnName) > 61) // "${column_name}_gen"
            throw new \RuntimeException("Auto increment column name '$columnName' on table '$tableName' is too long.");

        // Return true if the column already exists
        if ($createColumns === false) {
            if ($this->dbh->columnExists($tableName, $columnName)) {
                return true;
            }
        }

        // Determine the column type
        if (isset($columnDefinition['default']) && $columnDefinition['default'] == 'auto_increment')
        {
            $columnType = ($columnDefinition['type'] == 'bigint') ? 'bigserial' : 'serial';
        }
        else if (isset($columnDefinition['subtype']) && $columnDefinition['subtype'])
        {
            $columnType = $columnDefinition['type'] . " " . $columnDefinition['subtype'];
        }
        else if (isset($columnDefinition['type']))
        {
            $columnType = $columnDefinition['type'];
        }
        else
        {
            throw new \RuntimeException("Could not add $columnName to $tableName because missing type " . var_export($columnDefinition, true));
        }

        // Add column defaults
        $default = "";
        if (isset($columnDefinition['default']) && $columnDefinition['default'] != 'auto_increment')
        {
            $default = " DEFAULT '{$columnDefinition['default']}'";
        }

        /*
         * If this is a new table we do not want to run an alter, but rather just add
         * the column name so that it can be added to a create statement and return true.
         */
        if (is_array($createColumns)) {
            $createColumns[] = "{$columnName} {$columnType} $default";
            return true;
        }

        // Add column definition
        $sql = "ALTER TABLE $tableName ADD COLUMN {$columnName} {$columnType} $default";

        return ($this->dbh->query($sql)) ? true : false;
    }

    /**
     * Apply definition to a primary key
     *
     * @param string $tableName The name of the table we are creating
     * @param string|string[] $columnNameOrNames Either a single column name or an array of columns
     * @return true on success, false on failure
     */
    private function applyPrimaryKey($tableName, $columnNameOrNames)
    {
        // Normalize to an array so we can implode below
        if (!is_array($columnNameOrNames))
            $columnNameOrNames = array($columnNameOrNames);

        // First check to see if the primary key already exists
        if ($this->dbh->isPrimaryKey($tableName, $columnNameOrNames)) {
            return true;
        }

        // If the table already has a primary key, then leave it alone
        if ($this->dbh->hasPrimaryKey($tableName)) {
            // TODO: Log that the primary keys are different
            return true;
        }

        // Run the SQL
        $sql = "ALTER TABLE $tableName ADD PRIMARY KEY (" . implode(', ', $columnNameOrNames) . ");";
        return ($this->dbh->query($sql)) ? true : false;
    }

    /**
     * Add a foreign key to a table
     *
     * @param string $tableName
     * @param array $keyDefinition
     * @return bool true on sucess, false on failure
     */
    private function applyForeignKey($tableName, $keyDefinition)
    {
        // Make sure the definition is valid
        if (!isset($keyDefinition['property'])
            || isset($keyDefinition['references_bucket'])
            || isset($keyDefinition['references_property']))
        {
            $this->errors[] = new Error("Key definition for $tableName is invalid" . var_export($keyDefinition, true));
            return false;
        }

        // Set the key name
        $foreignKeyName = $tableName . "_" . $keyDefinition['property'] . "_fkey";

        if (strlen($foreignKeyName) > 63)
        {
            throw new \RuntimeException("Key name '$foreignKeyName' on table '$tableName' is too long");
        }

        // TODO: right now we don't do anything with keys
        return true;

        $sql = ($keyDefinition[0] == 'UNIQUE') ?  'CREATE UNIQUE INDEX' : 'CREATE INDEX';
        $sql .= " {$tableName}_{$foreignKeyName}_idx ON {$tableName} (" . implode(', ', $keyDefinition[1]) . ");";
        return ($this->dbh->query($sql)) ? true : false;
    }

    /**
     * @deprecated We replaced this with new INDEXES settings seen in applyIndex
     *
     * Old index was in keys
     *
     * @param string $tableName
     * @param string $foreignKeyName
     * @param array $keyDefinition
     * @return bool true on sucess, false on failure
     */
    private function applyIndexOld($tableName, $foreignKeyName, $keyDefinition)
    {
        // TODO: right now we don't do anything with keys
        return true;

        // The first element of the definition should be an array of columns
        if (!is_array($keyDefinition[1]))
        {
            $keyDefinition[1] = array($keyDefinition[1]);
        }

        if (strlen($tableName . $foreignKeyName) > 63)
        {
            throw new \RuntimeException("Key name '${$tableName}_$foreignKeyName' on table '$tableName' is too long");
        }

        $sql = ($keyDefinition[0] == 'UNIQUE') ?  'CREATE UNIQUE INDEX' : 'CREATE INDEX';
        $sql .= " {$tableName}_{$foreignKeyName}_idx ON {$tableName} (" . implode(', ', $keyDefinition[1]) . ");";
        return ($this->dbh->query($sql)) ? true : false;
    }

    /**
     * Add an index to the table
     *
     * @param string $tableName
     * @param array $indexData
     * @return bool true on sucess, false on failure
     */
    private function applyIndex($tableName, $indexData)
    {
        $indexName = implode("_", $indexData['properties']);

        if (strlen($tableName . $indexName) > 63)
        {
            throw new \RuntimeException("Key name '${$tableName}_$indexName' on table '$tableName' is too long");
        }

        // Return true if the index already exists
        if ($this->dbh->indexExists("{$tableName}_{$indexName}_idx")) {
            return true;
        }

        $sql = (isset($indexData['type']) && $indexData['type'] == 'UNIQUE') ?  'CREATE UNIQUE INDEX' : 'CREATE INDEX';
        $sql .= " {$tableName}_{$indexName}_idx ON {$tableName} (" . implode(', ',  $indexData['properties']) . ");";

        return ($this->dbh->query($sql)) ? true : false;
    }

    /**
     * Add a constraint to the table
     *
     * @param string $tableName The name of the table we are editing
     * @param string $constraintName Unique name of the constraint
     * @param string $constraint
     * @return bool true on success, false on failure
     */
    private function applyConstraint($tableName, $constraintName, $constraint)
    {
        // If already exists do nothing
        if ($this->dbh->constraintExists($tableName, $tableName . "_" . $constraintName))
            return true;

        $sql = "ALTER $tableName ADD CONSTRAINT {$tableName}_".$constraintName." CHECK (" . $constraint . ")";
        return ($this->dbh->query($sql)) ? true : false;
    }
}
