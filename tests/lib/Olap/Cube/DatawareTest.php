<?php
/**
 * This unit test specifically focusses on the datawarehouse cube implementation.
 *
 * Adhock cubes or non-cube-specific olap functions should not be tested here.
 */
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../../lib/Olap.php');

class Olap_Cube_DatawareTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;
	var $ant = null;
	var $testCubeName = "";

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
		$this->testCubeName = "tests/testCreateCube";
		
		$this->markTestSkipped('Depricated.');
	}
	
	function tearDown() 
	{
		//@unlink('/temp/test.log');	
	}
	
	/**
	 * This function can be used to limit the unit test to a single function
	 */
	/*function getTests()
	{        
		return array("testTabular");        
	}*/

	/**
	 * Test inserting new cube into datawarehouse
	 *
	 * This process is desinged to be very dynamic so that new cubes
	 * can be created easily simply by inserting data for the first time
	 */
	function testCreateCube()
	{
		$olap = new Olap($this->dbh);
		$olap->deleteCube($this->testCubeName); // Purge cube if already exists

		// Get new cube
		$cube = $olap->getCube($this->testCubeName);
		$this->assertTrue($cube->id > 0);

		// Check to see if tables exist
		$this->assertTrue($this->dbh->TableExists("facts_".$cube->id));
		$this->assertTrue($this->dbh->TableExists("dimdat_".$cube->id));

		// Cleanup
		$olap->deleteCube($this->testCubeName);
	}

	/**
	 * Test delting a cube from the datawarehouse
	 */
	function testDelete()
	{
		$olap = new Olap($this->dbh);
		$cube = $olap->getCube($this->testCubeName);
		$cid = $cube->id;
		$olap->deleteCube($this->testCubeName);
		$this->assertFalse($this->dbh->TableExists("facts_".$cid));
		$this->assertFalse($this->dbh->TableExists("dimdat_".$cid));
		$this->assertEquals($this->dbh->GetNumberRows($this->dbh->Query("select id from dataware_olap_cubes where name='".$this->testCubeName."'")), 0);
	}

	/**
	 * Test dynamically adding dimensions and measures
	 *
	 * This process is desinged to be very dynamic so that new cubes
	 * can be created easily simply by inserting data for the first time
	 */
	function testCreateCubeDimensionMeasures()
	{
		$olap = new Olap($this->dbh);
		$olap->deleteCube($this->testCubeName); // Purge cube if already exists

		// Get new cube
		$cube = $olap->getCube($this->testCubeName);

		// Now dynamically create dimension
		$dim = $cube->getDimension("country");
		$this->assertTrue($dim->id > 0);
		$this->assertTrue($this->dbh->ColumnExists("facts_".$cube->id, "dim_".$dim->id));

		// Now dynamically create measure
		$meas = $cube->getMeasure("count");
		$this->assertTrue($meas->id > 0);
		$this->assertTrue($this->dbh->ColumnExists("facts_".$cube->id, "m_".$meas->id));

		// Unload data just to make sure nothing is cached
		unset($cube);
		$cube = $olap->getCube($this->testCubeName);
        $dim = $cube->getDimension("country", null, false);
		$this->assertTrue($dim->id > 0); // do not create it if it does not exist (second param)
        
        $meas = $cube->getMeasure("count", false);
		$this->assertTrue($meas->id > 0); // do not create it if it does not exist (second param)

		$cube->remove();
	}

	/**
	 * Test dynamically adding dimensions based on suffix
	 */
	function testCreateCubeDimensionDynTypes()
	{
		$olap = new Olap($this->dbh);
		$olap->deleteCube($this->testCubeName); // Purge cube if already exists

		// Get new cube
		$cube = $olap->getCube($this->testCubeName);

		// Now dynamically create dimension called time which should default to type=time
		$dim = $cube->getDimension("time");
		$this->assertTrue($dim->id > 0);
		$this->assertEquals($dim->type, "time");
		//$this->assertTrue($this->dbh->ColumnExists("dataware.facts_".$cube->id, "dim_".$dim->id));

		// Now dynamically create measure with a *_ts suffix which should default to type=time
		$dim = $cube->getDimension("entered_ts");
		$this->assertTrue($dim->id > 0);
		$this->assertEquals($dim->type, "time");
		//$this->assertTrue($this->dbh->ColumnExists("dataware.facts_".$cube->id, "m_".$meas->id));

		// Unload data just to make sure nothing is cached
		unset($cube);
		$cube = $olap->getCube($this->testCubeName);
		$dim = $cube->getDimension("time", false); // do not automatically create
		$this->assertEquals($dim->type, "time");
		$dim = $cube->getDimension("entered_ts", false); // do not automatically create
		$this->assertEquals($dim->type, "time");

		$cube->remove();
	}

	/**
	 * Test pulling data with time-series data
	 */
	function testCreateCubeDimensionTimeSeries()
	{
		$olap = new Olap($this->dbh);
		$olap->deleteCube($this->testCubeName); // Purge cube if already exists

		// Get new cube
		$cube = $olap->getCube($this->testCubeName);

		// Record an entry for each quarter
		$data = array(
			'page' => "/index.php",
			'country' => "us",
			'time' => "1/1/2012",
		);
		$measures = array("hits" => 100);
		$cube->writeData($measures, $data);
		$data = array(
			'page' => "/about.php",
			'country' => "us",
			'time' => "4/1/2012",
		);
		$measures = array("hits" => 75);
		$cube->writeData($measures, $data);
		$data = array(
			'page' => "/about.php",
			'country' => "us",
			'time' => "7/1/2012",
		);
		$measures = array("hits" => 50);
		$cube->writeData($measures, $data);
		$data = array(
			'page' => "/about.php",
			'country' => "us",
			'time' => "10/1/2012",
		);
		$measures = array("hits" => 25);
		$cube->writeData($measures, $data);

		// Pull data for each quarter
		$query = new Olap_Cube_Query();
		$query->addMeasure("hits", "sum");
		$query->addDimension("time", "asc", "Q Y");
		$data = $cube->getData($query);
		$this->assertEquals($data['Q1 2012']['hits'], 100);
		$this->assertEquals($data['Q2 2012']['hits'], 75);
		$this->assertEquals($data['Q3 2012']['hits'], 50);
		$this->assertEquals($data['Q4 2012']['hits'], 25);

		// Try with second dimension
		$query = new Olap_Cube_Query();
		$query->addMeasure("hits", "sum");
		$query->addDimension("country");
		$query->addDimension("time", "asc", "Q Y");
		$data = $cube->getData($query);
		$this->assertEquals($data['us']['Q1 2012']['hits'], 100);
		$this->assertEquals($data['us']['Q2 2012']['hits'], 75);
		$this->assertEquals($data['us']['Q3 2012']['hits'], 50);
		$this->assertEquals($data['us']['Q4 2012']['hits'], 25);

		$cube->remove();
	}

	/**
	 * Test pulling data with time-series data
	 */
	function testCreateCubeDimensionTimeSeriesFilter()
	{
		$olap = new Olap($this->dbh);
		$olap->deleteCube($this->testCubeName); // Purge cube if already exists

		// Get new cube
		$cube = $olap->getCube($this->testCubeName);

		// Record an entry for this month, last month, and last year
		$data = array(
			'page' => "/index.php",
			'country' => "us",
			'time' => date("m/d/Y"),
		);
		$measures = array("hits" => 100);

		$cube->writeData($measures, $data);
		$data = array(
			'page' => "/about.php",
			'country' => "us",
			'time' => date("m/d/Y", strtotime("-32 days")),
		);
		$measures = array("hits" => 75);
		$cube->writeData($measures, $data);

		$data = array(
			'page' => "/about.php",
			'country' => "us",
			'time' => date("m/d/Y", strtotime("-1 year")),
		);
		$measures = array("hits" => 50);
		$cube->writeData($measures, $data);

		// This vs last month
		$query = new Olap_Cube_Query();
		$query->addMeasure("hits", "sum");
		$query->addDimension("time", "asc", "n, Y");
		$query->addFilter("and", "time", "last_x_months", 2);
		$data = $cube->getData($query);
		$this->assertEquals($data[date("n").", ".date("Y")]['hits'], 100);
		$this->assertEquals($data[date("n", strtotime("-32 days")).", ".date("Y", strtotime("-1 month"))]['hits'], 75);

		// This vs last year
		$query = new Olap_Cube_Query();
		$query->addMeasure("hits", "sum");
		$query->addDimension("time", "asc", "n, Y");
		$query->addFilter("and", "time", "month_is_equal", "<%current_month%>");
		$query->addFilter("and", "time", "last_x_years", 2);
		$data = $cube->getData($query);
		$this->assertEquals($data[date("n").", ".date("Y")]['hits'], 100);
		$this->assertEquals($data[date("n", strtotime("-1 year")).", ".date("Y", strtotime("-1 year"))]['hits'], 50);

		$cube->remove();
	}
	
	/**
	 * Test updating (resetting) a measure in the datawarehouse
	 *
	 * Updating a measure given the data will reset the measures rather than incrementing them.
	 * This comes in handy for batch runs where data is aggregated on the client side and
	 * then stored in the cube for analysis.
	 */
	function testUpdate()
	{
		$olap = new Olap($this->dbh);
		$cube = $olap->getCube("tests/testcube");

		// Now record that '/index.php' has received 100 hits in the us
		$data = array(
			'page' => "/index.php",
			'country' => "us",
		);
		$measures = array("hits" => 100);
		$cube->writeData($measures, $data);
		$data = array(
			'page' => "/about.php",
			'country' => "us",
		);
		$measures = array("hits" => 50);
		$cube->writeData($measures, $data);

		// Extract the data in a single dimension
		$query = new Olap_Cube_Query();
		$query->addMeasure("hits", "sum");
		$query->addDimension("page");
		$query->addFilter("and", "country", "is_equal", "us");
		$data = $cube->getData($query);
		$this->assertEquals($data['/index.php']['hits'], 100);
		$this->assertEquals($data['/about.php']['hits'], 50);

		// Extract the data in two dimensions
		$query = new Olap_Cube_Query();
		$query->addMeasure("hits", "sum");
		$query->addDimension("page");
		$query->addDimension("country");
		$query->addFilter("and", "country", "is_equal", "us");
		$data = $cube->getData($query);
		$this->assertEquals($data['/index.php']['us']['hits'], 100);
		$this->assertEquals($data['/about.php']['us']['hits'], 50);

		// Cleanup
		$cube->remove();
	}

	/**
	 * Test incrementing measure
	 */
	function testIncrement()
	{
		$olap = new Olap($this->dbh);
		$cube = $olap->getCube("tests/testcube");

		// Now record that '/index.php' has received 100 hits in the us
		$data = array(
			'page' => "/index.php",
			'country' => "us",
		);
		$measures = array("hits" => 100);
		$cube->writeData($measures, $data);
		$data = array(
			'page' => "/about.php",
			'country' => "us",
		);
		$measures = array("hits" => 50);
		$cube->writeData($measures, $data);

		// Now let's increment the data
		$data = array(
			'page' => "/index.php",
			'country' => "us",
		);
		$measures = array("hits" => 100);
		$cube->incrementData($measures, $data);
		$data = array(
			'page' => "/about.php",
			'country' => "us",
		);
		$measures = array("hits" => 50);
		$cube->incrementData($measures, $data);

		// Extract the data in a single dimension
		$query = new Olap_Cube_Query();
		$query->addMeasure("hits", "sum");
		$query->addDimension("page");
		$query->addFilter("and", "country", "is_equal", "us");
		$data = $cube->getData($query);
		$this->assertEquals($data['/index.php']['hits'], 200);
		$this->assertEquals($data['/about.php']['hits'], 100);

		// Cleanup
		$cube->remove();
	}

	/**
	 * Test summing data
	 */
	function testSum()
	{
	}

	/**
	 * Test max aggregate
	 */
	function testMax()
	{
	}

	/**
	 * Test min aggregate
	 */
	function testMin()
	{
	}

	/**
	 * Test averaging data
	 */
	function testAvg()
	{
		$olap = new Olap($this->dbh);
		$cube = $olap->getCube("tests/testcube");

		// Now record that '/index.php' has received 100 hits in the us
		$data = array(
			'page' => "/index.php",
			'country' => "us",
		);
		$measures = array("hits" => 100);
		$cube->writeData($measures, $data);

		// Now let's increment the data
		$data = array(
			'page' => "/index.php",
			'country' => "us",
		);
		$measures = array("hits" => 100);
		$cube->incrementData($measures, $data);

		// Test no dimension

		$query = new Olap_Cube_Query();
		$query->addMeasure("hits", "avg");
		$query->addFilter("and", "country", "is_equal", "us");
		$data = $cube->getData($query);
		$this->assertEquals($data['hits'], 200);

		// Test one dimension
		$query = new Olap_Cube_Query();
		$query->addMeasure("hits", "avg");
		$query->addDimension("page");
		$query->addFilter("and", "country", "is_equal", "us");
		$data = $cube->getData($query);
		$this->assertTrue($data['/index.php']['hits'] > 50);

		// Test two dimensions
		$query = new Olap_Cube_Query();
		$query->addMeasure("hits", "avg");
		$query->addDimension("page");
		$query->addDimension("country");
		$query->addFilter("and", "country", "is_equal", "us");
		$data = $cube->getData($query);
		$this->assertEquals($data['/index.php']['us']['hits'], 50);

		// Cleanup
		$cube->remove();
	}

	/**
	 * Test getting data in a tabular format (regular associative array)
	 */
	function testTabular()
	{
		$olap = new Olap($this->dbh);
		$cube = $olap->getCube("tests/testcube");

		// Now record that '/index.php' has received 100 hits in the us
		$data = array(
			'page' => "/index.php",
			'country' => "us",
		);
		$measures = array("hits" => 100);
		$cube->writeData($measures, $data);
		$data = array(
			'page' => "/about.php",
			'country' => "us",
		);
		$measures = array("hits" => 50);
		$cube->writeData($measures, $data);

		// Extract the data in a single dimension
		$query = new Olap_Cube_Query();
		$query->addDimension("page");
		$query->addFilter("and", "country", "is_equal", "us");
		$data = $cube->getTabularData($query);
		$this->assertEquals($data[0]['page'], "/index.php");
		$this->assertEquals($data[1]['page'], "/about.php");

		// Cleanup
		$cube->remove();
	}

	/**
	 * Populate data for UI tests/report tests
	 */
	public function testCreateCubeForUiTest()
	{
		$olap = new Olap($this->dbh);

		// Get new cube
		$cube = $olap->getCube("tests/report");

		// Record an entry for each quarter
		$data = array(
			'page' => "/index.php",
			'country' => "us",
			'time' => date("m/d/Y", strtotime("-4 months")),
		);
		$measures = array("hits" => 100);
		$cube->writeData($measures, $data);
		$data = array(
			'page' => "/about.php",
			'country' => "us",
			'time' => date("m/d/Y", strtotime("-3 months")),
		);
		$measures = array("hits" => 75);
		$cube->writeData($measures, $data);
		$data = array(
			'page' => "/about.php",
			'country' => "us",
			'time' => date("m/d/Y", strtotime("-2 months")),
		);
		$measures = array("hits" => 50);
		$cube->writeData($measures, $data);
		$data = array(
			'page' => "/about.php",
			'country' => "us",
			'time' => date("m/d/Y", strtotime("-1 months")),
		);
		$measures = array("hits" => 25);
		$cube->writeData($measures, $data);
	}
}
