<?php
/**
 * This unit test specifically focusses on the custom cube implementation.
 *
 * CustCubes populate cube data for working with OLAP but the data can be generated in any way
 * within the custom cube interface.
 */
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../../lib/Olap.php');

class Report_Cube_ProjectTest extends PHPUnit_Framework_TestCase
{
	var $dbh = null;
	var $user = null;
	var $ant = null;
	var $testCubeName = "";
	var $objTaskOn = null;
	var $objTaskOff = null;

	function setUp() 
	{
		$this->ant = new Ant();
		$this->dbh = $this->ant->dbh;
		$this->user = $this->ant->getUser(USER_SYSTEM);

		// Create some test data
		$this->objTaskOn = new CAntObject($this->dbh, "task");
		$this->objTaskOn->setValue("done", "t");
		$this->objTaskOn->setValue("deadline", date("m/d/Y", strtotime("+1 month")));
		$this->objTaskOn->setValue("user_id", $this->user->id);
		$this->objTaskOn->save();

		$this->objTaskOff = new CAntObject($this->dbh, "task");
		$this->objTaskOff->setValue("deadline", date("m/d/Y", strtotime("-1 month")));
		$this->objTaskOff->setValue("done", "f");
		$this->objTaskOff->setValue("user_id", $this->user->id);
		$this->objTaskOff->save();
	}
	
	/**
	 * Cleanup objects
	 */
	function tearDown() 
	{
		$this->objTaskOff->removeHard();
		$this->objTaskOn->removeHard();
	}
	
	/**
	 * This function can be used to limit the unit test to a single function
	function getTests()
	{        
		return array("testTabular");        
	}    
	 */

	/**
	 * Test getData with no dimensions
	 */
	public function testTrackDim()
	{
		$cube = new Olap_Cube_Custom($this->dbh, "Project_TaskTrack");
		
		// Pull data for each page
        $query = new Olap_Cube_Query();
        $query->addMeasure("count", "sum");
		$query->addDimension("track");
        $data = $cube->getData($query);

		$this->assertTrue($data['On Track']['count'] >= 1); // may be more than one
		$this->assertTrue($data['Off Track'] >= 1); // may be more than one
	}

	/**
	 * Test getData with one dimension
	 */
	public function testOneDim()
	{
		$cube = new Olap_Cube_Custom($this->dbh, "Project_TaskTrack");
		
		// Pull data for each page
        $query = new Olap_Cube_Query();
		$query->addMeasure("count", "sum");
		$query->addDimension("user_id");
		$query->addDimension("track");
        $data = $cube->getData($query);
        
        if(isset($data["Admin Account"]))
		    $this->assertTrue($data["Admin Account"]['On Track']['count'] >= 1); // may be more than one
		
        //$this->assertTrue($data["administrator"]['On Track'] >= 1); // may be more than one
	}
}
