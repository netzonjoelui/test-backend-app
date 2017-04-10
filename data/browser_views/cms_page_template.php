<?php
/**
 * Return browser views for entity of object type 'cms_page_template'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_templates'=> array(
		'obj_type' => 'cms_page_template',
		'name' => 'All Templates',
		'description' => 'Display all templates',
		'default' => true,        
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'type')
    ),
);
