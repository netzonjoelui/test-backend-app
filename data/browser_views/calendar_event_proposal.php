<?php
/**
 * Return browser views for entity of object type 'calendar_event_proposal'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'meeting_proposals'=> array(
		'obj_type' => 'calendar_event_proposal',
		'name' => 'Meeting Proposals',
		'description' => 'Meeting proposals that are still in process',
		'default' => true,
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
		'table_columns' => array('name', 'location', 'status_id', 'ts_updated')
    ),
		
	'closed_proposals'=> array(
		'obj_type' => 'calendar_event_proposal',
		'name' => 'Closed Proposals',
		'description' => 'Meeting proposals that have been closed and/or converted to events.',
		'default' => false,
		'conditions' => array(
			'closed' => array(
					'blogic' => Where::COMBINED_BY_AND,
					'field_name' => 'f_closed',
					'operator' => Where::OPERATOR_EQUAL_TO,
					'value' => 't'
			),
		),
		'order_by' => array(
			'date' => array(
					'field_name' => 'ts_entered',
					'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'location', 'status_id', 'ts_updated')
	),
		
	'all_meeting_proposals'=> array(
		'obj_type' => 'calendar_event_proposal',
		'name' => 'All Meeting Proposals',
		'description' => 'All Meeting Proposals',
		'default' => false,		
		'order_by' => array(
			'date' => array(
					'field_name' => 'ts_entered',
					'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'location', 'status_id', 'ts_updated')
	),
);
