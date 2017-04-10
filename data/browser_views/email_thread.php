<?php
/**
 * Return browser views for entity of object type 'email_thread'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'email_threads'=> array(
		'obj_type' => 'email_thread',
		'name' => 'Email Threads',
		'description' => '',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_delivered',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('senders', 'subject', 'ts_delivered', 'f_seen', 'f_flagged', 'num_attachments', 'num_messages')
    ),
);
