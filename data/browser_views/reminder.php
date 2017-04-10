<?php
/**
 * Return browser views for entity of object type 'reminder'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'my_reminders'=> array(
		'obj_type' => 'reminder',
		'name' => 'My Reminders',
		'description' => 'Display all my reminders',
		'default' => true,
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
    			'direction' => 'asc',
    		),
		),
		'table_columns' => array('name', 'ts_execute')
    ),

	'all_reminders'=> array(
		'obj_type' => 'reminder',
		'name' => 'All Reminders',
		'description' => 'Display all reminders',
		'default' => false,
		'order_by' => array(
			'date' => array(
				'field_name' => 'ts_entered',
				'direction' => 'asc',
			),
		),
		'table_columns' => array('name', 'ts_execute')
	),
);
