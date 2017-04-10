<?php
	// now copy all activities
	$result = $dbh_acc->Query("select id, name, scope, user_id from dc_databases where f_publish='t' and name not in (select name from applications)");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);
		$scope = ($row['scope'] && $row['scope'] != "undefined") ? $row['scope'] : "";

		$res2 = $dbh_acc->Query("INSERT INTO applications(name, short_title, title, scope, user_id, sort_order)
								VALUES('".$dbh_acc->Escape(strtolower($row['name']))."_app', 
										'".$dbh_acc->Escape($row['name'])."', 
										'".$dbh_acc->Escape($row['name'])."',
										'".$dbh_acc->Escape($scope)."', 
										".$dbh_acc->EscapeNumber($row['user_id']).", 
										'".($i+500)."');select currval('applications_id_seq') as id;");
		if ($dbh_acc->GetNumberRows($res2))
			$aid = $dbh_acc->GetValue($res2, 0, "id");

		if ($aid)
		{
			// Get objects
			$objects = array();
			$res2 = $dbh_acc->Query("SELECT name FROM dc_database_objects where database_id='".$row['id']."'");
			$num2 = $dbh_acc->GetNumberRows($res2);
			for ($j = 0; $j < $num2; $j++)
			{
				$oname = $dbh_acc->GetValue($res2, $j, "name");

				$res3 = $dbh_acc->Query("select id from app_object_types where name='".$row['id'].".".$oname."'");
				if ($dbh_acc->GetNumberRows($res3))
					$otid = $dbh_acc->GetValue($res3, 0, "id");

				if ($otid)
				{
					$dbh_acc->Query("INSERT INTO application_objects(application_id, object_type_id, f_parent_app) 
										VALUES('$aid', '$otid', 't')");
					$objects[] = $oname;
				}
			}

			// Get calendars
			$calendars = array();
			$res2 = $dbh_acc->Query("SELECT calendar_id FROM dc_database_calendars where database_id='".$row['id']."'");
			$num2 = $dbh_acc->GetNumberRows($res2);
			for ($j = 0; $j < $num2; $j++)
			{
				$cid = $dbh_acc->GetValue($res2, $j, "calendar_id");

				if ($cid)
				{
					$dbh_acc->Query("INSERT INTO application_calendars(calendar_id, application_id) VALUES('$cid', '$aid')");
					$calendars[] = $cid;
				}
			}

			// Create navigation
			// ---------------------------------------------
			$xml = "<navigation default='browse_".$objects[0]."'>";
			// Actions
			$xml .= "<section title='Actions'>";
			foreach ($objects as $oname)
			{
				$xml .= "<item type='object' name='new_".$oname."' title='New $oname' obj_type='".$row['id'].".$oname' icon='/images/icons/plus.png' />";
			}
			$xml .= "</section>";

			// Browse
			$xml .= "<section title='Browse'>";
			foreach ($objects as $oname)
			{
				$xml .= "<item type='browse' name='browse_".$oname."' title='$oname' obj_type='".$row['id'].".$oname' icon='/images/icons/folder_open_16.png' />";
			}
			$xml .= "</section>";

			// Calendars
			$xml .= "<section title='Calendars'>";
			foreach ($calendars as $cid)
			{
				$xml .= "<item type='calendar' name='cal_".$cid."' title='Calendar' id='$cid' icon='/images/icons/calendar.png' />";
			}
			$xml .= "</section>";

			$xml .= "</navigation>";

			$dbh_acc->Query("UPDATE applications SET xml_navigation='".$dbh_acc->Escape($xml)."' WHERE id='$aid'");
		}
			
		echo "Added application ".$row['name']."\n";
	}
?>
