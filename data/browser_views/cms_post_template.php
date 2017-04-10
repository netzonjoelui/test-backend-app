<?php
/**
 * Return browser views for entity of object type 'cms_post_template'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'site_display_templates'=> array(
		'obj_type' => 'cms_post_template',
		'name' => 'Site Display Templates',
		'description' => 'User Notes',
		'default' => true,
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'url')
    ),
);
