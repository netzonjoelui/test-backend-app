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
$obj_revision = 27;

$obj_fields = array(
	'name' => array(
		'title'=>'Name', 
		'type'=>'text', 
		'subtype'=>'', 
		'readonly'=>false,
		'required'=>true,
	),

	'notes' => array(
		'title'=>'Notes', 
		'type'=>'text', 
		'subtype'=>'', 
		'readonly'=>false
	),

	'closed_lost_reson' => array(
		'title'=>'Closed Lost Reason', 
		'type'=>'text', 
		'subtype'=>'', 
		'readonly'=>false
	),

	'expected_close_date' => array(
		'title'=>'Exp. Close Date', 
		'type'=>'date', 
		'subtype'=>'', 
		'readonly'=>false
	),

	'amount' => array(
		'title'=>'Est. Amount', 
		'type'=>'number', 
		'subtype'=>'number', 
		'readonly'=>false,
	),

	'ts_closed' => array(
		'title'=>'Time Closed', 
		'type'=>'timestamp', 
		'subtype'=>'', 
		'readonly'=>false
	),

	'probability_per' => array(
		'title'=>'Est. Probability %', 
		'type'=>'integer', 
		'subtype'=>'', 
		'readonly'=>false,
		'optional_values'=>array(
			"10"=>"10%", "25"=>"25%", "50"=>"50%", "75"=>"75%", "90"=>"90%", "100"=>"100%"
		),
	),

	'f_closed' => array(
		'title'=>'Closed', 
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>false,
		'default'=>array("value"=>"f", "on"=>"null"),
	),

	'f_won' => array(
		'title'=>'Won', 
		'type'=>'bool', 
		'subtype'=>'', 
		'readonly'=>false,
		'default'=>array("value"=>"f", "on"=>"null"),
	),

	// Marketing campaign references
	'campaign_id' => array(
		'title'=>'Campaign',
		'type'=>'object',
		'subtype'=>'marketing_campaign'
	),
);


$obj_fields['owner_id'] = array(
    'title'=>'Owner',
    'type'=>'object',
    'subtype'=>'user',
    'default'=>array("value"=>"-3", "on"=>"null"),
);

$obj_fields['stage_id'] = array('title'=>'Stage',
									  'type'=>'fkey',
									  'subtype'=>'customer_opportunity_stages',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['customer_id'] = array('title'=>'Contact',
									  'type'=>'object',
									  'subtype'=>'customer',
								  	  'required'=>true);

$obj_fields['lead_id'] = array('title'=>'Lead',
									  'type'=>'object',
									  'subtype'=>'lead');

$obj_fields['lead_source_id'] = array('title'=>'Source',
									  'type'=>'fkey',
									  'subtype'=>'customer_lead_sources',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['type_id'] = array('title'=>'Type',
									  'type'=>'fkey',
									  'subtype'=>'customer_opportunity_types',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['objection_id'] = array('title'=>'Objection',
									  'type'=>'fkey',
									  'subtype'=>'customer_objections',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['selling_point_id'] = array('title'=>'Selling Point',
										  'type'=>'fkey',
										  'subtype'=>'object_groupings',
											'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																										"ref_table"=>array(
																										"table"=>"object_grouping_mem", 
																										"this"=>"object_id", 
																										"ref"=>"grouping_id"
																													   )));

// Folder
$obj_fields['folder_id'] = array('title'=>'Files',
								   'type'=>'object',
								   'subtype'=>'folder',
								   'autocreate'=>true, // Create foreign object automatically
								   'autocreatebase'=>'/System/Customer Files/Opportunities', // Where to create
								   'autocreatename'=>'id', // the field to pull the new object name from
								   'fkey_table'=>array("key"=>"id", "title"=>"name"));

// Readonly default fields
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "My Open Opportunities";
$view->description = "Opportunities assigned to me that are not closed";
$view->fDefault = true;
$view->view_fields = array("name", "customer_id", "stage_id", "amount", "expected_close_date", "probability_per", "type_id");
$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->conditions[] = new CAntObjectCond("and", "f_closed", "is_not_equal", "t");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_2";
$view->name = "All My Opportunities";
$view->description = "Opportunities Assigned To Me";
$view->fDefault = true;
$view->view_fields = array("name", "customer_id", "stage_id", "amount", "expected_close_date", "probability_per", "owner_id");
$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_3";
$view->name = "All Open Opportunities";
$view->description = "All Open Opportunities";
$view->fDefault = true;
$view->view_fields = array("name", "customer_id", "stage_id", "amount", "expected_close_date", "probability_per", "owner_id");
$view->conditions[] = new CAntObjectCond("and", "f_closed", "is_not_equal", "t");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_4";
$view->name = "All Opportunities";
$view->description = "All Opportunities";
$view->fDefault = true;
$view->view_fields = array("name", "customer_id", "stage_id", "amount", "expected_close_date", "probability_per", "owner_id");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);
?>
