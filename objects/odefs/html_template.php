<?php
/**
 * html_template object definition
 */
$obj_revision = 9;

$obj_fields = array();
$obj_fields['name']					= array('title'=>'Name', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['subject']				= array('title'=>'Subject', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['body_html']			= array('title'=>'Html Body', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['body_plain']			= array('title'=>'Plain Body', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);

$obj_fields['obj_type'] = array(
	'title'=>'Type',
	'type'=>'text',
	'subtype'=>'128',
	'optional_values'=>array(
		"email_message"=>"Email",
		"content_feed_post"=>"Content Post"
	),
);

// Who this notification is sent to
$obj_fields['owner_id'] = array(
	'title' => 'Owner',
	'type' => 'object',
	'subtype' => 'user',
	'readonly' => false,
	'require' => true,
	'default' => array(
		"on" => "null",
		"value" => "-3",
	),
);

$obj_fields['scope'] = array(
	'title' => 'Scope',
	'type' => 'text',
	'subtype' => '32',
	'optional_values' => array(
		"system"=>"System/Everyone",
		"user"=>"User"
	),
);

$obj_fields['groups'] = array(
	'title'=>'Groups',
	'type'=>'fkey_multi',
	'subtype'=>'object_groupings',
	'fkey_table'=>array(
		"key"=>"id",
		"title"=>"name",
		"parent"=>"parent_id",
		"ref_table"=>array(
			"table"=>"object_grouping_mem",
			"this"=>"object_id",
			"ref"=>"grouping_id"
		)
	)
);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All HTML Templates";
$view->description = "Display all available HTML templates for all object types";
$view->fDefault = true;
$view->view_fields = array("name", "obj_type");
$view->sort_order[] = new CAntObjectSort("obj_type", "asc");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "Email Templates";
$view->description = "HTML templates designed specifically for email messages";
$view->fDefault = false;
$view->view_fields = array("name");
$view->conditions[] = new CAntObjectCond("and", "obj_type", "is_equal", "email_message");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_3";
$view->name = "My Templates";
$view->description = "HTML templates designed by me";
$view->fDefault = false;
$view->view_fields = array("name", "obj_type");
$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);
