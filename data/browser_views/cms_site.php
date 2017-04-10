<?php
/**
 * Return browser views for entity of object type 'cms_site'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_sites'=> array(
		'obj_type' => 'cms_site',
		'name' => 'All Sites',
		'description' => 'Display all available sites',
		'default' => true,
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'asc',
    		),
		),
		'table_columns' => array('name', 'url')
    ),
);
