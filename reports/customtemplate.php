<?php
	/*======================================================================================
		
		Page:		customtemplate.php

		Purpose:	Sample template for creating a custom report

		Author:		joe, sky.stebnicki@aereus.com
					Copyright (c) 2011 Aereus Corporation. All rights reserved.

		Usage:		This is included from xml_get_olap.php when a custom report name is passed.
					That means all variables are avaialable included $_POST conditions
					for filtering results. The conditions should be handled by CAntObjectLists
					below to allow for proper customization of filters. However, dimensions
					are not customizable for custom reports.

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
	$cdata = $chart->creatXmlData("xAxisName", "yAxisName", "Caption", "subcaption", "numpre", "0");
	$cdata->addCategory("Month 1");
	$cdata->addCategory("Month 2");
	$cdata->addCategory("Month 3");
	
	$set = $cdata->addSet("Product A", "FF0000");
	$set->addEntry("8343");
	$set->addEntry("6300");
	$set->addEntry("2900");

	$set = $cdata->addSet("Product B", "FF0000");
	$set->addEntry("9343");
	$set->addEntry("5200");
	$set->addEntry("8000");
	
	echo "<chart>".rawurlencode($chart->getChart($width, $width*0.5625))."</chart>";

	echo "</olap>";
?>
