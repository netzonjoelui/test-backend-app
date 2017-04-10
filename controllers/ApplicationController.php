<?php
/**
* Application Actions
*/
require_once("lib/Controller.php");
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");
require_once("lib/WorkFlow.php");
require_once("email/email_functions.awp");

// Dashboard includes
require_once("settings/settings_functions.php");        
require_once("lib/CDatabase.awp");
require_once("lib/aereus.lib.php/CChart.php");
require_once("users/user_functions.php");
require_once("calendar/calendar_functions.awp");
require_once("contacts/contact_functions.awp");
require_once("customer/customer_functions.awp");

/**
* Actions for interacting with basic application functions
*/
class ApplicationController extends Controller
{
    public function __construct($ant, $user)
    {
        $this->ant = $ant;
        $this->user = $user;
        UserLogAction($ant->dbh, $user->id);
    }
    
    public function getAppId($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['app'])
        {
            $query = "select id from applications where name='".$params['app']."'";
            $result = $dbh->Query($query);
            if ($dbh->GetNumberRows($result))
                $appid = $dbh->GetValue($result, 0, "id");
                $ret = $appid;
        }
        
        else
            $ret = array("error"=>"app is a required param");
        
        return $ret;
    }
    
    /**
    * save_layout - save the navigation portion of the application
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveLayout($params)
    {
        $dbh = $this->ant->dbh;
        
        // Save xml to 'applications' table in the xml_navigation column
        $name = $params['name'];
        $xml = $params['layout_xml'];
        
        if($name)
        {                        
            $dbh->Query("update applications set xml_navigation='".$dbh->Escape($xml)."' where name='$name'");
            $ret = 1;
        }
        else
            $ret = array("error"=>"name are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * save_layout - save the general portion of the application
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveGeneral($params) 
    {
        $dbh = $this->ant->dbh;
        
        $app = "";
        $title = "";
        $short_title = "";
        $scope = "";
        $user_id = "";
        $team_id = "";
        
        if(isset($params['app']))
            $app = rawurldecode($params['app']);
            
        if(isset($params['title']))
            $title = rawurldecode($params['title']);
            
        if(isset($params['short_title']))
            $short_title = rawurldecode($params['short_title']);
            
        if(isset($params['scope']))
            $scope = rawurldecode($params['scope']);
            
        if(isset($params['userId']))
            $user_id = rawurldecode($params['userId']);
            
        if(isset($params['teamId']))
            $team_id = rawurldecode($params['teamId']);

        $f_system = "f";
        if($scope=="system")
            $f_system = "t";
        
        // get the app id
        $appid = $this->getAppId($params);
		if (isset($appid['error']))
			$appid = null;
        
        if($appid && $title && $short_title && $scope)
        {
            $dbh->Query("update applications set 
                        title='" . $dbh->escape($title) . "', 
                        short_title='" . $dbh->escape($short_title) . "', 
                        scope='" . $dbh->escape($scope) . "', 
                        f_system='$f_system', 
                        user_id=" . $dbh->escapeNumber($user_id) . ",
                        team_id=" . $dbh->escapeNumber($team_id) . "
                        where name='$app'");
            $ret = 1;    // return success
        }
        else
            $ret = array("error"=>"app, title, short_title, scope are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * create_calendar - create an application calendar
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function createCalendar($params)
    {        
        $dbh = $this->ant->dbh;
        $app = rawurldecode($params['app']);
        $name = rawurldecode($params['cal_name']);

        // get the app id
        $appid = $this->getAppId($params);
        
        if ($appid && $name)
        {
            $result = $dbh->Query("insert into calendars(name, def_cal, date_created) 
                                    values('".rawurldecode($name)."', 'f', 'now');
                                    select currval('calendars_id_seq') as id;");
            if ($dbh->GetNumberRows($result))
            {
                $calid = $dbh->GetValue($result, 0, "id");

                if ($calid)
                {
                    $dbh->Query("insert into application_calendars(application_id, calendar_id) values('$appid', '$calid');");
                    $ret = $calid;
                }
            }
        }
        else
            $ret = array("error"=>"app and cal_name are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * delete_calendar - delete an application calendar
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function deleteCalendar($params)
    {
        $dbh = $this->ant->dbh;
        
        $app = rawurldecode($params['app']);
        $cal_id = rawurldecode($params['cal_id']);
        
        // get the app id
        $appid = $this->getAppId($params);
        
        if ($appid && $cal_id)
        {
            // Delete application calendar from calendars
            $dbh->Query("delete from calendars where id='$cal_id'");

            // Delete application calendar from application_calendars
            $dbh->Query("delete from application_calendars where calendar_id='$cal_id' and application_id=$appid");

            $ret = 1;    // return success
        }
        else
            $ret = array("error"=>"app and cal_id are required params");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * add_object_reference - add a reference to the object
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function addObjectReference($params)
    {
        $dbh = $this->ant->dbh;
        
        $app = rawurldecode($params['app']);
        $obj_type = rawurldecode($params['obj_type']);

        // get the app id
        $appid = $this->getAppId($params);
        
        if ($appid && $obj_type)
        {
            $sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
            $dm = $sl->get("EntityDefinition_DataMapper");
			$def = $dm->get($obj_type);
			$res = $dm->associateWithApp($def, $appid);
           	$ret = ($res) ? 1 : 0; // return success if association was a success
			/*
            $otid = objGetAttribFromName($dbh, $obj_type, "id");
            if ($otid)
            {
                objAssocTypeWithApp($dbh, $otid, $appid);
                $ret = 1; // return success
            }
			 */
        }
        else
            $ret = array("error"=>"app and obj_type are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * create_object - create a new object and link to this application
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function createObject($params)
    {
        $dbh = $this->ant->dbh;
        
        $app = $params['app'];
        $title = $params['obj_name'];

        // Create a valid format for obj_name
        $name = strtolower(str_replace(" ", "_", $title));
        
        // get the app id
        $appid = $this->getAppId($params);
		if (isset($appid['error']))
			$appid = null;
        
        if (!empty($params['obj_name']))
        {        
            $otid = objCreateType($dbh, $name, $title, $appid);

            // Return object params - can be decoded with escape
            $ret = array("id"=>$otid, "name"=>$name);
        }
        else
            $ret = array("error"=>"Object Name is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * delete_object_reference - delete object reference
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function deleteObjectReference($params)
    {
        $dbh = $this->ant->dbh;
        
        $app = $params['app'];
        $obj_type = $params['obj_type'];
        
        // get the app id
        $appid = $this->getAppId($params);
        
        if ($obj_type)
        {
            $otid = objGetAttribFromName($dbh, $obj_type, "id");

            if ($otid)
            {
                $dbh->Query("delete from application_objects where object_type_id='$otid' and application_id=$appid");
                
                if((string) $params['f_obj_reference']=="false")                
                {
                    objDeleteType($dbh, $otid);
                    $dbh->Query("drop table objtbl_$obj_type;"); // Delete the actual table
                }
                    
                $ret = 1;
            }
        }
        else
            $ret = array("error"=>"Object Id are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Gets the object reference
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getObjectReference($params)
    {
        $dbh = $this->ant->dbh;
        
        $app = $params['app'];
        
        // get the app id
        $appid = $this->getAppId($params);
        
        if ($appid)
        {
            $query = "select id, object_type_id from application_objects where application_id = '".$appid."'";
            $result = $dbh->Query($query);
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $oname = objGetNameFromId($dbh, $dbh->GetValue($result, $i, "object_type_id"));
                if ($oname)
                {
                    $odef = new CAntObject($dbh, $oname);
                    $ret[] = array("title" => $odef->title, "name" => $oname, "system" => "f");
                }
            }
        }
        else
            $ret = array("error"=>"App Name is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Delete Dashboard Report Graph
    * dashboardDelRptGraph() dont have a caller.
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function dashboardDelRptGraph($params)
    {
        $dbh = $this->ant->dbh;
        $eid = $params['eid'];
        
        if ($eid)
        {
            $result = $dbh->Query("delete from dc_dashboard where id='$eid' and user_id='". $this->user->id ."'");
            $ret = $eid;
        }
        else
            $ret = array("error"=>"eid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Save the Dashboard Layout
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function dashboardSaveLayout($params)
    {
        $dbh = $this->ant->dbh;
        
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = rawurldecode($params['appNavname']);
        $num = rawurldecode($params['num_cols']);
        if ($num)
        {
            for ($i = 0; $i < $num; $i++)
            {
                if(isset($params['col_'.$i]))
                {
                    $items = rawurldecode($params['col_'.$i]);
                    if ($items)
                    {
                        $widgets = explode(":", $items);

                        if (is_array($widgets))
                        {
                            for ($j = 0; $j < count($widgets); $j++)
                            {
                                $dbh->Query("update user_dashboard_layout set position='$j', col='$i' where user_id='". $this->user->id ."' 
                                and id='".$widgets[$j]."' and dashboard='$appNavname';");
                            }
                        }
                    }
                }
            }
        }
        
        $ret = "done";
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Save the Dashboard Layout Resize
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function dashboardSaveLayoutResize($params)
    {
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = "";
        if(isset($params['appNavname']))
            $appNavname = rawurldecode($params['appNavname']);
        $num = rawurldecode($params['num_cols']);
        if ($num)
        {
            for ($i = 0; $i < $num; $i++)
            {
                if(isset($params["col_".$i]))
                    $this->user->setSetting("$appNavname/col".$i."_width", rawurldecode($params["col_".$i]));
            }
        }
        
        $ret = "done";
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Delete the Dashboard Widget
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function dashboardDelWidget($params)
    {
        $dbh = $this->ant->dbh;
        
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = rawurldecode($params['appNavname']);
        
        $eid = $params['eid'];
        if ($eid)
        {
            $result = $dbh->Query("delete from user_dashboard_layout where id='$eid' and user_id='". $this->user->id ."' and dashboard='$appNavname'");
            $ret = $eid;
        }
        else
            $ret = array("error"=>"eid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Set the Dashboard Total Width
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function dashboardSetTotalWidth($params)
    {
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = rawurldecode($params['appNavname']);
        $width = $params['width'];            
        if (is_numeric($width))
        {
            $this->user->setSetting("$appNavname/dashboard_width", $width);

            for ($i = 0; $i < 3; $i++)
                $this->user->setSetting("$appNavname/col".$i."_width", (($width/3) - 5)."px");

            $ret = $width;
        }
        else
        {
            $this->user->setSetting("$appNavname/dashboard_width", "100%");
            $ret = "100%";
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Set the zipcode
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function setZipcode($params)
    {
        // Set new zip
        $this->user->setSetting("zipcode", $params['zipcode']);
        $ret = $params['zipcode'];
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Add a Widget
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function addWidget($params)
    {
        $dbh = $this->ant->dbh;
        
        if (is_numeric($params['wid']))
        {
            // AppNavname is the unique identifier to know which dashboard is to be updated
            $appNavname = rawurldecode($params['appNavname']);

            // Get next position id
            $result = $dbh->Query("select position from user_dashboard_layout where user_id='" . $this->user->id . "' 
                                                and dashboard='$appNavname' order by position DESC limit 1");
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $dbh->FreeResults($result);
                $use = $row['position'] + 1;
            }
            else
                $use = 1;


            $result = $dbh->Query("insert into user_dashboard_layout (user_id, col, position, widget_id, dashboard) 
                                            values('" . $this->user->id . "', '0', '$use', '".$params['wid']."', '$appNavname');
                                            select currval('user_dashboard_layout_id_seq') as id;");
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $ret = $row['id'];
            }
            else
                $ret = -1;
                
            $dbh->FreeResults($result);
        }
        else
            $ret = array("error"=>"numeric wid is a required param");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Set the Welcome Dashboard Color
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function setWelColor($params)
    {
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = rawurldecode($params['appNavname']);
        
        $ret = $this->user->setSetting("{$appNavname}_messagecntr_txtclr", $params['val']);
        if(empty($ret))
            $ret = $params['val'];
            
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get the Welcome Dashboard Color
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getWelColor($params)
    {
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = rawurldecode($params['appNavname']);
        
        $ret = $this->user->getSetting("{$appNavname}_messagecntr_txtclr");
        
        if(empty($ret))
            $ret = "#000000";
            
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Set the Welcome Dashboard Image
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function setWelImg($params)
    {
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = rawurldecode($params['appNavname']);
        
        $this->user->setSetting("{$appNavname}_messagecntr_image", $params['val']);
        
        $ret = $params['val'];
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Set the Welcome Dashboard Image Default
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function setWelImgDef($params)
    {
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = rawurldecode($params['appNavname']);
        
        $ret = $this->user->deleteSetting("{$appNavname}_messagecntr_image");
        $ret = $this->user->deleteSetting("{$appNavname}_messagecntr_txtclr");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get the Welcome Dashboard Image
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getWelImage($params)
    {
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = rawurldecode($params['appNavname']);
        
        // Look for custom image
        $custimg = $this->user->getSetting("{$appNavname}_messagecntr_image");
        $width = $params['width'];
        if ($custimg)
        {
            $ret = ($custimg == "none") ? "none" : "/userfiles/getthumb_by_id.awp?fid=$custimg&stretch=1&iw=$width";
        }
        else
        {
            $custom_default = $this->user->getSetting("general/welcome_image");

            if (is_numeric($custom_default))
            {
                $ret = "/files/images/$custom_default/$width";
            }
            else
            {
                $ret = "/userfiles/getthumb.awp?path=".base64_encode("/images/themes/".UserGetTheme($dbh, $this->user->id, 'name')."/greeting.png");
                $ret .= "&iw=$width&stretch=1&type=PNG\";";
            }
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Set Calendar Timespan
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function setCalTimespan($params)
    {
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = rawurldecode($params['appNavname']);
        
        $this->user->setSetting("calendar/$appNavname/span", $params['val']);
        if ($params['val'] >= 1)
            $ret = date("m/d/Y", strtotime("+ " . ($params['val'] + 1) . " days"));
        else
            $ret = date("m/d/Y");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get Calendar Timespan
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getCalTimespan($params)
    {
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = rawurldecode($params['appNavname']);
        
        $val = $this->user->getSetting("calendar/$appNavname/span");
        if ($val >= 1)
            $ret = date("m/d/Y", strtotime("+ " . ($val + 1) . " days"));
        else
            $ret = date("m/d/Y");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Set Rss Data
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function setRssData($params)
    {
        $dbh = $this->ant->dbh;
        
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = rawurldecode($params['appNavname']);
                
        $result = $dbh->Query("update user_dashboard_layout set 
                                            data='". $dbh->Escape(rawurldecode($params['data']))."' where id='".$params['id']."' and dashboard='$appNavname'");
        $ret = $params['data'];
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Set Widget Data
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function widgetSetData($params)
    {
        $dbh = $this->ant->dbh;
        
        // AppNavname is the unique identifier to know which dashboard is to be updated
        $appNavname = rawurldecode($params['appNavname']);
        
        $result = $dbh->Query("update user_dashboard_layout set 
                                            data='".$dbh->Escape(rawurldecode($params['data']))."' where id='".$params['id']."' and dashboard='$appNavname'");
        $ret = $params['data'];
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Loads widget url
    * 
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function loadWidgetUrl($params)
    {
        $width = $params["width"];
        
        $search = array("(", ")", " ", "\"", "'");
        $replace = array("%28", "%29", "%20", "%22", "%27");
        
        $url = str_replace($search, $replace, $params["url"]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        $parts = explode("/", $contentType);        
        $ret = array("contentType" => $contentType, "type" => $parts[0], "data" => $data);
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Check nav if to display reset default
     * 
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function checkNav($params)
    {
        $dbh = $this->ant->dbh;
        $ret = false;
        
        $query = "select xml_navigation from applications where name = '{$params['app']}'";
        $result = $dbh->Query($query);
        $row = $dbh->GetRow($result);
        if(!empty($row['xml_navigation']))
            $ret = true;            
        
        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
     * Get weather from the US government API
     * 
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function getWeather($params)
    {
        $dbh = $this->ant->dbh;
		$zip = isset($params['zip']) ? $params['zip'] : null;
		
		if (!$zip)
			$zip = $this->user->getSetting("zipcode");

		if (!$zip)
			return $this->sendOutputJson(array("error"=>"No zipcode provided for the current user"));

		// Check cache by zipcode before querying again
		$cache = CCache::getInstance();
		$ret = $cache->get("weather/data/" . $zip);
		if ($ret)
			return $this->sendOutputJson($ret);

		$antsystem = new AntSystem();
		$zipData = $antsystem->getZipcodeData($zip);

		$ret = array(
			"city"=>$zipData['city'], 
			"state"=>$zipData['state'], 
		);

		/* Create a new SOAP client using PEAR::SOAP's SOAP_Client-class: */
		$client = new SoapClient('http://www.weather.gov/forecasts/xml/DWMLgen/wsdl/ndfdXML.wsdl');
		//echo var_export($client->__getFunctions(), true);

		// Define the parameters we want to send to the server's helloWorld-function.
		// Note that these arguments should be sent as an array
		$params = array('latitude' => $zipData['latitude'],
							'longitude'  => $zipData['longitude'],
							'startDate' => date("Y-m-d"),
							'numDays' => 5,
							'format' => '24 hourly');

		// Send a request to the server, and store its response in $response
		//$xml_response = $client->call('NDFDgenByDay', $params, 'uri:DWMLgenByDay','uri:DWMLgenByDay/NDFDgenByDay', array('style'=>'document'));
		$resp = $client->NDFDgenByDay($zipData['latitude'], $zipData['longitude'], date("Y-m-d"), 5, null, "12 hourly");

		$data = new SimpleXMLElement($resp);
		$ret['days'] = array(
			0 => array("name"=>date("l")),
			1 => array("name"=>date("l", strtotime("+ 1 days"))),
			2 => array("name"=>date("l", strtotime("+ 2 days"))),
			3 => array("name"=>date("l", strtotime("+ 3 days"))),
			4 => array("name"=>date("l", strtotime("+ 4 days"))),
		);

		// Get conditions - DWML:DATA:PARAMETERS:WEATHER:WEATHER
		for ($i = 0; $i < count($data->data->parameters->weather->{"weather-conditions"}); $i++)
		{
			if ($i >= 5) continue; // skip anything past 5 days

			$ret['days'][$i]['forecast'] = (string)$data->data->parameters->weather->{"weather-conditions"}[$i]->attributes()->{"weather-summary"}[0];
		}

		// Get icons - DWML:DATA:PARAMETERS:CONDITIONS-ICON
		for ($i = 0; $i < count($data->data->parameters->{"conditions-icon"}->{"icon-link"}); $i++)
		{
			if ($i >= 5) continue; // skip anything past 5 days

			$ret['days'][$i]['icon'] = (string)$data->data->parameters->{"conditions-icon"}->{"icon-link"}[$i];
			$ret['days'][$i]['icon'] = str_replace("http://www.nws.noaa.gov/weather/images/fcicons/", 
													"/images/icons/weather/", 
													$ret['days'][$i]['icon']);
		}

		// Get temp min/max - DWML:DATA:PARAMETERS:TEMPERATURE:VALUE
		for ($i = 0; $i < count($data->data->parameters->{"temperature"}); $i++)
		{
			$key = ($data->data->parameters->{"temperature"}[$i]->attributes()->type == "minimum") ? "tempMin" : "tempMax";

			for ($j = 0; $j < count($data->data->parameters->{"temperature"}[$i]->value); $j++)
				$ret['days'][$j][$key] = (string)$data->data->parameters->{"temperature"}[$i]->value[$j];
		}

		// Cache for later
		$cache->set("weather/data/" . $zip, $ret, 3600);

        return $this->sendOutputJson($ret);
    }
}
