<?php
/**
 * Return browser views for entity of object type 'calendar'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'all_calendars'=> array(
		'obj_type' => 'calendar',
		'name' => 'All Calendars',
		'description' => 'Viewing All Calendars',
		'default' => true,
		'public' => array(
            'user' => array(
                'blogic' => Where::COMBINED_BY_AND,
                'field_name' => 'f_public',
                'operator' => Where::OPERATOR_EQUAL_TO,
                'value' => 't'
            ),
        ),
		'order_by' => array(
			'date' => array(
    			'field_name' => 'name',
    			'direction' => 'asc',
    		),
		),
		'table_columns' => array('name', 'description', 'user_id')
    ),
);
