<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');    
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');    
require_once(dirname(__FILE__).'/../../lib/Olap.php');    


class OlapTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;
	var $ant = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);
		
		$this->markTestSkipped('Depricated');
	}
	
	function tearDown() 
	{
		//@unlink('/temp/test.log');	
	}
	
	/**
	 * This function can be used to limit the unit test to a single function
	 */
	/*
	function getTests()
	{        
		return array("testLocalStoreListSearchElastic");        
	}    
	 */


	// TODO: test adhock cube
	// ===============================================================================


	// Test datawarehouse cube
	// ===============================================================================
	
	/**
	 * Test delting a cube from the datawarehouse
	 */
	function testDwDelete()
	{
	}

	/**
	 * Test inserting new cube into datawarehouse
	 *
	 * This process is desinged to be very dynamic so that new cubes
	 * can be created easily simply by inserting data for the first time
	 */
	function testDwCreateCube()
	{
		$olap = new Olap($this->dbh);
		$olap->deleteCube("tests/testCreateCube"); // Purge cube if already exists

		// Get new cube
		$cube = $olap->getCube("tests/testCreateCube");

		// Now record that '/index.php' has received 100 hits in the us
		$data = array(
			'page' => "/index.php",
			'country' => "us",
		);
		$measures = array("hits" => 100);
		$cube->writeData($measures, $data);

		// Unload data just to make sure nothing is cached
		unset($cube);
		$cube = $olap->getCube("tests/testCreateCube");

		// TODO: test to see if dimensions have been added to the cube dynamically
		// TODO: test to see if measures have been added to the cube and can be aggregated with sum, avg, max, min
	}
	
	/**
	 * Test updating (resetting) a measure in the datawarehouse
	 *
	 * Updating a measure given the data will reset the measures rather than incrementing them.
	 * This comes in handy for batch runs where data is aggregated on the client side and
	 * then stored in the cube for analysis.
	 */
	function testDwUpdate()
	{
        // TODO Dataware update function is missing
        
		/*$olap = new Olap($this->dbh);
		$cube = $olap->getCube("tests/testcube");

		// Now record that '/index.php' has received 100 hits in the us
		$data = array(
			'page' => "/index.php",
			'country' => "us",
		);
		$measures = array("hits" => 100);
		$cube->update($measures, $data);
        
		$data = array(
			'page' => "/about.php",
			'country' => "us",
		);
		$measures = array("hits" => 50);
		$cube->update($measures, $data);

		// Extract the data in a single dimension
		$byDim = array("page");
		$filter = array("country"=>"us");
		$data = $cube->getData($byDim, $filter);
        
		$this->assertEquals($data['/index.php']['hits'], 100);
		$this->assertEquals($data['/about.php']['hits'], 50);

		// Extract the data in two dimensions
		$byDim = array("page", "country");
		$filter = array("country"=>"us");
		$data = $cube->getData($byDim, $filter);
		$this->assertEquals($data['/index.php']['us']['hits'], 50);*/
	}

	/**
	 * Test incrementing measure
	 */
	function testDwIncrement()
	{
		$olap = new Olap($this->dbh);
		$cube = $olap->getCube("tests/testcube");
		//$cube->increment("tests/testcube", $measures, $dimensions);
	}
}
