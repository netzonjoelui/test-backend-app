<?php
/**
 * Return browser views for entity of object type 'note'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_payments'=> array(
		'obj_type' => 'sales_payments',
		'name' => 'All Payments',
		'description' => 'All Payments',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'date_paid',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('date_paid', 'amount', 'owner_id')
    ),
);
