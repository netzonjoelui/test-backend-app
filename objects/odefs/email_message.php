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
$obj_revision = 38;

$listTitle = "subject";
$isPrivate = true;
$defaultActivityLevel = 1;
$parentField = "mailbox_id";
$storeRevisions = false; // no need for revisins to be stored

$obj_fields = array();
$obj_fields['subject']			= array('title'=>'Subject', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['message_id']		= array('title'=>'Message Id', 'type'=>'text', 'subtype'=>'128', 'readonly'=>true);
$obj_fields['send_to']			= array('title'=>'To', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['sent_from']		= array('title'=>'From', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['cc']				= array('title'=>'CC', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['bcc']				= array('title'=>'BCC', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['reply_to']			= array('title'=>'Reply To', 'type'=>'text', 'subtype'=>'128', 'readonly'=>true);
$obj_fields['priority']			= array('title'=>'Priority', 'type'=>'text', 'subtype'=>'16', 'readonly'=>true);
$obj_fields['file_id']          = array('title'=>'File Id', 'type'=>'integer', 'subtype'=>'', 'readonly'=>true);
$obj_fields['flag_seen']		= array('title'=>'Seen', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['flag_draft']		= array('title'=>'Draft', 'type'=>'bool', 'subtype'=>'', 'readonly'=>true);
$obj_fields['flag_answered']	= array('title'=>'Answered', 'type'=>'bool', 'subtype'=>'', 'readonly'=>true);
$obj_fields['flag_flagged']		= array('title'=>'Flagged', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['flag_spam']		= array('title'=>'Is Spam', 'type'=>'bool', 'subtype'=>'', 'readonly'=>true);
$obj_fields['spam_report']		= array('title'=>'Spam Report', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['content_type']		= array('title'=>'Content Type', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true);
$obj_fields['return_path']		= array('title'=>'Return Path', 'type'=>'text', 'subtype'=>'128', 'readonly'=>true);
$obj_fields['in_reply_to']		= array('title'=>'Return Path', 'type'=>'text', 'subtype'=>'128', 'readonly'=>true);
$obj_fields['message_size']		= array('title'=>'Message Size', 'type'=>'integer', 'subtype'=>'', 'readonly'=>true);
$obj_fields['num_attachments']	= array('title'=>'Num Attachments', 'type'=>'integer', 'subtype'=>'', 'readonly'=>true);
$obj_fields['thread_count']		= array('title'=>'Thread Count', 'type'=>'integer', 'subtype'=>'', 'readonly'=>true);
$obj_fields['orig_header']		= array('title'=>'Full Header', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['keywords']			= array('title'=>'Keywords', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['f_indexed']		= array('title'=>'Indexed', 'type'=>'bool', 'subtype'=>'', 'readonly'=>true);
$obj_fields['body']				= array('title'=>'Body', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['body_type']		= array('title'=>'Body Content Type', 'type'=>'text', 'subtype'=>'32', 'readonly'=>true);
$obj_fields['parse_rev']		= array('title'=>'Indexed', 'type'=>'number', 'subtype'=>'integer', 'readonly'=>true);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['message_date']	= array('title'=>'Message Date', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['owner_id'] = array('title'=>'User', 'type'=>'object', 'subtype'=>'user', 'default'=>$default);

$obj_fields['mailbox_id'] = array(
    'title'=>'Mailbox',
    'type'=>'fkey',
	'subtype'=>'email_mailboxes',
	'fkey_table'=>
    array(
        "key"=>"id", 
        "title"=>
        "name", 
        "filter"=>array(
            "user_id"=>"owner_id"
        ),
        "parent"=>"parent_box"
    ),
	'readonly'=>false
);

$obj_fields['thread'] = array('title'=>'Thread',
									  'type'=>'object',
									  'subtype'=>'email_thread',
									  'fkey_table'=>array("key"=>"id", "title"=>"subject"),
								  	  'readonly'=>true);


$obj_fields['email_account'] = array('title'=>'Email Account',
									  'type'=>'fkey',
									  'subtype'=>'email_accounts',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"),
								  	  'readonly'=>true);
                                      
$obj_fields['message_uid']        = array('title'=>'Message Uid', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Email Messages";
$view->description = "";
$view->fDefault = true;
$view->view_fields = array("subject", "message_date", "sent_from", "send_to", "priority");
$view->sort_order[] = new CAntObjectSort("message_date", "desc");
$obj_views[] = $view;
unset($view);

?>
