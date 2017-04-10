<?php
/**************************************************************************************
*
*	Object Definition: user
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 39;

$listTitle = "full_name";

$obj_fields = array(
	// User name
	'name' => array(
		'title'=>'User Name', 
		'type'=>'text', 
		'subtype'=>'128', 
		'readonly'=>false, 
		'required'=>true,
		'unique'=>true,
	),
	
	// Full name first + last
	'full_name' => array(
		'title'=>'Full Name', 
		'type'=>'text', 
		'subtype'=>'128', 
		'readonly'=>false, 
		'required'=>true
	),

	'password' => array(
		'title'=>'Description', 
		'type'=>'text', 
		'subtype'=>'password', 
		'readonly'=>false
	),

	'password_salt' => array(
		'title'=>'PW Salt', 
		'type'=>'text', 
		'subtype'=>'password', 
		'readonly'=>true
	),

	'theme' => array(
		'title'=>'Theme', 
		'type'=>'text', 
		'subtype'=>'32',
	),

	'timezone' => array(
		'title'=>'Timezone', 
		'type'=>'text', 
		'subtype'=>'64',
	),

	'notes' => array(
		'title'=>'About', 
		'type'=>'text', 
		'subtype'=>'',
	),

	'email' => array(
		'title'=>'Email', 
		'type'=>'text', 
		'subtype'=>'256', 
		'readonly'=>false, 
		'required'=>false
	),

	'phone_office' => array(
		'title'=>'Office Phone', 
		'type'=>'text', 
		'subtype'=>'64', 
		'readonly'=>false, 
		'mask'=>'phone_dash'
	),

	'phone_ext' => array(
		'title'=>'Phone Ext.', 
		'type'=>'text', 
		'subtype'=>'16', 
		'readonly'=>false
	),

	'phone_mobile' => array(
		'title'=>'Mobile Phone', 
		'type'=>'text', 
		'subtype'=>'64', 
		'readonly'=>false, 
		'mask'=>'phone_dash'
	),

	'phone_mobile_carrier' => array(
		'title'=>'Mobile Carrier', 
		'type'=>'text', 
		'subtype'=>'64', 
		'readonly'=>false, 
		'mask'=>'phone_dash',
		'optional_values'=>array(
			""=>"None",
			"@vtext.com"=>"Verizon Wireless",
			"@messaging.sprintpcs.com"=>"Sprint/Nextel",
			"@txt.att.net"=>"AT&T Wireless",
			"@tmomail.net"=>"T Mobile",
			"@cingularme.com"=>"Cingular Wireless",
			"@mobile.surewest.com"=>"SureWest",
			"@mymetropcs.com"=>"Metro PCS",
		),
	),

	'phone_home' => array(
		'title'=>'Home Phone',
		'type'=>'text',
		'subtype'=>'64',
		'readonly'=>false,
		'mask'=>'phone_dash'
	),

    // Aereus customer number
    'customer_number' => array(
        'title'=>'Netric Customer Number',
        'type'=>'text',
        'subtype'=>'64',
        'readonly'=>true,
    ),
);

$obj_fields['job_title']		= array('title'=>'Job Title', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false, 'required'=>false);
$obj_fields['city']				= array('title'=>'City', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false, 'required'=>false);
$obj_fields['state']			= array('title'=>'State', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false, 'required'=>false);

$default = array("value"=>"t", "on"=>"null");
$obj_fields['active'] 			= array('title'=>'Active', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false, "default"=>$default);

// Timestamps
$obj_fields['last_login']		= array('title'=>'Last Login', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true);

// References

$obj_fields['image_id'] = array('title'=>'Image',
									  'type'=>'object',
									  'subtype'=>'file');

$obj_fields['team_id'] = array('title'=>'Team',
									  'type'=>'fkey',
									  'subtype'=>'user_teams',
									  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id"));

$obj_fields['groups'] = array('title'=>'Groups',
									  'type'=>'fkey_multi',
									  'subtype'=>'user_groups',
									  'fkey_table'=>array("key"=>"id", "title"=>"name", "ref_table"=>array(
																									"table"=>"user_group_mem", 
																									"this"=>"user_id", 
																									"ref"=>"group_id"
																								   )));

$obj_fields['manager_id'] = array('title'=>'Manager',
									  'type'=>'object',
									  'subtype'=>'user');

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Active";
$view->description = "Active Users";
$view->fDefault = true;
$view->view_fields = array("full_name", "name", "last_login", "team_id", "manager_id");
$view->conditions[] = new CAntObjectCond("and", "active", "is_equal", "t");
$view->conditions[] = new CAntObjectCond("and", "id", "is_greater", "0");
$view->sort_order[] = new CAntObjectSort("full_name", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "Inactive Users";
$view->description = "Inactive Users";
$view->fDefault = true;
$view->view_fields = array("full_name", "name", "last_login", "timezone_id", "manager_id");
$view->conditions[] = new CAntObjectCond("and", "active", "is_equal", "f");
$view->sort_order[] = new CAntObjectSort("full_name", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_3";
$view->name = "All User";
$view->description = "All Users";
$view->fDefault = true;
$view->view_fields = array("full_name", "name", "last_login", "timezone_id", "manager_id");
$view->sort_order[] = new CAntObjectSort("full_name", "asc");
$obj_views[] = $view;
unset($view);
