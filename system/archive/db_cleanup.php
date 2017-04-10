<?php
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	require_once("../userfiles/file_functions.awp");
	require_once("../users/user_functions.php");
	require_once("../project/project_functions.awp");
	// ANT LIB
	require_once("../lib/CDatabase.awp");
	require_once("../lib/aereus.lib.php/CAnsClient.php");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	ini_set("max_execution_time", "28800");	
	ini_set('default_socket_timeout', "28800"); 

	$DEBUG = TRUE;
	
	$dbh = new CDatabase($settings_db_server, "ant_teamromito", $settings_db_user, $settings_db_password, $settings_db_type);
	$result = $dbh->Query("select id from project_templates");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$tid = $dbh->GetValue($result, $i, "id");

		$res2 = $dbh->Query("select id, date_deadline, date_started from projects where template_id='$tid'");
		$num2 = $dbh->GetNumberRows($res2);
		for ($j = 0; $j < $num2; $j++)
		{
			$projRow = $dbh->GetRow($res2, $j);
			$pid = $projRow['id'];

			$res3 = $dbh->Query("select id, name, start_interval, start_count, due_interval, due_count, 
									timeline, type, file_id, user_id, position_id, timeline_date_begin, timeline_date_due 
									from project_template_tasks where user_id='319' and template_id='$tid' 
									and id not in (select template_id from project_tasks where project='$pid')");
			$num3 = $dbh->GetNumberRows($res3);
			for ($m = 0; $m < $num3; $m++)
			{
				$task_row = $dbh->GetNextRow($res3, $m);

				$tl_date_begin = ($task_row['timeline_date_begin']) ? $task_row['timeline_date_begin'] : 'date_deadline';
				$tl_date_due = ($task_row['timeline_date_due']) ? $task_row['timeline_date_due'] : 'date_deadline';
				
				if (($tl_date_begin == "date_deadline" || $tl_date_due == "date_deadline") && $projRow["date_deadline"]=="")
					continue; // Skip over, deadline is not provided

				if (strtotime(ProjectGetExeTime($dbh, $projRow[$tl_date_begin], $task_row['start_count'], $task_row['start_interval'], $task_row['timeline']))<time())
					continue;

				echo "Add task ".$task_row['name']." to start ".ProjectGetExeTime($dbh, $projRow[$tl_date_begin], 
																				  $task_row['start_count'], $task_row['start_interval'], 
																				  $task_row['timeline'])."\n";

				$query = "insert into project_tasks (name, user_id, position_id, done, date_entered, start_date,
								    entered_by, project, priority, deadline, type, notes, file_id, template_task_id)
								    values
								    ('".$dbh->Escape($task_row['name'])."',  
									".db_CheckNumber($task_row['user_id']).", 
									".db_CheckNumber($task_row['position_id']).",
								    'f', '".date("m/d/Y")."', 
								    ".$dbh->EscapeDate(ProjectGetExeTime($dbh, $projRow[$tl_date_begin], $task_row['start_count'], 
														 $task_row['start_interval'], $task_row['timeline'])).", 
								    '$USERNAME', '$pid', '1',
								    ".$dbh->EscapeDate(ProjectGetExeTime($dbh, $projRow[$tl_date_due], $task_row['due_count'], 
														 $task_row['due_interval'], $task_row['timeline'])).",
								    ".db_CheckNumber($task_row['type']).", 
								    '".$dbh->Escape(stripslashes($task_row['notes']))."',  
								    ".db_CheckNumber($task_row['file_id']).", 
									".db_CheckNumber($task_row['id']).")";
				$dbh->Query($query);
			}
		}
	}
	/*
	$ans = new CAnsCLient();

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	// Get database to use from account
	$res_sys = $dbh_sys->Query("select id, database, name from accounts");
	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		$acid = $dbh_sys->GetValue($res_sys, $s, 'id');
		$aname = $dbh_sys->GetValue($res_sys, $s, 'name');
		$cnd = 0;

		if ($dbname)
		{
			$dbh = new CDatabase($settings_db_server, $dbname, $settings_db_user, $settings_db_password, $settings_db_type);
			if (!$dbh->dbHandle)
			{
				echo "*$dbname: Remove reference to this db **\n";
				continue;
			}
			else if (!$dbh->TableExists("accounts"))
			{
				echo "*$dbname: Remove reference to this db **\n";
				continue;
			}


			$result = $dbh->Query("select name, count(*) from users group by name having count(*) > 1;");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				echo "Problem $dbname:".$row['name']."\t\t".$row['count']."\n";
			}
			$dbh->FreeResults($result);
		}
	}
	 */
?>
