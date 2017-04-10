<?php
/**
 * This file is used to create default reports
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/Object/Report.php");

if (!$ant)
	die("This script must be called from the system schema manager and ant mut be set");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);

/**
 * Sales Reports
 */

// Leads By Source
$reportObject = new CAntObject_Report($dbh, "uname:sales/leads-by-source", $user);
$rev = 5;
if (!$reportObject->id || $reportObject->getValue("revision") < $rev)
{
    $reportObject->setValue("name", "Leads By Source");
    $reportObject->setValue("description", "Leads By Source");
    $reportObject->setValue("obj_type", "lead");
    $reportObject->setValue("f_display_table", "f");
    $reportObject->setValue("f_display_chart", "t");
    $reportObject->setValue("scope", "system");
    $reportObject->setValue("owner_id", USER_ADMINISTRATOR);    
    $reportObject->setValue("revision", $rev-1);
    $reportObject->setValue("uname", "sales/leads-by-source");

    // Chart Data
    $reportObject->setValue("chart_type", "Pie3D");
    $reportObject->setValue("chart_measure", "count");
    $reportObject->setValue("chart_measure_agg", "sum");
    $reportObject->setValue("chart_dim1", "source_id");
    $reportObject->setValue("chart_dim1_grp", "");
    $reportObject->setValue("chart_dim2", "");
    $reportObject->setValue("chart_dim2_grp", "");

    // Table Data
    $reportObject->setValue("table_type", "summary"); // tabular, summary, matrix
    $reportObject->addReportDim("source_id ", "asc", null, null, null); // used for summary & matrix
    $reportObject->addReportMeasure("count", "sum", null);
    
    // Filters
    $reportObject->addReportFilter("and", "ts_created", "last_x_days", 30);
    
    $rid = $reportObject->save(false);
}

// Leads Trend by Source
unset($reportObject);
$reportObject = new CAntObject_Report($dbh, "uname:sales/leads-tr-by-source", $user);
$rev = 1;
if (!$reportObject->id || $reportObject->getValue("revision") < $rev)
{
    $reportObject->setValue("name", "Leads By Source");
    $reportObject->setValue("description", "Leads Trend by Source");
    $reportObject->setValue("obj_type", "lead");
    $reportObject->setValue("f_display_table", "f");
    $reportObject->setValue("f_display_chart", "t");
    $reportObject->setValue("scope", "system");
    $reportObject->setValue("owner_id", USER_ADMINISTRATOR);    
    $reportObject->setValue("revision", $rev);
    $reportObject->setValue("uname", "sales/leads-tr-by-source");

    // Chart Data
    $reportObject->setValue("chart_type", "StckdArea");
    $reportObject->setValue("chart_measure", "count");
    $reportObject->setValue("chart_measure_agg", "sum");
    $reportObject->setValue("chart_dim1", "ts_entered");
    $reportObject->setValue("chart_dim1_grp", "m");
    $reportObject->setValue("chart_dim2", "source_id");
    $reportObject->setValue("chart_dim2_grp", "");

    // Table Data
    $reportObject->setValue("table_type", "summary"); // tabular, summary, matrix
    $reportObject->addReportDim("source_id ", "asc", null, null, null); // used for summary & matrix
    $reportObject->addReportMeasure("count", "sum", null);
    
    // Filters
    $reportObject->addReportFilter("and", "ts_created", "last_x_months", 12);
    
    $rid = $reportObject->save(false);
}

// Opportunities Pipeline By Stage
unset($reportObject);
$reportObject = new CAntObject_Report($dbh, "uname:sales/opp-pip-by-stage", $user);
$rev = 1;
if (!$reportObject->id || $reportObject->getValue("revision") < $rev)
{
    $reportObject->setValue("name", "Opportunities Pipeline By Stage");
    $reportObject->setValue("description", "Opportunities Pipeline By Stage");
    $reportObject->setValue("obj_type", "opportunity");
    $reportObject->setValue("f_display_table", "f");
    $reportObject->setValue("f_display_chart", "t");
    $reportObject->setValue("scope", "system");
    $reportObject->setValue("owner_id", USER_ADMINISTRATOR);    
    $reportObject->setValue("revision", $rev);
    $reportObject->setValue("uname", "sales/opp-pip-by-stage");

    // Chart Data
    $reportObject->setValue("chart_type", "Funnel");
    $reportObject->setValue("chart_measure", "count");
    $reportObject->setValue("chart_measure_agg", "sum");
    $reportObject->setValue("chart_dim1", "stage_id");
    $reportObject->setValue("chart_dim1_grp", "");
    $reportObject->setValue("chart_dim2", "");
    $reportObject->setValue("chart_dim2_grp", "");

    // Table Data
    $reportObject->setValue("table_type", "summary"); // tabular, summary, matrix
    $reportObject->addReportDim("stage_id ", "asc", null, null, null); // used for summary & matrix
    $reportObject->addReportMeasure("count", "sum", null);
    
    // Filters
    $reportObject->addReportFilter("and", "expected_close_date", "month_is_equal", date("m"));
    
    $rid = $reportObject->save(false);
}

