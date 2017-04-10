<?php
/**
 * Return browser views for entity of object type 'note'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'files_and_documents'=> array(
		'obj_type' => 'file',
		'name' => 'Files &amp; Documents',
		'description' => 'View all files and directories',
		'default' => true,
		'order_by' => array(
			'name' => array(
    			'field_name' => 'name',
    			'direction' => 'desc',
    		),
		),
		'table_columns' => array('name', 'ts_updated', 'owner_id', 'file_size')
    ),
);
