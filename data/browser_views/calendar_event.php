<?php
/**
 * Return browser views for entity of object type 'calendar_event'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'upcoming_events'=> array(
		'obj_type' => 'calendar_event',
		'name' => 'Upcoming Events',
		'description' => 'Events occurring in the future',
		'default' => true,
		'conditions' => array(
            'start' => array(
                'blogic' => Where::COMBINED_BY_AND,
                'field_name' => 'ts_start',
                'operator' => Where::OPERATOR_LESS_THAN_OR_EQUAL_TO,
                'value' => 'now'
            ),
        ),
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_start',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'location', 'ts_start', 'ts_end', 'user_id')
    ),
		
	'my_past_events'=> array(
		'obj_type' => 'calendar_event',
		'name' => 'My Past Events',
		'description' => 'Events that occurred in the past',
		'default' => false,
		'conditions' => array(
			'start' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'ts_start',
				'operator' => Where::OPERATOR_LESS_THAN,
				'value' => 'now'
			),
		),
		'order_by' => array(
			'date' => array(
				'field_name' => 'ts_start',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'location', 'ts_start', 'ts_end', 'user_id')
	),
		
	'all_events'=> array(
		'obj_type' => 'calendar_event',
		'name' => 'All Events',
		'description' => 'All Events',
		'default' => false,
		'order_by' => array(
			'date' => array(
				'field_name' => 'ts_start',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'location', 'ts_start', 'ts_end', 'user_id')
	),
);
