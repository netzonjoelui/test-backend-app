<?php
	$user = new AntUser($dbh_acc, USER_ADMINISTRATOR);
	
	// Reports
	// ------------------------------------------------------------------------------------------------------
	$obja = new CAntObject($dbh_acc, "report", "uname:project/taskburn");
	$rev = 5;
	if (!$obja->id || $obja->getValue("revision") < $rev)
	{
		$obja->setValue("name", "Task Burndown");
		$obja->setValue("description", "");
		$obja->setValue("obj_type", "task");
		$obja->setValue("chart_type", "MSLine");
		$obja->setValue("f_display_table", "f");
		$obja->setValue("f_display_chart", "t");
		$obja->setValue("f_calculate", "t");
		$obja->setValue("dim_one_fld", "cost_estimated");
		$obja->setValue("dim_one_grp", "");
		$obja->setValue("dim_two_fld", "cost_estimated");
		$obja->setValue("dim_two_grp", "");
		$obja->setValue("measure_one_fld", "id");
		$obja->setValue("measure_one_agg", "");
		$obja->setValue("scope", "system");
		$obja->setValue("owner_id", USER_ADMINISTRATOR);
		$obja->setValue("custom_report", "project/taskburn");
		$obja->setValue("revision", $rev-1);
		$obja->setValue("uname", "project/taskburn");
		$rid = $obja->save(false);
	}

	$obja = new CAntObject($dbh_acc, "report", "uname:project/taskstrack");
	$rev = 3;
	if (!$obja->id || $obja->getValue("revision") < $rev)
	{
		$obja->setValue("name", "Tasks By Status");
		$obja->setValue("description", "");
		$obja->setValue("obj_type", "task");
		$obja->setValue("chart_type", "Column2D");
		$obja->setValue("f_display_table", "f");
		$obja->setValue("f_display_chart", "t");
		$obja->setValue("f_calculate", "t");
		$obja->setValue("dim_one_fld", "cost_estimated");
		$obja->setValue("dim_one_grp", "");
		$obja->setValue("dim_two_fld", "cost_estimated");
		$obja->setValue("dim_two_grp", "");
		$obja->setValue("measure_one_fld", "id");
		$obja->setValue("measure_one_agg", "");
		$obja->setValue("scope", "system");
		$obja->setValue("owner_id", USER_ADMINISTRATOR);
		$obja->setValue("custom_report", "project/taskstrack");
		$obja->setValue("revision", $rev-1);
		$obja->setValue("uname", "project/taskstrack");
		$rid = $obja->save(false);
	}

	$obja = new CAntObject($dbh_acc, "report", "uname:project/taskpri");
	$rev = 3;
	if (!$obja->id || $obja->getValue("revision") < $rev)
	{
		$obja->setValue("name", "Tasks By Priority");
		$obja->setValue("description", "");
		$obja->setValue("obj_type", "task");
		$obja->setValue("chart_type", "Pie2D");
		$obja->setValue("f_display_table", "f");
		$obja->setValue("f_display_chart", "t");
		$obja->setValue("f_calculate", "t");
		$obja->setValue("dim_one_fld", "priority");
		$obja->setValue("dim_one_grp", "");
		$obja->setValue("dim_two_fld", "");
		$obja->setValue("dim_two_grp", "");
		$obja->setValue("measure_one_fld", "id");
		$obja->setValue("measure_one_agg", "count");
		$obja->setValue("scope", "system");
		$obja->setValue("owner_id", USER_ADMINISTRATOR);
		$obja->setValue("custom_report", "");
		$obja->setValue("revision", $rev-1);
		$obja->setValue("uname", "project/taskpri");
		$rid = $obja->save(false);
	}

	$obja = new CAntObject($dbh_acc, "report", "uname:project/casebystatus");
	$rev = 3;
	if (!$obja->id || $obja->getValue("revision") < $rev)
	{
		$obja->setValue("name", "Cases By Status");
		$obja->setValue("description", "");
		$obja->setValue("obj_type", "case");
		$obja->setValue("chart_type", "Pie2D");
		$obja->setValue("f_display_table", "f");
		$obja->setValue("f_display_chart", "t");
		$obja->setValue("f_calculate", "t");
		$obja->setValue("dim_one_fld", "status_id");
		$obja->setValue("dim_one_grp", "");
		$obja->setValue("dim_two_fld", "");
		$obja->setValue("dim_two_grp", "");
		$obja->setValue("measure_one_fld", "id");
		$obja->setValue("measure_one_agg", "count");
		$obja->setValue("scope", "system");
		$obja->setValue("owner_id", USER_ADMINISTRATOR);
		$obja->setValue("custom_report", "");
		$obja->setValue("revision", $rev-1);
		$obja->setValue("uname", "project/casebystatus");
		$rid = $obja->save(false);
	}

	// Workflows
	// ------------------------------------------------------------------------------------------------------

	// Notify user when task is assigned
?>
