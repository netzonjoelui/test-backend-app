<?php
/**
 * Return browser views for entity of object type 'opportunity'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
	'my_open_opportunities'=> array(
		'obj_type' => 'opportunity',
		'name' => 'My Open Opportunities',
		'description' => 'Opportunities assigned to me that are not closed',
		'default' => true,
		'conditions' => array(
			'owner' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'owner_id',
				'operator' => Where::OPERATOR_EQUAL_TO,
				'value' => -3
			),
			'closed' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'f_closed',
				'operator' => Where::OPERATOR_NOT_EQUAL_TO,
				'value' => 't'
			),
		),
		'order_by' => array(
			'date' => array(
				'field_name' => 'ts_entered',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'customer_id', 'stage_id', 'amount', 'expected_close_date', 'probability_per', 'type_id')
	),

	'all_my_opportunities'=> array(
		'obj_type' => 'opportunity',
		'name' => 'All My Opportunities',
		'description' => 'Opportunities Assigned To Me',
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
			'date' => array(
				'field_name' => 'ts_entered',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'customer_id', 'stage_id', 'amount', 'expected_close_date', 'probability_per', 'owner_id')
	),

	'all_open_opportunities'=> array(
		'obj_type' => 'opportunity',
		'name' => 'All Open Opportunities',
		'description' => 'Opportunities Assigned To Me',
		'default' => false,
		'conditions' => array(
			'closed' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'f_closed',
				'operator' => Where::OPERATOR_NOT_EQUAL_TO,
				'value' => 't'
			),
		),
		'order_by' => array(
			'date' => array(
				'field_name' => 'ts_entered',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'customer_id', 'stage_id', 'amount', 'expected_close_date', 'probability_per', 'owner_id')
	),

	'all_opportunities'=> array(
		'obj_type' => 'opportunity',
		'name' => 'All Opportunities',
		'description' => 'All Opportunities',
		'default' => false,
		'order_by' => array(
			'date' => array(
				'field_name' => 'ts_entered',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'customer_id', 'stage_id', 'amount', 'expected_close_date', 'probability_per', 'owner_id')
	),
);
