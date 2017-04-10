<?php
/**
 * Test entity definition loader class that is responsible for creating and initializing exisiting definitions
 *
 * Most tests are inherited from DmTestsAbstract.php.
 * Only define pgsql specific tests here and try to avoid name collision with the tests
 * in the parent class. For the most part, the parent class tests all public functions
 * so private functions should be tested below.
 */
namespace NetricTest\EntityDefinition\DataMapper;

use Netric;
use PHPUnit_Framework_TestCase;

class PgsqlTest extends DmTestsAbstract
{
    /**
     * Handle to pgsql database
     *
     * @var Db\Pgsql
     */
    private $dbh = null;

    /**
     * Use this funciton in all the datamappers to construct the datamapper
     *
     * @return EntityDefinition_DataMapperInterface
     */
    protected function getDataMapper()
    {
        $sm = $this->account->getServiceManager();
        $dbh = $sm->get("Db");
        $this->dbh = $dbh;
        return new \Netric\EntityDefinition\DataMapper\Pgsql($this->account, $dbh);
    }

    /**
     * Test to make sure the schema is automatically updated
     */
    public function testCreateObjectTable()
    {
        // Setup
        $dm = $this->getDataMapper();
        $dbh = $this->dbh;
        $objName = "utest_newtype";

        // First cleanup
        $dbh->query("delete from app_object_types where name='$objName'");
        if ($dbh->tableExists("objects_" . $objName . "_act"))
            $dbh->query("DROP TABLE objects_" . $objName . "_act");
        if ($dbh->tableExists("objects_" . $objName . "_del"))
            $dbh->query("DROP TABLE objects_" . $objName . "_del");
        if ($dbh->tableExists("objects_" . $objName))
            $dbh->query("DROP TABLE objects_" . $objName);

        // Create unit test type
        $query = "insert into app_object_types(name, title, revision) 
                                     values('$objName', '$objName', '0');
                                     select currval('app_object_types_id_seq') as id;";

        $result = $dbh->query($query);
        if ($dbh->getNumRows($result)) {
            $otid = $dbh->GetValue($result, 0, "id");

            // Open the new object which should initialize the schema
            $dm = $this->getDataMapper();
            $def = $dm->fetchByName($objName);

            // Make sure tables exist
            $this->assertTrue($dbh->tableExists("objects_" . $objName));
            $this->assertTrue($dbh->tableExists("objects_" . $objName . "_act"));
            $this->assertTrue($dbh->tableExists("objects_" . $objName . "_del"));

            // Make sure fields have been added to object meta data
            $this->assertTrue($dbh->getNumRows($dbh->query("select id from app_object_type_fields where type_id='$otid'")) > 0);

            // Cleanup
            $dbh->query("drop table objects_" . $objName . "_act;");
            $dbh->query("drop table objects_" . $objName . "_del;");
            $dbh->query("drop table objects_" . $objName . ";");
            $dbh->query("delete from app_object_types where name='$objName'");
        }
    }

    /**
     * Test checkObjColumn to make sure columns are actually created
     */
    public function testCheckObjColumn()
    {
        // Setup
        $dm = $this->getDataMapper();
        $dbh = $this->dbh;

        $dm = $this->getDataMapper();
        $def = $dm->fetchByName("customer");

        // Create test grouping field to make sure they _fval column is also created to cache label
        $field = new \Netric\EntityDefinition\Field();
        $field->name = "ut_test_check_obj_column";
        $field->title = "ut_test_check_obj_column";
        $field->type = "fkey_multi";

        // Cleanup in case this is left over
        $dbh->query("DELETE FROM app_object_type_fields WHERE name='" . $field->name . "' and type_id='" . $def->getId() . "'");
        if ($dbh->ColumnExists($def->getTable(), $field->name))
            $dbh->query("ALTER TABLE " . $def->getTable() . " DROP COLUMN " . $field->name . ";");
        if ($dbh->ColumnExists($def->getTable(), $field->name . "_fval"))
            $dbh->query("ALTER TABLE " . $def->getTable() . " DROP COLUMN " . $field->name . "_fval;");

        // Get access to private checkObjColumn with reflection object
        $refIm = new \ReflectionObject($dm);
        $checkObjColumn = $refIm->getMethod("checkObjColumn");
        $checkObjColumn->setAccessible(true);
        $this->assertTrue($checkObjColumn->invoke($dm, $def, $field));

        // Make sure the column was added
        $this->assertTrue($dbh->columnExists($def->getTable(), $field->name));
        $this->assertTrue($dbh->columnExists($def->getTable(), $field->name . "_fval"));

        // Cleanup
        $dbh->query("DELETE FROM app_object_type_fields WHERE name='" . $field->name . "' and type_id='" . $def->getId() . "'");
        $dbh->query("ALTER TABLE " . $def->getTable() . " DROP COLUMN " . $field->name . ";");
        $dbh->query("ALTER TABLE " . $def->getTable() . " DROP COLUMN " . $field->name . "_fval;");
    }

    public function testFkeyFields()
    {
        // Setup
        $dm = $this->getDataMapper();
        $dbh = $this->dbh;

        $def = $dm->fetchByName("customer");
        $customerFields = $def->getFields();

        // Loop thru customer fields and find a field with fkey type
        foreach ($customerFields as $field)
        {
            if ($field->type === "fkey") {

                // Insert sample optional value in the custom table
                $dbh->query("INSERT INTO {$field->subtype} (name) VALUES('test_optional_value_custom_table')");

                // Insert sample optional values in the generic table
                $dbh->query("INSERT INTO app_object_field_options(field_id, key, value) VALUES('{$field->id}', 'test_optional_value_generic', 'test_optional_value_generic')");

                // Fetch the customer object again and test the status field
                $customerDefinition = $dm->fetchByName("customer");
                $customerField = $customerDefinition->getField($field->name);
                $this->assertTrue(sizeof($customerField->optionalValues) > 1);
                $this->assertTrue(in_array("test_optional_value_custom_table", $customerField->optionalValues));
                $this->assertTrue(in_array("test_optional_value_generic", $customerField->optionalValues));

                // Clean the inserted data
                $dbh->query("DELETE FROM {$field->subtype} WHERE name = 'test_optional_value_custom_table'");
                $dbh->query("DELETE FROM app_object_field_options WHERE field_id = {$field->id} and key = 'test_optional_value_generic'");

                // After we tested a field with fkey type, then let's break the loop
                break;
            }
        }
    }
}
