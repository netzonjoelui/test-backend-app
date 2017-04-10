<?php
/**************************************************************************************
*
*	Object Definition: project_milestone 
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 10;

$inheritDaclRef = "project_id";

$obj_fields = array();
$obj_fields['name']			    = array('title'=>'Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['notes']		    = array('title'=>'Notes', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$default = array("value"=>"f", "on"=>"null");
$obj_fields['f_completed']		= array('title'=>'Completed', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false, "default"=>$default);

// Timestamps
$default = array("value"=>"now", "on"=>"null");
$obj_fields['date_start']= array('title'=>'Date Start', 'type'=>'date', 'subtype'=>'', 'readonly'=>false, "default"=>$default);
$obj_fields['deadline']	= array('title'=>'Deadline', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);


// References
$obj_fields['project_id'] = array('title'=>'Project',
									  'type'=>'object',
									  'subtype'=>'project',
									  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent"));

$default = array("value"=>"-3", "on"=>"null");
$obj_fields['user_id'] = array('title'=>'Owner', 'type'=>'object', 'subtype'=>'user', 'default'=>$default);

$default_form_xml = "";


// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Default View: All Milestones";
$view->description = "";
$view->fDefault = true;
$view->view_fields = array("name", "deadline", "user_id", "project_id", "f_completed");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("deadline", "desc");
$obj_views[] = $view;
unset($view);

?>
