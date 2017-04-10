<?php
/**************************************************************************************
*
*	Object Definition: content_feed_post 
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 21;

$parentField = "parent_id";
$unameSettings = "feed_id:title";
$listTitle = "title";

$obj_fields = array(
	'title' => array(
		'title'=>'Title', 
		'type'=>'text', 
		'subtype'=>'256', 
		'readonly'=>false,
		'required'=>true,
	),

	'author' => array(
		'title'=>'Author', 
		'type'=>'text', 
		'subtype'=>'256', 
		'readonly'=>false,
	),

	'data' => array(
		'title'=>'Body', 'type'=>'text', 'subtype'=>'', 'readonly'=>false
	),

	'image' => array(
		'title'=>'Image', 
		'type'=>'object', 
		'subtype'=>'file', 
		'readonly'=>false
	),

	'f_publish' => array(
		'title'=>'Published', 
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>false,
		'default'=>array("value"=>"f", "on"=>"null"),
	),

	"time_publish" => array(
		'title'=>'Publish After', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>false
	),

	"time_expires" => array(
		'title'=>'Expires', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>false
	),

	"time_entered" => array(
		'title'=>'Post Date', 
		'type'=>'timestamp', 
		'subtype'=>'', 
		'readonly'=>false,
		"default"=>array("value"=>"now", "on"=>"create"),
	),

	"ts_updated" => array(
		'title'=>'Updated', 
		'type'=>'timestamp', 
		'subtype'=>'', 
		'readonly'=>true,
		"default"=>array("value"=>"now", "on"=>"update"),
	),

	"user_id" => array(
		'title'=>'User', 
		'type'=>'object', 
		'subtype'=>'user', 
		'readonly'=>false,
		"default"=>array("value"=>"-3", "on"=>"null"),
	),

	"feed_id" => array(
		'title'=>'Feed', 
		'type'=>'object', 
		'subtype'=>'content_feed', 
		'readonly'=>false,
		'required'=>true,
	),

	// Type: Article, Page, Widget
	/*
	'type_id' => array(
		'title'=>'Type',
		'type'=>'fkey',
		'subtype'=>'object_groupings',
		'fkey_table'=>array(
			"key"=>"id", 
			"title"=>"name", 
			"parent"=>"parent_id",
			"ref_table"=>array(
				"table"=>"object_grouping_mem", 
				"this"=>"object_id", 
				"ref"=>"grouping_id",
			),
		),
	),
	 */

	'status_id' => array(
		'title'=>'Status',
		'type'=>'fkey',
		'subtype'=>'object_groupings',
		'required'=> true,
		'fkey_table'=>array(
			"key"=>"id", 
			"title"=>"name", 
			"ref_table"=>array(
				"table"=>"object_grouping_mem", 
				"this"=>"object_id", 
				"ref"=>"grouping_id"
			)
		)
	),

	// Type : Post, Page, Widget
	'type' => array(
		'title'=>'Type',
		'type'=>'text',
		'subtype'=>'32',
		'optional_values'=>array("post"=>"Post", "page"=>"Page", "widget"=>"Widget")
	),

	// Posts can be linked to sites
	"site_id" => array(
		'title'=>'Site', 
		'type'=>'object', 
		'subtype'=>'cms_site', 
		'readonly'=>false,
	),

	"categories" => array(
		'title'=>'Categories', 
		'type'=>'fkey_multi', 
		'subtype'=>'xml_feed_post_categories', 
		'fkey_table'=>array(
			"key"=>"id", 
			"title"=>"name", 
			"parent"=>"parent_id", 
			"filter"=>array("feed_id"=>"feed_id"),
			"ref_table"=>array(
				"table"=>"xml_feed_post_cat_mem", 
				"this"=>"post_id", 
				"ref"=>"category_id"
			),
		),
	),

	// The parent post
	"parent_id" => array(
		'title'=>'Parent', 
		'type'=>'object', 
		'subtype'=>'content_feed_post', 
		'readonly'=>false,
	),

);


// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Posts";
$view->description = "All Content Feed Posts";
$view->fDefault = true;
$view->view_fields = array("title", "status_id", "user_id", "time_entered", "ts_updated");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("time_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "Drafts";
$view->description = "Drafts";
$view->fDefault = false;
$view->view_fields = array("title", "user_id", "time_entered", "ts_updated");
$view->conditions[] = new CAntObjectCond("and", "f_publish", "is_not_equal", "t");
$view->sort_order[] = new CAntObjectSort("time_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_3";
$view->name = "Published";
$view->description = "All published posts";
$view->fDefault = false;
$view->view_fields = array("title", "user_id", "time_entered", "ts_updated");
$view->conditions[] = new CAntObjectCond("and", "f_publish", "is_equal", "t");
$view->sort_order[] = new CAntObjectSort("time_entered", "desc");
$obj_views[] = $view;
unset($view);
