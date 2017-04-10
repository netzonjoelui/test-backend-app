<?php
/**
 * Return browser views for entity of object type 'cms_page'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_pages'=> array(
		'obj_type' => 'cms_page',
		'name' => 'All Pages',
		'description' => 'Display all pages',
		'default' => true,        
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'asc',
    		),
		),
		'table_columns' => array('name', 'uname', 'title', 'parent_id')
    ),
);