// Opportunities Pipeline By Probability
unset($reportObject);
$reportObject = new CAntObject_Report($dbh, "uname:sales/opp-pip-by-prob", $user);
$rev = 2;
if (!$reportObject->id || $reportObject->getValue("revision") < $rev)
{
    $reportObject->setValue("name", "Opportunities Pipeline By Probability");
    $reportObject->setValue("description", "Opportunities Pipeline By Probability");
    $reportObject->setValue("obj_type", "opportunity");
    $reportObject->setValue("f_display_table", "f");
    $reportObject->setValue("f_display_chart", "t");
    $reportObject->setValue("scope", "system");
    $reportObject->setValue("owner_id", USER_ADMINISTRATOR);    
    $reportObject->setValue("revision", $rev);
    $reportObject->setValue("uname", "sales/opp-pip-by-prob");

    // Chart Data
    $reportObject->setValue("chart_type", "Funnel");
    $reportObject->setValue("chart_measure", "count");
    $reportObject->setValue("chart_measure_agg", "sum");
    $reportObject->setValue("chart_dim1", "probability_per");
    $reportObject->setValue("chart_dim1_grp", "");
    $reportObject->setValue("chart_dim2", "");
    $reportObject->setValue("chart_dim2_grp", "");

    // Table Data
    $reportObject->setValue("table_type", "summary"); // tabular, summary, matrix
    $reportObject->addReportDim("probability_per ", "asc", null, null, null); // used for summary & matrix
    $reportObject->addReportMeasure("count", "sum", null);
    
    // Filters
    $reportObject->addReportFilter("and", "expected_close_date", "month_is_equal", date("m"));
    
    $rid = $reportObject->save(false);
}


/**
 * Custom project reports
 */

// Tasks by status - on track | off track
$obja = new CAntObject_Report($ant->dbh, "uname:project/taskstrack", $user);
$rev = 5;
if (!$obja->id || $obja->getValue("revision") < $rev)
{
	$obja->setValue("name", "Tasks By Status");
	$obja->setValue("description", "View Tasks By Status");
	$obja->setValue("obj_type", "task");
	$obja->setValue("f_display_table", "f");
	$obja->setValue("f_display_chart", "t");
	$obja->setValue("scope", "system");
	$obja->setValue("owner_id", USER_ADMINISTRATOR);
	$obja->setValue("custom_report", "Project_TaskTrack");
	$obja->setValue("revision", $rev);
	$obja->setValue("uname", "project/taskstrack");

	// Chart Data
	$obja->setValue("chart_type", "Column2D");
	$obja->setValue("chart_measure", "count");
	$obja->setValue("chart_measure_agg", "sum");
	$obja->setValue("chart_dim1", "track");
	$obja->setValue("chart_dim1_grp", "");
	$obja->setValue("chart_dim2", "");
	$obja->setValue("chart_dim2_grp", "");

	// Table Data
	$obja->setValue("table_type", "matrix"); // tabular, summary, matrix
	$obja->addReportDim("track", "asc"); // used only for matrix & tabular
	$obja->addReportMeasure("count", "sum");

	$rid = $obja->save(false);
}

// Tasks by priority
$obja = new CAntObject_Report($ant->dbh, "uname:project/taskpri", $user);
$rev = 3;
if (!$obja->id || $obja->getValue("revision") < $rev)
{
	$obja->setValue("name", "Open Tasks By Priority");
	$obja->setValue("description", "View Open Tasks By Priority");
	$obja->setValue("obj_type", "task");
	$obja->setValue("f_display_table", "f");
	$obja->setValue("f_display_chart", "t");
	$obja->setValue("scope", "system");
	$obja->setValue("owner_id", USER_ADMINISTRATOR);
	$obja->setValue("custom_report", "");
	$obja->setValue("revision", $rev);
	$obja->setValue("uname", "project/taskpri");

	// Chart Data
	$obja->setValue("chart_type", "Pie3D");
	$obja->setValue("chart_measure", "count");
	$obja->setValue("chart_measure_agg", "sum");
	$obja->setValue("chart_dim1", "priority");
	$obja->setValue("chart_dim1_grp", "");
	$obja->setValue("chart_dim2", "");
	$obja->setValue("chart_dim2_grp", "");

	// Table Data
	$obja->setValue("table_type", "summary"); // tabular, summary, matrix

	// Add OLAP data
	$obja->addReportDim("priority", "asc"); // used only for matrix & tabular
	$obja->addReportMeasure("count", "sum");
	$obja->addReportFilter("and", "done", "is_not_equal", "t");

	$rid = $obja->save(false);
}

// Case by Status
$obja = new CAntObject_Report($ant->dbh, "uname:project/casebystatus", $user);
$rev = 3;
if (!$obja->id || $obja->getValue("revision") < $rev)
{
	$obja->setValue("name", "Cases By Status");
	$obja->setValue("description", "View Cases By Status");
	$obja->setValue("obj_type", "case");
	$obja->setValue("f_display_table", "f");
	$obja->setValue("f_display_chart", "t");
	$obja->setValue("scope", "system");
	$obja->setValue("owner_id", USER_ADMINISTRATOR);
	$obja->setValue("custom_report", "");
	$obja->setValue("revision", $rev);
	$obja->setValue("uname", "project/casebystatus");

	// Chart Data
	$obja->setValue("chart_type", "Pie3D");
	$obja->setValue("chart_measure", "count");
	$obja->setValue("chart_measure_agg", "sum");
	$obja->setValue("chart_dim1", "status_id");
	$obja->setValue("chart_dim1_grp", "");
	$obja->setValue("chart_dim2", "");
	$obja->setValue("chart_dim2_grp", "");

	// Table Data
	$obja->setValue("table_type", "summary"); // tabular, summary, matrix

	// Add OLAP data
	$obja->addReportDim("status_id", "asc"); // used only for matrix & tabular
	$obja->addReportMeasure("count", "sum");

	$rid = $obja->save(false);
}
