<?php
	$layout = array(
		// Col 1
		array(
			"widgets" => array(
				"CWidActivity",
			),
		),

		// Col 2
		array(
			"width" => "320px",
			"widgets" => array(
				"CWidCalendar",
				"CWidTasks",
				"CWidWeather",
			),
		),
	);
	
	/*
    $db_layout = array();    

	// Column 1
    $db_layout[] = array( 
		array("widgetClass" => "CWidTasks"),
		array("widgetClass" => "CWidCalendar"),
		array("widgetClass" => "CWidWebsearch"),
    );

	// Column 2
    $db_layout[] = array( 
		array("widgetClass" => "CWidWelcome"),
		array("widgetClass" => "CWidWeather"),
    );

	// Column 3
    $db_layout[] = array( 
		array("widgetClass" => "CWidReport"),
    );
	 */
?>
