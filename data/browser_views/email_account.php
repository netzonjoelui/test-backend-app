<?php
/**
 * Return browser views for entity of object type 'email_account'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_email_accounts'=> array(
        'obj_type' => 'email_account',
        'name' => 'All Email Accounts',
        'description' => 'Display all email accounts',
        'default' => true,
        'order_by' => array(
            'date' => array(
                'field_name' => 'name',
                'direction' => 'asc',
            ),
        ),
        'table_columns' => array('name', "address", 'reply_to', 'f_default')
    ),
);
