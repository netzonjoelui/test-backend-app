<?php
/**************************************************************************************
*
*	Object Definition: activity
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 40;
$storeRevisions = false; // no need for revisins to be stored

$obj_fields = array(
	"name" => array(
		'title'=>'Title',
		'type'=>'text', 
		'subtype'=>'256', 
		'readonly'=>false, 
		"required"=>true,
	),

	"notes" => array(
		'title'=>'Details',
		'type'=>'text', 
		'subtype'=>'', 
		'readonly'=>false, 
	),

	"user_name" => array(
		'title'=>'User Name',
		'type'=>'text', 
		'subtype'=>'128', 
		'readonly'=>true, 
	),

	"f_readonly" => array(
		'title'=>'Read Only',
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>true, 
	),

	"f_private" => array(
		'title' => 'Private',
		'type' => 'bool', 
		'subtype' => '', 
		'readonly' => true,
		'default' => array(
			"value"=>'f', 
			"on"=>"null",
		),
	),

	"direction" => array(
		'title'=>'Direction',
		'type'=>'text', 
		'subtype'=>'1', 
		'readonly'=>false, 
		'optional_values'=>array(
			"n"=>"None", 
			"i"=>"Incoming", 
			"o"=>"Outgoing",
		),
	),

	"level" => array(
		'title'=>'Level',
		'type'=>'number', 
		'subtype'=>'integer', 
		'readonly'=>true, 
		'default'=>array(
			"value"=>"3", 
			"on"=>"null",
		),
	),
    
    // What action was done
	"verb" => array(
		'title' => 'Action',
		'type' => 'text', 
		'subtype' => '32', 
		'readonly' => true,
		'default' => array(
			"value"=>'create', 
			"on"=>"null",
		),
	),
    
    // Optional reference to object that was used to perform the action/verb
    'verb_object' => array(
        'title'=>'Origin', 
        'type'=>'object', 
        'subtype'=>'', 
        'readonly'=>true,
    ),
    
    // Who/what did the action
    'subject' => array(
        'title'=>'Subject', 
        'type'=>'object', 
        'subtype'=>'', 
        'readonly'=>true,
    ),
    
    // What the action was done to
    'obj_reference' => array(
        'title'=>'Reference',
		'type'=>'object',
		'subtype'=>'',
		'readonly'=>true,
    ),
    
    

    // File attachments
	'attachments' => array(
		'title'=>'Attachments',
		'type'=>'object_multi',
		'subtype'=>'file',
	),
    
);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'When', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>false, 'default'=>$default);

$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$obj_fields['user_id'] = array('title'=>'User',
									  'type'=>'object',
									  'subtype'=>'user',
								  	  'default'=>array("value"=>"-3", "on"=>"null"));

$obj_fields['type_id'] = array('title'=>'Type',
									  'type'=>'fkey',
									  'subtype'=>'activity_types',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"),
								  	   "required"=>true);


// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Activity";
$view->description = "";
$view->fDefault = true;
$view->view_fields = array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference");
$view->conditions[] = new CAntObjectCond("and", "level", "is_greater_or_equal", "3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "My Team Activity";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference");
$view->conditions[] = new CAntObjectCond("and", "user_id.team_id", "is_equal", "-3");
$view->conditions[] = new CAntObjectCond("and", "level", "is_greater_or_equal", "3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_3";
$view->name = "My Activity";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference");
$view->conditions[] = new CAntObjectCond("and", "user_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_4";
$view->name = "Tasks";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference");
$view->conditions[] = new CAntObjectCond("and", "type_id", "is_equal", "Task");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_5";
$view->name = "Comments";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference");
$view->conditions[] = new CAntObjectCond("and", "type_id", "is_equal", "Comment");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_6";
$view->name = "Status Updates";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference");
$view->conditions[] = new CAntObjectCond("and", "type_id", "is_equal", "Status Update");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_7";
$view->name = "Phone Calls";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference");
$view->conditions[] = new CAntObjectCond("and", "type_id", "is_equal", "Phone Call");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_8";
$view->name = "Calendar Events";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference");
$view->conditions[] = new CAntObjectCond("and", "type_id", "is_equal", "Event");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_9";
$view->name = "Email";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference");
$view->conditions[] = new CAntObjectCond("and", "type_id", "is_equal", "Email");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);
