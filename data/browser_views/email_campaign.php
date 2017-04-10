<?php
/**
 * Return browser views for entity of object type 'email_campaign'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_email_campaigns'=> array(
		'obj_type' => 'email_campaign',
		'name' => 'All Email Campaigns',
		'description' => 'Display all available HTML templates for all object types',
		'default' => true,
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'description')
    ),
);
