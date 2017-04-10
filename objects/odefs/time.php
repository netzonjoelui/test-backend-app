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
$obj_revision = 15;

$obj_fields = array();
$obj_fields['name']				= array('title'=>'Description', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false, 'required'=>true);
$obj_fields['notes']			= array('title'=>'Notes', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['hours']			= array('title'=>'Hours', 'type'=>'number', 'subtype'=>'double precision', 'required'=>true, 'readonly'=>false);
$obj_fields['date_applied']		= array('title'=>'Date', 'type'=>'date', 'subtype'=>'', 'readonly'=>false, 'required'=>true);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['owner_id'] = array('title'=>'User',
								  'type'=>'object',
								  'subtype'=>'user',
								  'required'=>true,
								  'default'=>$default);

$default = array("value"=>"-3", "on"=>"null");
$obj_fields['creator_id'] = array('title'=>'Entered By',
								  'type'=>'object',
								  'subtype'=>'user',
								  'readonly'=>true,
								  'default'=>$default);

$obj_fields['task_id'] = array('title'=>'Task',
									  'type'=>'object',
									  'subtype'=>'task',
									  'readonly'=>false);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "My Time";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("date_applied", "owner_id", "hours", "name", "task_id");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("date_applied", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "My Team's Time";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("date_applied", "owner_id", "hours", "name", "task_id");
$view->conditions[] = new CAntObjectCond("and", "owner_id.team_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("date_applied", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_3";
$view->name = "All Time";
$view->description = "";
$view->fDefault = true;
$view->view_fields = array("date_applied", "owner_id", "hours", "name", "task_id");
$view->sort_order[] = new CAntObjectSort("date_applied", "desc");
$obj_views[] = $view;
unset($view);

// Aggregates
$aggregates = array();
$agg = new stdClass();
$agg->field = "task_id";
$agg->refField = "cost_actual";
$agg->calcField = "hours";
$agg->type = "sum";
$aggregates[] = $agg;
?>
