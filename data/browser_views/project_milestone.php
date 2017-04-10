<?php
/**
 * Return browser views for entity of object type 'project_milestone'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'default'=> array(
		'obj_type' => 'project_milestone',
		'name' => 'Default View',
		'description' => 'All Milestones',
		'default' => true,
		'order_by' => array(
			'deadline' => array(
    			'field_name' => 'deadline',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'deadline', 'user_id', 'project_id', 'f_completed')
    ),
);
