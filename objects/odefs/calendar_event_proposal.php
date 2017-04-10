<?php
/**************************************************************************************
*
*	Object Definition: calendar_event_proposal
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 14;

$isPrivate = true;
$defaultActivityLevel = 1;

$obj_fields = array();
$obj_fields['name']				= array('title'=>'Name', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['location']			= array('title'=>'Location', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['notes']			= array('title'=>'Notes', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_closed']			= array('title'=>'Closed/Converted', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);

$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Time Created', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['user_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user',
									  'default'=>$default);

$obj_fields['event_id'] = array('title'=>'Event',
									  'type'=>'fkey',
									  'subtype'=>'calendar_events',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"),
								  	  'readonly'=>true);

$obj_fields['attendees'] = array('title'=>'Attendees',
                                  'type'=>'object_multi',
                                  'subtype'=>'member');

$obj_fields['status_id'] = array('title'=>'Status',
								  'type'=>'fkey',
								  'subtype'=>'object_groupings',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																									"ref_table"=>array(
																									"table"=>"object_grouping_mem", 
																									"this"=>"object_id", 
																									"ref"=>"grouping_id"
																												   )));


// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Meeting Proposals";
$view->description = "Meeting proposals that are still in process";
$view->fDefault = true;
$view->view_fields = array("name", "location", "status_id", "ts_updated");
//$view->conditions[] = new CAntObjectCond("and", "user_id", "is_equal", "-3");
$view->conditions[] = new CAntObjectCond("and", "f_closed", "is_not_equal", "t");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "Closed Proposals";
$view->description = "Meeting proposals that have been closed and/or converted to events.";
$view->fDefault = true;
$view->view_fields = array("name", "location", "status_id", "ts_updated");
$view->conditions[] = new CAntObjectCond("and", "f_closed", "is_equal", "t");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_3";
$view->name = "All Meeting Proposals";
$view->description = "All Meeting Proposals";
$view->fDefault = true;
$view->view_fields = array("name", "location", "status_id", "ts_updated");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);
?>
