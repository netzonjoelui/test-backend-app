<?php
/**************************************************************************************
*
*	Object Definition: case
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 20;

$parentField = "parent";

$obj_fields = array();
$obj_fields['name']			    = array('title'=>'Title', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['notes']		    = array('title'=>'Description', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['news']		        = array('title'=>'News', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['date_started']	= array('title'=>'Start Date', 'type'=>'date', 'subtype'=>'', 'readonly'=>false, 'default'=>$default);
$obj_fields['date_deadline']	= array('title'=>'Deadline', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$obj_fields['date_completed']	= array('title'=>'Completed', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_created']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$obj_fields['parent'] = array('title'=>'Parent',
									  'type'=>'object',
									  'subtype'=>'project',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['priority'] = array('title'=>'Priority',
									  'type'=>'fkey',
									  'subtype'=>'project_priorities',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['user_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user');

$obj_fields['customer_id'] = array('title'=>'Contact',
								   'type'=>'object',
								   'subtype'=>'customer');

$obj_fields['template_id'] = array('title'=>'Template',
								   'type'=>'fkey',
                                   'subtype'=>'project_templates',
                                   'readonly'=>true,
								   'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['groups'] = array('title'=>'Groups',
								  'type'=>'fkey_multi',
								  'subtype'=>'project_groups',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																									"ref_table"=>array(
																									"table"=>"project_group_mem", 
																									"this"=>"project_id", 
																									"ref"=>"group_id"
																												   )));

$obj_fields['members'] = array('title'=>'Members',
								  'type'=>'fkey_multi',
								  'subtype'=>'users',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", 
																				"ref_table"=>array(
																				"table"=>"project_membership", 
																				"this"=>"project_id", 
																				"ref"=>"user_id"
																							   )));

$obj_fields['folder_id'] = array('title'=>'Files',
								   'type'=>'object',
								   'subtype'=>'folder',
								   'autocreate'=>true, // Create foreign object automatically
								   'autocreatebase'=>'/System/Project Files', // Where to create (for folders, the path with no trail slash)
								   'autocreatename'=>'id', // the field to pull the new object name from
								   'fkey_table'=>array("key"=>"id", "title"=>"name"));

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "My Open Projects";
$view->description = "";
$view->fDefault = true;
$view->view_fields = array("name", "priority", "date_started", "date_deadline", "date_completed");
$view->conditions[] = new CAntObjectCond("and", "members", "is_equal", "-3");
$view->conditions[] = new CAntObjectCond("and", "date_completed", "is_equal", "");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "My Closed Projects";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "priority", "date_started", "date_deadline", "date_completed");
$view->conditions[] = new CAntObjectCond("and", "members", "is_equal", "-3");
$view->conditions[] = new CAntObjectCond("and", "date_completed", "is_not_equal", "");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_3";
$view->name = "All Projects";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "priority", "date_started", "date_deadline", "date_completed");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_4";
$view->name = "All Open Projects";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "priority", "date_started", "date_deadline", "date_completed");
$view->conditions[] = new CAntObjectCond("and", "date_completed", "is_equal", "");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_5";
$view->name = "Ongoing Projects (no deadline)";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "priority", "date_started", "date_deadline", "date_completed");
$view->conditions[] = new CAntObjectCond("and", "date_deadline", "is_equal", "");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_6";
$view->name = "Late Projects";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "priority", "date_started", "date_deadline", "date_completed");
$view->conditions[] = new CAntObjectCond("and", "date_deadline", "is_less", "now");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);

$child_dacls = array("case", "task", "project_milestone");
?>
