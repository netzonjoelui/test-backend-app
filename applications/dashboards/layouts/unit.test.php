<?php
/**
 * This is a test layout file used for both an example and also for unit testing
 */

$layout = array(
	// Col 1
	array(
		"widgets" => array(
			"CWidActivity",
		),
	),

	// Col 2
	array(
		"width" => "350px",
		"widgets" => array(
			"CWidCalendar",
			"CWidTasks",
			"CWidWeather",
		),
	),
);
