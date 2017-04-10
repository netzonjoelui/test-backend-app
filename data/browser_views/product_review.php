<?php
/**
 * Return browser views for entity of object type 'product_review'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'default'=> array(
		'obj_type' => 'product_review',
		'name' => 'Default View',
		'description' => 'All Product Reviews',
		'default' => true,
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'creator_id', 'rating', 'ts_updated', 'ts_entered')
    ),
);
