<?php
/**
 * Return browser views for entity of object type 'member'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_members'=> array(
		'obj_type' => 'member',
		'name' => 'All Members',
		'description' => '',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_entered',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'role', 'f_accepted', 'ts_entered', 'obj_reference')
    ),
);
