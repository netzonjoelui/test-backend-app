<?php
/**
 * Return browser views for entity of object type 'invoice'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'invoices'=> array(
		'obj_type' => 'invoice',
		'name' => 'Invoices',
		'description' => 'Events occurring in the future',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'date_due',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'status_id', 'created_by', 'ts_entered', 'amount', 'date_due')
    ),
);
