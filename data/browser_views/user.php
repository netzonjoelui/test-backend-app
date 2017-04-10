<?php
/**
 * Return browser views for entity of object type 'user'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'active'=> array(
		'obj_type' => 'user',
		'name' => 'Active',
		'description' => 'Active Users',
		'default' => true,
		'conditions' => array(
            'active' => array(
                'blogic' => Where::COMBINED_BY_AND,
                'field_name' => 'active',
                'operator' => Where::OPERATOR_EQUAL_TO,
                'value' => 't'
            ),
        	'id' => array(
        		'blogic' => Where::COMBINED_BY_AND,
        		'field_name' => 'id',
        		'operator' => Where::OPERATOR_GREATER_THAN,
        		'value' => 0
        	),
        ),
		'order_by' => array(
			'name' => array(
    			'field_name' => 'full_name',
    			'direction' => 'asc',
    		),
		),
		'table_columns' => array('full_name', 'name', 'last_login', 'team_id', 'manager_id')
    ),
		
	'inactive_users'=> array(
		'obj_type' => 'user',
		'name' => 'Inactive Users',
		'description' => 'Inactive Users',
		'default' => false,
		'conditions' => array(
			'active' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'active',
				'operator' => Where::OPERATOR_EQUAL_TO,
				'value' => 'f'
			),
		),
		'order_by' => array(
			'name' => array(
				'field_name' => 'full_name',
				'direction' => 'asc',
			),
		),
		'table_columns' => array('full_name', 'name', 'last_login', 'team_id', 'manager_id')
	),
		
	'all_users'=> array(
		'obj_type' => 'user',
		'name' => 'All Users',
		'description' => 'All Users',
		'default' => false,
		'order_by' => array(
			'name' => array(
				'field_name' => 'full_name',
				'direction' => 'asc',
			),
		),
		'table_columns' => array('full_name', 'name', 'last_login', 'team_id', 'manager_id')
	),
);
