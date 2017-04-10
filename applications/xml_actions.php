<?php
/**
 * @depricated I don't think this is being referenced anywhere any more after a search for references. We can delete this next update.
 * - joe
 */
require_once("../lib/AntConfig.php");
require_once("ant.php");
require_once("ant_user.php");
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");
require_once("lib/WorkFlow.php");
require_once("email/email_functions.awp");

// Dashboard includes
require_once("../lib/aereus.lib.php/CChart.php");
require_once("../users/user_functions.php");
require_once("../calendar/calendar_functions.awp");
require_once("../contacts/contact_functions.awp");
require_once("../customer/customer_functions.awp");
require_once("../lib/aereus.lib.php/CPageCache.php");

$dbh = $ANT->dbh;
$USERNAME = $USER->name;
$USERID =  $USER->id;
$ACCOUNT = $USER->accountId;

$FUNCTION = $_GET['function'];

if ($_GET['app'])
{
	$result = $dbh->Query("select id from applications where name='".$_GET['app']."'");
	if ($dbh->GetNumberRows($result))
		$appid = $dbh->GetValue($result, 0, "id");
}

ini_set("max_execution_time", "7200");	
ini_set("max_input_time", "7200");	
ini_set("memory_limit", "500M");	

// Log activity - not idle
UserLogAction($dbh, $USERID);

header("Content-type: text/xml");			// Returns XML document
echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

