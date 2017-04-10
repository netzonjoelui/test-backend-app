<?php
/**
 * Return browser views for entity of object type 'content_feed_post'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_posts'=> array(
		'obj_type' => 'content_feed_post',
		'name' => 'All Posts',
		'description' => 'All Content Feed Posts',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'time_entered',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('title', 'status_id', 'user_id', 'time_entered', 'ts_updated')
    ),
		
	'drafts'=> array(
		'obj_type' => 'content_feed_post',
		'name' => 'Drafts',
		'description' => 'Drafts',
		'default' => false,
		'conditions' => array(
			'not_publish' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'f_publish',
				'operator' => Where::OPERATOR_NOT_EQUAL_TO,
				'value' => 't'
			),
		),
		'order_by' => array(
			'date' => array(
					'field_name' => 'time_entered',
					'direction' => 'desc',
			),
		),
		'table_columns' => array('title', 'user_id', 'time_entered', 'ts_updated')
	),
		
	'published'=> array(
		'obj_type' => 'content_feed_post',
		'name' => 'Published',
		'description' => 'All published posts',
		'default' => false,
		'conditions' => array(
			'not_publish' => array(
				'blogic' => Where::COMBINED_BY_AND,
				'field_name' => 'f_publish',
				'operator' => Where::OPERATOR_EQUAL_TO,
				'value' => 't'
			),
		),
		'order_by' => array(
			'date' => array(
				'field_name' => 'time_entered',
				'direction' => 'desc',
			),
		),
		'table_columns' => array('title', 'user_id', 'time_entered', 'ts_updated')
	),
);
