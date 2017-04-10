<?php
/**
 * status_update is essentually a wall post
 */
$obj_revision = 9;

$listTitle = "id";
$defaultActivityLevel = 5; // Display all comments

$obj_fields = array();
$obj_fields['comment']		= array('title'=>'Comment', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['notified']		= array('title'=>'Notified', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['notify']		= array('title'=>'Send To', 'type'=>'text', 'subtype'=>'', 'readonly'=>true); // Object data comma separated

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Date', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['owner_id'] = array('title'=>'User', 'type'=>'object', 'subtype'=>'user', 'default'=>$default);

$obj_fields['obj_reference'] = array('title'=>'Concerning',
									  'type'=>'object',
									  'subtype'=>'',
									  'readonly'=>true);

$obj_fields['sent_by'] = array('title'=>'Sent By',
									  'type'=>'object',
									  'subtype'=>'',
									  'readonly'=>true);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "News Feed";
$view->description = "";
$view->fDefault = true;
$view->view_fields = array("owner_id", "ts_entered", "obj_reference", "comment", "notified", "sent_by");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "DESC");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "My Status Updates";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("owner_id", "ts_entered", "obj_reference", "comment", "notified", "sent_by");
$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "DESC");
$obj_views[] = $view;
unset($view);
