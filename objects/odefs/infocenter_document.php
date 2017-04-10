<?php
/**************************************************************************************
*
*	Object Definition: infocenter_document 
*
*	Purpose:	KB Document
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 10;

$listTitle = "title";

$obj_fields = array();
$obj_fields['title']			= array('title'=>'Title', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['keywords']			= array('title'=>'Keywords', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['author_name']		= array('title'=>'Author Names', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['body']				= array('title'=>'Body', 'type'=>'text', 'subtype'=>'html', 'readonly'=>false);
$obj_fields['rating']			= array('title'=>'Rating', 'type'=>'number', 'subtype'=>'integer', 'readonly'=>false);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Created', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$obj_fields['video_file_id'] = array('title'=>'Video File',
									  'type'=>'object',
									  'subtype'=>'file',
									  'fkey_table'=>array("key"=>"id", "title"=>"file_title"));

$default = array("value"=>"-3", "on"=>"null");
$obj_fields['owner_id'] = array('title'=>'Owner', 'type'=>'object', 'subtype'=>'user', 'readonly'=>false, 'default'=>$default);

$obj_fields['groups'] = array('title'=>'Groups',
								  'type'=>'fkey_multi',
								  'subtype'=>'ic_groups',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																									"ref_table"=>array(
																									"table"=>"ic_document_group_mem", 
																									"this"=>"document_id", 
																									"ref"=>"group_id"
																								   )));

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Documents";
$view->description = "All InfoCenter Documents";
$view->fDefault = true;
$view->view_fields = array("title", "keywords", "ts_updated", "owner_id");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("title", "asc");
$obj_views[] = $view;
unset($view);

?>
