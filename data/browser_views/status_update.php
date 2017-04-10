<?php
/**
 * Return browser views for entity of object type 'status_update'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'news_feed'=> array(
		'obj_type' => 'status_update',
		'name' => 'News Feed',
		'description' => '',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_entered',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('owner_id', 'ts_entered', 'obj_reference', 'comment', 'notified', 'sent_by')
    ),
		
	'my_status_updates'=> array(
		'obj_type' => 'status_update',
		'name' => 'My Status Updates',
		'description' => '',
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
		'table_columns' => array('owner_id', 'ts_entered', 'obj_reference', 'comment', 'notified', 'sent_by')
	),
);
