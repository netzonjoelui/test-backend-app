<?php
/**************************************************************************************
*
*	Object Definition: order
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2011 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 9;

$obj_fields = array();
$obj_fields['name']				= array('title'=>'Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);
$obj_fields['created_by']		= array('title'=>'Created By', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true);
$obj_fields['tax_rate']			= array('title'=>'Tax %', 'type'=>'integer', 'subtype'=>'', 'readonly'=>false);
$obj_fields['amount']			= array('title'=>'Amount', 'type'=>'number', 'subtype'=>'double precision', 'readonly'=>false);
$obj_fields['ship_to']			= array('title'=>'Ship To', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$default = array("value"=>"t", "on"=>"null");
$obj_fields['ship_to_cship']	= array('title'=>'Use Shipping Address', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false, 'default'=>$default);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_updated']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"null");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$obj_fields['owner_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user');

$obj_fields['status_id'] = array('title'=>'Status',
									  'type'=>'fkey',
									  'subtype'=>'customer_order_status',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['customer_id'] = array('title'=>'Customer',
								   'type'=>'object',
								   'subtype'=>'customer');

$obj_fields['invoice_id'] = array('title'=>'Invoice',
                                   'type'=>'object',
                                   'subtype'=>'invoice');
                                   
$obj_fields['sales_order_id'] = array('title'=>'Sales Order Id',
								   'type'=>'integer');


// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Orders";
$view->description = "Orders";
$view->fDefault = true;
$view->view_fields = array("name", "status_id", "created_by", "ts_entered", "amount", "customer_id");
//$view->conditions[] = new CAntObjectCond("and", "date_start", "is_greater_or_equal", "now");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);
