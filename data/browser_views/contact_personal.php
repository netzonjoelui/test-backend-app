<?php
/**
 * Return browser views for entity of object type 'contact_personal'
 */
namespace data\browser_views;

use Netric\EntityQuery\Where;

return array(
    'my_contacts'=> array(
		'obj_type' => 'contact_personal',
		'name' => 'My Contacts',
		'description' => 'User Contacts',
		'default' => true,
		'conditions' => array(
            'user' => array(
                'blogic' => Where::COMBINED_BY_AND,
                'field_name' => 'user_id',
                'operator' => Where::OPERATOR_EQUAL_TO,
                'value' => -3
            ),
        ),
		'order_by' => array(
			'first_name' => array(
    			'field_name' => 'first_name',
    			'direction' => 'asc',
    		),
			'last_name' => array(
				'field_name' => 'last_name',
				'direction' => 'asc',
			),
		),
		'table_columns' => array('name', 'phone_cell', 'phone_home', 'phone_work', 'email_default', 'city', 'state')
    ),
);
