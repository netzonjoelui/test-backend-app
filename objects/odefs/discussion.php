<?php
/**************************************************************************************
*
*	Object Definition: discussion
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 14;
$defaultActivityLevel = 5;

$obj_fields = array();
$obj_fields['name']				= array('title'=>'Subject / Topic', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['message']			= array('title'=>'Message', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['notified']			= array('title'=>'Invited', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['notify']			= array('title'=>'Invite', 'type'=>'text', 'subtype'=>'', 'readonly'=>true); // buffer used to send notifications

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Created', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['owner_id'] = array('title'=>'User', 'type'=>'object', 'subtype'=>'user', 'readonly'=>true, 'default'=>$default);

$obj_fields['obj_reference'] = array('title'=>'Concerning',
									  'type'=>'object',
									  'subtype'=>'',
									  'readonly'=>true);

$obj_fields['members'] = array('title'=>'Notify',
								  'type'=>'object_multi',
								  'subtype'=>'user');

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Discussions";
$view->description = "Discussions";
$view->fDefault = true;
$view->view_fields = array("name", "ts_updated", "ts_entered", "owner_id", "obj_reference");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_updated", "desc");
$obj_views[] = $view;
unset($view);

?>
