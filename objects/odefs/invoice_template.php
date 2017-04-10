<?php
/**************************************************************************************
*
*	Object Definition: invoice
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 9;

$obj_fields = array();
$obj_fields['name']				= array('title'=>'Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['company_name']		= array('title'=>'Company Name', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['company_slogan']	= array('title'=>'Company Slogan', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['notes_line1']		= array('title'=>'Notes - Line 1', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['notes_line2']		= array('title'=>'Notes - Line 2', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['footer_line1']			= array('title'=>'Footer', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);


$obj_fields['company_logo'] = array('title'=>'Logo',
									  'type'=>'object',
									  'subtype'=>'file',
									  'fkey_table'=>array("key"=>"id", "title"=>"file_title"));
// Timestamps
$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$obj_fields['owner_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user');


// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Templates";
$view->description = "View All Invoice Templates";
$view->fDefault = true;
$view->view_fields = array("name", "company_name");
//$view->conditions[] = new CAntObjectCond("and", "date_start", "is_greater_or_equal", "now");
$view->sort_order[] = new CAntObjectSort("name", "desc");
$obj_views[] = $view;
unset($view);
?>
