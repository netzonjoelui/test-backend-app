<?php
/**************************************************************************************
*
*	Object Definition: email_message_attachment
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 9;

$isPrivate = true;
$defaultActivityLevel = 0;

$obj_fields = array();
$obj_fields['filename'] 		= array('title'=>'File Name', 'type'=>'text', 'subtype'=>'512', 'readonly'=>true);
$obj_fields['name'] 			= array('title'=>'Name', 'type'=>'text', 'subtype'=>'512', 'readonly'=>true);
$obj_fields['content_type']		= array('title'=>'Content Type', 'type'=>'text', 'subtype'=>'512', 'readonly'=>true);
$obj_fields['encoding'] 		= array('title'=>'Content Transfer Encoding', 'type'=>'text', 'subtype'=>'128', 'readonly'=>true);
$obj_fields['content_id']		= array('title'=>'Content Id', 'type'=>'text', 'subtype'=>'512', 'readonly'=>true);
// TODO: add column to table
//$obj_fields['description'] 		= array('title'=>'Content Description', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['disposition'] 		= array('title'=>'Content Disposition', 'type'=>'text', 'subtype'=>'128', 'readonly'=>true);
$obj_fields['size'] 			= array('title'=>'Size', 'type'=>'number', 'subtype'=>'integer', 'readonly'=>true);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered'] = array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_changed']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['owner_id'] = array('title'=>'User', 'type'=>'object', 'subtype'=>'user', 'default'=>$default);

$obj_fields['message_id'] = array('title'=>'Message', 'type'=>'object', 'subtype'=>'email_message', 'required'=>true);

$obj_fields['file_id'] = array('title'=>'Download',
									  'type'=>'object',
									  'subtype'=>'file');

// Default form layouts
$default_form_xml = "";

$default_form_mobile_xml = "";

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Email Attachments";
$view->description = "My Email Message Attachments";
$view->fDefault = true;
$view->view_fields = array("name", "filename", "phone_home", "phone_work", "email_default", "image_id");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);
?>
