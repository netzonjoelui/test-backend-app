<?php
/**
 * reminder object definition
 */
$obj_revision = 10;

$obj_fields = array(
	// Textual name or subject
	'name' => array(
		'title'=>'Subject', 
		'type'=>'text', 
		'subtype'=>'256', 
		'readonly'=>false,
	),

	// The object we are reminding on
	'obj_reference' => array(
		'title'=>'Concering', 
		'type'=>'object', 
		'subtype'=>'', 
		'readonly'=>false,
	),

	// The interval to execute
	'interval' => array(
		'title'=>'Interval', 
		'type'=>'number', 
		'subtype'=>'integer', 
		'readonly'=>false,
	),

	// The units to use with interval (mintes|hours|days|weeks|months|years)
	'interval_unit' => array(
		'title'=>'Interval Unit', 
		'type'=>'text', 
		'subtype'=>'32', 
		'readonly'=>false,
		'optional_values'=>array(
			"minutes" => "Minute(s)",
			"hours" => "Hour(s)",
			"days" => "Day(s)",
			"weeks" => "Week(s)",
			"months" => "Month(s)",
			"years" => "Year(s)",
		),
	),

	// The timestamp or data field to use to calculate ts_execute against
	'field_name' => array(
		'title'=>'Field Name', 
		'type'=>'text', 
		'subtype'=>'256', 
		'readonly'=>true,
	),

	// The actual time when this reminder should execute
	'ts_execute' => array(
		'title'=>'Execute Time', 
		'type'=>'timestamp', 
		'subtype'=>'', 
		'readonly'=>false,
	),

	// The owner of this reminder
	'owner_id' => array(
		'title'=>'Owner', 
		'type'=>'object', 
		'subtype'=>'user', 
		'readonly'=>false,
		'default'=>array(
			"on"=>"null",
			"value"=>"-3",
		),
	),

	// Flag indicating the reminder was executed
	'f_executed' => array(
		'title'=>'Completed', 
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>true,
	),

	// Send to variable
	'send_to' => array(
		'title'=>'Send To', 
		'type'=>'text', 
		'subtype'=>'', 
		'readonly'=>true,
	),

	// Notes for reminder if manual
	'notes' => array(
		'title'=>'Notes', 
		'type'=>'text', 
		'subtype'=>'', 
		'readonly'=>false,
	),

	// The timestamp or data field to use to calculate ts_execute against
	'action_type' => array(
		'title'=>'Type', 
		'type'=>'text', 
		'subtype'=>'32', 
		'readonly'=>false,
		'optional_values'=>array(
			"popup" => "Pop-up",
			"email" => "Email",
			"sms" => "SMS",
		),
	),
);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "My Reminders";
$view->description = "Display all my reminders";
$view->fDefault = true;
$view->view_fields = array("name", "ts_execute");
$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_execute", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "All Reminders";
$view->description = "Display all reminders";
$view->fDefault = false;
$view->view_fields = array("name", "ts_execute");
$view->sort_order[] = new CAntObjectSort("ts_execute", "asc");
$obj_views[] = $view;
unset($view);
