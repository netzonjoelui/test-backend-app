<?php
/**************************************************************************************
*
*	Object Definition: contact_personal
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 38;

$isPrivate = true;
$defaultActivityLevel = 1;

$obj_fields = array();
$default = array("value"=>"Untitled", "on"=>"update", "coalesce"=>array(array("first_name", "last_name"), "company"));
$obj_fields['name'] 			= array('title'=>'Name', 'type'=>'text', 'subtype'=>'512', 'readonly'=>true, 'default'=>$default);
$obj_fields['first_name']		= array('title'=>'First Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['last_name'] 		= array('title'=>'Last Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['middle_name']		= array('title'=>'Middle Name', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['spouse_name']		= array('title'=>'Spouse Name', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['company'] 			= array('title'=>'Company', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['job_title']		= array('title'=>'Job Title', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['salutation']		= array('title'=>'Salutation', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['email'] 			= array('title'=>'Email Home', 'type'=>'text', 'subtype'=>'email', 'readonly'=>false);
$obj_fields['email2'] 			= array('title'=>'Email Work', 'type'=>'text', 'subtype'=>'email', 'readonly'=>false);
$obj_fields['email_spouse'] 	= array('title'=>'Email Spouse', 'type'=>'text', 'subtype'=>'email', 'readonly'=>false);
$obj_fields['phone_home']		= array('title'=>'Home Phone', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['phone_work']		= array('title'=>'Work Phone', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['phone_other']		= array('title'=>'Other Phone', 'type'=>'text', 'subtype'=>'32', 'readonly'=>false);
$obj_fields['street']			= array('title'=>'Home Street', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['street_2']			= array('title'=>'Home Street 2', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['city']				= array('title'=>'Home City', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['state']			= array('title'=>'Home State', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['zip']				= array('title'=>'Home Zip', 'type'=>'text', 'subtype'=>'zipcode', 'readonly'=>false);
$obj_fields['notes'] 			= array('title'=>'Notes', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['phone_cell']		= array('title'=>'Mobile Phone', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['phone_fax']		= array('title'=>'Fax', 'type'=>'text', 'subtype'=>'32', 'readonly'=>false);
$obj_fields['phone_pager']		= array('title'=>'Pager', 'type'=>'text', 'subtype'=>'32', 'readonly'=>false);
$obj_fields['website'] 			= array('title'=>'Website', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['nick_name'] 		= array('title'=>'Nick Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['last_contacted'] 	= array('title'=>'Last Contacted', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>false);
$obj_fields['business_street']	= array('title'=>'Business Street', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['business_street_2'] = array('title'=>'Business Street 2', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['business_city'] 	= array('title'=>'Business City', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['business_state'] 	= array('title'=>'Business State', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['business_zip']		= array('title'=>'Business Zip', 'type'=>'text', 'subtype'=>'zipcode', 'readonly'=>false);
$obj_fields['business_website'] = array('title'=>'Business Website', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);

$obj_fields['birthday'] 		= array('title'=>'Birthday', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$obj_fields['birthday_spouse'] 	= array('title'=>'Spouse Birthday', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$obj_fields['anniversary'] 		= array('title'=>'Anniversary', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);

$obj_fields['ext'] 		= array('title'=>'Ext.', 'type'=>'text', 'subtype'=>'32', 'readonly'=>false);

$default = array("value"=>"email", "on"=>"null", "coalesce"=>array("email", "email2"));
$obj_fields['email_default']= array('title'=>'Default Email', 'type'=>'alias', 'subtype'=>'email', 'readonly'=>false, $default);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['date_entered']	= array('title'=>'Date Entered', 'type'=>'date', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

//$default = array("value"=>"now", "on"=>"create");
//$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

//$default = array("value"=>"now", "on"=>"update");
//$obj_fields['ts_changed']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"now", "on"=>"update");
$obj_fields['date_changed']	= array('title'=>'Date Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

$default = array("value"=>"-3", "on"=>"null");
$obj_fields['user_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user',
									  'default'=>$default);

$obj_fields['image_id'] = array('title'=>'Image',
									  'type'=>'object',
									  'subtype'=>'file');

// one to many
$obj_fields['groups'] = array('title'=>'Groups',
								  'type'=>'fkey_multi',
								  'subtype'=>'contacts_personal_labels',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id", "filter"=>array("user_id"=>"user_id"),
								  															"ref_table"=>array(
																									"table"=>"contacts_personal_label_mem", 
																									"this"=>"contact_id", 
																									"ref"=>"label_id")));
// Customer
$obj_fields['customer_id'] = array('title'=>'Customer',
								   'type'=>'object',
								   'subtype'=>'customer');

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "My Contacts";
$view->description = "User notes";
$view->fDefault = true;
$view->view_fields = array("name", "phone_cell", "phone_home", "phone_work", "email_default", "city", "state");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("first_name", "asc");
$view->sort_order[] = new CAntObjectSort("last_name", "asc");
$obj_views[] = $view;
unset($view);
?>
