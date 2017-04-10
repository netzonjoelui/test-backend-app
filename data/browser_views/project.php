<?php
/**
 * Return browser views for entity of object type 'project'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
	'all_projects'=> array(
		'obj_type' => 'project',
		'name' => 'All Projects',
		'description' => '',
		'default' => false,
		'order_by' => array(
			'name' => array(
				'field_name' => 'name',
				'direction' => 'asc',
			),
		),
		'table_columns' => array('name', 'priority', 'date_started', 'date_deadline', 'date_completed')
	),

    'my_open_projects'=> array(
		'obj_type' => 'project',
		'name' => 'My Open Projects',
		'description' => '',
		'default' => true,
		'conditions' => array(
            'members' => array(
                'blogic' => Where::COMBINED_BY_AND,
                'field_name' => 'members',
                'operator' => Where::OPERATOR_EQUAL_TO,
                'value' => -3
            ),
        	'completed' => array(
        		'blogic' => Where::COMBINED_BY_AND,
        		'field_name' => 'date_completed',
        		'operator' => Where::OPERATOR_EQUAL_TO,
        		'value' => ''
        	),
        ),
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'asc',
    		),
		),
		'table_columns' => array('name', 'priority', 'date_started', 'date_deadline', 'date_completed')
    ),

	'my_closed_projects'=> array(
		'obj_type' => 'project',
		'name' => 'My Closed Projects',
		'description' => '',
		'default' => false,
		'conditions' => array(
			'members' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'members',
				'operator' => Where::OPERATOR_EQUAL_TO,
				'value' => -3
			),
			'completed' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'date_completed',
				'operator' => Where::OPERATOR_NOT_EQUAL_TO,
				'value' => ''
			),
		),
		'order_by' => array(
			'name' => array(
				'field_name' => 'name',
				'direction' => 'asc',
			),
		),
		'table_columns' => array('name', 'priority', 'date_started', 'date_deadline', 'date_completed')
	),

	'all_open_projects'=> array(
		'obj_type' => 'project',
		'name' => 'All Open Projects',
		'description' => '',
		'default' => false,
		'conditions' => array(
			'completed' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'date_completed',
				'operator' => Where::OPERATOR_EQUAL_TO,
				'value' => ''
			),
		),
		'order_by' => array(
			'name' => array(
				'field_name' => 'name',
				'direction' => 'asc',
			),
		),
		'table_columns' => array('name', 'priority', 'date_started', 'date_deadline', 'date_completed')
	),

	'ongoing_projects'=> array(
		'obj_type' => 'project',
		'name' => 'Ongoing Projects (no deadline)',
		'description' => '',
		'default' => false,
		'conditions' => array(
			'deadline' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'date_deadline',
				'operator' => Where::OPERATOR_EQUAL_TO,
				'value' => ''
			),
		),
		'order_by' => array(
			'name' => array(
					'field_name' => 'name',
					'direction' => 'asc',
			),
		),
		'table_columns' => array('name', 'priority', 'date_started', 'date_deadline', 'date_completed')
	),
		
	'late_projects'=> array(
		'obj_type' => 'project',
		'name' => 'Late Projects',
		'description' => '',
		'default' => false,
		'conditions' => array(
			'deadline' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'date_deadline',
				'operator' => Where::OPERATOR_LESS_THAN,
				'value' => 'now'
			),
		),
		'order_by' => array(
			'name' => array(
				'field_name' => 'name',
				'direction' => 'asc',
			),
		),
		'table_columns' => array('name', 'priority', 'date_started', 'date_deadline', 'date_completed')
	),
);
