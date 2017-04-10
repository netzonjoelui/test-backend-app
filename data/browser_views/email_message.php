<?php
/**
 * Return browser views for entity of object type 'email_message'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'email_messages'=> array(
		'obj_type' => 'email_message',
		'name' => 'Email Messages',
		'description' => '',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'message_date',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('subject', 'message_date', 'sent_from', 'send_to', 'priority')
    ),
);
