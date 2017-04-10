<?php
/**************************************************************************************
*
*	Object Definition: invoice
*
*	Purpose:	$this refers to CAntObjectFields class which inlcludes this file
*
*	Author: 	joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation, All Rights Reserved.
*
**************************************************************************************/
$obj_revision = 27;

$obj_fields = array();
$obj_fields['name']				= array('title'=>'Name', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false, "required"=>true);
$obj_fields['number']			= array('title'=>'Number', 'type'=>'text', 'subtype'=>'512', 'readonly'=>true);
$obj_fields['created_by']		= array('title'=>'Created By', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true);
$obj_fields['updated_by']		= array('title'=>'Updated By', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true);
$obj_fields['notes_line1']		= array('title'=>'Notes Line 1', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['notes_line2']		= array('title'=>'Notes Line 2', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['footer_line1']		= array('title'=>'Footer Line 1', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);
$obj_fields['payment_terms']	= array('title'=>'Payment Terms', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['tax_rate']			= array('title'=>'Tax %', 'type'=>'integer', 'subtype'=>'', 'readonly'=>false);
$obj_fields['amount']			= array('title'=>'Amount', 'type'=>'number', 'subtype'=>'double precision', 'readonly'=>false);
$obj_fields['send_to']			= array('title'=>'Send To', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['reference']		= array('title'=>'Reference', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$default = array("value"=>"t", "on"=>"null");
$obj_fields['send_to_cbill']	= array('title'=>'Use Billing Address', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false, 'default'=>$default);

$obj_fields['type']				= array('title'=>'Type', 'type'=>'text', 'subtype'=>'32', 'readonly'=>false, 
										'optional_values'=>array("r"=>"Receivable", "p"=>"Payable"),
										'default'=>array("value"=>"r", "on"=>"null"));

// Timestamps
$obj_fields['date_due']	= array('title'=>'Due Date', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$obj_fields['owner_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user');

$obj_fields['status_id'] = array('title'=>'Status',
									  'type'=>'fkey',
									  'subtype'=>'customer_invoice_status',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['template_id'] = array('title'=>'Template',
									  'type'=>'fkey',
									  'subtype'=>'customer_invoice_templates',
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

$obj_fields['customer_id'] = array('title'=>'Customer',
								   'type'=>'object',
								   'subtype'=>'customer');

$obj_fields['sales_order_id'] = array('title'=>'Order',
								   'type'=>'object',
								   'subtype'=>'sales_order');


// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Invoices";
$view->description = "Events occurring in the future";
$view->fDefault = true;
$view->view_fields = array("name", "status_id", "created_by", "ts_entered", "amount", "date_due");
//$view->conditions[] = new CAntObjectCond("and", "date_start", "is_greater_or_equal", "now");
$view->sort_order[] = new CAntObjectSort("date_due", "desc");
$obj_views[] = $view;
unset($view);
?>
