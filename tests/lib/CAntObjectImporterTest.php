<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/CAntObjectList.php');
require_once(dirname(__FILE__).'/../../lib/CAntObjectImporter.php');
require_once(dirname(__FILE__).'/../../customer/customer_functions.awp');
require_once(dirname(__FILE__).'/../../lib/aereus.lib.php/CAntObjectApi.php');

class CAntObjectImporterTest extends PHPUnit_Framework_TestCase 
{
	var $obj = null;
	var $dbh = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, -1); // -1 = administrator
	}
	
	function tearDown() 
	{
		//@unlink('/temp/test.log');	
	}

	/**
	 * Used to execute a single test
	function getTests()
	{
		return array("testObjectListSearchElastic");
	}
	 */

	/**
	 * Test import
	 */
	function testImportLocal() 
	{
		// Create new importer
		$imp = new CAntObjectImporter($this->dbh, "customer", $this->user);
		$imp->setSourceFile(dirname(__FILE__).'/../data/imp_cust.csv');

		// Set column to field maps
		$imp->addFieldMap(0, "first_name");
		$imp->addFieldMap(1, "last_name");
		$imp->addFieldMap(2, "nick_name");

		// Set field defaults (to be used if source cell is empty)
		//$imp->addFieldDefault("email", "tester@tester.com");

		// Set fields/columns to use for merging into existing records
		//$imp->addMergeBy($mb);

		// Run the import
		$ret = $imp->import();

		$this->assertEquals($imp->numImported, 2);

		// Cleanup
		$olist = new CAntObjectList($this->dbh, "customer", $this->user);
		$olist->addCondition("and", "first_name", "begins_with", "UTIMP_");
		$olist->getObjects();
		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$obj = $olist->getObject($i);

			//$this->assertEquals($obj->getValue("email"), "tester@tester.com");

			$obj->removeHard();
		}
	}

	/**
	 * Test default
	 */
	function testDefault() 
	{
		// Create new importer
		$imp = new CAntObjectImporter($this->dbh, "customer", $this->user);
		$imp->setSourceFile(dirname(__FILE__).'/../data/imp_cust.csv');

		// Set column to field maps
		$imp->addFieldMap(0, "first_name");
		$imp->addFieldMap(1, "last_name");
		$imp->addFieldMap(2, "nick_name");
		$imp->addFieldMap(3, "email");

		// Set field defaults (to be used if source cell is empty)
		$imp->addFieldDefault("email", "tester@tester.com");

		// Run the import
		$ret = $imp->import();

		// Cleanup
		$olist = new CAntObjectList($this->dbh, "customer", $this->user);
		$olist->addCondition("and", "first_name", "begins_with", "UTIMP_");
		$olist->getObjects();
		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$obj = $olist->getObject($i);

			
			if ($obj->getValue("first_name") == "UTIMP_SKY") // should be default set above
				$this->assertEquals($obj->getValue("email"), "tester@tester.com");
			else if ($obj->getValue("first_name") == "UTIMP_JOHN") // was aready set, should be unchanged from umported data
				$this->assertEquals($obj->getValue("email"), "utimptester@tester.com");

			$obj->removeHard();
		}
	}

	/**
	 * Test merge
	 */
	function testMerge() 
	{
		// First cleanup
		$olist = new CAntObjectList($this->dbh, "customer", $this->user);
		$olist->addCondition("and", "first_name", "begins_with", "UTIMP_");
		$olist->getObjects();
		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$obj = $olist->getObject($i);
			$obj->removeHard();
		}

		// Create new importer
		$imp = new CAntObjectImporter($this->dbh, "customer", $this->user);
		$imp->setSourceFile(dirname(__FILE__).'/../data/imp_cust.csv');

		// Set column to field maps
		$imp->addFieldMap(0, "first_name");
		$imp->addFieldMap(1, "last_name");
		$imp->addFieldMap(2, "nick_name");
		$imp->addFieldMap(3, "email"); // Should not exist

		// Set field defaults (to be used if source cell is empty)
		$imp->addFieldDefault("email", "utimptester@tester.com");

		// Set fields/columns to use for merging into existing records
		// Because it is the default, then subsequent records will be merged to the first
		$imp->addMergeBy("email");

		// Run the import
		$ret = $imp->import();

		// The second record should have merged into the first
		$this->assertEquals(1, $imp->numMerged);

		// Cleanup
		$olist = new CAntObjectList($this->dbh, "customer", $this->user);
		$olist->addCondition("and", "first_name", "begins_with", "UTIMP_");
		$olist->getObjects();
		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$obj = $olist->getObject($i);

			$obj->removeHard();
		}
	}
}
