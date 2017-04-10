<?php
/**
 * Return browser views for entity of object type 'discussion'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'discussions'=> array(
		'obj_type' => 'discussion',
		'name' => 'Discussions',
		'description' => 'Discussions',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_updated',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'ts_updated', 'ts_entered', 'owner_id', 'obj_reference')
    ),
);
