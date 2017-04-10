<?php
/**
 * Return browser views for entity of object type 'customer'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'default'=> array(
		'obj_type' => 'customer',
		'name' => 'Default View',
		'description' => 'Default System View',
		'default' => true,
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'asc',
    		),
		),
		'table_columns' => array('name', 'email_default', 'stage_id', 'status_id', 'owner_id', 'city', 'state')
    ),
		
	'default'=> array(
		'obj_type' => 'customer',
		'name' => 'Assigned to me',
		'description' => 'Default System View',
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
		'table_columns' => array('name', 'email_default', 'stage_id', 'status_id', 'owner_id', 'city', 'state')
	),
);
