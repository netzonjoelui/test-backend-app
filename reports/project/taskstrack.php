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

	$chart = new CChart($chart_type);
	$cdata = $chart->creatXmlData("", "", "", "", "", "");

	// Pull number of tasks that are on track
	$olist = new CAntObjectList($dbh, $OBJ_TYPE, $USER);
	$olist->processFormConditions($_POST);
	$olist->addCondition("and", "done", "is_equal", "t");
	$olist->addCondition("or", "deadline", "is_greater_or_equal", "now");
	$olist->addCondition("or", "deadline", "is_equal", "");
	$olist->getObjects();
	$num = $olist->getNumObjects();
	$cdata->addEntry($num, "On Track", "006F00");

	// Pull number of tasks that are off track
	$olist = new CAntObjectList($dbh, $OBJ_TYPE, $USER);
	$olist->processFormConditions($_POST);
	$olist->addCondition("and", "done", "is_not_equal", "t");
	$olist->addCondition("and", "deadline", "is_not_equal", "");
	$olist->addCondition("and", "deadline", "is_less", "now");
	$olist->getObjects();
	$num = $olist->getNumObjects();
	$cdata->addEntry($num, "Off Track", "FF0000");

	echo "<chart>".rawurlencode($chart->getChart($width, $width*0.5625))."</chart>";

	echo "</olap>";
?>
