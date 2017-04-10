<?php
/**
 * Return browser views for entity of object type 'infocenter_document'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_documents'=> array(
		'obj_type' => 'infocenter_document',
		'name' => 'All Documents',
		'description' => 'All InfoCenter Documents',
		'default' => true,
		'order_by' => array(
			'title' => array(
    			'field_name' => 'title',
    			'direction' => 'asc',
    		),
		),
		'table_columns' => array('title', 'keywords', 'ts_updated', 'owner_id')
    ),
);
