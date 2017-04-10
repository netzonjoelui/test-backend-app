<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/CAntObjectList.php');
require_once(dirname(__FILE__).'/../../customer/customer_functions.awp');
require_once(dirname(__FILE__).'/../../lib/aereus.lib.php/CAntObjectApi.php');

class CAntObjectListTest extends PHPUnit_Framework_TestCase 
{
	var $obj = null;
	var $dbh = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = new AntUser($this->dbh, -1); // -1 = administrator

		// Make sure the current user belongs to a team
		$this->user->verifyDefaultUserTeam();

		// Make sure the full-text only option is disabled for indexes
		// so we can test the entire filter capabilities of the index
		AntConfig::getInstance()->setValue("object_index", "fulltext_only", 0);
	}
	
	/**************************************************************************
	 * Function: 	testObjectListSearch
	 *
	 * Purpose:		Test the accuracy of the conditions that are passed to 
	 *				buildAdvancedConditionString
	 *
	 *				Test each operator:
	 *				is_equal
	 *				is_not_equal
	 *				is_greater
	 *				is_less
	 *				is_greater_or_equal
	 *				is_less_or_equal
	 *				begins_with
	 *				contains
	 *				day_is_equal
	 *				month_is_equal
	 *				year_is_equal
	 *				last_x_days
	 *				last_x_weeks
	 *				last_x_months
	 *				last_x_years
	 *				next_x_days
	 *				next_x_weeks
	 *				next_x_months
	 *				next_x_years
	 **************************************************************************/
    
    /**
     * The entityquery is now the proxy to the new Netric\EntityQuery interface
     */
    function testObjectListSearchEq()
	{
		$this->runTestObjectListSearch("entityquery");
	}
    
	/**
	 * Test database index
	 *
	 * @group db
     */
	function testObjectListSearchDb()
	{
		$this->runTestObjectListSearch("db");
	}

	/*
	function testObjectListSearchSolr()
	{
		$this->markTestSkipped('Cannot test since elastic search was not setup.');

		if (index_is_available("solr"))
			$this->runTestObjectListSearch("solr");
	}

	function testObjectListSearchElastic()
	{
		$this->markTestSkipped('Cannot test since elastic search was not setup.');

		if (index_is_available("elastic"))
			$this->runTestObjectListSearch("elastic");
	}
	*/

	/**
	 * Run all search types for a given index
	 *
	 * @param string $indtype The type of index to text: db, solr, elastic
	 */
	private function runTestObjectListSearch($indtype)
	{
		$dbh = $this->dbh;

		$obj = new CAntObject($dbh, "customer", null, $this->user);
		$grpdat = $obj->getGroupingEntryByName("groups", "testOlCacheObj Test Group");
		if (!$grpdat)
			$grpdat = $obj->addGroupingEntry("groups", "testOlCacheObj Test Group", "e3e3e3");
		$obj->setIndex($indtype);
		$obj->setValue("name", "TestCantObjectName");
		$obj->setValue("type_id", CUST_TYPE_ACCOUNT);
		$obj->setValue("owner_id", $this->user->id);
		$obj->setMValue("groups", $grpdat['id']);
        $statuses = $obj->getGroupingData("status_id");
		$obj->setValue("status_id", $statuses[0]["id"]);
		$custid = $obj->save();

		// NUMBER:	is_equal, is_not_equal, is_greater, is_less, 
		// 			is_greater_or_equal, is_less_or_equal
		// -----------------------------------------------------------------------

		// Test operator "is_equal"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->getObjects();
		//echo var_export($objList->lastQuery, true) . "\n";
		$this->assertTrue($objList->getNumObjects()>0);
		$objMin = $objList->getObjectMin(0);
		$this->assertEquals($objMin['id'], $custid);
		unset($objList);

		// Test operator "is_equal" with a negative value
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype);
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
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->addCondition("and", "type_id", "is_not_equal", CUST_TYPE_ACCOUNT);
		$objList->getObjects();
		$this->assertEquals($objList->getNumObjects(), 0);
		unset($objList);

		// Test operator "is_not_equal" fkey
        $groupings = $obj->getGroupingData("status_id");
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->addCondition("and", "status_id", "is_not_equal", $groupings[0]["id"]); // Any should do since we did not set status
		$objList->getObjects();
		//echo var_export($objList->lastQuery, true)."\n";
		$this->assertEquals($objList->getNumObjects(), 0);
		unset($objList);

		// Test project for fkey_multi
		$objProj = new CAntObject($dbh, "project", null, $this->user);
		$objProj->setIndex($indtype);
		$objProj->setValue("name", "TestCantObjectProject");
		$objProj->setMValue("members", $this->user->id);
		$pid = $objProj->save();
		$objList = new CAntObjectList($dbh, "project", $this->user);
		$objList->setIndex($indtype);
		$objList->addCondition("and", "id", "is_equal", $pid);
		$objList->addCondition("and", "members", "is_equal", $this->user->id);
		$objList->getObjects();
		//echo "<pre>".$objList->lastQuery."</pre>";
		//echo "<pre>".var_export($objList->objects, true)."</pre>";
		//echo "<pre>$pid</pre>";
		$this->assertEquals($objList->getNumObjects(), 1);
		$objProj->removeHard();
		unset($objList);

		// Test operator "is_equal" with USER_CURRENT variable
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype);
		$objList->addCondition("and", "owner_id", "is_equal", USER_CURRENT);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->getObjects();
		//echo "<pre>".var_export($objList->lastQuery, true)."</pre>";
		$this->assertTrue($objList->getNumObjects()>0);
		$objMin = $objList->getObjectMin(0);
		//echo "<pre>".var_export($objList->objects, true)."</pre>";
		$this->assertEquals($objMin['id'], $custid);
		unset($objList);

		// Test operator "is_equal" with cross-object query
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype);
		$objList->addCondition("and", "owner_id.team_id", "is_equal", TEAM_CURRENTUSER);
		$objList->addCondition("and", "id", "is_equal", $custid);
		//$objList->debug = true;
		$objList->getObjects();
		//echo var_export($objList->lastQuery, true)."\n";
		$this->assertTrue($objList->getNumObjects()>0);
		$objMin = $objList->getObjectMin(0);
		//echo "<pre>".var_export($objList->objects, true)."</pre>";
		$this->assertEquals($objMin['id'], $custid);
		unset($objList);

		// TEXT:	is_equal, is_not_equal, beings, contains
		// -----------------------------------------------------------------------
		
		// Test operator "is_equal"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "name", "is_equal", "TestCantObjectName");
		$objList->getObjects();
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);
		
		// Test operator "is_not_equal"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->addCondition("and", "name", "is_not_equal", "TestCantObjectName");
		$objList->getObjects();
		$this->assertEquals($objList->getNumObjects(), 0);
		unset($objList);

		// Test operator "begins"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "name", "begins", "TestCantObjectNam");
		$objList->getObjects();
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Test operator "contains"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "name", "contains", "estCantObjectNam");
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
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "last_contacted", "is_equal", "");
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects()>0); // Will be at least one because last_contacted was not set above
		unset($objList);

		// Test operator "is_greater_or_equal"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "is_greater_or_equal", date("m/d/Y"));
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects()>0); // Will be at least one
		unset($objList);

		// Test operator "is_greater_or_equal" beyond current range
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "is_greater_or_equal", date("m/d/Y", strtotime("+1 week")));
		$objList->getObjects(0, 10);
		$this->assertEquals($objList->getNumObjects(), 0); // Should be 0
		unset($objList);
		
		// Test operator "is_less_or_equal"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "is_less_or_equal", date("m/d/Y", strtotime("tomorrow"))); // will be at least one
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Test operator "is_less_or_equal" beyond range
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "is_less_or_equal", date("m/d/Y", strtotime("yesterday")));
		$objList->addCondition("and", "id", "is_equal", $obj->id);
		$objList->getObjects(0, 1);
		$this->assertEquals($objList->getNumObjects(), 0); // Should be 0
		unset($objList);
		
		// Test operator "year_is_equal"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "year_is_equal", date("Y")); // will be at least one
		$objList->getObjects(0, 10);
		//echo "<pre>".$objList->lastQuery."</pre>";
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Test operator "month_is_equal"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "month_is_equal", date("m")); // will be at least one
		$objList->getObjects(0, 10);
        /*
		if ($indtype == "elastic")
		    echo "<pre>CID: $custid\n".var_export($objList->lastQuery, true)."</pre>";
         */
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Test operator "day_is_equal"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "day_is_equal", date("d")); // will be at least one
		$objList->getObjects(0, 10);
		//echo "<pre>".var_export($objList->lastQuery, true)."</pre>";
		$this->assertTrue($objList->getNumObjects()>=0);
		unset($objList);

		// Test operator "last_x_days"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "last_x_days", 1); // will be at least one
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Test operator "last_x_weeks"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "last_x_weeks", 1); // will be at least one
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Test operator "last_x_months"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index  type
		$objList->addCondition("and", "time_entered", "last_x_months", 1); // will be at least one
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Test operator "last_x_years"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "last_x_years", 1); // will be at least one
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Test operator "next_x_days"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "next_x_days", 1); // Nothing was entered in the future
		$objList->getObjects(0, 10);            
		//echo "<pre>".$objList->lastQuery."</pre>";
		$this->assertTrue($objList->getNumObjects() >= 0); // Possible to have a 1 value.
		unset($objList);

		// Test operator "next_x_weeks"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "next_x_weeks", 1); // Nothing was entered in the future
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects() >= 0); // Possible to have a 1 value.
		unset($objList);

		// Test operator "next_x_months"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "next_x_months", 1); // Nothing was entered in the future
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects() >= 0); // Possible to have a 1 value.
		unset($objList);

		// Test operator "next_x_years"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "time_entered", "next_x_years", 1); // Nothing was entered in the future
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects() >= 0); // Possible to have a 1 value.
		unset($objList);


		// OBJECTS:	is_equal, is_not_equal, is_greater, is_less, 
		// 			is_greater_or_equal, is_less_or_equal
		// -----------------------------------------------------------------------
		$objAct = new CAntObject($dbh, "activity", null, $this->user);
		$objAct->setIndex($indtype); // Manually set index type
		$objAct->setValue("name", "TestCAntObjActTest");
		$objAct->setValue("obj_reference", "customer:$custid");
		$objAct->setMValue("associations", "customer:$custid");
		$actid = $objAct->save();
		unset($objList);

		// Single object reference
		$objList = new CAntObjectList($dbh, "activity", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "obj_reference", "is_equal", "customer:$custid");
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Assocaition type with id
		$objList = new CAntObjectList($dbh, "activity", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "associations", "is_equal", "customer:$custid");
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Assocaition type without id
		$objList = new CAntObjectList($dbh, "activity", $this->user);
		$objList->setIndex($indtype); // Manually set index type
		$objList->addCondition("and", "associations", "is_equal", "customer");
		$objList->getObjects(0, 10);
		$this->assertTrue($objList->getNumObjects()>0);
		unset($objList);

		// Delete the activity
		$objAct->removeHard();


		// Facet Search
		// -----------------------------------------------------------------------

		// Test operator "is_equal"
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->addFacetField("name");
		$objList->getObjects(0, 1);
		$this->assertTrue($objList->getNumObjects()>0);
		//echo "<pre>".var_export($objList->facetCounts, true)."</pre>";
		$val = ($objList->facetCounts['name']['TestCantObjectName']) 
			? $objList->facetCounts['name']['TestCantObjectName'] : $objList->facetCounts['name']['testcantobjectname'];
		$this->assertEquals(1, $val);

		unset($objList);

		// Test Aggregates
		// -----------------------------------------------------------------------
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->addAggregateField("owner_id", "sum");
		$objList->getObjects(0, 1);
		$this->assertTrue($objList->getNumObjects()>0);
		$this->assertEquals($objList->aggregateCounts['owner_id']['sum'], $this->user->id);

		unset($objList);

		// Now test number of queries - should only be one per query for db and none for indexes
		// -----------------------------------------------------------------------
		$statBefore = $dbh->statNumQueries;
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->setIndex($indtype);
		$objList->addCondition("and", "groups", "is_equal", $grpdat['id']);
		$objList->getObjects(0, 1);
		$this->assertTrue($objList->getNumObjects()>0);
		$loadedObj = $objList->getObject(0); // should load from cache of results
		if ($indtype == "db")
		{
			/* 
			 * The following should result in 4 queries
			 *
			 * 1. Set timezone for account
			 * 2. Check for child labels of this group
			 * 3. Main data query
			 * 4. Total count query
			 * The data for the object should load from the query results
			 */
			$this->assertTrue(($dbh->statNumQueries-$statBefore)<=4); // Loading an object should never take more than 4 queries
		}
		else
		{
			/*
			 * The following should result in 1 queries because the index should load everythign from cached data
			 * but the condition with the groups will cause the query builder to look for sub-groups
			 */
			//$this->assertTrue(($dbh->statNumQueries-$statBefore)<=1);
		}

		//$dbh->debug = false;
		//echo "<pre>Num Queries: ".($dbh->statNumQueries-$statBefore)."</pre>";

		// Now make sure the object loaded right with the cache
		$this->assertTrue($obj->getMValueExists("groups", $grpdat['id']));


		// Test deleted query
		// ---------------------------------------------------------------------------
		$this->runTestSearchDeleted($indtype);

		// Delete the customer
		$obj->deleteGroupingEntry("groups", $grpdat['id']);
		//$obj->removeHard();
	}

	/**
	 * Search for deleted items
	 *
	 * @param string $indtype The type of index to text: db, solr, elastic
	 */
	private function runTestSearchDeleted($indtype)
	{
		// Create new customer to delete
		$obj = new CAntObject($this->dbh, "project_story", null, $this->user);
		$obj->setIndex($indtype);
		$obj->setValue("name", "runTestSearchDeleted");
		$obj->setValue("owner_id", $this->user->id);
		$custid = $obj->save();
		$obj->remove(); // soft delete

		// First test regular query without f_deleted flag set
		$objList = new CAntObjectList($this->dbh, "project_story", $this->user);
		$objList->setIndex($indtype);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->getObjects();
		$this->assertEquals($objList->getNumObjects(), 0);
		unset($objList);

		// Test deleted flag set should return with deleted customer
		$objList = new CAntObjectList($this->dbh, "project_story", $this->user);
		$objList->setIndex($indtype);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->addCondition("and", "f_deleted", "is_equal", 't');
		$objList->getObjects();
		$this->assertTrue($objList->getNumObjects()>0);
		$objMin = $objList->getObjectMin(0);
		$this->assertEquals($objMin['id'], $custid);
		unset($objList);

		// Cleanup
		$obj->remove(); // hard delete
	}

	/*
	 * Test addMinField in CAntObjectList to make data is pulled
	 */
	public function testOlMinFieldAdd() 
	{
		$dbh = $this->dbh;
		
		$name = "Happy OL Min Test";
		// Test default phone
		$obj = new CAntObject($this->dbh, "customer", null, $this->user);
		$obj->setValue("name", $name);
		$custid = $obj->save();

		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->addMinField("name"); // pull 'name' in initial query
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->getObjects();
		//echo "<pre>$custid: ".var_export($objList->objects, true)."</pre>";
		$objMin = $objList->getObjectMin(0); // should update cache
		$this->assertEquals($objMin['name'], $name);

		$obj = $objList->getObject(0); // should update cache
		$obj->removeHard();
	}

	/**
	 * Test caching capacitlity of CAntObjectList
	 */
	public function testOlCacheObj() 
	{
		$dbh = $this->dbh;

		$name = "Happy OL Cache Test";
		// Test default phone
		$obj = new CAntObject($this->dbh, "customer", null, $this->user);
		$grpdat = $obj->addGroupingEntry("groups", "testOlCacheObj Test Group", "e3e3e3");
		$obj->setValue("name", $name);
		$obj->setValue("owner_id", $this->user->id);
		$obj->setMValue("groups", $grpdat['id']);
		$custid = $obj->save();

		// Test name (string value)
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->getObjects();
		$this->assertEquals(count($objList->objects), 1);
		$objList->objects[0]['data'] = array("name"=>$name, "revision"=>$obj->getValue("revision"));
		$objTest = $objList->getObject(0);
		$this->assertEquals($objTest->getValue("name"), $name);

		// Test a foreign key - this tests query ability to populate foreign keys, but should be per index type not just default
		/*
		$objList = new CAntObjectList($dbh, "customer", $this->user);
		$objList->addCondition("and", "id", "is_equal", $custid);
		$objList->getObjects();
		$this->assertEquals(count($objList->objects), 1);
		$objTest = $objList->getObject(0);
		$this->assertTrue(strlen($objTest->fValues["owner_id"]) > 0);
		*/

		$obj->deleteGroupingEntry("groups", $grpdat['id']);
		$obj->removeHard();
	}

	/**
	 * Test full-text only option for alternate index
	 *
	 * @group fulltextonly
	 */
	public function testFullTextOnlyAltInd()
	{
		$dbh = $this->dbh;
		$customerName = "testFullTextOnlyAltInd Test";

		// Turn on the full-text only option
		AntConfig::getInstance()->setValue("object_index", "fulltext_only", 1);

		// Create new customer
		$obj = new CAntObject($this->dbh, "customer", null, $this->user);
		$obj->setValue("name", $customerName);
		$obj->setValue("owner_id", $this->user->id);
		$custid = $obj->save(false);

		$this->assertTrue($obj->indexFullTextOnly);

		// Further tests if 'elastic' index is available
		if (index_is_available("elastic"))
		{
			// Test filtered lists without full-text
			$objList = new CAntObjectList($dbh, "customer", $this->user);
			$objList->setIndex("elastic"); // Manually set index type
			$objList->addCondition("and", "name", "is_equal", $customerName);
			$objList->getObjects();
			// What should have happened is the 'db' index should have been used
			$this->assertEquals($objList->lastIndexUsed, "db");

			// Now test full text
			$objList = new CAntObjectList($dbh, "customer", $this->user);
			$objList->setIndex("elastic"); // Manually set index type
			$objList->addConditionText($customerName);
			$objList->getObjects();
			// What should have happened is the 'elastic' index should have been used
			$this->assertEquals($objList->lastIndexUsed, "elastic");

			// Turn off the full-text only option
			AntConfig::getInstance()->setValue("object_index", "fulltext_only", 0);

			// Test filtered list again but this time it should be exclusively elastic
			$objList = new CAntObjectList($dbh, "customer", $this->user);
			$objList->setIndex("elastic"); // Manually set index type
			$objList->addCondition("and", "name", "is_equal", $customerName);
			$objList->getObjects();
			// What should have happened is the 'db' index should have been used
			$this->assertEquals($objList->lastIndexUsed, "elastic");
		}

		// Cleanup
		$obj->removeHard();
	}

	/**
	 * Test hierarcy subqueries
	 *
	 * @group testHierarcySubqueries
	 */
	public function testHierarcySubqueries()
	{
		$indexes = array("db");
		if (index_is_available("elastic"))
			$indexes[] = "elastic";
		
		// Setup files and folders for example
		$antfs = new AntFs($this->dbh, $this->user);
		$fldr = $antfs->openFolder("/tests/testHierarcySubqueries", true);
		$this->assertNotNull($fldr);
		$fldr2 = $antfs->openFolder("/tests/testHierarcySubqueries/Child", true);
		$this->assertNotNull($fldr2);
		$file = $fldr2->openFile("testsync", true);
		$this->assertNotNull($file);

		foreach ($indexes as $indName)
		{
			$fldr->setIndex($indName);
			$fldr->index();
			$fldr2->setIndex($indName);
			$fldr2->index();
			$file->setIndex($indName);
			$file->index();

			// Test equal to root which should return none
			$objList = new CAntObjectList($this->dbh, "file", $this->user);
			$objList->setIndex($indName); // Manually set index type
			$objList->addCondition("and", "folder_id", "is_equal", $fldr->id);
			$objList->getObjects();
			$this->assertEquals(0, $objList->getNumObjects());

			// Now test with is_less_or_equal
			$objList = new CAntObjectList($this->dbh, "file", $this->user);
			$objList->setIndex($indName); // Manually set index type
			$objList->addCondition("and", "folder_id", "is_less_or_equal", $fldr->id);
			$objList->getObjects();
			$this->assertTrue($objList->getNumObjects() > 0);
		}

		// Cleanup
		$file->removeHard();
		$fldr2->removeHard();
		$fldr->removeHard();
	}

	/**
	 * Test if using an fkey label works
	 *
	 * @group testFkeyLabelToId
	 */
	public function testFkeyLabelToId()
	{
		$dbh = $this->dbh;

		$obj = new CAntObject($dbh, "activity", null, $this->user);
		$grpdat = $obj->getGroupingEntryByName("type_id", "testFkeyLabelToId");
		if (!$grpdat)
			$grpdat = $obj->addGroupingEntry("type_id", "testFkeyLabelToId");
		$obj->setValue("name", "Test customer testFkeyLabelToId");
		$obj->setValue("type_id", $grpdat["id"]);
		$oid = $obj->save();

		// Query based on type_id label
		$objList = new CAntObjectList($this->dbh, "activity", $this->user);
		$objList->addCondition("and", "type_id", "is_equal", "testFkeyLabelToId");
		$objList->getObjects();
		$this->assertTrue($objList->getNumObjects() > 0);

		// Cleanup
		$obj->deleteGroupingEntry("groups", $grpdat['id']);
		$obj->removeHard();
	}

	/**
	 * Test query string patter explosion
	 *
	 * @group testSearchStrExpl
	 */
	public function testSearchStrExpl()
	{
		$dbh = $this->dbh;

		$obj = new CAntObject($dbh, "activity", null, $this->user);
		$index = $obj->getIndex();

		// Single email address
		$qstr = "sky.stebnicki@aereus.com";
		$terms = $index->queryStringToTerms($qstr);
		$this->assertEquals($terms[0], "sky.stebnicki@aereus.com");

		// terms and phrases
		$qstr = "sky.stebnicki@aereus.com \"in quotes\" single";
		$terms = $index->queryStringToTerms($qstr);
		$this->assertEquals($terms[0], "sky.stebnicki@aereus.com");
		$this->assertEquals($terms[1], "\"in quotes\"");
		$this->assertEquals($terms[2], "single");
	}
}
