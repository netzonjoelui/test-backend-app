<?php
/**
 * Return browser views for entity of object type 'dashboard'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_dashboards'=> array(
		'obj_type' => 'dashboard',
		'name' => 'All Dashboards',
		'description' => 'Viewing All Dashboards',
		'default' => true,
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'description')
    ),
);
