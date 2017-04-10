<?php
/**
 * Return browser views for entity of object type 'activity'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_activity'=> array(
		'obj_type' => 'activity',
		'name' => 'All Activity',
		'description' => '',
		'default' => true,
		'conditions' => array(
            'level' => array(
                'blogic' => Where::COMBINED_BY_AND,
                'field_name' => 'level',
                'operator' => Where::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
                'value' => 3
            ),
        ),
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_entered',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference")
    ),
		
	'my_team_activity'=> array(
		'obj_type' => 'activity',
		'name' => 'My Team Activity',
		'description' => '',
		'default' => false,
		'conditions' => array(
			'level' => array(
					'blogic' => Where::COMBINED_BY_AND,
					'field_name' => 'level',
					'operator' => Where::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
					'value' => 3
			),
			'team' => array(
					'blogic' => Where::COMBINED_BY_AND,
					'field_name' => 'user_id.team_id',
					'operator' => Where::OPERATOR_EQUAL_TO,
					'value' => -3
			),
		),
		'order_by' => array(
			'date' => array(
					'field_name' => 'ts_entered',
					'direction' => 'desc',
			),
		),
		'table_columns' => array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference")
	),
		
	'my_activity'=> array(
		'obj_type' => 'activity',
		'name' => 'My Activity',
		'description' => '',
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
					'field_name' => 'ts_entered',
					'direction' => 'desc',
			),
		),
		'table_columns' => array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference")
	),
		
	'tasks'=> array(
		'obj_type' => 'activity',
		'name' => 'Tasks',
		'description' => '',
		'default' => false,
		'conditions' => array(
			'type' => array(
					'blogic' => Where::COMBINED_BY_AND,
					'field_name' => 'type_id',
					'operator' => Where::OPERATOR_EQUAL_TO,
					'value' => "Task"
			),
		),
		'order_by' => array(
			'date' => array(
					'field_name' => 'ts_entered',
					'direction' => 'desc',
			),
		),
		'table_columns' => array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference")
	),
		
	'comments'=> array(
		'obj_type' => 'activity',
		'name' => 'Commments',
		'description' => '',
		'default' => false,
		'conditions' => array(
			'type' => array(
					'blogic' => Where::COMBINED_BY_AND,
					'field_name' => 'type_id',
					'operator' => Where::OPERATOR_EQUAL_TO,
					'value' => "Comment"
			),
		),
		'order_by' => array(
			'date' => array(
					'field_name' => 'ts_entered',
					'direction' => 'desc',
			),
		),
		'table_columns' => array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference")
	),
		
	'status_updates'=> array(
		'obj_type' => 'activity',
		'name' => 'Status Updates',
		'description' => '',
		'default' => false,
		'conditions' => array(
			'type' => array(
					'blogic' => Where::COMBINED_BY_AND,
					'field_name' => 'type_id',
					'operator' => Where::OPERATOR_EQUAL_TO,
					'value' => "Status Update"
			),
		),
		'order_by' => array(
			'date' => array(
					'field_name' => 'ts_entered',
					'direction' => 'desc',
			),
		),
		'table_columns' => array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference")
	),
		
	'phone_calls'=> array(
		'obj_type' => 'activity',
		'name' => 'Phone Calls',
		'description' => '',
		'default' => false,
		'conditions' => array(
			'type' => array(
					'blogic' => Where::COMBINED_BY_AND,
					'field_name' => 'type_id',
					'operator' => Where::OPERATOR_EQUAL_TO,
					'value' => "Phone Call"
			),
		),
		'order_by' => array(
			'date' => array(
					'field_name' => 'ts_entered',
					'direction' => 'desc',
			),
		),
		'table_columns' => array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference")
	),
		
	'calendar_events'=> array(
		'obj_type' => 'activity',
		'name' => 'Calendar Events',
		'description' => '',
		'default' => false,
		'conditions' => array(
			'type' => array(
					'blogic' => Where::COMBINED_BY_AND,
					'field_name' => 'type_id',
					'operator' => Where::OPERATOR_EQUAL_TO,
					'value' => "Event"
			),
		),
		'order_by' => array(
			'date' => array(
					'field_name' => 'ts_entered',
					'direction' => 'desc',
			),
		),
		'table_columns' => array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference")
	),
		
	'email'=> array(
		'obj_type' => 'activity',
		'name' => 'Email',
		'description' => '',
		'default' => false,
		'conditions' => array(
			'type' => array(
					'blogic' => Where::COMBINED_BY_AND,
					'field_name' => 'type_id',
					'operator' => Where::OPERATOR_EQUAL_TO,
					'value' => "Email"
			),
		),
		'order_by' => array(
			'date' => array(
					'field_name' => 'ts_entered',
					'direction' => 'desc',
			),
	),
		'table_columns' => array("name", "type_id", "direction", "ts_entered", "user_id", "notes", "obj_reference")
	),
);
