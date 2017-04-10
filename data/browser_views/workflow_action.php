<?php
/**
 * Return browser views for entity of object type 'workflow_action'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_workflow_actions'=> array(
        'obj_type' => 'workflow_action',
        'name' => 'All Actions',
        'description' => 'Display all actions',
        'default' => true,
        'order_by' => array(
            'order' => array(
                'field_name' => 'id',
                'direction' => 'asc',
            ),
        ),
        'table_columns' => array('name', 'type_name')
    ),
);
