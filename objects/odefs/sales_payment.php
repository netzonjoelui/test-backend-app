<?php
/**
 * Payment object used to track payments to sales_orders or invoices
 */
$obj_revision = 6;


// Amount Paid, Date Paid, Payment Method (check, visa), Ref/Check Number

$obj_fields = array();
$obj_fields['amount']			= array('title'=>'Amount', 'type'=>'number', 'subtype'=>'double precision', 'readonly'=>false);
$obj_fields['date_paid']		= array('title'=>'Date Paid', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$obj_fields['ref']				= array('title'=>'Ref / Check Number', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['payment_method']	= array('title'=>'Payment Method', 'type'=>'text', 'subtype'=>'256', 'readonly'=>false);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_updated']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"null");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$obj_fields['owner_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user');

$obj_fields['customer_id'] = array('title'=>'Customer',
								   'type'=>'object',
								   'subtype'=>'customer');

$obj_fields['invoice_id'] = array('title'=>'Invoice',
								   'type'=>'object',
								   'subtype'=>'invoice');

$obj_fields['order_id'] = array('title'=>'Order',
								   'type'=>'object',
								   'subtype'=>'order');

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Payments";
$view->description = "Payments";
$view->fDefault = true;
$view->view_fields = array("date_paid", "amount", "owner_id");
//$view->conditions[] = new CAntObjectCond("and", "date_start", "is_greater_or_equal", "now");
$view->sort_order[] = new CAntObjectSort("date_paid", "desc");
$obj_views[] = $view;
unset($view);
