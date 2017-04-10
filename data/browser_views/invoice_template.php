<?php
/**
 * Return browser views for entity of object type 'invoice_template'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'templates'=> array(
		'obj_type' => 'invoice_template',
		'name' => 'Templates',
		'description' => 'View All Invoice Templates',
		'default' => true,
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'company_name')
    ),
);
