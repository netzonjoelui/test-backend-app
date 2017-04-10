<?php
/**
 * Return browser views for entity of object type 'time'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'my_time'=> array(
		'obj_type' => 'time',
		'name' => 'My Time',
		'description' => '',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'date_applied',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('date_applied', 'owner_id', 'hours', 'name', 'task_id')
    ),
		
	'my_teams_time'=> array(
		'obj_type' => 'time',
		'name' => "My Team's Time",
		'description' => '',
		'default' => false,
		'conditions' => array(
			'team' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'owner_id.team_id',
				'operator' => Where::OPERATOR_EQUAL_TO,
				'value' => -3
			),
		),
		'order_by' => array(
			'date' => array(
				'field_name' => 'date_applied',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('date_applied', 'owner_id', 'hours', 'name', 'task_id')
	),
		
	'all_time'=> array(
		'obj_type' => 'time',
		'name' => "All Time",
		'description' => '',
		'default' => false,
		'order_by' => array(
			'date' => array(
				'field_name' => 'date_applied',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('date_applied', 'owner_id', 'hours', 'name', 'task_id')
	),
);
