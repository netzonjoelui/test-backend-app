<?php
/**************************************************************************************
*
*	Object Definition: content_feed 
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 17;
$listTitle = "title";

$obj_fields = array();
$obj_fields['title']            = array('title'=>'Title', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['description']      = array('title'=>'Description', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['sort_by']			= array('title'=>'Publish Sort', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['limit_num']		= array('title'=>'Publish Num', 'type'=>'text', 'subtype'=>'8', 'readonly'=>false);
$obj_fields['subs_title']		= array('title'=>'Subscribe Label', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['subs_body']		= array('title'=>'Subscribe Body', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_created']	= array('title'=>'Created', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);


// References
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['user_id'] = array('title'=>'User', 'type'=>'object', 'subtype'=>'user', 'readonly'=>true, 'default'=>$default);

$obj_fields['groups'] = array('title'=>'Groups',
								  'type'=>'fkey_multi',
								  'subtype'=>'xml_feed_groups',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																									"ref_table"=>array(
																									"table"=>"xml_feed_group_mem", 
																									"this"=>"feed_id", 
																									"ref"=>"group_id")
									)
);

// Feeds can be linked to a specific site
$obj_fields["site_id"] = array(
	'title'=>'Site', 
	'type'=>'object', 
	'subtype'=>'cms_site', 
	'readonly'=>false,
);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Feeds";
$view->description = "All Content Feeds";
$view->fDefault = true;
$view->view_fields = array("title", "ts_created", "groups", "site_id");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("title", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "My Feeds";
$view->description = "My Content Feeds";
$view->fDefault = false;
$view->view_fields = array("title", "ts_created", "groups", "site_id");
$view->conditions[] = new CAntObjectCond("and", "user_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("title", "asc");
$obj_views[] = $view;
unset($view);
?>
