<?php
/**
 * Return browser views for entity of object type 'cms_snippet'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_snippets'=> array(
		'obj_type' => 'cms_snippet',
		'name' => 'All Snippets',
		'description' => 'All Snippets',
		'default' => true,
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name')
    ),
);
