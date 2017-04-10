<?php
/**
 * Return browser views for entity of object type 'phone_call'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_pages'=> array(
		'obj_type' => 'phone_call',
		'name' => 'All Pages',
		'description' => 'Display all pages',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'name',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'uname', 'title', 'parent_id')
    ),
);