switch ($FUNCTION)
{
	// ---------------------------------------------------------
	// save_layout - save the navigation portion of the application
	// xml definition.
	// ---------------------------------------------------------
	case "save_layout":
		// Save xml to 'applications' table in the xml_navigation column
		$name = $_POST['name'];
		$xml = $_POST['layout_xml'];
		if($xml && $name)
		{
			$dbh->Query("update applications set xml_navigation='".$dbh->Escape($xml)."' where name='$name'");
			$retval = 1;
		}
		else
			$retval = "-1";
		break;

		// ---------------------------------------------------------
		// save_layout - save the general portion of the application
		// ---------------------------------------------------------
	case "save_general":
		$retval = -1;
		$app = rawurldecode($_GET['app']);
		$title = rawurldecode($_GET['title']);
		$short_title = rawurldecode($_GET['short_title']);
		$scope = rawurldecode($_GET['scope']);

		if($appid && $title && $short_title && $scope)
		{
			$dbh->Query("update applications set title='$title', short_title='$short_title', scope='$scope' where name='$app'");
			$retval = "1";	// return success
		}
		break;

		// ---------------------------------------------------------
		// create_calendar - create an application calendar
		// ---------------------------------------------------------
	case "create_calendar":
		$retval = -1;
		$app = rawurldecode($_GET['app']);
		$name = rawurldecode($_GET['cal_name']);

		if ($appid && $name)
		{
			$result = $dbh->Query("insert into calendars(name, def_cal, date_created, global_share) 
			values('".rawurldecode($name)."', 'f', 'now', 't');
			select currval('calendars_id_seq') as id;");
			if ($dbh->GetNumberRows($result))
			{
				$calid = $dbh->GetValue($result, 0, "id");

				if ($calid)
				{
					$dbh->Query("insert into application_calendars(application_id, calendar_id) values('$appid', '$calid');");
					$retval = $calid;
				}
			}
		}
		break;

		// ---------------------------------------------------------
		// delete_calendar - delete an application calendar
		// ---------------------------------------------------------
	case "delete_calendar":
		$retval = -1;
		$app = rawurldecode($_GET['app']);
		$cal_id = rawurldecode($_GET['cal_id']);

		if ($appid && $cal_id)
		{
			// Delete application calendar from calendars
			$dbh->Query("delete from calendars where id='$cal_id'");

			// Delete application calendar from application_calendars
			$dbh->Query("delete from application_calendars where calendar_id='$cal_id' and application_id=$appid");

			$retval= "1";	// return success
		}
		break;

		// ---------------------------------------------------------
		// add_object_reference - add a reference to the object
		// ---------------------------------------------------------
	case "add_object_reference":
		$retval = -1;
		$app = rawurldecode($_GET['app']);
		$obj_type = rawurldecode($_GET['obj_type']);

		if ($appid && $obj_type)
		{
			$otid = objGetAttribFromName($dbh, $obj_type, "id");
			if ($otid)
			{
				objAssocTypeWithApp($dbh, $otid, $appid);
				$retval = "1"; // return success
			}
		}
		break;

		// ---------------------------------------------------------
		// create_object - create a new object and link to this application
		// ---------------------------------------------------------
	case "create_object":
		$retval = -1;
		$app = rawurldecode($_GET['app']);
		$obj_name = rawurldecode($_GET['obj_name']);

		if ($appid && $obj_name)
		{		
			$otid = objCreateType($dbh, $obj_name, $obj_name, $appid);

			// Return object params - can be decoded with escape
			$retval = "{id:$otid, name:$obj_name}";
		}
		break;

		// ---------------------------------------------------------
		// delete_object_reference - delete object reference
		// ---------------------------------------------------------
	case "delete_object_reference":
		$retval = -1;
		$app = rawurldecode($_GET['app']);
		$obj_type = rawurldecode($_GET['obj_type']);

		if ($appid && $obj_type)
		{
			$otid = objGetAttribFromName($dbh, $obj_type, "id");

			if ($otid)
			{
				$dbh->Query("delete from application_objects where object_type_id='$otid' and application_id=$appid");
				$retval= "1";	// return success
			}
		}
		break;

		// ---------------------------------------------------------
		// START - Dashboard Functions
		// ---------------------------------------------------------
	case "dashboard_del_rpt_graph":
		// this function :dashboard_del_rpt_graph() dont have a caller.
		
		$eid = $_GET['eid'];
		if ($eid)
		{
			$result = $dbh->Query("delete from dc_dashboard where id='$eid' and user_id='$USERID'");
			$retval = $eid;
		}
		break;
	case "dashboard_save_layout":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
		$num = rawurldecode($_GET['num_cols']);            
		if ($num)
		{
			for ($i = 0; $i < $num; $i++)
			{
				$items = rawurldecode($_GET['col_'.$i]);
				if ($items)
				{
					$widgets = explode(":", $items);

					if (is_array($widgets))
					{
						for ($j = 0; $j < count($widgets); $j++)
						{
							$dbh->Query("update user_dashboard_layout set position='$j', col='$i' where user_id='$USERID' 
							and id='".$widgets[$j]."' and dashboard='$appNavname';");
						}
					}
				}
			}
		}
		$retval = "done";
		break;
	case "dashboard_save_layout_resize":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
		$num = rawurldecode($_GET['num_cols']);
		if ($num)
		{
			for ($i = 0; $i < $num; $i++)
				UserSetPref($dbh, $USERID, "$appNavname/col".$i."_width", rawurldecode($_GET["col_".$i]));
		}
		$retval = "done";
		break;

	case "dashboard_del_widget":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
		
		$eid = $_GET['eid'];
		if ($eid)
		{
			// Purge cached data if exists
			$cache = new CPageCache(null, "widgets-$appNavname-".$eid);
			$cache->purge();

			$result = $dbh->Query("delete from user_dashboard_layout where id='$eid' and user_id='$USERID' and dashboard='$appNavname'");
			$retval = $eid;
		}
		break;

	case "dashboard_set_total_width":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
		$width = $_GET['width'];            
		if (is_numeric($width))
		{
			UserSetPref($dbh, $USERID, "$appNavname/dashboard_width", $width);

			for ($i = 0; $i < 3; $i++)
				UserSetPref($dbh, $USERID, "$appNavname/col".$i."_width", (($width/3) - 5)."px");

			$retval = $width;
		}
		else
		{
			UserSetPref($dbh, $USERID, "$appNavname/dashboard_width", "100%");
		}
		break;

		// Weather: Set zipcode
		//-----------------------------------------------------
	case "set_zipcode":
		// Purge cache file if exitst// 1800 = 30 minutes
		$oldzip = UserGetPref($dbh, $USERID, "userdata/zipcode");
		if ($oldzip)
		{
			$cache = new CPageCache(null, "widgets-xml-weather-$USERID-$oldzip");
			$cache->purge();
		}

		// Set new zip
		UserSetPref($dbh, $USERID, "userdata/zipcode", $_GET['zipcode']);
		$retval = $_GET['zipcode'];
		break;

		// Settings
		//-----------------------------------------------------
	case "add_widget":
		if (is_numeric($_GET['wid']))
		{
			// AppNavname is the unique identifier to know which dashboard is to be updated
			$appNavname = rawurldecode($_GET['appNavname']);

			// Get next position id
			$result = $dbh->Query("select position from user_dashboard_layout where user_id='$USERID' 
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
			values('$USERID', '0', '$use', '".$_GET['wid']."', '$appNavname');
			select currval('user_dashboard_layout_id_seq') as id;");
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);
				$retval = $row['id'];
			}
			else
				$retbal = -1;
			$dbh->FreeResults($result);
		}
		else
			$retval = -1;

		break;

		// Welcome Center Options
		//-----------------------------------------------------
	case "set_wel_color":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
		
		$retval = UserSetPref($dbh, $USERID, "{$appNavname}_messagecntr_txtclr", $_GET['val']);
		if(empty($retval))
			$retval = $_GET['val'];
		break;
	case "get_wel_color":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
		
		$retval = UserGetPref($dbh, $USERID, "{$appNavname}_messagecntr_txtclr");
		
		if(empty($retval))
			$retval = "000000";
		break;
	case "set_wel_img":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
		
		UserSetPref($dbh, $USERID, "{$appNavname}_messagecntr_image", $_GET['val']);
		break;
	case "set_wel_img_def":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
		
		UserDeletePref($dbh, $USERID, "{$appNavname}_messagecntr_image");
		UserDeletePref($dbh, $USERID, "{$appNavname}_messagecntr_txtclr");
		break;
	case "get_wel_image":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
		
		// Look for custom image
		$custimg = UserGetPref($dbh, $USERID, "{$appNavname}_messagecntr_image");
		$width = $_GET['width'];
		if ($custimg)
		{
			$retval = ($custimg == "none") ? "none" : "/userfiles/getthumb_by_id.awp?fid=$custimg&stretch=1&iw=$width";
		}
		else
		{
			$custom_default = $ANT->settingsGet("general/welcome_image");

			if (is_numeric($custom_default))
			{
				$retval = "/files/images/$custom_default/$width";
			}
			else
			{
				$retval = "/userfiles/getthumb.awp?path=".base64_encode("/images/themes/".UserGetTheme($dbh, $USERID, 'name')."/greeting.png");
				$retval .= "&iw=$width&stretch=1&type=PNG\";";
			}
		}
		break;

		// Calendar Options
		//-----------------------------------------------------
	case "set_cal_timespan":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
		
		UserSetPref($dbh, $USERID, "calendar/$appNavname/span", $_GET['val']);
		if ($_GET['val'] > 1)
			$retval = date("m/d/Y", strtotime("+ ".($_GET['val']-1)." days"));
		else
			$retval = date("m/d/Y");

		// Purge cached data if exists
		$cache = new CPageCache(null, "calendar-events-viewer-$USERID");
		$cache->purge();
		break;
	case "get_cal_timespan":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
		
		$val = UserGetPref($dbh, $USERID, "calendar/$appNavname/span");
		if ($val > 1)
			$retval = date("m/d/Y", strtotime("+ ".($val-1)." days"));
		else
			$retval = date("m/d/Y");

		// Purge cached data if exists
		$cache = new CPageCache(null, "calendar-events-viewer-$USERID");
		$cache->purge();
		break;
		// RSS Options
		//-----------------------------------------------------
	case "rss_set_data":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
				
		// Purge cached data if exists
		$cache = new CPageCache(null, "widgets-$appNavname-".$_GET['id']);
		$cache->purge();

		$result = $dbh->Query("update user_dashboard_layout set 
		data='".$dbh->Escape(rawurldecode($_GET['data']))."' where id='".$_GET['id']."' and dashboard='$appNavname'");
		$retval = $_GET['data'];
		break;
		// General
		//-----------------------------------------------------
	case "widget_set_data":
		// AppNavname is the unique identifier to know which dashboard is to be updated
		$appNavname = rawurldecode($_GET['appNavname']);
		
		$result = $dbh->Query("update user_dashboard_layout set 
		data='".$dbh->Escape(rawurldecode($_GET['data']))."' where id='".$_GET['id']."' and dashboard='$appNavname'");
		$retval = $_GET['data'];
		break;
		// ---------------------------------------------------------
		// END - Dashboard Functions
		// ---------------------------------------------------------
}

// Check for RPC
if ($retval)
{
	echo "<response>";
	echo "<retval>" . rawurlencode($retval) . "</retval>";
	echo "<message>" . rawurlencode($message) . "</message>";
	echo "<cb_function>" . $_GET['cb_function'] . "</cb_function>";
	echo "</response>";
}
?>
