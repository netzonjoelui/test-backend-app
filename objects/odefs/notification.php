<?php
/**
 * notification object definition
 */
$obj_revision = 13;

$isPrivate = true;
$defaultActivityLevel = 1;
$storeRevisions = false; // no need for revisins to be stored

$obj_fields = array(
	// Textual name or subject
	'name' => array(
		'title'=>'Title', 
		'type'=>'text', 
		'subtype'=>'256', 
		'readonly'=>false,
		'require'=>true,
	),

	// Notification content text
	'description' => array(
		'title'=>'Description', 
		'type'=>'text', 
		'subtype'=>'', 
		'readonly'=>false,
	),

	// The object we are reminding on
	'obj_reference' => array(
		'title'=>'Concering', 
		'type'=>'object', 
		'subtype'=>'', 
		'readonly'=>false,
	),

	// Who this notification is sent to
	'owner_id' => array(
		'title'=>'Owner', 
		'type'=>'object', 
		'subtype'=>'user', 
		'readonly'=>false,
		'require'=>true,
		'default'=>array(
			"on"=>"null",
			"value"=>"-3",
		),
	),

	// Who created this notification
	'creator_id' => array(
		'title'=>'Creator', 
		'type'=>'object', 
		'subtype'=>'user', 
		'readonly'=>false,
		'require'=>true,
		'default'=>array(
			"on"=>"null",
			"value"=>"-3",
		),
	),

	// Flag indicating if the notification has been seen
	'f_seen' => array(
		'title'=>'Seen', 
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>false,
		'default'=>array(
			"on"=>"null",
			"value"=>"f",
		),
	),

	// Flag indicating if the notification has been showed already
	'f_shown' => array(
		'title'=>'Showed',
		'type'=>'bool',
		'subtype'=>'',
		'readonly'=>false,
		'default'=>array(
			"on"=>"null",
			"value"=>false,
		),
	),

	// Flag indicating if the notification should be a popup
	'f_popup' => array(
		'title'=>'Popup Alert', 
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>false,
	),

	// Flag indicating if the notification should be emailed
	'f_email' => array(
		'title'=>'Send Email', 
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>false,
	),

	// Flag indicating if the notification should be text messaged
	'f_sms' => array(
		'title'=>'Send SMS', 
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>false,
	),

	// The actual time when this reminder should execute
	'ts_execute' => array(
		'title'=>'Execute Time', 
		'type'=>'timestamp', 
		'subtype'=>'', 
		'readonly'=>false,
		'default'=>array(
			"on"=>"null",
			"value"=>"now",
		),
	),
);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "My Notifications";
$view->description = "Display all my reminders";
$view->fDefault = true;
$view->view_fields = array("name", "ts_execute");
$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "DESC");
$obj_views[] = $view;
unset($view);
