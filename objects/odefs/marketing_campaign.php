<?php
/**
 * The Campaign object is used to track the effectiveness of marketing campaigns
 *
 * Name: campaign
 */
$obj_revision = 15;
$parentField = "parent_id";

$obj_fields = array();
$obj_fields['name']				= array('title'=>'Title', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false, 'required'=>true);
$obj_fields['description']		= array('title'=>'Description', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['date_start']		= array('title'=>'Start Date', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$obj_fields['date_end']			= array('title'=>'End Date', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);

$obj_fields['date_completed']	= array('title'=>'Date Completed', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);

$obj_fields['cost_estimated']	= array('title'=>'Estimated Cost', 'type'=>'number', 'subtype'=>'', 'readonly'=>false);
$obj_fields['cost_actual'] 		= array('title'=>'Actual Cost', 'type'=>'number', 'subtype'=>'', 'readonly'=>false);

$obj_fields['rev_estimated']	= array('title'=>'Estimated Revenue', 'type'=>'number', 'subtype'=>'', 'readonly'=>false);
$obj_fields['rev_actual'] 		= array('title'=>'Actual Revenue', 'type'=>'number', 'subtype'=>'', 'readonly'=>false);

$obj_fields['num_sent'] 		= array('title'=>'Number Sent', 'type'=>'number', 'subtype'=>'', 'readonly'=>false);

$obj_fields['resp_estimated']	= array('title'=>'Estimated Response %', 'type'=>'number', 'subtype'=>'', 'readonly'=>false);
$obj_fields['resp_actual']		= array('title'=>'Actual Response %', 'type'=>'number', 'subtype'=>'', 'readonly'=>false);

// Email stats
$statsDef = array("value"=>"0", "on"=>"null");
$obj_fields['email_opens'] 		= array('title'=>'Opens', 'type'=>'number', 'subtype'=>'', 'readonly'=>false, "default"=>$statsDef);
$obj_fields['email_unsubscribers'] = array('title'=>'Unsubscribers', 'type'=>'number', 'subtype'=>'', 'readonly'=>false, "default"=>$statsDef);
$obj_fields['email_bounced'] 	= array('title'=>'Bounced', 'type'=>'number', 'subtype'=>'', 'readonly'=>false, "default"=>$statsDef);

// References
$obj_fields['type_id'] = array('title'=>'Type',
								  'type'=>'fkey',
								  'subtype'=>'object_groupings',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																									"ref_table"=>array(
																									"table"=>"object_grouping_mem", 
																									"this"=>"object_id", 
																									"ref"=>"grouping_id"
																												   )));

$obj_fields['status_id'] = array('title'=>'Status',
								  'type'=>'fkey',
								  'subtype'=>'object_groupings',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																									"ref_table"=>array(
																									"table"=>"object_grouping_mem", 
																									"this"=>"object_id", 
																									"ref"=>"grouping_id"
																												   )));

$obj_fields['parent_id'] = array('title'=>'Parent Campaign',
									  'type'=>'object',
									  'subtype'=>'marketing_campaign');

$obj_fields['email_campaign_id'] = array('title'=>'Email Campaign',
										  'type'=>'object',
										  'subtype'=>'email_campaign');


// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Campaigns";
$view->description = "View all campaigns both active and inactive";
$view->fDefault = true;
$view->view_fields = array("name", "status_id", "type_id", "date_start");
//$view->conditions[] = new CAntObjectCond("and", "date_start", "is_greater_or_equal", "now");
$view->sort_order[] = new CAntObjectSort("date_start", "desc");
$obj_views[] = $view;
unset($view);
