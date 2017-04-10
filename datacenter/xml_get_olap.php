<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("lib/COlapCube.php");
	require_once("lib/aereus.lib.php/CChart.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID = $USER->id;
	$ACCOUNT = $USER->accountId;

	$OBJ_TYPE = $_GET['obj_type'];
	$dim1 = $_GET['dim1'];
	$dim1_group = $_GET['dim1_group'];
	$dim2 = $_GET['dim2'];
	$dim2_group = $_GET['dim2_group'];

	if ($_GET['chart_type'])
		$chart_type = $_GET['chart_type'];
	else
		$chart_type = ($dim1 && $dim2) ? "MSLine" : "Line";

	$width = ($_GET['chart_width']) ? $_GET['chart_width'] : 800;

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	if ($_GET['custom_report'])
	{
		include("reports/".$_GET['custom_report'].".php");
		exit;
	}

	if (!$OBJ_TYPE || !$dim1)
	{
		echo "<result><error>Object type and dimensions 1 must be populated</error></result>";
	}

	$cube = new COlapCube($dbh, $OBJ_TYPE, $USER);
	if (isset($_POST) && is_array($_POST))
		$cube->setFormConditions($_POST); // usually $_POST is passed
	if (is_array($_POST['measures']))
	{
		foreach ($_POST['measures'] as $measid)
			$cube->addMeasure($_POST['measure_field_'.$measid], $_POST['measure_aggregate_'.$measid]);
	}
	$cube->setDimension(0, $dim1, $dim1_group);
	if ($dim2)
		$cube->setDimension(1, $dim2, $dim2_group);

	$cube->queryValues();


	echo "<olap>";

	// Set dimensions
	// ------------------------------------------------------------
	echo "<dimensions>";

	echo "<dimension name=\"".rawurlencode($cube->dimension1)."\">";
	foreach ($cube->dimension1_arr as $ent)
		echo "<entry value=\"".rawurlencode($ent[0])."\" label=\"".rawurlencode($ent[1])."\"></entry>";
	echo "</dimension>";

	if ($cube->dimension2)
	{
		echo "<dimension name=\"".rawurlencode($cube->dimension2)."\">";
		foreach ($cube->dimension2_arr as $ent)
			echo "<entry value=\"".rawurlencode($ent[0])."\" label=\"".rawurlencode($ent[1])."\"></entry>";
		echo "</dimension>";
	}
	echo "</dimensions>";

	// Print data
	// ------------------------------------------------------------
	echo $cube->getData();

	// Print chart
	// ------------------------------------------------------------
	$chart = new CChart($chart_type);	
	$cube->setChartData($chart);
	
	echo "<chart>".rawurlencode($chart->getChart($width, $width*0.5625))."</chart>";
	//echo "<query>".rawurlencode($cube->query)."</query>";

	echo "</olap>";
?>
