<?php
/**
 * Return browser views for entity of object type 'sales_order'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_orders'=> array(
		'obj_type' => 'sales_order',
		'name' => 'All Orders',
		'description' => 'All Orders',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_entered',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'status_id', 'created_by', 'ts_entered', 'amount', 'customer_id')
    ),
);
