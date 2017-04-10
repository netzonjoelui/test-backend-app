<?php
/**
 * This unit test specifically focusses on the custom cube implementation.
 *
 * CustCubes populate cube data for working with OLAP but the data can be generated in any way
 * within the custom cube interface.
 */
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../../lib/Olap.php');

class Olap_Cube_CustomTest extends PHPUnit_Framework_TestCase
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
		
		$this->markTestSkipped('Depricated.');
	}
	
	function tearDown() 
	{
		//@unlink('/temp/test.log');	
	}
	
	/**
	 * This function can be used to limit the unit test to a single function
	function getTests()
	{        
		return array("testTabular");        
	}    
	 */

	/**
	 * Test if the dynamic loading of the CustCube is working
	 */
	public function testLoadCube()
	{
		$cube = new Olap_Cube_Custom($this->dbh, "Template");
		$this->assertTrue($cube->interface != null);
		$this->assertTrue(count($cube->getDimensions())>0);
		$this->assertTrue(count($cube->getMeasures())>0);
	}

	/**
	 * Test getData
	 */
	public function testGetData()
	{
		$cube = new Olap_Cube_Custom($this->dbh, "Template");
		
		// Pull data for each page
        $query = new Olap_Cube_Query();
        $query->addMeasure("hits", "sum");
        $query->addDimension("page", "asc");
        $query->addDimension("country", "asc");
        $data = $cube->getData($query);

		$this->assertEquals($data['index.php']['USA']['hits'], 500);
		$this->assertEquals($data['index.php']['USA']['visits'], 100);
	}

	/**
	 * Test getTabularData
	 */
	public function testTabularData()
	{
		$cube = new Olap_Cube_Custom($this->dbh, "Template");
		
		// Pull data for each page
        $query = new Olap_Cube_Query();
        $query->addMeasure("hits", "sum");
        $query->addDimension("page", "asc");
        $query->addDimension("country", "asc");
        $data = $cube->getTabularData($query);

		$this->assertTrue(count($data)>0);
	}
}
