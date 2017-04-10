<?php
/**************************************************************************************
*
*    Object Definition: calendar_event
*
*    Purpose:    $this refers to CAntObjectFields class which inlcludes this file
*
*    Author:     joe, sky.stebnicki@aereus.com
*                Copyright (c) 2010 Aereus Corporation, All Rights Reserved. 
*
**************************************************************************************/
$obj_revision = 33;

$isPrivate = false;
$defaultActivityLevel = 1;
$recurRules= array("field_time_start"=>"ts_start", "field_time_end"=>"ts_end", 
                          "field_date_start"=>"ts_start", "field_date_end"=>"ts_end", 
                          "field_recur_id"=>"recur_id");

$obj_fields = array();
$obj_fields['name'] = array('title'=>'Name', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['location'] = array('title'=>'Location', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['notes'] = array('title'=>'Notes', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['start_block'] = array('title'=>'Start Minute', 'type'=>'integer', 'subtype'=>'', 'readonly'=>true);
$obj_fields['end_block'] = array('title'=>'End Minute', 'type'=>'integer', 'subtype'=>'', 'readonly'=>true);
$obj_fields['all_day'] = array('title'=>'All Day', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$optional_vals = array("2"=>"Public", "1"=>"Private");
$obj_fields['sharing'] = array('title'=>'Sharing', 'type'=>'integer', 'subtype'=>'', 'optional_values'=>$optional_vals, 'readonly'=>false);
$obj_fields['inv_eid'] = array('title'=>'Inv. Eid', 'type'=>'integer', 'subtype'=>'', 'readonly'=>true);
$obj_fields['inv_rev'] = array('title'=>'Inv. Revision', 'type'=>'integer', 'subtype'=>'', 'readonly'=>true);
$obj_fields['inv_uid'] = array('title'=>'Inv. Id', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);

// Timestamps
$obj_fields['date_start']        = array('title'=>'Date Start', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$obj_fields['date_end']         = array('title'=>'Date Start', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);

$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']    = array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"now", "on"=>"null");
$obj_fields['ts_start']        = array('title'=>'Time Start', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>false, 'default'=>$default);
$obj_fields['ts_end']        = array('title'=>'Time End', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>false, 'default'=>$default);

// References
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['user_id'] = array('title'=>'Owner',
                                      'type'=>'object',
                                      'subtype'=>'user',
                                      'default'=>$default);

$obj_fields['recur_id'] = array('title'=>'Recurrance',
                                      'type'=>'integer',
                                        'readonly'=>true);

$obj_fields['recurrence_pattern'] = array('title'=>'Recurrence',
                                           'readonly'=>true,
                                           'type'=>'integer');

// contact_id is deprecated. Use obj_reference field as field reference
$obj_fields['contact_id'] = array('title'=>'Contact',
                                   'type'=>'object',
                                   'subtype'=>'contact_personal');

$obj_fields['calendar'] = array('title'=>'Calendar',
                                   'type'=>'fkey',
                                   'subtype'=>'calendars',
                                   'fkey_table'=>array("key"=>"id", "title"=>"name"));

// customer_id is deprecated. Use obj_reference field as field reference
$obj_fields['customer_id'] = array('title'=>'Customer',
                                   'type'=>'object',
                                   'subtype'=>'customer');


$obj_fields['attendees'] = array('title'=>'Attendees',
                                  'type'=>'object_multi',
                                  'subtype'=>'member');

$obj_fields['obj_reference'] = array('title'=>'Reference',
    'type'=>'object',
    'subtype'=>'',
    'readonly'=>true);


// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Upcoming Events";
$view->description = "Events occurring in the future";
$view->fDefault = true;
$view->view_fields = array("name", "location", "ts_start", "ts_end", "user_id");
//$view->conditions[] = new CAntObjectCond("and", "user_id", "is_equal", "-3");
$view->conditions[] = new CAntObjectCond("and", "ts_start", "is_greater_or_equal", "now");
$view->sort_order[] = new CAntObjectSort("ts_start", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "My Past Events";
$view->description = "Events that occurred in the past";
$view->fDefault = true;
$view->view_fields = array("name", "location", "ts_start", "ts_end", "user_id");
$view->conditions[] = new CAntObjectCond("and", "ts_start", "is_less", "now");
$view->sort_order[] = new CAntObjectSort("ts_start", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_3";
$view->name = "All Events";
$view->description = "All Events";
$view->fDefault = true;
$view->view_fields = array("name", "location", "ts_start", "ts_end", "user_id");
$view->sort_order[] = new CAntObjectSort("ts_start", "desc");
$obj_views[] = $view;
unset($view);
?>
