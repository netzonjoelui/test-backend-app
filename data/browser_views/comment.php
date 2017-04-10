<?php
/**
 * Return browser views for entity of object type 'comment'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'default'=> array(
		'obj_type' => 'comment',
		'name' => 'Comments',
		'description' => '',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_entered',
    			'direction' => 'asc',
    		),
		),
		'table_columns' => array('owner_id', 'ts_entered', 'obj_reference', 'comment', 'notified', 'sent_by')
    ),
);
