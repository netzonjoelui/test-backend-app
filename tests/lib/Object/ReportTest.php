<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Report.php');
require_once(dirname(__FILE__).'/../../../lib/Controller.php');
require_once(dirname(__FILE__).'/../../../controllers/DatacenterController.php');
require_once(dirname(__FILE__).'/../../../lib/aereus.lib.php/antapi.php');

class CAntObject_ReportTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;

    function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
    }
    
    function tearDown() 
    {
    }
    
    
	/*
    function getTests()
    {        
        return array("testReportObject");
    }
	 */
    
    /**
    * Test Report Object - getDetails()
    */
    function testReportObject()
    {
        // Instantiate Datacenter Controller
        $dcController = new DatacenterController($this->ant, $this->user);
        $dcController->debug = true;
        
        // Create Report
        $params['reportType'] = "object";
        $params['objType'] = "customer";        
        $reportId = $dcController->saveReportData($params);
        $this->assertTrue($reportId > 0);
        
        // Update Report
        $params['id'] = $reportId;
        $params['name'] = "unit test report";
        $params['table_type'] = "summary";
        $result = $dcController->updateReportData($params);
        $this->assertEquals($result, $reportId);
        
        // Instantiate Report Object
        $reportObject = new CAntObject_Report($this->ant->dbh, $reportId, $this->user);        
        
        // Add Filter
        $fIdx = "f0";        
        $reportObject->addReportFilter("and", "owner_id", "is_equal", "-1");
        $reportObject->saveReportFilter();
        
        // Add Dimension
        $reportObject->addReportDim("ts_created", "asc", "Y Q", null, null, null);
        $reportObject->saveReportTableDims();
        
        // Add Measure
        $reportObject->addReportMeasure("avg");
        $reportObject->saveReportTableMeasures();
        
        // Test Report Details
        $reportDetails = $reportObject->getDetails();
        $this->assertEquals($reportDetails['id'], $reportId);
        $this->assertEquals($reportDetails['obj_type'], $params['objType']);
        $this->assertEquals($reportDetails['name'], $params['name']);
        $this->assertEquals($reportDetails['table_type'], $params['table_type']);
        
        // Test Report Filter
        $reportFilter = $reportObject->getFilters();
        $this->assertTrue($reportFilter[0]['id'] > 0);        
        $this->assertEquals($reportFilter[0]['blogic'], "and");
        $this->assertEquals($reportFilter[0]['fieldName'], "owner_id");
        $this->assertEquals($reportFilter[0]['operator'], "is_equal");
        $this->assertEquals($reportFilter[0]['condValue'], "-1");
        
        // Test Report Dimension
        $reportDim = $reportObject->getDimensions();
        $this->assertTrue($reportDim[0]['id'] > 0);
        $this->assertEquals($reportDim[0]['table_type'], $reportObject->tableType);
        $this->assertEquals($reportDim[0]['name'], "ts_created");
        $this->assertEquals($reportDim[0]['sort'], "asc");
        $this->assertEquals($reportDim[0]['format'], "Y Q");
        
        // Test Report Measure
        $reportMeas = $reportObject->getMeasures();        
        $this->assertTrue($reportMeas[0]['id'] > 0);
        $this->assertEquals($reportMeas[0]['table_type'], $reportObject->tableType);
        $this->assertEquals($reportMeas[0]['name'], "avg");        
        
        // Clean Data
        $params['id'] = $reportId;
        $result = $dcController->deleteReport($params);
        $this->assertEquals($result, 1);
    }
}
