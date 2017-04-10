<?php
/**
 * Return browser views for entity of object type 'email_message_attachment'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_email_attachments'=> array(
		'obj_type' => 'email_message_attachment',
		'name' => 'All Email Attachments',
		'description' => 'My Email Message Attachments',
		'default' => true,
		'order_by' => array(
			'date' => array(
    			'field_name' => 'ts_entered',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'filename', 'phone_home', 'phone_work', 'email_default', 'image_id')
    ),
);
