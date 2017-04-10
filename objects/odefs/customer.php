<?php
/**************************************************************************************
*
*	Object Definition: customer
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 538;

$unameSettings = "name";

$obj_fields = array();
$default = array("value"=>"New Contact", "on"=>"null", "coalesce"=>array(array("first_name", "last_name"), "company"));
$obj_fields['name'] 			= array('title'=>'Name', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false, 'default'=>$default);
$obj_fields['first_name']		= array('title'=>'First Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['last_name'] 		= array('title'=>'Last Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['middle_name']		= array('title'=>'Middle Name', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['spouse_name']		= array('title'=>'Spouse Name', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['company'] 			= array('title'=>'Company', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['job_title']		= array('title'=>'Job Title', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['salutation']		= array('title'=>'Salutation', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['email'] 			= array('title'=>'Email Home', 'type'=>'text', 'subtype'=>'email', 'readonly'=>false);
$obj_fields['email2'] 			= array('title'=>'Email Work', 'type'=>'text', 'subtype'=>'email', 'readonly'=>false);
$obj_fields['email3'] 			= array('title'=>'Email Other', 'type'=>'text', 'subtype'=>'email', 'readonly'=>false);
$obj_fields['phone_home']		= array('title'=>'Home Phone', 'type'=>'text', 'subtype'=>'phone', 'readonly'=>false);
$obj_fields['phone_work']		= array('title'=>'Work Phone', 'type'=>'text', 'subtype'=>'phone', 'readonly'=>false);
$obj_fields['phone_cell']		= array('title'=>'Mobile Phone', 'type'=>'text', 'subtype'=>'phone', 'readonly'=>false);
$obj_fields['phone_ext'] 		= array('title'=>'Ext.', 'type'=>'text', 'subtype'=>'32', 'readonly'=>false);
$obj_fields['street']			= array('title'=>'Home Street', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['street2']			= array('title'=>'Home Street 2', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['city']				= array('title'=>'Home City', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['state']			= array('title'=>'Home State', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['zip']				= array('title'=>'Home Zip', 'type'=>'text', 'subtype'=>'zipcode', 'readonly'=>false);
$obj_fields['phone_fax']		= array('title'=>'Fax', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['phone_pager']		= array('title'=>'Pager', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['business_street']	= array('title'=>'Business Street', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['business_street2'] = array('title'=>'Business Street 2', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['business_city'] 	= array('title'=>'Business City', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['business_state'] 	= array('title'=>'Business State', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['business_zip']		= array('title'=>'Business Zip', 'type'=>'text', 'subtype'=>'zipcode', 'readonly'=>false);

$obj_fields['shipping_street']	= array('title'=>'Shipping Street', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['shipping_street2'] = array('title'=>'Shipping Street 2', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['shipping_city'] 	= array('title'=>'Shipping City', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['shipping_state'] 	= array('title'=>'Shipping State', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['shipping_zip']		= array('title'=>'Shipping Zip', 'type'=>'text', 'subtype'=>'zipcode', 'readonly'=>false);

$obj_fields['billing_street']	= array('title'=>'Billing Street', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['billing_street2'] 	= array('title'=>'Billing Street 2', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['billing_city'] 	= array('title'=>'Billing City', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['billing_state'] 	= array('title'=>'Billing State', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['billing_zip']		= array('title'=>'Billing Zip', 'type'=>'text', 'subtype'=>'zipcode', 'readonly'=>false);

$obj_fields['website'] 			= array('title'=>'Website', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['birthday'] 		= array('title'=>'Birthday', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$obj_fields['birthday_spouse'] 	= array('title'=>'Spouse Birthday', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$obj_fields['anniversary'] 		= array('title'=>'Anniversary', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$obj_fields['last_contacted'] 	= array('title'=>'Last Contacted', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>false);
$obj_fields['nick_name'] 		= array('title'=>'Nick Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['email_spouse'] 	= array('title'=>'Email Spouse', 'type'=>'text', 'subtype'=>'email', 'readonly'=>false);
$obj_fields['notes'] 			= array('title'=>'Notes', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_nocall'] 		= array('title'=>'Do Not Call', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_noemailspam'] 	= array('title'=>'No Bulk Email', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_nocontact'] 		= array('title'=>'Do Not Contact', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_emailverified']	= array('title'=>'Email Verified', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);

// Used for personal contacts to filter per user
$obj_fields['f_private'] 		= array(
	'title'=>'Personal Contact', 
	'type'=>'bool', 
	'subtype'=>'', 
	'readonly'=>false,
	'default'=>array("value"=>"f", "on"=>"null"),
);

// Social
$obj_fields['facebook'] 		= array('title'=>'Facebook URL', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['twitter'] 			= array('title'=>'Twitter User', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['linkedin'] 		= array('title'=>'LinkedIn Profile', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);


$obj_fields['type_id'] = array('title'=>'Type',
									  'type'=>'integer',
									  'subtype'=>'',
									  'optional_values'=>array("2"=>"Organization", "1"=>"Person"));

$obj_fields['status_id'] = array('title'=>'Status',
									  'type'=>'fkey',
									  'subtype'=>'customer_status',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

// Used for organizations
$obj_fields['primary_contact'] = array('title'=>'Primary Contact',
									  'type'=>'object',
									  'subtype'=>'customer');

// Used for people
$obj_fields['primary_account'] = array('title'=>'Organization',
									  'type'=>'object',
									  'subtype'=>'customer');

$obj_fields['stage_id'] = array('title'=>'Stage',
									  'type'=>'fkey',
									  'subtype'=>'customer_stages',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$default = array("value"=>"email", "on"=>"null", "coalesce"=>array("email", "email2"));
$obj_fields['email_default']= array('title'=>'Default Email', 'type'=>'alias', 'subtype'=>'email', 'readonly'=>false, 'default'=>$default);

$obj_fields['address_default']	= array('title'=>'Default Address', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false, 
										'optional_values'=>array("home"=>"Home", "business"=>"Business"));

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['time_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"update");
$obj_fields['time_changed']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// one to one
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['owner_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user',
									  'default'=>$default);

// one to many
$obj_fields['groups'] = array('title'=>'Groups',
								  'type'=>'fkey_multi',
								  'subtype'=>'customer_labels',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																									"ref_table"=>array(
																									"table"=>"customer_label_mem", 
																									"this"=>"customer_id", 
																									"ref"=>"label_id"
																												   )));
// Folder
$obj_fields['folder_id'] = array('title'=>'Files',
								   'type'=>'object',
								   'subtype'=>'folder',
								   'autocreate'=>true, // Create foreign object automatically
								   'autocreatebase'=>'/System/Customer Files', // Where to create (for folders, the path with no trail slash)
								   'autocreatename'=>'id', // the field to pull the new object name from
								   'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['image_id'] = array('title'=>'Image',
									  'type'=>'object',
									  'subtype'=>'file',
									  'fkey_table'=>array("key"=>"id", "title"=>"file_title"));

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Default View";
$view->description = "Default System View";
$view->fDefault = true;
$view->view_fields = array("name", "email_default", "stage_id", "status_id", "owner_id", "city", "state");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "Assigned to me";
$view->description = "";
$view->fDefault = false;
$view->view_fields = array("name", "email_default", "stage_id", "status_id", "owner_id", "city", "state");
$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);

?>
