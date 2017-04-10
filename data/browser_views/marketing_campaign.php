<?php
/**
 * Return browser views for entity of object type 'marketing_campaign'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_campaigns'=> array(
		'obj_type' => 'marketing_campaign',
		'name' => 'All Campaigns',
		'description' => 'View all campaigns both active and inactive',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'date_start',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'status_id', 'type_id', 'date_start')
    ),
);
