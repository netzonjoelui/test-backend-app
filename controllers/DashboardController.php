<?php
require_once(dirname(__FILE__).'/../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../lib/AntUser.php');
require_once(dirname(__FILE__).'/../lib/Ant.php');
require_once(dirname(__FILE__).'/../lib/Controller.php');

class DashboardController extends Controller
{
    
    var $dashboardCols;
    var $globalWidth;
    var $userDashboard;
    
    public function __construct($ant, $user)
    {
        $this->ant = $ant;
        $this->user = $user;
        $this->dashboardCols = 2; // TODO needs to be dynamic
        $this->globalWidth = "300px";
        $this->userDashboard = "dashboard" . $this->user->id . "/dashboard_width";
    }
    
    /**
    * Loads the saved dashboards
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function loadDashboards($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        $numRecords = 500;
        $offset = 0;
                
        // Instantiate Dashboard Object
        $dashboardList = new CAntObjectList($dbh, "dashboard", $this->user);
        
        // Additional fields to query
        $dashboardList->addMinField("name"); 
        $dashboardList->addMinField("scope"); 
        
        // Add to get either system or user
        $dashboardList->addCondition("and", "app_dash", "is_equal", "");
        $dashboardList->addCondition("and", "scope", "is_equal", "system");
        $dashboardList->addCondition("or", "owner_id", "is_equal", $this->user->id);
        
        $dashboardList->getObjects($offset, $numRecords);
        $num = $dashboardList->getNumObjects();
        $total = $dashboardList->getTotalNumObjects();
        
        for ($i = 0; $i < $num; $i++)
        {            
            $obj = $dashboardList->getObject($i);
            
            // Gather Data
			if ($obj->dacl->checkAccess($this->user, "View", ($this->user->id==$obj->owner_id)?true:false))
			{
				$ret[] = array(
					"id" => $obj->id, 
					"name" => $obj->getValue("name"), 
					"scope" => $obj->getValue("scope"),
					"uname" => $obj->getValue("uname"),
				);
			}
            
            $offset++;            
            // If result set is larger than $numRecords
            if ($i == ($num-1) && $offset < $total)
            {                
                $dashboardList->getObjects($offset, $numRecords); // Get next batch
                $num = $dashboardList->getNumObjects();
                $i = -1;
            }
        }
        
        $this->sendOutput($ret);
        return $ret;
    }

    /**
     * Get a user-specific dashboard object id for a given application dashboard name
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function loadAppDashForUser($params)
    {
        $dbh = $this->ant->dbh;

		$dashName = $params["dashboard_name"];
		if (!$dashName)
			return $this->sendOutputJson(-1);

		$uname = $dashName . "-" . $this->user->id;

		// Try to open dashboard by uname with the user id appended {dashname-[userid]}
		$obj = CAntObject::factory($dbh, "dashboard", "uname:" . $uname, $this->user);
		if (!$obj->id)
		{
			// Create the dashboard using template found in /applications/dashboards if found
			$obj->setValue("name", ucwords(str_replace("-", " ", substr($dashName, strpos($dashName, '.')+1))));
			$obj->setValue("description", "User specific implementation of application dashboard - $dashName. Simply delete this dashboard to reset use to default application dashboard.");
			$obj->setValue("scope", "user");
			$obj->setValue("app_dash", $dashName);
			$obj->setValue("uname", $uname);

			// copy dashboard layout
			$obj->importAppDashLayout($dashName);
			
			$obj->save();
		}

		return $this->sendOutputJson($obj->id);
	}
    
    /**
    * Get the dashbaord widgets
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function loadWidgets($params)
    {
        $ret = array();
        
        $dashboardId = $params['dashboardId'];
        
        if($dashboardId)
        {
            $dashObj = CAntObject::factory($this->ant->dbh, "dashboard", $dashboardId, $this->user);
            $ret = $dashObj->getWidgets();
        }
        
        $this->sendOutput($ret);
        return $ret;
    }

	/**
	 * Get dashboard layout
     *
     * @param array $params An assocaitive array of parameters passed to this function.
	 */
	public function getLayout($param)
	{
		$dbid = $param['dbid'];

		$dash = CAntObject::factory($this->ant->dbh, "dashboard", $dbid, $this->user);
		return $this->sendOutputJson($dash->getLayout());
	}
    
    /**
    * Saves the layout of the dashboard
    *
    * @params array $params An assocaitive array of parameters passed to this function.
    */
    public function saveLayout($params)
    {
        $dashboardId = $params['dashboardId'];
        $columnCount = $params['columnCount'];
        
        if($dashboardId)
        {
            $dashObj = CAntObject::factory($this->ant->dbh, "dashboard", $dashboardId, $this->user);
            
            $newlayout = array();
            for ($i = 0; $i < $columnCount; $i++)
            {
                $newlayout[] = explode(":", $params["col_$i"]);
                
                // Set column width
                $dashObj->setColumnParam($i, "width", $params["columnWidth_$i"]);
            }
            
            $dashObj->updateLayout($newlayout);
            
            $ret = $dashObj->save();
        }
        else
            $ret = array("error" => "Dashboard Id is a required param.");
            
        $this->sendOutput($ret);
        return $ret;
    }
    
    /**
     * Append a widget to a column of this dashboard
     *
     * @params array $params An assocaitive array of parameters passed to this function.     
     */
    public function addWidget($params)
    {
        $dashboardId = $params['dashboardId'];
        $widgetId = $params['widgetId'];
        
        if($widgetId)
        {
            $dashObj = CAntObject::factory($this->ant->dbh, "dashboard", $dashboardId, $this->user);
            
            $widgetData = $dashObj->getWidgetInfo($widgetId);
            $ret = $dashObj->addWidget($widgetData['class_name'], 0);
            
            // Commit the changes
			if ($dashboardId)
            	$dashObj->save();
            
            // Get the id of the newly saved widget
            $ret["id"] = $dashObj->savedWidgetsId[0];
        }
        else
			$ret = array("error" => "widgetId is a required param");
            
        return $this->sendOutputJson($ret);
    }
    
    /**
    * Remove the dashboard widget
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function removeWidget($params)
    {
        $dashboardId = $params['dashboardId'];
        $dwid = $params['dwid'];
        
        if($dwid)
        {
            $dashObj = CAntObject::factory($this->ant->dbh, "dashboard", $dashboardId, $this->user);            
            $ret = $dashObj->removeWidget($dwid);
        }
        else
            $ret = array("error" => "Widget Id is a required params.");
            
        $this->sendOutput($ret);
        return $ret;
    }
    
    /**
    * Saves the widget data
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveData($params)
    {
        $dashboardId = $params['dashboardId'];
        $dwid = $params['dwid'];
        $data = $params['data'];
        
        if($dwid)
        {
            $dashObj = CAntObject::factory($this->ant->dbh, "dashboard", $dashboardId, $this->user);            
            $ret = $dashObj->saveData($dwid, $data);
        }
        else
            $ret = array("error" => "Widget Id is a required params.");
            
        $this->sendOutput($ret);
        return $ret;
    }
}
