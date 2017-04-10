<?php
/**
 * email_account object definition
 */
$obj_revision = 7;

$isPrivate = true;
$defaultActivityLevel = 1;
$storeRevisions = true;

$obj_fields = array(
    // Textual name of the account
    'name' => array(
        'title'=>'Title',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false,
        'require'=>true
    ),

    "type" => array(
        'title'=>'Server Type',
        'type'=>'text',
        'subtype'=>'4',
        'readonly'=>false,
        'optional_values'=>array(
            "none"=>"None - just reply from this address",
            "imap"=>"IMAP",
            "pop3"=>"POP3",
        ),
    ),

    'address' => array(
        'title'=>'Email Address',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false,
        'require'=>true
    ),

    'reply_to' => array(
        'title'=>'Reply To',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false
    ),

    'signature' => array(
        'title'=>'Signature',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false
    ),

    'host' => array(
        'title'=>'Host',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false,
        'require'=>true
    ),

    'username' => array(
        'title'=>'Username',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false,
        'require'=>true
    ),

    'password' => array(
        'title'=>'Password',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false,
        'require'=>true
    ),

    'port' => array(
        'title'=>'Port',
        'type'=>'number',
        'subtype'=>'integer',
        'readonly'=>false,
        'require'=>true
    ),

    'f_default' => array(
        'title'=>'Default Account',
        'type'=>'bool',
        'readonly'=>false
    ),

    'f_ssl' => array(
        'title'=>'Require SSL',
        'type'=>'bool',
        'readonly'=>false
    ),

    'sync_data' => array(
        'title'=>'Sync Data',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false
    ),

    'ts_last_full_sync' => array(
        'title'=>'Last Full Sync',
        'type'=>'timestamp',
        'subtype'=>'',
        'readonly'=>false
    ),

    'f_synchronizing' => array(
        'title'=>'Sync In Process',
        'type'=>'bool',
        'subtype'=>'true'
    ),

    'f_system' => array(
        'title'=>'System',
        'type'=>'bool',
        'readonly'=>false
    ),

    'f_outgoing_auth' => array(
        'title'=>'Outgoing Auth',
        'type'=>'bool',
        'readonly'=>false
    ),

    'host_out' => array(
        'title'=>'Host Out',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false
    ),

    'port_out' => array(
        'title'=>'Port Out',
        'type'=>'number',
        'subtype'=>'integer',
        'readonly'=>false
    ),

    'f_ssl_out' => array(
        'title'=>'SSL Out',
        'type'=>'bool',
        'readonly'=>false
    ),

    'username_out' => array(
        'title'=>'Username Out',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false
    ),

    'password_out' => array(
        'title'=>'Password Out',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false
    ),

    'forward' => array(
        'title'=>'Forward',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false
    ),

    'owner_id' => array(
        'title'=>'Owner',
        'type'=>'object',
        'subtype'=>'user'
    )
);