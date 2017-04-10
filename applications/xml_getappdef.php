<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectFields.php");
	require_once("lib/global_functions.php");
	require_once("objects/object_functions.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	$result = $dbh->Query("select id, name, short_title, title, xml_navigation, scope, f_system, user_id, team_id, settings
							from applications where name='".$dbh->Escape($_GET['app'])."'");
	if ($dbh->GetNumberRows($result))
	{
		$appId = $dbh->GetValue($result, 0, "id");
		$appName = $dbh->GetValue($result, 0, "name");
		$appTitle = $dbh->GetValue($result, 0, "title");
		$appShortTitle = $dbh->GetValue($result, 0, "short_title");
		$xmlNavigation = $dbh->GetValue($result, 0, "xml_navigation");
		$scope = $dbh->GetValue($result, 0, "scope");
        $isSystem = $dbh->GetValue($result, 0, "f_system");
        $userId = $dbh->GetValue($result, 0, "user_id");
		$teamId = $dbh->GetValue($result, 0, "team_id");
		$settings = $dbh->GetValue($result, 0, "settings");

		echo "<application 
				name=\"$appName\" 
				title=\"".rawurlencode($appTitle)."\" 
				short_title=\"".rawurlencode($appShortTitle)."\"
				scope=\"".rawurlencode($scope)."\" 
				isSystem=\"".rawurlencode($isSystem)."\" 
				userId=\"".rawurlencode($userId)."\"
				teamId=\"".rawurlencode($teamId)."\" 
				settings='$settings' ";
		if ($ANT->getServiceLocator()->get("Help")->tourItemExists("apps/" . $appName))
			echo "tour=\"apps/".rawurlencode($appName)."\" ";

		echo ">";

		// Get objects
		// ----------------------------------------------------------------------------------
		echo "<objects>";
        
        // Transferred the getting of non-system referenced objects to ApplicationController
		/*$result = $dbh->Query("select id, object_type_id from application_objects where application_id='".$appId."'");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$oname = objGetNameFromId($dbh, $dbh->GetValue($result, $i, "object_type_id"));
			if ($oname)
			{
				$odef = new CAntObject($dbh, $oname);
				echo "<object title=\"".rawurlencode($odef->title)."\" name='$oname' system='f'";
				echo " />";
			}
		}*/

		// Get default system references if exist
		if (file_exists("orefs/".$appName.".php"))
			include("orefs/".$appName.".php");
		
		echo "</objects>";

		// Get calendars
		// ----------------------------------------------------------------------------------
		echo "<calendars>";
		$result = $dbh->Query("select calendars.id, calendars.name from calendars, application_calendars WHERE
								application_calendars.calendar_id=calendars.id and application_calendars.application_id='".$appId."';");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
			echo "<calendar name=\"".rawurlencode($row['name'])."\" id='".$row['id']."' />";
		}
		echo "</calendars>";

		// Get navigation
		// ----------------------------------------------------------------------------------
		if ($xmlNavigation)
			echo $xmlNavigation;
		else if (file_exists("nav/".$appName.".php"))
			include("nav/".$appName.".php");
		else
			echo "<navigation></navigation>";

		echo "</application>";
	}
	else if ($_GET['app'] == "settings")
	{
		echo "<application name='settings' title='Settings' short_title='Settings' settings='no'>";
		echo "<objects></objects>";
		echo "<calendars></calendars>";
		include("nav/settings.php");
		echo "</application>";
	}
	else if ($_GET['app'] == "help")
	{
		echo "<application name='help' title='Help &amp; Support' short_title='Help' settings='no'>";
		echo "<objects></objects>";
		echo "<calendars></calendars>";
		include("nav/help.php");
		echo "</application>";
	}
	else
	{
		echo "<error>Application not installed - " . $dbh->lastError . "</error>";
	}
?>
