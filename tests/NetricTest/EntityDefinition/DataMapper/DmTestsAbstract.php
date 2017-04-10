<?php
/**
 * Define common tests that will need to be run with all data mappers.
 *
 * In order to implement the unit tests, a datamapper test case just needs
 * to extend this class and create a getDataMapper class that returns the
 * datamapper to be tested
 */
namespace NetricTest\EntityDefinition\DataMapper;

use Netric;
use Netric\EntityDefinition;
use Netric\Entity\ObjType\UserEntity;
use Netric\Permissions\Dacl;
use PHPUnit_Framework_TestCase;

abstract class DmTestsAbstract extends PHPUnit_Framework_TestCase 
{
    /**
     * Tennant account
     * 
     * @var \Netric\Account\Account
     */
    protected $account = null;

    /**
     * Definitions to cleanup
     *
     * @var EntityDefinition[]
     */
    protected $testDefinitions = [];

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
        $this->account = \NetricTest\Bootstrap::getAccount();
	}

    protected function tearDown()
    {
        $dm = $this->getDataMapper();
        foreach ($this->testDefinitions as $def) {
            $dm->deleteDef($def);
        }
    }

    /**
	 * Use this funciton in all the datamappers to construct the datamapper
	 *
	 * @return Netric\EntityDefinition\DataMapperAbstract;
	 */
	protected function getDataMapper()
	{
		return false;
	}

	/**
	 * Test loading data into the definition from an array
	 */
	public function testFetchByName()
	{
		$dm = $this->getDataMapper();

		$entDef = $dm->fetchByName("customer");

		// Make sure the ID is set
		$this->assertFalse(empty($entDef->id));

		// Make sure revision is not 0 which means uninitialized
		$this->assertTrue($entDef->revision > 0);

		// Field tests
		// ------------------------------------------------
		
		// Verify that we have a name field of type text
		$field = $entDef->getField("name");
		$this->assertEquals("text", $field->type);

		// Test optional values
		$field = $entDef->getField("type_id");
		$this->assertTrue(count($field->optionalValues) > 1);

		// Test fkey_multi
		$field = $entDef->getField("groups");
		$this->assertFalse(empty($field->id));
		$this->assertEquals("parent_id", $field->fkeyTable['parent']);
		$this->assertEquals("fkey_multi", $field->type);
		$this->assertEquals("customer_labels", $field->subtype);
		$this->assertEquals("customer_label_mem", $field->fkeyTable['ref_table']['table']);
		$this->assertEquals("customer_id", $field->fkeyTable['ref_table']['this']);
		$this->assertEquals("label_id", $field->fkeyTable['ref_table']['ref']);

		// Test object reference with autocreate
		$field = $entDef->getField("folder_id");
		$this->assertFalse(empty($field->id));
		$this->assertEquals("object", $field->type);
		$this->assertEquals("folder", $field->subtype);
		$this->assertEquals('/System/Customer Files', $field->autocreatebase);
		$this->assertEquals('id', $field->autocreatename);

	}

    /**
     * Test saving a discretionary access control list (DACL)
     */
	public function testSaveDef_Dacl()
	{
        $dataMapper = $this->getDataMapper();

        $def = new EntityDefinition("utest_save_dacl");
        $def->setTitle("Unit Test Dacl");
        $def->setSystem(false);
        $dacl = new Netric\Permissions\Dacl();
        $def->setDacl($dacl);

        // Test inserting with dacl
        $dataMapper->saveDef($def);
        $this->testDefinitions[] = $def;

        // Reload and check DACL
        $reloadedDef = $dataMapper->fetchByName("utest_save_dacl");
        $this->assertNotNull($reloadedDef->getDacl());

        // Now test updating the dacl
        $daclEdit = $def->getDacl();
        $daclEdit->allowGroup(UserEntity::GROUP_USERS, Dacl::PERM_FULL);
        $id = $dataMapper->saveDef($def);

        // Reload and check DACL
        $reloadedDef = $dataMapper->fetchByName("utest_save_dacl");
        $this->assertNotNull($reloadedDef->getDacl());
        $daclData = $reloadedDef->getDacl()->toArray();
        $this->assertEquals([UserEntity::GROUP_USERS], $daclData['entries'][0]['groups']);
	}

    /**
     * Test unsetting the DACL
     */
	public function testSaveDef_EmptyDacl()
	{
        $dataMapper = $this->getDataMapper();

        $def = new EntityDefinition("utest_save_empty_dacl");
        $def->setTitle("Unit Test Dacl");
        $def->setSystem(false);
        $dacl = new Netric\Permissions\Dacl();
        $def->setDacl($dacl);

        // Test inserting with dacl
        $dataMapper->saveDef($def);
        $this->testDefinitions[] = $def;

        // Reload and check DACL
        $reloadedDef = $dataMapper->fetchByName("utest_save_empty_dacl");
        $this->assertNotNull($reloadedDef->getDacl());

        // Now clear the dacl
        $def->setDacl(null);
        $id = $dataMapper->saveDef($def);

        // Reload
        $reloadedDef = $dataMapper->fetchByName("utest_save_empty_dacl");
        $this->assertNull($reloadedDef->getDacl());
	}

	/**
	 * Get groupings
	 */
	public function testGetGroupings()
	{
		$dm = $this->getDataMapper();
		if (!$dm)
			return; // skip if no mapper was defined

		// TODO: needs to be defined
		/*
		$entDef = $dm->fetchByName("customer");

		$groups = $dm->getGroupings($entDef, "groups", array());
		 */
	}
}
