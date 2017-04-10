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
$obj_revision = 36;

$listTitle = "title";
$inheritDaclRef = "project_id";

$obj_fields = array();
$obj_fields['title']			= array('title'=>'Title', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['description']		= array('title'=>'Description', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$default = array("value"=>"<%username%>", "on"=>"create");
$obj_fields['created_by']		= array('title'=>'Entered By', 'type'=>'text', 'subtype'=>'128', 'readonly'=>true, 'default'=>$default);
$obj_fields['notify_email']		= array('title'=>'Notify Email', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['date_reported']	= array('title'=>'Date Entered', 'type'=>'date', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$obj_fields['related_bug'] = array('title'=>'Related Case',
									  'type'=>'object',
									  'subtype'=>'case');

$obj_fields['status_id'] = array('title'=>'Status',
									  'type'=>'fkey',
									  'subtype'=>'project_bug_status',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));
									  //'fkey_table'=>array("key"=>"id", "title"=>"name", "filter"=>array("project_id"=>"project_id")));

$obj_fields['severity_id'] = array('title'=>'Severity',
									  'type'=>'fkey',
									  'subtype'=>'project_bug_severity',
									  'fkey_table'=>array("key"=>"id", "title"=>"name")); // filter = referenced_field => object field
										//"filter"=>array("project_id"=>"project_id")

$obj_fields['owner_id'] = array(
	'title'=>'Owner',
	'type'=>'object',
	'subtype'=>'user',
	'default'=>array("value"=>"-3", "on"=>"null"),
);

$obj_fields['project_id'] = array('title'=>'Project',
									  'type'=>'object',
									  'subtype'=>'project');

$obj_fields['type_id'] = array('title'=>'Type',
									  'type'=>'fkey',
									  'subtype'=>'project_bug_types',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));
									  //'fkey_table'=>array("key"=>"id", "title"=>"name", "filter"=>array("project_id"=>"project_id")));

$obj_fields['customer_id'] = array('title'=>'Contact',
								   'type'=>'object',
								   'subtype'=>'customer');

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Open Cases";
$view->description = "All that have not yet been closed";
$view->fDefault = true;
$view->view_fields = array("title", "status_id", "type_id", "severity_id", "owner_id", "created_by", "project_id", "date_reported");
$view->conditions[] = new CAntObjectCond("and", "status_id", "is_not_equal", "Closed: Resolved");
$view->conditions[] = new CAntObjectCond("and", "status_id", "is_not_equal", "Closed: Unresolved");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$view->sort_order[] = new CAntObjectSort("severity_id", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "My Cases";
$view->description = "Cases Assigned To Me";
$view->fDefault = false;
$view->view_fields = array("title", "status_id", "type_id", "severity_id", "owner_id", "created_by", "project_id", "date_reported");
$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$view->sort_order[] = new CAntObjectSort("severity_id", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_3";
$view->name = "All Cases";
$view->description = "All cases in any status";
$view->fDefault = false;
$view->view_fields = array("title", "status_id", "type_id", "severity_id", "owner_id", "created_by", "project_id", "date_reported");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$view->sort_order[] = new CAntObjectSort("severity_id", "desc");
$obj_views[] = $view;
unset($view);
