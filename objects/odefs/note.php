<?php
/**************************************************************************************
*
*	Object Definition: note 
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 17;

$isPrivate = true;
$defaultActivityLevel = 1;

$obj_fields = array();
$obj_fields['name']				= array('title'=>'Title', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['website']			= array('title'=>'Website', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['body']				= array('title'=>'Body', 'type'=>'text', 'subtype'=>'html', 'readonly'=>false);
$default = array("value"=>"html", "on"=>"null");
$obj_fields['body_type'] 		= array('title'=>'Type', 'type'=>'text', 'subtype'=>'32', 'readonly'=>true, 'default'=>$default);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Created', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['user_id'] = array('title'=>'User', 'type'=>'object', 'subtype'=>'user', 'readonly'=>true, 'default'=>$default);

$obj_fields['groups'] = array(
    'title'=>'Groups',
    'type'=>'fkey_multi',
    'subtype'=>'user_notes_categories',
    'fkey_table'=>array(
          "key"=>"id", 
          "title"=>"name", 
          "parent"=>"parent_id", 
          "filter"=>array(
            "user_id"=>"user_id"
          ),
          "ref_table"=>array(
              "table"=>"user_notes_cat_mem", 
              "this"=>"note_id", 
              "ref"=>"category_id"
          )
      )
    );

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "My Notes";
$view->description = "User notes";
$view->fDefault = true;
$view->view_fields = array("name", "ts_entered", "body");
$view->conditions[] = new CAntObjectCond("and", "user_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);