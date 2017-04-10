<?php
	/*======================================================================================
		
		Report:		project/taskburn

		Purpose:	Create task burndown chart. Can be filtered by project_id or owner_id

		Author:		joe, sky.stebnicki@aereus.com
					Copyright (c) 2011 Aereus Corporation. All rights reserved.

	======================================================================================*/
	echo "<olap>";

	// Set dimensions
	// ------------------------------------------------------------
	echo "<dimensions>";

	echo "<dimension name=\"project_id\">";
	echo "<entry value=\"1\" label=\"Test Project\"></entry>";
	echo "</dimension>";

	echo "<dimension name=\"ficdate\">";
	echo "<entry value=\"1/1/2011\" label=\"Jan 1st 2011\"></entry>";
	echo "</dimension>";
	echo "</dimensions>";

	// Print data
	// ------------------------------------------------------------
	echo "<data>";
	echo "<dimension value=\"1\" label=\"Test Project\">";
	echo 	"<dimension value=\"1/1/2011\" label=\"Jan 1st 2011\">";
	echo 		"<measure name=\"ficdate\">".round(100, 0)."</measure>";
	echo 	"</dimension>";
	echo "</dimension>";
	echo "</data>";

	// Print chart
	// ------------------------------------------------------------
	$width = ($_GET['chart_width']) ? $_GET['chart_width'] : 800;

	$chart = new CChart("MSLine");
	$cdata = $chart->creatXmlData("Time", "Work Cost", "Task Burndown", "", "", "0");
	$cdata->addCategory("Jan");
	$cdata->addCategory("Feb");
	$cdata->addCategory("Mar");
	
	$set = $cdata->addSet("Actual", "00FF00");
	$set->addEntry("9343");
	$set->addEntry("6300");
	$set->addEntry("2900");

	$set = $cdata->addSet("Planned", "FF0000");
	$set->addEntry("9343");
	$set->addEntry("5000");
	$set->addEntry("0");
	
	echo "<chart>".rawurlencode($chart->getChart($width, $width*0.5625))."</chart>";
	//echo "<query>".rawurlencode($cube->query)."</query>";

	echo "</olap>";
?>
