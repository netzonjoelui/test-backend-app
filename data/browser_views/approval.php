<?php
/**
 * Return browser views for entity of object type 'approval'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'awaiting_my_approval'=> array(
		'obj_type' => 'approval',
		'name' => 'Awaiting My Approval',
		'description' => '',
		'default' => false,
		'conditions' => array(
            'owner' => array(
                'blogic' => Where::COMBINED_BY_AND,
                'field_name' => 'owner_id',
                'operator' => Where::OPERATOR_EQUAL_TO,
                'value' => -3
            ),
			'status' => array(
        		'blogic' => Where::COMBINED_BY_AND,
        		'field_name' => 'status',
        		'operator' => Where::OPERATOR_EQUAL_TO,
        		'value' => 'awaiting'
        	),
        ),
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_entered',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'status', 'requested_by', 'owner_id', 'ts_entered')
    ),
		
	'all_approval_request'=> array(
		'obj_type' => 'approval',
		'name' => 'All Approval Requests',
		'description' => '',
		'default' => false,
		'order_by' => array(
			'date' => array(
					'field_name' => 'ts_entered',
					'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'status', 'requested_by', 'owner_id', 'ts_entered')
	),
		
	'my_approved'=> array(
		'obj_type' => 'approval',
		'name' => 'My Approved',
		'description' => '',
		'default' => true,
		'conditions' => array(
			'user' => array(
					'blogic' => Where::COMBINED_BY_AND,
					'field_name' => 'user_id',
					'operator' => Where::OPERATOR_EQUAL_TO,
					'value' => -3
			),
			'status' => array(
					'blogic' => Where::COMBINED_BY_AND,
					'field_name' => 'status',
					'operator' => Where::OPERATOR_EQUAL_TO,
					'value' => 'approved'
			),
		),
		'order_by' => array(
			'date' => array(
					'field_name' => 'ts_entered',
					'direction' => 'desc',
			),
		),
		'table_columns' => array('name', 'status', 'requested_by', 'owner_id', 'ts_entered')
	),
);
