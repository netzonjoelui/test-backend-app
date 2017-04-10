<?php
/**************************************************************************************
*
*	Object Definition: lead
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 106;

$listTitle = "first_name";

$default = array("value"=>"Untitled", "on"=>"null", "coalesce"=>array(array("first_name", "last_name"), "company"));
$obj_fields['name'] 			= array('title'=>'Name', 'type'=>'auto', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$obj_fields['first_name']		= array('title'=>'First Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['last_name'] 		= array('title'=>'Last Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['email'] 			= array('title'=>'Email', 'type'=>'text', 'subtype'=>'email', 'readonly'=>false);
$obj_fields['phone']			= array('title'=>'Phone', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false, 'mask'=>'phone_dash');
$obj_fields['phone2']			= array('title'=>'Phone 2', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false, 'mask'=>'phone_dash');
$obj_fields['phone3']			= array('title'=>'Phone 3', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false, 'mask'=>'phone_dash');
$obj_fields['fax']				= array('title'=>'Fax', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['street']			= array('title'=>'Street', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['street2']			= array('title'=>'Street 2', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['city']				= array('title'=>'City', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['state']			= array('title'=>'State', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['zip']				= array('title'=>'Zip', 'type'=>'text', 'subtype'=>'zipcode', 'readonly'=>false);
$obj_fields['notes'] 			= array('title'=>'Notes', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['company'] 			= array('title'=>'Company', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['title']			= array('title'=>'Job Title', 'type'=>'text', 'subtype'=>'64', 'readonly'=>false);
$obj_fields['website'] 			= array('title'=>'Website', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['country'] 			= array('title'=>'Country', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['f_converted']		= array('title'=>'Converted', 'type'=>'bool', 'subtype'=>'', 'readonly'=>true);

$obj_fields['f_seen'] = array(
	'title'=>'Seen', 
	'type'=>'bool', 
	'subtype'=>'', 
	'readonly'=>true, 
	"default"=>array(
		"value"=>'f',
		"on"=>"null"
	)
);

// Marketing campaign references
$obj_fields['campaign_id'] = array(
	'title'=>'Campaign',
	'type'=>'object',
	'subtype'=>'marketing_campaign'
);

$obj_fields['queue_id'] = array('title'=>'Queue',
									  'type'=>'fkey',
									  'subtype'=>'customer_lead_queues',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$default = array("value"=>"-3", "on"=>"null");
$obj_fields['owner_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user',
								  	  'default'=>$default);

$obj_fields['source_id'] = array('title'=>'Source',
									  'type'=>'fkey',
									  'subtype'=>'customer_lead_sources',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['rating_id'] = array('title'=>'Rating',
									  'type'=>'fkey',
									  'subtype'=>'customer_lead_rating',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['status_id'] = array('title'=>'Status',
									  'type'=>'fkey',
									  'subtype'=>'customer_lead_status',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['class_id'] = array('title'=>'Class',
									  'type'=>'fkey',
									  'subtype'=>'customer_lead_classes',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['converted_opportunity_id'] = array(
	'title'=>'Opportunity',
	'type'=>'object',
	'subtype'=>'opportunity'
);

$obj_fields['converted_customer_id'] = array(
	'title'=>'Customer',
	'type'=>'object',
	'subtype'=>'customer'
);

/*
$obj_fields['lead_id'] = array('title'=>'Lead',
									  'readonly'=>true,
									  'type'=>'fkey',
									  'subtype'=>'customer_leads',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));
 */

// Readonly default fields
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$obj_fields['ts_converted']	= array('title'=>'Time Converted', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "My Leads";
$view->description = "Leads Assigned To Me";
$view->fDefault = true;
$view->view_fields = array("first_name", "last_name", "email", "phone", "city", "state", "status_id", "rating_id", "ts_entered");
$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "All Leads";
$view->description = "All Leads";
$view->fDefault = true;
$view->view_fields = array("first_name", "last_name", "email", "phone", "city", "state", "status_id", "owner_id", "ts_entered");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

?>
