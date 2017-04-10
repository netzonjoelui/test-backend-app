<?php
/**
 * Return browser views for entity of object type 'html_template'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_html_templates'=> array(
		'obj_type' => 'html_template',
		'name' => 'All HTML Templates',
		'description' => 'Display all available HTML templates for all object types',
		'default' => true,
		'order_by' => array(
			'obj_type' => array(
    			'field_name' => 'obj_type',
    			'direction' => 'asc',
    		),
			'name' => array(
					'field_name' => 'name',
					'direction' => 'asc',
			),
		),
		'table_columns' => array('name', 'obj_type')
    ),
		
	'email_templates'=> array(
		'obj_type' => 'html_template',
		'name' => 'Email Templates',
		'description' => 'HTML templates designed specifically for email messages',
		'default' => false,
		'conditions' => array(
			'obj_type' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'obj_type',
				'operator' => Where::OPERATOR_EQUAL_TO,
				'value' => 'email_message'
			),
		),
		'order_by' => array(
			'name' => array(
				'field_name' => 'name',
				'direction' => 'asc',
			),
		),
		'table_columns' => array('name')
	),
		
	'my_templates'=> array(
		'obj_type' => 'html_template',
		'name' => 'My Templates',
		'description' => 'HTML templates designed by me',
		'default' => false,
		'conditions' => array(
			'owner' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'owner_id',
				'operator' => Where::OPERATOR_EQUAL_TO,
				'value' => -3
			),
		),
		'order_by' => array(
			'name' => array(
				'field_name' => 'name',
				'direction' => 'asc',
			),
		),
		'table_columns' => array('name', 'obj_type')
	),
);
