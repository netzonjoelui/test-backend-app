<?php
/**
 * Return browser views for entity of object type 'folder'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'default'=> array(
		'obj_type' => 'folder',
		'name' => 'Default View',
		'description' => '',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_entered',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('id')
    ),
);
