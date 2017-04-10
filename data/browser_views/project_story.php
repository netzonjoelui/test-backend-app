<?php
/**
 * Return browser views for entity of object type 'project_story'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_stories'=> array(
		'obj_type' => 'project_story',
		'name' => 'All Stories',
		'description' => 'View all stories both in the backlog and those assigned to a milestone/sprint',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_entered',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'status_id', 'priority_id', 'owner_id', 'cost_estimated')
    ),
		
	'in_progress'=> array(
		'obj_type' => 'project_story',
		'name' => 'In-Progress',
		'description' => 'Stories that are currently being worked on',
		'default' => false,
		'conditions' => array(
			'status' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'status_id',
				'operator' => Where::OPERATOR_EQUAL_TO,
				'value' => 'In-Progress'
			),
		),
		'order_by' => array(
			'priority' => array(
				'field_name' => 'priority_id',
				'direction' => 'desc',
			),
			'date' => array(
				'field_name' => 'ts_entered',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'status_id', 'priority_id', 'owner_id', 'cost_estimated')
	),
		
	'backlog'=> array(
		'obj_type' => 'project_story',
		'name' => 'Backlog',
		'description' => 'Stories not yet assigned to a milestone/sprint and incomplete',
		'default' => false,
		'conditions' => array(
			'milestone' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'milestone_id',
				'operator' => Where::OPERATOR_EQUAL_TO,
				'value' => ''
			),
			'status_not_completed' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'is_not_equal',
				'operator' => Where::OPERATOR_NOT_EQUAL_TO,
				'value' => 'Completed'
			),
			'status_not_rejected' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'is_not_equal',
				'operator' => Where::OPERATOR_NOT_EQUAL_TO,
				'value' => 'Rejected'
			),
		),
		'order_by' => array(
			'priority' => array(
				'field_name' => 'priority_id',
				'direction' => 'desc',
			),
			'date' => array(
				'field_name' => 'ts_entered',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'status_id', 'priority_id', 'owner_id', 'cost_estimated')
	),
		
	'completed'=> array(
		'obj_type' => 'project_story',
		'name' => 'Completed',
		'description' => 'Stories that are completed',
		'default' => false,
		'conditions' => array(
			'status' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'status_id',
				'operator' => Where::OPERATOR_EQUAL_TO,
				'value' => 'Completed'
			),
		),
		'order_by' => array(
			'date' => array(
				'field_name' => 'ts_entered',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'status_id', 'priority_id', 'owner_id', 'cost_estimated')
	),
);
