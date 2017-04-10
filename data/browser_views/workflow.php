<?php
/**
 * Return browser views for entity of object type 'workflow'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_workflows'=> array(
        'obj_type' => 'workflow',
        'name' => 'All Workflows',
        'description' => 'Display all workflows',
        'default' => true,
        'order_by' => array(
            'date' => array(
                'field_name' => 'name',
                'direction' => 'asc',
            ),
        ),
        'table_columns' => array('name', "object_type", 'f_active')
    ),
);
