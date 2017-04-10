<?php
/**************************************************************************************
*
*	Object Definition: product_family
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2011 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 9;

$obj_fields = array();
$obj_fields['name']			    = array('title'=>'Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['notes']		    = array('title'=>'Notes', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_available']		= array('title'=>'Available', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false, 'default'=>array("value"=>"t", "on"=>"null"));

$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$obj_fields['image_id'] = array('title'=>'Image',
									  'type'=>'object',
									  'subtype'=>'file',
									  'fkey_table'=>array("key"=>"id", "title"=>"file_title"));


// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Default View: All Product Families";
$view->description = "";
$view->fDefault = true;
$view->view_fields = array("name", "ts_updated", "ts_entered");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("name", "desc");
$obj_views[] = $view;
unset($view);

?>
