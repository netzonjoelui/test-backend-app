<?php
/**************************************************************************************
*
*	Object Definition: task
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 44;

$inheritDaclRef = "project";
$recurRules = array("field_time_start"=>"", "field_time_end"=>"", 
					  "field_date_start"=>"start_date", "field_date_end"=>"deadline", 
					  "field_recur_id"=>"recur_id");

$obj_fields = array();
$obj_fields['name']				= array('title'=>'Name', 'type'=>'text', 'subtype'=>'', 'readonly'=>false, 'required'=>true);
$obj_fields['notes']			= array('title'=>'Notes', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['done'] 			= array('title'=>'Completed', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['entered_by']		= array('title'=>'Entered By', 'type'=>'text', 'subtype'=>'128', 'readonly'=>true);
$obj_fields['cost_estimated']	= array('title'=>'Estimated Time', 'type'=>'number', 'subtype'=>'double precision', 'readonly'=>false);
$obj_fields['cost_actual'] 		= array('title'=>'Actual Time', 'type'=>'number', 'subtype'=>'double precision', 'readonly'=>true);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['date_entered']	= array('title'=>'Date Entered', 'type'=>'date', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

//$default = array("value"=>"now", "on"=>"create");
//$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"now", "on"=>"null", "where"=>array("done"=>'t'));
$obj_fields['date_completed']	= array('title'=>'Date Completed', 'type'=>'date', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$obj_fields['deadline']	= array('title'=>'Date Due', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);

$obj_fields['start_date']	= array('title'=>'Start Date', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);

// References
$obj_fields['milestone_id'] = array('title'=>'Milestone',
									  'type'=>'object',
									  'subtype'=>'project_milestone',
									  "filter"=>array("project"=>"project_id"), // this.project = project_milestone.project_id
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['depends_task_id'] = array('title'=>'Depends On',
								  'type'=>'object',
								  'subtype'=>'task',
								  "filter"=>array("project_id"=>"project_id"), // this.project = project_milestone.project_id
								  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$default = array("value"=>"-3", "on"=>"null");
$obj_fields['user_id'] = array('title'=>'Assigned To',
								  'type'=>'object',
								  'subtype'=>'user',
								  'default'=>$default);

$default = array("value"=>"-3", "on"=>"null");
$obj_fields['creator_id'] = array('title'=>'Creator',
								  'type'=>'object',
								  'subtype'=>'user',
								  'readonly'=>true,
								  'default'=>$default);

$obj_fields['priority'] = array('title'=>'Priority',
								  'type'=>'fkey',
								  'subtype'=>'project_priorities',
								  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['project'] = array('title'=>'Project',
								  'type'=>'object',
								  'subtype'=>'project');

$obj_fields['case_id'] = array('title'=>'Case',
								  'type'=>'object',
								  'subtype'=>'case');

/*
$obj_fields['project'] = array('title'=>'Project',
								  'type'=>'fkey',
								  'subtype'=>'projects',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent"));
*/

$obj_fields['contact_id'] = array('title'=>'Contact',
								   'type'=>'object',
								   'subtype'=>'contact_personal');

$obj_fields['customer_id'] = array('title'=>'Contact',
								   'type'=>'object',
								   'subtype'=>'customer');

$obj_fields['story_id'] = array('title'=>'Story',
								   'type'=>'object',
								   'subtype'=>'project_story');

$obj_fields['template_task_id'] = array('title'=>'Template Task',
								   'readonly'=>true,
								   'type'=>'fkey',
								   'subtype'=>'project_template_tasks',
								   'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['position_id'] = array('title'=>'Position',
								   'readonly'=>true,
								   'type'=>'fkey',
								   'subtype'=>'project_positions',
								   'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['category'] = array('title'=>'Category',
								  'type'=>'fkey',
								  'private'=>true,
								  'subtype'=>'object_groupings',
								  'fkey_table'=>array(
										"key"=>"id", 
										"title"=>"name", 
										"parent"=>"parent_id", 
										"filter"=>array("user_id"=>"user_id"),
										"ref_table"=>array(
											"table"=>"object_grouping_mem", 
											"this"=>"object_id", 
											"ref"=>"grouping_id"
										)
									)
								);

