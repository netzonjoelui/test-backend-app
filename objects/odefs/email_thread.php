<?php
/**************************************************************************************
*
*	Object Definition: email_message
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 29;

$listTitle = "subject";
$isPrivate = true;
$defaultActivityLevel = 1;
$storeRevisions = false; // no need for revisins to be stored

$obj_fields = array();
$obj_fields['subject']			= array('title'=>'Subject', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['body']				= array('title'=>'Body', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['keywords']			= array('title'=>'Keywords', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['senders']			= array('title'=>'From', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['receivers']		= array('title'=>'To', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$default = array("value"=>"0", "on"=>"null");
$obj_fields['num_attachments']	= array('title'=>'Num Attachments', 'type'=>'integer', 'subtype'=>'', 'readonly'=>true, "default"=>$default);
$obj_fields['num_messages']		= array('title'=>'Num Messages', 'type'=>'integer', 'subtype'=>'', 'readonly'=>true);
$obj_fields['f_seen']			= array('title'=>'Seen', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_flagged']		= array('title'=>'Flagged', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);

// Timestamps
$default = array("value"=>"now", "on"=>"update");
$obj_fields['time_updated']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_delivered']	= array('title'=>'Time Delivered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['owner_id'] = array(
    'title'=>'User',
    'type'=>'object',
    'subtype'=>'user',
    'default'=>$default
);
/*
$obj_fields['mailbox_id'] = array('title'=>'Groups',
									  'type'=>'fkey_multi',
									  'subtype'=>'email_mailboxes',
									  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_box"),
								      'readonly'=>false);
*/

$obj_fields['mailbox_id'] = array(
    'title'=>'Groups',
    'type'=>'fkey_multi',
    'subtype'=>'email_mailboxes',
    'fkey_table'=>array(
        "key"=>"id", 
        "title"=>"name", 
        "parent"=>"parent_box",
        "filter"=>array(
            "user_id"=>"owner_id"
        ),
        "ref_table"=>array(
            "table"=>"email_thread_mailbox_mem", 
            "this"=>"thread_id", 
            "ref"=>"mailbox_id"
        )
    )
);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Email Threads";
$view->description = "";
$view->fDefault = true;
$view->view_fields = array("senders", "subject", "ts_delivered", "f_seen", "f_flagged", "num_attachments", "num_messages");
$view->sort_order[] = new CAntObjectSort("ts_delivered", "desc");
$obj_views[] = $view;
unset($view);
?>
