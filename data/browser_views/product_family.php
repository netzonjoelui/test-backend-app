<?php
/**
 * Return browser views for entity of object type 'product_family'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'default'=> array(
		'obj_type' => 'product_family',
		'name' => 'Default View: All Product Families',
		'description' => '',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'name',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'ts_updated', 'ts_entered')
    ),
);
