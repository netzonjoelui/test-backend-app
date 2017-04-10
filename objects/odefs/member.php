<?php
/**************************************************************************************
*
*	Object Definition: member
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2011 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 10;

$obj_fields = array();
$obj_fields['name']			= array('title'=>'Member', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['role']			= array('title'=>'Role', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['f_invsent']	= array('title'=>'Inv. Sent', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_accepted']	= array('title'=>'Accepted', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_required']	= array('title'=>'Required', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Date', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);


$obj_fields['obj_member'] = array('title'=>'Member',
									  'type'=>'object',
									  'subtype'=>'',
									  'readonly'=>true);

$obj_fields['obj_reference'] = array('title'=>'Reference',
									  'type'=>'object',
									  'subtype'=>'',
									  'readonly'=>true);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Members";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "role", "f_accepted", "ts_entered", "obj_reference");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
?>
