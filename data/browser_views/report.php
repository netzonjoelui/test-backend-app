<?php
/**
 * Return browser views for entity of object type 'report'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'reports'=> array(
		'obj_type' => 'report',
		'name' => 'Reports',
		'description' => 'Default list of reports',
		'default' => true,
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'asc',
    		),
		),
		'table_columns' => array('name', 'description')
    ),
);
