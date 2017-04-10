<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../controllers/OlapController.php');

class OlapControllerTest extends PHPUnit_Framework_TestCase
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
	}
	
	/**
	 * Cleanup objects
	 */
	function tearDown() 
	{
	}

	/*
    function getTests()
    {        
        return array("testMoveByGrouping");        
    }    
	 */
    
    /**
     * Test ANT Controller - testQueryCubeJson($params)
     */
    function testQueryCubeJsonCustom()
    {        
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

		// Test olap cube getting data
		$params = array();
        $params['obj_type'] = "task";
        $params['customReport'] = "Project_TaskTrack";
        $params['measures'] = array("0");
        $params['measure_name_0'] = "count";
        $params['measure_aggregate_0'] = "sum";
        $params['dimensions'] = array("0");
        $params['dimension_name_0'] = "track";
        $params['dimension_sort_0'] = "asc";
        $params['chart_type'] = "Column2D";
        $params['chart_width'] = "1056";
        $params['chart_height'] = "400";
        $params['display_graph'] = "true";
		/*
        $params['filters'] = array("0");
        $params['filter_blogic_0'] = "and";
        $params['filter_field_0'] = "project";
        $params['filter_operator_0'] = "is_equal";
        $params['filter_condition_0'] = "42";
		 */

        $objController = new OlapController($this->ant, $this->user);
        $data = $objController->queryCubeJson($params);

		$this->assertTrue($data['On Track']['count'] >= 1); // may be more than one
		$this->assertTrue($data['Off Track']['count'] >= 1); // may be more than one
        

		// Delete test data
		$this->objTaskOff->removeHard();
		$this->objTaskOn->removeHard();
    }
}
