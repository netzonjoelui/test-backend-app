<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/Controller.php');
require_once(dirname(__FILE__).'/../../controllers/ApplicationController.php');

class ApplicationControllerTest extends PHPUnit_Framework_TestCase
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

	/**
	 * Limit the number of tests run
     */
    /*function getTests()
    {        
        return array("testGetCalTimespan");
    }*/

    /**
    * Test ANT Application - getAppId($params)
    */
    function testGetAppId()
    {
        $params['app'] = "crm";
        
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $aid = $appController->getAppId($params);
        $this->assertTrue($aid > 0);
    }
    
    /**
    * Test ANT Application - saveLayout($params)
    */
    function testSaveLayout()
    {           
        $params['name'] = "crm";
        $params['layout_xml'] = "UnitTest LayoutXml";
        
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $ret = $appController->saveLayout($params);
        $this->assertTrue($ret > 0);
        $this->assertNull($ret['error']);
        
        // Clear Data        
        $this->ant->dbh->Query("update applications set xml_navigation=NULL where name='". $params['name'] ."'");
    }
    
    /**
    * Test ANT Application - saveGeneral($params)
    */
    function testSaveGeneral()
    {   
        $params['app'] = "crm";
        $params['title'] = "Customer Relationship Management";
        $params['short_title'] = "CRM";
        $params['scope'] = "system";
        
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $ret = $appController->saveGeneral($params);
        $this->assertTrue($ret > 0);        
    }
    
    /**
    * Test ANT Application - createCalendar($params)
    */
    function testCreateCalendar()
    {   
        $params['app'] = "crm";
        $params['cal_name'] = "UnitTest Calendar";        
        
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $calid = $appController->createCalendar($params);
        $this->assertTrue($calid > 0);
        
        // clean data
        $params['cal_id'] = $calid;
        $appController->deleteCalendar($params);
    }
    
    /**
    * Test ANT Application - deleteCalendar($params)
    */
    function testDeleteCalendar()
    {   
        $params['app'] = "crm";
        $params['cal_name'] = "UnitTest Calendar";        
        
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $calid = $appController->createCalendar($params);
        $this->assertTrue($calid > 0);
        
        // test delete calendar
        $params['cal_id'] = $calid;
        $ret = $appController->deleteCalendar($params);
        $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Application - addObjectReference($params)
    */
    function testAddObjectReference()
    {
        $params['app'] = "crm";
        $params['obj_type'] = "calendar_event_proposal";        
        
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $ret = $appController->addObjectReference($params);
        $this->assertTrue($ret > 0);
        
        // clean data
        $ret = $appController->deleteObjectReference($params);
    }
    
    /**
    * Test ANT Application - deleteObjectReference($params)
    */
    function testDeleteObjectReference()
    {
        $params['app'] = "crm";
        $params['obj_type'] = "calendar_event_proposal";        
        
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $ret = $appController->deleteObjectReference($params);
        $this->assertTrue($ret > 0);
        
        // test delete object reference
        $ret = $appController->deleteObjectReference($params);
        $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Application - createObject($params)
    */
    function testCreateObject()
    {
        $params['app'] = "crm";
        $params['obj_name'] = "calendar_event_proposal";        
        
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $ret = $appController->createObject($params);        
        $this->assertTrue(sizeof($ret) > 1);
        $this->assertTrue($ret["id"] >= 0);
        $this->assertEquals($ret["name"], $ret["name"]);
    }
    
    /**
    * Test ANT Application - dashboardSaveLayoutResize($params)
    */
    function testDashboardSaveLayoutResize()
    {
        $params['appNavName'] = "crm.cust_dash";
        $params['num_cols'] = 1;
        
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $ret = $appController->dashboardSaveLayoutResize($params);        
        $this->assertEquals($ret, "done");
    }
    
    /**
    * Test ANT Application - addWidget($params)
    */
    function testAddWidget()
    {
        $params['appNavname'] = "crm.cust_dash";
        $params['wid'] = 13; // Rss Widget
        
        // add user dashboard layout first
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $did = $appController->addWidget($params);        
        $this->assertTrue($did > 0);
        
        // clean up data
        $params['eid'] = $did;
        $ret = $appController->dashboardDelWidget($params);        
        $this->assertTrue($ret > 0);
        $this->assertEquals($ret, $did);
    }
    
    /**
    * Test ANT Application - dashboardDelWidget($params)
    */
    function testDashboardDelWidget()
    {
        $params['appNavname'] = "crm.cust_dash";
        $params['wid'] = 13; // Rss Widget
        
        // add user dashboard layout first        
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $did = $appController->addWidget($params);        
        $this->assertTrue($did > 0);
        
        // test the delete widget
        $params['eid'] = $did;        
        $ret = $appController->dashboardDelWidget($params);        
        $this->assertTrue($ret > 0);
        $this->assertEquals($ret, $did);
    }
    
    /**
    * Test ANT Application - dashboardSetTotalWidth($params)
    */
    function testDashboardSetTotalWidth()
    {
        $params['appNavname'] = "crm.cust_dash";
        $params['width'] = 1024; 
                
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $width = $appController->dashboardSetTotalWidth($params);        
        $this->assertTrue($width > 0);
        $this->assertEquals($width, $params['width']);
    }
    
    /**
    * Test ANT Application - setZipcode($params)
    */
    function testSetZipcode()
    {
        $params['zipcode'] = "8000";
                
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $zipcode = $appController->setZipcode($params);        
        $this->assertTrue($zipcode > 0);
        $this->assertEquals($zipcode, $params['zipcode']);
    }
    
    /**
    * Test ANT Application - setWelColor($params)
    */
    function testSetWelColor()
    {
        $params['appNavname'] = "crm.cust_dash";
        $params['val'] = "#001FB2"; // Rss Widget
        
        // add user dashboard layout first
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $color = $appController->setWelColor($params);        
        $this->assertTrue(count($color) > 0);
        $this->assertEquals($color, $params['val']);
    }
    
    /**
    * Test ANT Application - getWelColor($params)
    */
    function testGetWelColor()
    {
        $this->testSetWelColor();
        
        $params['appNavname'] = "crm.cust_dash";        
        
        // add user dashboard layout first
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $color = $appController->getWelColor($params);        
        $this->assertTrue(count($color) > 0);        
        $this->assertEquals($color, "#001FB2");        
    }
    
    /**
    * Test ANT Application - setWelImg($params)
    */
    function testSetWelImg()
    {
        $params['appNavname'] = "crm.cust_dash";
        $params['val'] = "1";
        
        // add user dashboard layout first
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $ret = $appController->setWelImg($params);        
        $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Application - setWelImgDef($params)
    */
    function testSetWelImgDef()
    {
        $params['appNavname'] = "crm.cust_dash";        
        
        // add user dashboard layout first
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $ret = $appController->setWelImgDef($params);        
        $this->assertEquals($ret, true);
    }
    
    /**
    * Test ANT Application - getWelImage($params)
    */
    function testGetWelImage()
    {
        $params['appNavname'] = "crm.cust_dash";        
        $params['width'] = "1024";        
        
        // add user dashboard layout first
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $ret = $appController->getWelImage($params);        
        $this->assertTrue(count($ret) > 0);
    }
    
    /**
    * Test ANT Application - setCalTimespan($params)
    */
    function testSetCalTimespan()
    {
        $params['appNavname'] = "crm.cust_dash";        
        $params['val'] = "3";
        
        // add user dashboard layout first
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $ret = $appController->setCalTimespan($params);
        $this->assertTrue(strtotime($ret) >= strtotime("+3 days"));
    }
    
    /**
    * Test ANT Application - getCalTimespan($params)
    */
    function testGetCalTimespan()
    {        
        $this->testSetCalTimespan();
        $params['appNavname'] = "crm.cust_dash";        
        
        // add user dashboard layout first
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $ret = $appController->getCalTimespan($params);        
        $this->assertTrue(count($ret) > 0);
        $this->assertTrue(strtotime($ret) >= strtotime("+3 days"));
    }
    
    /**
    * Test ANT Application - setRssData($params)
    */
    function testSetRssData()
    {
        $params['appNavname'] = "crm.cust_dash";
        $params['data'] = "TestUnit RssData";
        $params['wid'] = 13; // Rss Widget
        
        // add user dashboard layout first
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $did = $appController->addWidget($params);        
        $this->assertTrue($did > 0);
        
        // test the set Rss Data
        $params['id'] = $did;        
        $ret = $appController->setRssData($params);        
        $this->assertTrue(count($ret) > 0);
        $this->assertEquals($ret, $params['data']);
        
        // clean up data
        $params['eid'] = $did;
        $ret = $appController->dashboardDelWidget($params);        
        $this->assertTrue($ret > 0);
        $this->assertEquals($ret, $did);
    }
    
    /**
    * Test ANT Application - widgetSetData($params)
    */
    function testWidgetSetData()
    {
        $params['appNavname'] = "crm.cust_dash";
        $params['data'] = "TestUnit WidgetData";
        $params['wid'] = 3; // Web Search Widget
        
        // add user dashboard layout first
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;
        
        $did = $appController->addWidget($params);        
        $this->assertTrue($did > 0);
        
        // test the set Rss Data
        $params['id'] = $did;        
        $ret = $appController->setRssData($params);        
        $this->assertTrue(count($ret) > 0);
        $this->assertEquals($ret, $params['data']);
        
        // clean up data
        $params['eid'] = $did;
        $ret = $appController->dashboardDelWidget($params);        
        $this->assertTrue($ret > 0);
        $this->assertEquals($ret, $did);
    }

	/**
	 * Test getWeather
	 *
	 * @group getWeather
	 */
	public function testGetWeather()
	{
        // add user dashboard layout first
        $appController = new ApplicationController($this->ant, $this->user);
        $appController->debug = true;

        $data = $appController->getWeather(array("zip"=>97477));

		$this->assertTrue(is_array($data));
		$this->assertTrue(count($data) >= 5);
	}
}
