<?php
/**************************************************************************************
*
*	Object Definition: approval
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 42;
$defaultActivityLevel = 4;

$obj_fields = array();
$obj_fields['name']				= array('title'=>'Subject', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['notes']			= array('title'=>'Details', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['workflow_action_id'] = array('title'=>'Workflow Action', 'type'=>'integer', 'subtype'=>'', 'readonly'=>true);

$default = array("value"=>"awaiting", "on"=>"null");
$obj_fields['status']			= array('title'=>'Status', 'type'=>'text', 'subtype'=>'32', 'readonly'=>true, 'default'=>$default,
										'optional_values'=>array("awaiting"=>"Awaiting Approval", "approved"=>"Approved", "declined"=>"Declined"));

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Time Requested', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$obj_fields['ts_status_change']	= array('title'=>'Time Status Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true);

// References
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['requested_by'] = array('title'=>'Requested By',
									  'type'=>'object',
									  'subtype'=>'user',
									  'readonly'=>true,
									  'required'=>true,
								  	  'default'=>$default);

$obj_fields['owner_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user',
									  'readonly'=>false,
								  	  'required'=>true);

$obj_fields['obj_reference'] = array('title'=>'Reference',
									  'type'=>'object',
									  'subtype'=>'',
									  'readonly'=>true);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Awaiting My Approval";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "status", "requested_by", "owner_id", "ts_entered");
$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->conditions[] = new CAntObjectCond("and", "status", "is_equal", "awaiting");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "All Approval Requests";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "status", "requested_by", "owner_id", "ts_entered");
//$view->conditions[] = new CAntObjectCond("and", "user_id.team_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_3";
$view->name = "My Approved";
$view->description = "";
$view->fDefault = true;
$view->view_fields = array("name", "status", "requested_by", "owner_id", "ts_entered");
$view->conditions[] = new CAntObjectCond("and", "user_id", "is_equal", "-3");
$view->conditions[] = new CAntObjectCond("and", "status", "is_equal", "approved");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);