/*
$obj_fields['recur_id'] = array('title'=>'Recurrence Parent',
								   'readonly'=>true,
								   'type'=>'fkey',
								   'subtype'=>'project_tasks_recurring',
								   'fkey_table'=>array("key"=>"id", "title"=>"name"));
*/

$obj_fields['recur_id'] = array('title'=>'Recurrence',
										   'readonly'=>true,
										   'type'=>'integer');

$obj_fields['obj_reference'] = array('title'=>'Reference',
									  'type'=>'object',
									  'subtype'=>'',
									  'readonly'=>true);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "My Incomplete Tasks";
$view->description = "Incomplete tasks assigned to me";
$view->fDefault = true;
$view->view_fields = array("name", "project", "priority",  "deadline", "done", "user_id");
$view->conditions[] = new CAntObjectCond("and", "user_id", "is_equal", "-3");
$view->conditions[] = new CAntObjectCond("and", "done", "is_not_equal", "t");
$view->sort_order[] = new CAntObjectSort("date_entered", "desc");
$view->sort_order[] = new CAntObjectSort("deadline", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "My Incomplete Tasks (due today)";
$view->description = "Incomplete tasks assigned to me that are due today";
$view->fDefault = true;
$view->view_fields = array("name", "project", "priority",  "deadline", "done", "user_id");
$view->conditions[] = new CAntObjectCond("and", "user_id", "is_equal", "-3");
$view->conditions[] = new CAntObjectCond("and", "done", "is_not_equal", "t");
$view->conditions[] = new CAntObjectCond("and", "deadline", "is_less_or_equal", "now");
$view->sort_order[] = new CAntObjectSort("date_entered", "desc");
$view->sort_order[] = new CAntObjectSort("deadline", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_3";
$view->name = "All My Tasks";
$view->description = "All tasks assigned to me";
$view->fDefault = false;
$view->view_fields = array("name", "project", "priority",  "deadline", "done", "user_id");
$view->conditions[] = new CAntObjectCond("and", "user_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("date_completed", "desc");
$view->sort_order[] = new CAntObjectSort("deadline", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_4";
$view->name = "Tasks I Have Assigned";
$view->description = "Tasks that were created by me but assigned to someone else";
$view->fDefault = true;
$view->view_fields = array("name", "project", "priority",  "deadline", "done", "user_id");
$view->conditions[] = new CAntObjectCond("and", "creator_id", "is_equal", "-3");
$view->conditions[] = new CAntObjectCond("and", "user_id", "is_not_equal", "-3");
$view->conditions[] = new CAntObjectCond("and", "done", "is_not_equal", "t");
$view->sort_order[] = new CAntObjectSort("date_entered", "desc");
$view->sort_order[] = new CAntObjectSort("deadline", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_5";
$view->name = "All Incomplete Tasks";
$view->description = "All Tasks that have not yet been completed";
$view->fDefault = false;
$view->view_fields = array("name", "project", "priority",  "deadline", "done", "user_id");
$view->conditions[] = new CAntObjectCond("and", "done", "is_not_equal", "t");
$view->sort_order[] = new CAntObjectSort("date_completed", "desc");
$view->sort_order[] = new CAntObjectSort("deadline", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_6";
$view->name = "All Tasks";
$view->description = "All tasks";
$view->fDefault = false;
$view->view_fields = array("name", "project", "priority",  "deadline", "done", "user_id");
$view->sort_order[] = new CAntObjectSort("date_completed", "desc");
$view->sort_order[] = new CAntObjectSort("deadline", "asc");
$obj_views[] = $view;
unset($view);

// Aggregates
$aggregates = array();
$agg = new stdClass();
$agg->field = "story_id";
$agg->refField = "cost_actual";
$agg->calcField = "cost_actual";
$agg->type = "sum";
$aggregates = array($agg);
$aggregates[] = $agg;
?>
