<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/Email.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/RpcSvr.php');
require_once(dirname(__FILE__).'/../../lib/Controller.php');
require_once(dirname(__FILE__).'/../../controllers/DashboardController.php');


class DashboardControllerTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;
    var $backend = null;

    function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
    }

    function tearDown() 
    {
    }

    /*function getTests()
    {        
        return array("testApplicationDashboard");
    }*/
    
    /**
    * Test Ant Application Dashboard
    */    
    function testApplicationDashboard()
    {
        $dashboardController = new DashboardController($this->ant, $this->user);
        $dashboardController->debug = true;
        
        // Create dashboard first
        $antObject = new CAntObject($this->dbh, "dashboard", null, $this->user);
        $antObject->setValue("name", "testDashboardName");
        $dashboardId = $antObject->save(false);
        
        // Test Load Dashboard - ::loadDashboards()
        $params = array();
        $result = $dashboardController->loadDashboards($params);
        $this->assertTrue(is_array($result));
        $this->assertTrue($result[0]["id"] > 0);
        unset($result);
        
        // Test Add Widget - ::addWidget()
        $widgetId = 1; // Pre-defined Widget (Task Manager)
        $params["dashboardId"] = $dashboardId;
        $params["widgetId"] = $widgetId;
        $params["col"] = 0;
        $params["pos"] = 0;
        $result = $dashboardController->addWidget($params);
        $this->assertTrue(is_array($result));
        $this->assertEquals($result["widget"], "CWidTasks");
        unset($result);
        
        // Load Saved Widgets - ::loadWidgets()
        unset($params);
        $params["dashboardId"] = $dashboardId;
        $result = $dashboardController->loadWidgets($params);
        $dwid = $result[0][0]["id"];
        $this->assertTrue($result[0][0]["id"] > 0);
        $this->assertEquals($result[0][0]["widget"], "CWidTasks");
        unset($result);
        
        // Save the dashboard layout - ::saveLayout()
        $result = $dashboardController->saveLayout($params);
        $this->assertEquals($result, $dashboardId);
        unset($result);
        
        // Save the dashboard data - ::saveData()
        unset($params);
        $params['dwid'] = $dwid;
        $params['data'] = "unitTestData";
        $result = $dashboardController->saveData($params);
        $this->assertEquals($result, $dwid);
        
        // Remove Widget - ::removeWidget()
        unset($params);
        $params["dwid"] = $dwid;
        $result = $dashboardController->removeWidget($params);
        $this->assertEquals($result, $dwid);
        
        // Remove test data
        $antObject->removeHard();        
    }

	/**
	 * Test loadAppDashForUser
	 *
	 * @group loadAppDashForUser
	 */
	public function testLoadAppDashForUser()
	{
		$dbc = new DashboardController($this->ant, $this->user);
        $dbc->debug = true;
        
        // Delete test dashboard if it already exists
        $obj = new CAntObject($this->dbh, "dashboard", "uname:app.utest-" . $this->user->id, $this->user);
		if ($obj->id)
			$obj->removeHard();
        
		// Test controller action
        $params = array("dashboard_name"=>"app.utest");
        $result = $dbc->loadAppDashForUser($params);
        $this->assertTrue(json_decode($result) > 0);

		// Cleanup
        $obj = new CAntObject($this->dbh, "dashboard", "uname:app.utest-" . $this->user->id, $this->user);
		$obj->removeHard();
	}
}
