<?php
/**
 * Return browser views for entity of object type 'case'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_open_cases'=> array(
		'obj_type' => 'case',
		'name' => 'All Open Cases',
		'description' => 'All that have not yet been closed',
		'default' => true,
		'conditions' => array(
            'not_resolved' => array(
                'blogic' => Where::COMBINED_BY_AND,
                'field_name' => 'status_id',
                'operator' => Where::OPERATOR_NOT_EQUAL_TO,
                'value' => 'Closed: Resolved'
            ),
        	'unresolved' => array(
        		'blogic' => Where::COMBINED_BY_AND,
        		'field_name' => 'status_id',
        		'operator' => Where::OPERATOR_NOT_EQUAL_TO,
        		'value' => 'Closed: Unresolved'
        	),
        		
        ),
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_entered',
    			'direction' => 'desc',
    		),
			'severity' => array(
					'field_name' => 'severity_id',
					'direction' => 'desc',
			),
		),
		'table_columns' => array('title', 'status_id', 'type_id', 'severity_id', 'owner_id', 'created_by', 'project_id', 'date_reported')
    ),
		
	'my_cases'=> array(
		'obj_type' => 'case',
		'name' => 'My Cases',
		'description' => 'Cases Assigned To Me',
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
			'severity' => array(
				'field_name' => 'severity_id',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('title', 'status_id', 'type_id', 'severity_id', 'owner_id', 'created_by', 'project_id', 'date_reported')
	),
		
	'all cases'=> array(
		'obj_type' => 'case',
		'name' => 'All Cases',
		'description' => 'All cases in any status',
		'default' => false,
		'order_by' => array(
			'date' => array(
				'field_name' => 'ts_entered',
				'direction' => 'desc',
			),
			'severity' => array(
				'field_name' => 'severity_id',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('title', 'status_id', 'type_id', 'severity_id', 'owner_id', 'created_by', 'project_id', 'date_reported')
	),
);
