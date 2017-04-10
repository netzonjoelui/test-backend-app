<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');    
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');    
require_once(dirname(__FILE__).'/../../controllers/CustomerController.php');
require_once(dirname(__FILE__).'/../../controllers/ObjectController.php');
require_once(dirname(__FILE__).'/../../lib/aereus.lib.php/antapi.php');        
require_once(dirname(__FILE__).'/../../lib/Olap.php');	

class AntApi_ObjectListTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;
	var $ant = null;

	/**
	 * The url of the ANT sever (usually set to localhost)
	 *
	 * @var string
	 */
	public $antServer = "";

	/**
	 * User to be used when connecting to ant
	 *
	 * @var string
	 */
	public $antUser = "";

	/**
	 * password to be used when connecting to ant
	 *
	 * @var string
	 */
	public $antPass = "";

	function setUp() 
	{
		// Elastic local store
		if (index_is_available("elastic"))
		{
			global $ANTAPI_STORE_ELASTIC_IDX, $ANTAPI_STORE_ELASTIC_HOST;

			$ANTAPI_STORE_ELASTIC_IDX = "tmp_ant_uni_test";
			$ANTAPI_STORE_ELASTIC_HOST = ANT_INDEX_ELASTIC_HOST;
		}

		// PGSQL local store
		if (AntConfig::getInstance()->db['host'] && AntConfig::getInstance()->db['type'] == "pgsql")
		{
			global $ANTAPI_STORE_PGSQL_HOST, $ANTAPI_STORE_PGSQL_DBNAME, $ANTAPI_STORE_PGSQL_USER, $ANTAPI_STORE_PGSQL_PASSWORD;

			$ANTAPI_STORE_PGSQL_HOST = AntConfig::getInstance()->db['host'];
			$ANTAPI_STORE_PGSQL_DBNAME = "tmp_ant_uni_test";
			$ANTAPI_STORE_PGSQL_USER = AntConfig::getInstance()->db['user'];
			$ANTAPI_STORE_PGSQL_PASSWORD = AntConfig::getInstance()->db['password'];
		}

		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);

		$this->antServer = $this->ant->getAccBaseUrl(false);
		$this->antUser = $this->user->name;
		$this->antPass = "Password1";
	}
	
	function tearDown() 
	{
		//@unlink('/temp/test.log');	
	}

	/**
	 * Test ObjectList search functionality
	 *
	 * Each store type will have its own test function calling 
	 * runStoreListSearch. Each store needs to be in its own
	 * function for failure reporting but all the same condition
	 * tests will be run via runStoreListSearch
	 */
	public function testAntApiObjectList()
	{
		$this->runStoreListSearch("ant"); // remote api
	}
	
	public function testLocalStoreListSearchElastic()
	{
		global $ANTAPI_STORE_ELASTIC_HOST;

		if ($ANTAPI_STORE_ELASTIC_HOST)
		{
			// Cleanup
			$store = new AntApi_ObjectStore_Elastic();
			$store->deleteIndex();

			$this->runStoreListSearch("elastic");
		}
	}

	public function testLocalStoreListSearchPgsql()
	{
		global $ANTAPI_STORE_PGSQL_HOST;

		if ($ANTAPI_STORE_PGSQL_HOST)
		{
			$this->runStoreListSearch("pgsql");
		}
	}

	/**
	 * Helper used to test object lists for each store type
	 *
	 * @param string $indType The type of index we are testing
	 */
	public function runStoreListSearch($indtype)
	{
		global $ANTAPI_STORE_ELASTIC_HOST, $ANTAPI_STORE_PGSQL_HOST, $ANTAPI_STORE_PGSQL_DBNAME;
		
		$obj = new AntApi_Object($this->antServer, $this->antUser, $this->antPass, "customer");
		$obj->setStoreSource($indtype);
		$obj->setValue("name", "TestCantObjectApiName");
		$obj->setValue("type_id", CUST_TYPE_ACCOUNT);
		$obj->setValue("owner_id", $this->user->id);
		$obj->debug = true;
		$custid = $obj->save();
		
		// NUMBER:	is_equal, is_not_equal, is_greater, is_less, 
		// 			is_greater_or_equal, is_less_or_equal
		// -----------------------------------------------------------------------

		// Test operator "is_equal"
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "customer");
		$objList->setStoreSource($indtype);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->getObjects();
		$this->assertTrue($objList->getNumObjects()>0);
		$objMin = $objList->getObjectMin(0);
		$this->assertEquals($objMin['id'], $custid);
		unset($objList);

		// Test operator "is_equal" with a negative value
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "customer");
		$objList->setStoreSource($indtype);
		$objList->addCondition("and", "owner_id", "is_equal", $this->user->id);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->getObjects();
		//echo "<pre>".var_export($objList->lastQuery, true)."</pre>";
		$this->assertTrue($objList->getNumObjects()>0);
		$objMin = $objList->getObjectMin(0);
		//echo "<pre>".var_export($objList->objects, true)."</pre>";
		$this->assertEquals($objMin['id'], $custid);
		unset($objList);
		
		// Test operator "is_not_equal"
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "customer");
		$objList->setStoreSource($indtype);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->addCondition("and", "type_id", "is_not_equal", CUST_TYPE_ACCOUNT);
		$objList->getObjects();
		//echo "<pre>".$objList->lastQuery."</pre>";
		//echo "<pre>".var_export($objList->objects, true)."</pre>";
		$this->assertEquals($objList->getNumObjects(), 0);
		unset($objList);

		// Test project for fkey_multi
		$objProj = new AntApi_Object($this->antServer, $this->antUser, $this->antPass, "project");
		$objProj->setStoreSource($indtype);
		$objProj->setValue("name", "TestCantObjectProject");
		$objProj->setMValue("members", $this->user->id);
		$pid = $objProj->save();
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "project");
		$objList->setStoreSource($indtype);
		$objList->addCondition("and", "id", "is_equal", $pid);
		$objList->addCondition("and", "members", "is_equal", $this->user->id);
		$objList->getObjects();
		//echo "<pre>Query [$pid]: ".$objList->lastQuery."</pre>";
		//echo "<pre>".var_export($objList->objects, true)."</pre>";
		//echo "<pre>$pid</pre>";
		$this->assertEquals($objList->getNumObjects(), 1);
		// Delete with native api because hard deletes are not exposed over wapi
		$objProj = new CAntObject($this->dbh, "project", $pid, $this->user);
		$objProj->removeHard();
		unset($objList);

		// TEXT:	is_equal, is_not_equal, beings, contains
		// -----------------------------------------------------------------------
		
		// Test operator "is_equal"
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "customer");
		$objList->setStoreSource($indtype); // Manually set index type
		$objList->addCondition("and", "name", "is_equal", "TestCantObjectApiName");
		$objList->getObjects();
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);
		
		// Test operator "is_not_equal"
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "customer");
		$objList->setStoreSource($indtype); // Manually set index type
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->addCondition("and", "name", "is_not_equal", "TestCantObjectApiName");
		$objList->getObjects();
		$this->assertEquals($objList->getNumObjects(), 0);
		unset($objList);

		// Test operator "begins"
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "customer");
		$objList->setStoreSource($indtype); // Manually set index type
		$objList->addCondition("and", "name", "begins", "TestCantObjectApiNam");
		$objList->getObjects();
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Test operator "contains"
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "customer");
		$objList->setStoreSource($indtype); // Manually set index type
		$objList->addCondition("and", "name", "contains", "estCantObjectApiNam"); // TODO: Trace and Debug why "contains" operator not working
		$objList->getObjects();
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// DATE/TIME:	is_equal, is_not_equal,is_greater, is_less, 
		// 				is_greater_or_equal, is_less_or_equal, day_is_equal,
		//				month_is_equal, year_is_equal, last_x_days, last_x_weeks,
		//				last_x_months, last_x_years, next_x_days, next_x_weeks,
		//				next_x_months, next_x_years
		//
		// -----------------------------------------------------------------------
		
		// Test operator "is_equal" to null
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "customer");
		$objList->setStoreSource($indtype); // Manually set index type
		$objList->addCondition("and", "last_contacted", "is_equal", "");
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects()>0); // Will be at least one because last_contacted was not set above
		unset($objList);

		// Test operator "is_greater_or_equal"
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "customer");
		$objList->setStoreSource($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "is_greater_or_equal", date("m/d/Y"));
		$objList->getObjects(0, 10);
		//echo "<pre>".var_export($objList->lastQuery, true)."</pre>";
		$this->assertTrue($objList->getNumObjects()>0); // Will be at least one
		unset($objList);
		
		// Test operator "is_less_or_equal"
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "customer");
		$objList->setStoreSource($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "is_less_or_equal", date("m/d/Y", strtotime("tomorrow"))); // will be at least one
		$objList->getObjects(0, 10);
		//echo "<pre>".var_export($objList->lastQuery, true)."</pre>";
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Facet Search
		// -----------------------------------------------------------------------

		/*
		// Test operator "is_equal"
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "customer");
		$objList->setStoreSource($indtype); // Manually set index type
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->addFacetField("name");
		$objList->getObjects(0, 1);
		$this->assertTrue($objList->getNumObjects()>0);
		//echo "<pre>".var_export($objList->facetCounts['name'], true)."</pre>";
		$this->assertEquals($objList->facetCounts['name']['TestCantObjectApiName'], 1);
		unset($objList);
		 */
		
		// Start testing condition for null object value 
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "project");
		$objList->setStoreSource($indtype);
		$objList->addCondition("and", "parent", "is_equal", "");
		$objList->getObjects(); // Get the current list of projects without parent
		$currProjCnt = $objList->getNumObjects(); // Get the current list of projects without parent
		unset($objList);
		
		// Create another object to test null field
		$projObj = new AntApi_Object($this->antServer, $this->antUser, $this->antPass, "project");
		$projObj->setStoreSource($indtype);
		$projObj->setValue("name", "TestObjectWithNullField");
		$projObj->setValue("parent", 1); // Set project parent to be tested
		$projObj->debug = true;
		$projId = $projObj->save();
		$this->assertTrue($projId > 0);
		
		// Test operator "is_equal" with null value
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "project");
		$objList->setStoreSource($indtype);
		$objList->addCondition("and", "parent", "is_equal", "");
		$objList->getObjects();
		$projCnt = $objList->getNumObjects(); // Get the current list of projects without parent            
		$this->assertEquals($currProjCnt, $projCnt); // The newly added project should be disregarded since parent field is not null
		unset($objList);
		
		// Create another object to test null field
		$projObj1 = new AntApi_Object($this->antServer, $this->antUser, $this->antPass, "project");
		$projObj1->setStoreSource($indtype);
		$projObj1->setValue("name", "TestObjectWithNullField");
		$projObj1->setValue("parent", null);
		$projObj1->debug = true;
		$newProjId = $projObj1->save();
		$this->assertTrue($newProjId > 0);
		
		// Test operator "is_equal" with null value
		$objList = new AntApi_ObjectList($this->antServer, $this->antUser, $this->antPass, "project");
		$objList->setStoreSource($indtype);
		$objList->addCondition("and", "parent", "is_equal", "");
		$objList->addSortOrder("id", "desc");
		$objList->getObjects();
		$newprojCnt = $objList->getNumObjects(); // Get the current list of projects without parent            
		$this->assertNotEquals($projCnt, $newprojCnt); // Should not be equal, since the second project created has a null value of parent field
		
		// Test the project id
		$objMin = $objList->getObjectMin(0);
		$this->assertEquals($objMin['id'], $newProjId);
		unset($objList);
		 
		// Cleanup
		// ----------------------------------------------------------------------
		$antObj = new CAntObject($this->dbh, "customer", $custid);
		$antObj->save();
		$antObj->removeHard();
		unset($antObj);
		
		$antObj = new CAntObject($this->dbh, "project", $projId, $this->user);
		$antObj->removeHard();
		unset($antObj);
		
		$antObj = new CAntObject($this->dbh, "project", $newProjId, $this->user);
		$antObj->removeHard();
		unset($antObj);
	}	
}
