<?php
/**
 * Return browser views for entity of object type 'note'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'my_tasks'=> array(
		'obj_type' => 'task',
		'name' => 'My Incomplete Tasks',
		'description' => 'Incomplete tasks assigned to me',
		'default' => true,
		'conditions' => array(
            'user' => array(
                'blogic' => Where::COMBINED_BY_AND,
                'field_name' => 'user_id',
                'operator' => Where::OPERATOR_EQUAL_TO,
                'value' => -3
            ),
			'done' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'done',
				'operator' => Where::OPERATOR_NOT_EQUAL_TO,
        		'value' => 't'
        	),
        ),
    	'order_by' => array(
			'date' => array(
				'field_name' => 'date_entered',
				'direction' => 'desc',    		
    		),
    		'deadline' => array(
				'field_name' => 'deadline',
    			'direction' => 'asc'
			),
		),
    	'table_columns' => array('name', 'project', 'priority',  'deadline', 'done', 'user_id')
    ),
		
	'my_tasks_due_today' => array(
		'obj_type' => 'task',
		'name' => 'My Incomplete Tasks (due today)',
		'description' => 'Incomplete tasks assigned to me that are due today',
		'default' => false,
		'conditions' => array(
				'user' => array(
						'blogic' => Where::COMBINED_BY_AND,
						'field_name' => 'user_id',
						'operator' => Where::OPERATOR_EQUAL_TO,
						'value' => -3
				),
				'done' => array(
						'blogic' => Where::COMBINED_BY_AND,
						'field_name' => 'done',
						'operator' => Where::OPERATOR_NOT_EQUAL_TO,
						'value' => 't'
				),
				'deadline' => array(
						'blogic' => Where::COMBINED_BY_AND,
						'field_name' => 'deadline',
						'operator' => Where::OPERATOR_LESS_THAN_OR_EQUAL_TO,
						'value' => 'now'
				),
		),
		'order_by' => array(
				'date' => array(
						'field_name' => 'date_entered',
						'direction' => 'desc',
				),
				'deadline' => array(
						'field_name' => 'deadline',
						'direction' => 'asc'
				),
		),
		'table_columns' => array('name', 'project', 'priority',  'deadline', 'done', 'user_id')
	),
		
	'all_my_tasks' => array(
		'obj_type' => 'task',
		'name' => 'All My Tasks',
		'description' => 'All tasks assigned to me',
		'default' => false,
		'conditions' => array(
				'user' => array(
						'blogic' => Where::COMBINED_BY_AND,
						'field_name' => 'user_id',
						'operator' => Where::OPERATOR_EQUAL_TO,
						'value' => -3
				),
		),
		'order_by' => array(
				'date' => array(
						'field_name' => 'date_entered',
						'direction' => 'desc',
				),
				'deadline' => array(
						'field_name' => 'deadline',
						'direction' => 'asc'
				),
		),
		'table_columns' => array('name', 'project', 'priority',  'deadline', 'done', 'user_id')
	),
		
	'tasks_i_have_assigned' => array(
		'obj_type' => 'task',
		'name' => 'Tasks I Have Assigned',
		'description' => 'Tasks that were created by me but assigned to someone else',
		'default' => false,
		'conditions' => array(
				'creator' => array(
						'blogic' => Where::COMBINED_BY_AND,
						'field_name' => 'creator_id',
						'operator' => Where::OPERATOR_EQUAL_TO,
						'value' => -3
				),
				'user' => array(
						'blogic' => Where::COMBINED_BY_AND,
						'field_name' => 'user_id',
						'operator' => Where::OPERATOR_NOT_EQUAL_TO,
						'value' => -3
				),
				'done' => array(
						'blogic' => Where::COMBINED_BY_AND,
						'field_name' => 'done',
						'operator' => Where::OPERATOR_NOT_EQUAL_TO,
						'value' => 't'
				),
		),
		'order_by' => array(
				'date' => array(
						'field_name' => 'date_entered',
						'direction' => 'desc',
				),
				'deadline' => array(
						'field_name' => 'deadline',
						'direction' => 'asc'
				),
		),
		'table_columns' => array('name', 'project', 'priority',  'deadline', 'done', 'user_id')
	),
		
	'all_incomplete_tasks' => array(
		'obj_type' => 'task',
		'name' => 'All Incomplete Tasks',
		'description' => 'All Tasks that have not yet been completed',
		'default' => false,
		'conditions' => array(
				'done' => array(
						'blogic' => Where::COMBINED_BY_AND,
						'field_name' => 'done',
						'operator' => Where::OPERATOR_NOT_EQUAL_TO,
						'value' => 't'
				),
		),
		'order_by' => array(
				'date' => array(
						'field_name' => 'date_completed',
						'direction' => 'desc',
				),
				'deadline' => array(
						'field_name' => 'deadline',
						'direction' => 'asc'
				),
		),
		'table_columns' => array('name', 'project', 'priority',  'deadline', 'done', 'user_id')
	),
		
	'all_tasks' => array(
		'obj_type' => 'task',
		'name' => 'All Tasks',
		'description' => 'All Tasks',
		'default' => false,
		'order_by' => array(
				'date' => array(
						'field_name' => 'date_entered',
						'direction' => 'desc',
				),
				'deadline' => array(
						'field_name' => 'deadline',
						'direction' => 'asc'
				),
		),
		'table_columns' => array('name', 'project', 'priority',  'deadline', 'done', 'user_id')
	),
);
