<?php
/**
 * Return browser views for entity of object type 'product'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'default'=> array(
		'obj_type' => 'product',
		'name' => 'Default View',
		'description' => 'All Products',
		'default' => true,
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'price', 'notes', 'ts_updated', 'ts_entered')
    ),
);
