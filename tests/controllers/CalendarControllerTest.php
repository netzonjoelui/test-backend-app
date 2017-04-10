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
require_once(dirname(__FILE__).'/../../controllers/CalendarController.php');


class CalendarControllerTest extends PHPUnit_Framework_TestCase
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
        return array("testSaveEvent");
    }*/

    /**
    * Test ANT Calendar - getCalendarName($params)
    */
    function testGetCalendarName()
    {        
        $params['app'] = "crm";
        $params['cal_name'] = "UnitTest Calendar";        
        
        // instantiate controllers
        $appController = new ApplicationController($this->ant, $this->user);
        $appCalendar = new CalendarController($this->ant, $this->user);
        
        $appController->debug = true;
        $appCalendar->debug = true;
        
        // add calendar first        
        $calid = $appController->createCalendar($params);
        $this->assertTrue($calid > 0);
                
        // test get calendar name        
        $params['calid'] = $calid;        
        $calName = $appCalendar->getCalendarName($params);        
        $this->assertEquals($calName, $params['cal_name']);
        
         //clear data
         $params['cal_id'] = $calid;
         $ret = $appController->deleteCalendar($params);
         $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Calendar - getUserSettings($params)
    */
    function testGetUserSettings()
    {
        $params['app'] = "crm";
        $params['cal_name'] = "UnitTest Calendar";        
        
        // instantiate controllers
        $appController = new ApplicationController($this->ant, $this->user);
        $appCalendar = new CalendarController($this->ant, $this->user);
        
        $appController->debug = true;
        $appCalendar->debug = true;
        
        // add calendar first        
        $calid = $appController->createCalendar($params);
        $this->assertTrue($calid > 0);
                
        // test get calendar name        
        $params['calendar_id'] = $calid;        
        $ret = $appCalendar->getUserSettings($params);        
        $this->assertTrue(sizeof($ret)>0);
        $this->assertNotNull($ret->defaultView);
        
         //clear data
         $params['cal_id'] = $calid;
         $ret = $appController->deleteCalendar($params);
         $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Calendar - getCalendars($params)
    */
    function testGetCalendars()
    {
        $params['app'] = "crm";
        $params['cal_name'] = "UnitTest Calendar";        
        
        // instantiate controllers
        $appController = new ApplicationController($this->ant, $this->user);
        $appCalendar = new CalendarController($this->ant, $this->user);
        
        $appController->debug = true;
        $appCalendar->debug = true;
        
        // add calendar first        
        $calid = $appController->createCalendar($params);
        $this->assertTrue($calid > 0);
                
        // test get calendar name        
        $params['calendar_id'] = $calid;        
        $ret = $appCalendar->getCalendars($params);        
        $this->assertTrue(sizeof($ret)>0);
        $this->assertTrue(is_array($ret));        
        $this->assertTrue(is_array($ret["myCalendars"]));        
        
         //clear data
         $params['cal_id'] = $calid;
         $ret = $appController->deleteCalendar($params);
         $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Calendar - setFView($params)
    */
    function testSetFView()
    {
        $params['app'] = "crm";
        $params['cal_name'] = "UnitTest Calendar";        
        
        // instantiate controllers
        $appController = new ApplicationController($this->ant, $this->user);
        $appCalendar = new CalendarController($this->ant, $this->user);
        
        $appController->debug = true;
        $appCalendar->debug = true;
        
        // add calendar first        
        $calid = $appController->createCalendar($params);
        $this->assertTrue($calid > 0);
                
        // test set F View
        $params['calendar_id'] = $calid;        
        $params['f_view'] = "t";        
        $ret = $appCalendar->setFView($params);
        $this->assertTrue($ret > 0);        
        $this->assertEquals($ret, $calid);        
        
         //clear data
         $params['cal_id'] = $calid;
         $ret = $appController->deleteCalendar($params);
         $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Calendar - userSetSetting($params)
    */
    public function testUserSetSetting()
    {
        $params['setting_name'] = "UnitTest CalendarSettingKey";        
        $params['setting_value'] = "UnitTest CalendarSettingValue";        
        
        $appCalendar = new CalendarController($this->ant, $this->user);        
        $appCalendar->debug = true;
        
        $ret = $appCalendar->userSetSetting($params);
        $this->assertTrue($ret > 0);
        
        $this->assertEquals($this->user->getSetting($params['setting_name']), $params['setting_value']);
    }
    
    /**
    * Test ANT Calendar - calSetColor($params)
    */
    public function testCalSetColor()
    {
        $params['app'] = "crm";
        $params['cal_name'] = "UnitTest Calendar";        
        
        // instantiate controllers
        $appController = new ApplicationController($this->ant, $this->user);
        $appCalendar = new CalendarController($this->ant, $this->user);
        
        $appController->debug = true;
        $appCalendar->debug = true;
        
        // add calendar first        
        $calid = $appController->createCalendar($params);
        $this->assertTrue($calid > 0);
                
        // test set color
        $params['calendar_id'] = $calid;        
        $params['color'] = "000000";        
        $ret = $appCalendar->calSetColor($params);
        $this->assertNotNull($ret);        
        $this->assertEquals($ret, $params['color']);        
        
         //clear data
         $params['cal_id'] = $calid;
         $ret = $appController->deleteCalendar($params);
         $this->assertTrue($ret > 0);
    }
    
    
    /**
    * Test ANT Calendar - createCalendar($params)
    */
    function testCreateCalendar()
    {
        $params["name"] = "UnitTest Calendar";
        $appCalendar = new CalendarController($this->ant, $this->user);        
        $appCalendar->debug = true;
        
        $cid = $appCalendar->createCalendar($params);
        $this->assertTrue($cid > 0);
        
        // clear data
        $params["id"] = $cid;
        $ret = $appCalendar->deleteCalendar($params);
        $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Calendar - deleteCalendar($params)
    */    
    function testDeleteCalendar()
    {   
        // instantiate calendar controller
        $appCalendar = new CalendarController($this->ant, $this->user);        
        $appCalendar->debug = true;
        
        // create calendar first
        $params["name"] = "UnitTest Calendar";
        $cid = $appCalendar->createCalendar($params);
        $this->assertTrue($cid > 0);
        
        // test calendar delete
        $params["id"] = $cid;
        $ret = $appCalendar->deleteCalendar($params);
        $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Calendar - addSharedCalendar($params)
    */    
    function testAddSharedCalendar()
    {
        // instantiate calendar controller
        $appCalendar = new CalendarController($this->ant, $this->user);        
        $appCalendar->debug = true;
        
        // add calendar first
        $params["name"] = "UnitTest Calendar";        
        $cid = $appCalendar->createCalendar($params);
        $this->assertTrue($cid > 0);
        
        // test add sharing calendar
        $params["calendar_id"] = $cid;
        $params["user_id"] = $this->user->id;
        $sid = $appCalendar->addSharedCalendar($params);
        $this->assertTrue($sid > 0);
        
        // clear data
        $params["id"] = $cid;
        $ret = $appCalendar->deleteCalendar($params);
        $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Calendar - deleteShare($params)
    */    
    function testDeleteShare()
    {
        // instantiate calendar controller
        $appCalendar = new CalendarController($this->ant, $this->user);        
        $appCalendar->debug = true;
        
        // add calendar first
        $params["name"] = "UnitTest Calendar";        
        $cid = $appCalendar->createCalendar($params);
        $this->assertTrue($cid > 0);
        
        // add sharing calendar first
        $params["calendar_id"] = $cid;
        $sid = $appCalendar->addSharedCalendar($params);
        $this->assertTrue($sid > 0);        
        
        // test delete share
        $params["id"] = $sid;
        $ret = $appCalendar->addSharedCalendar($params);
        $this->assertTrue($ret > 0);
        
        // clear data
        unset($params);
        $params["id"] = $cid;
        $ret = $appCalendar->deleteCalendar($params);
        $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Calendar - saveEvent($params)
    */    
    function testSaveEvent()
    {
        // instantiate calendar controller
        $appCalendar = new CalendarController($this->ant, $this->user);        
        $appCalendar->debug = true;
        
        // add calendar first
        $params["name"] = "UnitTest Calendar";        
        $cid = $appCalendar->createCalendar($params);
        $this->assertTrue($cid > 0);
        
        // test save event
        $params["title"] = "UnitTest Calendar";        
        $params["calendar_id"] = $cid;
        $params["sharing"] = 1;
        $params["date_start"] = "11/25/2011";
        $params["date_end"] = "11/26/2011";
        $params["time_start"] = "09:00 AM";
        $params["time_end"] = "11:00 AM";
        $params["all_day"] = "f";
        $ret = $appCalendar->saveEvent($params);
        $this->assertTrue(is_array($ret));
        $this->assertTrue($ret['eid'] > 0);
        
        // test save event update mode
        $params["eid"] = $ret['eid'];
        $ret = $appCalendar->saveEvent($params);
        $this->assertTrue(is_array($ret));
        $this->assertTrue($ret['eid'] > 0);
        $this->assertEquals($ret['eid'], $params["eid"]);
        
        // clear data        
        unset($params);
        $params["id"] = $cid;
        $ret = $appCalendar->deleteCalendar($params);
        $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Calendar - deleteEvent($params)
    */
    public function testDeleteEvent()
    {
        // instantiate calendar controller
        $appCalendar = new CalendarController($this->ant, $this->user);        
        $appCalendar->debug = true;
        
        // add calendar first
        $params["name"] = "UnitTest Calendar";        
        $cid = $appCalendar->createCalendar($params);
        $this->assertTrue($cid > 0);
        
        // add event
        $params["title"] = "UnitTest Calendar";        
        $params["calendar_id"] = $cid;
        $params["sharing"] = 1;
        $params["date_start"] = "11/25/2011";
        $params["date_end"] = "11/26/2011";
        $params["time_start"] = "09:00 AM";
        $params["time_end"] = "11:00 AM";
        $params["all_day"] = "f";
        $ret = $appCalendar->saveEvent($params);
        $this->assertTrue(is_array($ret));
        $this->assertTrue($ret['eid'] > 0);
        
        // test delete event
        unset($params);
        $params["eid"] = $ret['eid'];
        $ret = $appCalendar->deleteEvent($params);
        $this->assertTrue($ret > 0);        
        
        // clear data        
        unset($params);
        $params["id"] = $cid;
        $ret = $appCalendar->deleteCalendar($params);
        $this->assertTrue($ret > 0);
    }
    
    /**
    * Test ANT Calendar - getReminderVariables($params)
    */
    function testGetReminderVariables()
    {
        // instantiate calendar controller
        $appCalendar = new CalendarController($this->ant, $this->user);        
        $appCalendar->debug = true;
        
        $ret = $appCalendar->getReminderVariables();
        $this->assertTrue(is_array($ret));
    }
}
