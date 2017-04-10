<?php
/**
 * Return browser views for entity of object type 'note'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'default'=> array(
		'obj_type' => 'note',
		'name' => 'Default View',
		'description' => 'My Notes',
		'default' => true,
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
		'table_columns' => array('name', 'ts_entered', 'body')
    ),
    'test'=> array(
        'obj_type' => 'note',
        'name' => 'Test',
        'description' => '',
        'default' => false,
        'conditions' => array(
            'user' => array(
                'blogic' => Where::COMBINED_BY_AND,
                'field_name' => 'user_id',
                'operator' => Where::OPERATOR_EQUAL_TO,
                'value' => -3
            ),
            'ts_entered' => array(
                'blogic' => Where::COMBINED_BY_AND,
                'field_name' => 'name',
                'operator' => Where::OPERATOR_CONTAINS,
                'value' => 'test'
            ),
        ),
        'order_by' => array(
            'date' => array(
                'field_name' => 'ts_entered',
                'direction' => 'desc',
            ),
        ),
        'table_columns' => array('name', 'ts_entered', 'body')
    ),
);
