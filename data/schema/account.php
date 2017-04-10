<?php
/**
 * Schema file for an account's database
 *
 * This is the new schema file for netric. All changes to the schema will be entered here
 * and each time 'netric update' is run it will go through every table and make sure
 * every column exists and matches the type.
 *
 * Column drops will need to be handled in the update deltas found in ../../bin/scripts/update/* but now
 * all deltas must assume the newest schema so they will be used for post-update processing,
 * to migrate data, and to clean-up after previous changes.
 */
namespace data\schema;

use Netric\Application\Schema\SchemaProperty;

return array(
    /**
     * Activity types are groupings used to track types
     */
    "activity_types" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'obj_type' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("obj_type")),
        )
    ),

    "app_object_field_defaults" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'field_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'on_event' => array('type' => SchemaProperty::TYPE_CHAR_32, 'notnull' => true),
            'value' => array('type' => SchemaProperty::TYPE_CHAR_TEXT, 'notnull' => true),
            'coalesce' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'where_cond' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("field_id")),
        )
    ),
    "app_object_field_options" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'field_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'key' => array('type' => SchemaProperty::TYPE_CHAR_TEXT, 'notnull' => true),
            'value' => array('type' => SchemaProperty::TYPE_CHAR_TEXT, 'notnull' => true),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("field_id")),
        )
    ),
    "app_object_imp_maps" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'template_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'col_name' => array('type' => SchemaProperty::TYPE_CHAR_256, 'notnull' => true),
            'property_name' => array('type' => SchemaProperty::TYPE_CHAR_256, 'notnull' => true),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("template_id")),
        )
    ),

    "app_object_imp_templates" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'type_id' => array('type' => SchemaProperty::TYPE_INT),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("type_id")),
            array('properties' => array("user_id")),
        )
    ),

    "app_object_list_cache" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'ts_created' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'query' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'total_num' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("query")),
        )
    ),

    "app_object_list_cache_flds" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'list_id' => array('type' => SchemaProperty::TYPE_INT),
            'field_id' => array('type' => SchemaProperty::TYPE_INT),
            'value' => array('type' => SchemaProperty::TYPE_CHAR_512),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("list_id")),
            array('properties' => array("field_id")),
            array('properties' => array("value")),
        )
    ),

    "app_object_list_cache_res" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'list_id' => array('type' => SchemaProperty::TYPE_INT),
            'results' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("list_id")),
        )
    ),

    "app_object_type_fields" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'type_id' => array('type' => SchemaProperty::TYPE_INT),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'title' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'type' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'subtype' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'fkey_table_key' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'fkey_multi_tbl' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'fkey_multi_this' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'fkey_multi_ref' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'fkey_table_title' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'sort_order' => array('type' => SchemaProperty::TYPE_INT, "default" => '0'),
            'parent_field' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'autocreatebase' => array("type" => SchemaProperty::TYPE_CHAR_TEXT),
            'autocreatename' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'mask' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'f_readonly' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'autocreate' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_system' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_required' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'filter' => array("type" => SchemaProperty::TYPE_CHAR_TEXT),
            'use_when' => array("type" => SchemaProperty::TYPE_CHAR_TEXT),
            'f_indexed' => array("type" => SchemaProperty::TYPE_BOOL),
            'f_unique' => array("type" => SchemaProperty::TYPE_BOOL),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("type_id")),
        )
    ),

    "app_object_type_frm_layouts" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'type_id' => array('type' => SchemaProperty::TYPE_INT),
            'team_id' => array('type' => SchemaProperty::TYPE_INT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'scope' => array("type" => SchemaProperty::TYPE_CHAR_128),
            'form_layout_xml' => array("type" => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("type_id")),
            array('properties' => array("team_id")),
            array('properties' => array("user_id")),
            array('properties' => array("scope")),
        )
    ),

    "app_object_types" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array("type" => SchemaProperty::TYPE_CHAR_256),
            'title' => array("type" => SchemaProperty::TYPE_CHAR_256),
            'object_table' => array("type" => "character varying(260)"),
            'revision' => array('type' => SchemaProperty::TYPE_INT, 'default' => '1'),
            //'label_fields'	=> array("type"=>"character varying(512)"),
            'f_system' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_table_created' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'application_id' => array('type' => SchemaProperty::TYPE_INT),
            'capped' => array('type' => SchemaProperty::TYPE_INT),
            'head_commit_id' => array("type" => "bigint"),
            'dacl' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("name")),
            array('properties' => array("application_id")),
        )
    ),

    "app_object_view_conditions" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'view_id' => array('type' => SchemaProperty::TYPE_INT),
            'field_id' => array('type' => SchemaProperty::TYPE_INT),
            'blogic' => array("type" => SchemaProperty::TYPE_CHAR_128, "notnull" => true),
            'operator' => array("type" => SchemaProperty::TYPE_CHAR_128, "notnull" => true),
            'value' => array("type" => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("view_id")),
            array('properties' => array("field_id")),
        )
    ),

    "app_object_view_fields" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'view_id' => array('type' => SchemaProperty::TYPE_INT),
            'field_id' => array('type' => SchemaProperty::TYPE_INT),
            'sort_order' => array('type' => SchemaProperty::TYPE_INT, 'default' => '0'),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("view_id")),
            array('properties' => array("field_id")),
        )
    ),

    "app_object_view_orderby" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'view_id' => array('type' => SchemaProperty::TYPE_INT),
            'field_id' => array('type' => SchemaProperty::TYPE_INT),
            'order_dir' => array('type' => SchemaProperty::TYPE_CHAR_32, 'notnull' => true),
            'sort_order' => array('type' => SchemaProperty::TYPE_INT, 'default' => '0'),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("view_id")),
            array('properties' => array("field_id")),
        )
    ),

    "app_object_views" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256, 'notnull' => true),
            'scope' => array('type' => SchemaProperty::TYPE_CHAR_16),
            'description' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'filter_key' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'f_default' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'team_id' => array('type' => SchemaProperty::TYPE_INT),
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT),
            'report_id' => array('type' => SchemaProperty::TYPE_INT),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'conditions_data' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'order_by_data' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'table_columns_data' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id")),
            array('properties' => array("object_type_id")),
            array('properties' => array("report_id")),
        )
    ),

    "app_us_zipcodes" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'zipcode' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'city' => array('type' => SchemaProperty::TYPE_CHAR_64, 'notnull' => true),
            'state' => array('type' => SchemaProperty::TYPE_CHAR_2),
            'latitude' => array('type' => SchemaProperty::TYPE_REAL, 'notnull' => true),
            'longitude' => array('type' => SchemaProperty::TYPE_REAL, 'notnull' => true),
            'dst' => array('type' => SchemaProperty::TYPE_SMALLINT, 'notnull' => true),
            'timezone' => array('type' => SchemaProperty::TYPE_DOUBLE, 'notnull' => true),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("zipcode")),
        )
    ),

    "app_widgets" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'title' => array('type' => SchemaProperty::TYPE_CHAR_64, 'notnull' => true),
            'class_name' => array('type' => SchemaProperty::TYPE_CHAR_64, 'notnull' => true),
            'file_name' => array('type' => SchemaProperty::TYPE_CHAR_64, 'notnull' => true),
            'type' => array('type' => SchemaProperty::TYPE_CHAR_32, 'default' => 'system'),
            'description' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
    ),

    "application_calendars" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'calendar_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'application_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("calendar_id")),
            array('properties' => array("application_id")),
        )
    ),

    "application_objects" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'application_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'f_parent_app' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("application_id")),
            array('properties' => array("object_type_id")),
        )
    ),

    "applications" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256, 'notnull' => true),
            'short_title' => array('type' => SchemaProperty::TYPE_CHAR_256, 'notnull' => true),
            'title' => array('type' => SchemaProperty::TYPE_CHAR_512, 'notnull' => true),
            'scope' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'settings' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'xml_navigation' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'f_system' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'team_id' => array('type' => SchemaProperty::TYPE_INT),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'icon' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'default_route' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id")),
            array('properties' => array("team_id")),
        )
    ),

    "async_states" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'key' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'value' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'att_id' => array('type' => SchemaProperty::TYPE_INT),
            'time_id' => array('type' => SchemaProperty::TYPE_INT),
            'response' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id")),
            array('properties' => array("key")),
            array('properties' => array("att_id")),
            array('properties' => array("time_id")),
        )
    ),

    "calendar_event_coord" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'ts_entered' => array('type' => "timestamp with time zone"),
            'ts_updated' => array('type' => "timestamp with time zone"),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(),
    ),

    "calendar_event_coord_times" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'cec_id' => array('type' => SchemaProperty::TYPE_INT),
            'ts_start' => array('type' => "timestamp with time zone"),
            'ts_end' => array('type' => "timestamp with time zone"),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("cec_id")),
        )
    ),

    "calendar_event_coord_att_times" => array(
        "PROPERTIES" => array(
            'att_id' => array('type' => SchemaProperty::TYPE_INT),
            'time_id' => array('type' => "integer"),
            'response' => array('type' => "integer"),
        ),
        "INDEXES" => array(
            array('properties' => array("att_id")),
            array('properties' => array("time_id")),
        )
    ),


    "calendar_events" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'location' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'sharing' => array('type' => SchemaProperty::TYPE_INT),
            'all_day' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'calendar' => array('type' => SchemaProperty::TYPE_INT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'ts_start' => array('type' => "timestamp with time zone"),
            'ts_end' => array('type' => "timestamp with time zone"),
            'inv_rev' => array('type' => SchemaProperty::TYPE_INT), // event invitation revision
            'inv_uid' => array('type' => SchemaProperty::TYPE_CHAR_TEXT), // remove invitattion id
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'ts_entered' => array('type' => "timestamp with time zone"),
            'ts_updated' => array('type' => "timestamp with time zone"),
            'user_status' => array('type' => "integer"),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'user_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            )
        ),
        "INDEXES" => array(
            array('properties' => array("ts_updated")),
            array('properties' => array("ts_start")),
            array('properties' => array("ts_end")),
        )
    ),

    "calendar_events_reminders" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'complete' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'event_id' => array('type' => SchemaProperty::TYPE_INT),
            'recur_id' => array('type' => SchemaProperty::TYPE_INT),
            'count' => array('type' => SchemaProperty::TYPE_INT),
            'interval' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'type' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'execute_time' => array('type' => "timestamp without time zone"),
            'send_to' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'is_snooze' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("event_id")),
            array('properties' => array("execute_time")),
        )
    ),

    "calendar_sharing" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'calendar' => array('type' => SchemaProperty::TYPE_INT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'accepted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "true"),
            'f_view' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "true"),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("calendar")),
            array('properties' => array("user_id")),
        )
    ),

    "calendars" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'def_cal' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"), // users default
            'f_view' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "true"),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'date_created' => array('type' => SchemaProperty::TYPE_DATE),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id")),
            array('properties' => array("user_id", "f_view")),
        )
    ),

    "chat_friends" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'friend_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'friend_server' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'session_id' => array('type' => SchemaProperty::TYPE_INT),
            'f_online' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'local_name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'status' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'team_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id")),
            array('properties' => array("team_id")),
        )
    ),

    "chat_queue_agents" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'queue_id' => array('type' => SchemaProperty::TYPE_INT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("queue_id")),
            array('properties' => array("user_id")),
        )
    ),

    "chat_queue_entries" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'ts_created' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'session_id' => array('type' => SchemaProperty::TYPE_INT),
            'token_id' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'queue_id' => array('type' => SchemaProperty::TYPE_INT),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("queue_id")),
            array('properties' => array("session_id")),
            array('properties' => array("token_id")),
        )
    ),

    "chat_server" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'user_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'friend_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'friend_server' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'message' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'ts_last_message' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_read' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'message_timestamp' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id")),
            array('properties' => array("f_read")),
            array('properties' => array("friend_name")),
            array('properties' => array("message_timestamp")),
            array('properties' => array("user_name")),
        )
    ),

    "chat_server_session" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'user_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'friend_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'friend_server' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'f_typing' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_popup' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_online' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_newmessage' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'last_timestamp' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id")),
            array('properties' => array("user_name")),
            array('properties' => array("friend_name")),
            array('properties' => array("friend_server")),
            array('properties' => array("f_popup")),
        )
    ),

    "comments" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'entered_by' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'user_name_chache' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'comment' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'notified' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("owner_id")),
        )
    ),

    "contacts_personal_labels" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id")),
            array('properties' => array("parent_id")),
        )
    ),

    "contacts_personal_label_mem" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'contact_id' => array('type' => SchemaProperty::TYPE_INT),
            'label_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("contact_id", "label_id"), 'type' => 'UNIQUE'),
        )
    ),

    "customer_association_types" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'f_child' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'inherit_fields' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customer_associations" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
            'customer_id' => array('type' => SchemaProperty::TYPE_INT),
            'type_id' => array('type' => SchemaProperty::TYPE_INT),
            'relationship_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("customer_id")),
            array('properties' => array("parent_id")),
            array('properties' => array("type_id")),
        )
    ),

    "customer_ccards" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'customer_id' => array('type' => SchemaProperty::TYPE_INT),
            'ccard_name' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'ccard_number' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'ccard_type' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'ccard_exp_month' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'ccard_exp_year' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'enc_ver' => array('type' => SchemaProperty::TYPE_CHAR_16),
            'f_default' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("customer_id")),
            array('properties' => array("parent_id")),
            array('properties' => array("type_id")),
        )
    ),

    "customer_invoice_templates" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'company_logo' => array('type' => SchemaProperty::TYPE_INT),
            'company_name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'company_slogan' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'notes_line1' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'notes_line2' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'footer_line1' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customer_invoices" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'number' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'customer_id' => array('type' => SchemaProperty::TYPE_INT),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'status_id' => array('type' => SchemaProperty::TYPE_INT),
            'created_by' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'updated_by' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'date_due' => array('type' => SchemaProperty::TYPE_DATE),
            'template_id' => array('type' => SchemaProperty::TYPE_INT),
            'notes_line1' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'notes_line2' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'footer_line1' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'payment_terms' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'send_to' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'tax_rate' => array('type' => SchemaProperty::TYPE_INT),
            'amount' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'sales_order_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("owner_id")),
            array('properties' => array("customer_id")),
            array('properties' => array("status_id")),
            array('properties' => array("template_id")),
        )
    ),

    "customer_invoice_detail" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'invoice_id' => array('type' => SchemaProperty::TYPE_INT),
            'product_id' => array('type' => SchemaProperty::TYPE_INT),
            'quantity' => array('type' => SchemaProperty::TYPE_NUMERIC, 'default' => '1'),
            'amount' => array('type' => SchemaProperty::TYPE_NUMERIC, 'default' => '0'),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("invoice_id")),
            array('properties' => array("product_id")),
        )
    ),

    "customer_invoice_status" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'f_paid' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customer_labels" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
            'f_special' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("parent_id")),
        )
    ),

    "customer_label_mem" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'customer_id' => array('type' => SchemaProperty::TYPE_INT),
            'label_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("customer_id")),
            array('properties' => array("label_id")),
        )
    ),

    "customer_leads" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'queue_id' => array('type' => SchemaProperty::TYPE_INT),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'first_name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'last_name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'email' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'phone' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'street' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'street2' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'city' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'state' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'zip' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'source_id' => array('type' => SchemaProperty::TYPE_INT),
            'rating_id' => array('type' => SchemaProperty::TYPE_INT),
            'status_id' => array('type' => SchemaProperty::TYPE_INT),
            'company' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'title' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'website' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'country' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'customer_id' => array('type' => SchemaProperty::TYPE_INT),
            'opportunity_id' => array('type' => SchemaProperty::TYPE_INT),
            'ts_first_contacted' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_last_contacted' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'class_id' => array('type' => SchemaProperty::TYPE_INT),
            'phone2' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'phone3' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'fax' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("customer_id")),
            array('properties' => array("source_id")),
            array('properties' => array("rating_id")),
            array('properties' => array("status_id")),
            array('properties' => array("opportunity_id")),
        )
    ),

    "customer_lead_status" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'f_closed' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_converted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customer_lead_sources" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customer_lead_rating" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customer_lead_classes" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customer_lead_queues" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'dacl_edit' => array('type' => SchemaProperty::TYPE_INT),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customer_objections" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'description' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customer_opportunities" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'stage_id' => array('type' => SchemaProperty::TYPE_INT),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'expected_close_date' => array('type' => SchemaProperty::TYPE_DATE),
            'amount' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'customer_id' => array('type' => SchemaProperty::TYPE_INT),
            'lead_id' => array('type' => SchemaProperty::TYPE_INT),
            'lead_source_id' => array('type' => SchemaProperty::TYPE_INT),
            'probability_per' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'created_by' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'type_id' => array('type' => SchemaProperty::TYPE_INT),
            'updated_by' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'ts_closed' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_first_contacted' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_last_contacted' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'objection_id' => array('type' => SchemaProperty::TYPE_INT),
            'closed_lost_reson' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("stage_id")),
            array('properties' => array("customer_id")),
            array('properties' => array("lead_id")),
            array('properties' => array("lead_source_id")),
            array('properties' => array("type_id")),
        )
    ),

    "customer_opportunity_stages" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'f_closed' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_won' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customer_opportunity_types" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customer_publish" => array(
        "PROPERTIES" => array(
            'customer_id' => array('type' => SchemaProperty::TYPE_BIGINT, 'notnull' => true),
            'username' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'password' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'f_files_view' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_files_upload' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_files_modify' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_modify_contact' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_update_image' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
        ),
        'PRIMARY_KEY' => 'customer_id',
        "INDEXES" => array(
            array('properties' => array("username")),
            array('properties' => array("password")),
        )
    ),

    "customer_stages" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customer_status" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'f_closed' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "customers" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'first_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'last_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'company' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'salutation' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'email' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'email2' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'email3' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'phone_home' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'phone_work' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'phone_cell' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'phone_other' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'street' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'street_2' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'city' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'zip' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'time_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'job_title' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'phone_fax' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'phone_pager' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'middle_name' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'time_changed' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'email_default' => array('type' => SchemaProperty::TYPE_CHAR_16),
            'spouse_name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'business_street' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'business_street_2' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'business_city' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'business_state' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'business_zip' => array('type' => SchemaProperty::TYPE_CHAR_16),
            'phone_ext' => array('type' => SchemaProperty::TYPE_CHAR_16),
            'website' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'birthday' => array('type' => SchemaProperty::TYPE_DATE),
            'birthday_spouse' => array('type' => SchemaProperty::TYPE_DATE),
            'anniversary' => array('type' => SchemaProperty::TYPE_DATE),
            'last_contacted' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'nick_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'source' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'status' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'email_spouse' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'source_notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'contacted' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'type_id' => array('type' => SchemaProperty::TYPE_INT),
            'address_default' => array('type' => SchemaProperty::TYPE_CHAR_16),
            'f_nocall' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_noemailspam' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_nocontact' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'status_id' => array('type' => SchemaProperty::TYPE_INT),
            'stage_id' => array('type' => SchemaProperty::TYPE_INT),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'address_billing' => array('type' => SchemaProperty::TYPE_CHAR_16),
            'ts_first_contacted' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'shipping_street' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'shipping_street2' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'shipping_city' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'shipping_state' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'shipping_zip' => array('type' => SchemaProperty::TYPE_CHAR_16),
            'billing_street' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'billing_street2' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'billing_city' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'billing_state' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'billing_zip' => array('type' => SchemaProperty::TYPE_CHAR_16),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("owner_id")),
            array('properties' => array("stage_id")),
            array('properties' => array("status_id")),
            array('properties' => array("type_id")),
            array('properties' => array("email")),
            array('properties' => array("email2")),
        )
    ),

    "discussions" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'message' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'notified' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("owner_id")),
        )
    ),

    "email_accounts" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512, 'notnull' => true),
            'address' => array('type' => SchemaProperty::TYPE_CHAR_512, 'notnull' => true),
            'reply_to' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'ts_last_full_sync' => array('type' => SchemaProperty::TYPE_INT),
            'f_default' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'signature' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'type' => array('type' => SchemaProperty::TYPE_CHAR_16),
            'username' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'password' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'host' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'port' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'ssl' => array('type' => SchemaProperty::TYPE_CHAR_8),
            'sync_data' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'f_ssl' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_system' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_outgoing_auth' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'host_out' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'port_out' => array('type' => SchemaProperty::TYPE_CHAR_8),
            'f_ssl_out' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'username_out' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'password_out' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'forward' => array('type' => SchemaProperty::TYPE_CHAR_256),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
            array('properties' => array("address")),
        )
    ),


    "email_video_message_themes" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256, 'notnull' => true),
            'html' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'header_file_id' => array('type' => SchemaProperty::TYPE_INT),
            'footer_file_id' => array('type' => SchemaProperty::TYPE_INT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'background_color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'scope' => array('type' => SchemaProperty::TYPE_CHAR_32),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
            array('properties' => array("scope")),
        )
    ),

    "email_filters" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512, 'notnull' => true),
            'kw_subject' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'kw_to' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'kw_from' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'kw_body' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'f_active' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "true"),
            'act_mark_read' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'act_move_to' => array('type' => SchemaProperty::TYPE_INT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
        )
    ),

    "email_settings_spam" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'preference' => array('type' => SchemaProperty::TYPE_CHAR_32, 'notnull' => true),
            'value' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
        )
    ),

    "email_mailboxes" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'parent_box' => array('type' => SchemaProperty::TYPE_INT),
            'flag_special' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_system' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'type' => array('type' => SchemaProperty::TYPE_CHAR_16),
            'mailbox' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
            array('properties' => array("parent_box")),
        )
    ),


    "email_message_original" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'message_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'file_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'lo_message' => array('type' => SchemaProperty::TYPE_BINARY_OID),
            'antmail_version' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("message_id", "objects_email_messages", "id")),
        )
    ),

    "email_message_queue" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'lo_message' => array('type' => SchemaProperty::TYPE_BINARY_OID),
            'ts_delivered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
        )
    ),

    "email_thread_mailbox_mem" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'thread_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'mailbox_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("mailbox_id", "email_mailboxes", "id")),
            array('properties' => array("thread_id", "email_threads", "id")),
        )
    ),

    "email_video_messages" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'file_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'title' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'subtitle' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'message' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'footer' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'theme' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'logo_file_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'f_template_video' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'facebook' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'twitter' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
        )
    ),

    "favorites_categories" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
            array('properties' => array("parent_id", "favorites_categories", "id")),
        )
    ),

    "favorites" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'favorite_category' => array('type' => SchemaProperty::TYPE_INT),
            'url' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
            array('properties' => array("favorite_category", "favorites_categories", "id")),
        )
    ),

    "ic_groups" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("parent_id")),
        )
    ),

    "ic_document_group_mem" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'document_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'group_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("document_id", "ic_documents", "id")),
            array('properties' => array("group_id", "ic_groups", "id")),
        )
    ),


    "ic_documents" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'title' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'keywords' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'body' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'video_file_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'owner_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            )
        ),
        "INDEXES" => array(
        )
    ),


    "members" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'role' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_accepted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "true"),
            'f_required' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_invsent' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    /**
     * Based table where all object tables inherit from
     */
    "objects" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'owner_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'owner_id_fval' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'creator_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'creator_id_fval' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'dacl' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'tsv_fulltext' => array('type' => SchemaProperty::TYPE_TEXT_TOKENS),
            'num_comments' => array('type' => SchemaProperty::TYPE_INT),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        )
    ),

    /**
     * Table stores reference of moved objects to another object (like when merged)
     */
    "objects_moved" => array(
        "PROPERTIES" => array(
            'object_type_id' => array('type' => SchemaProperty::TYPE_BIGINT, 'notnull' => true),
            'object_id' => array('type' => SchemaProperty::TYPE_BIGINT, 'notnull' => true),
            'moved_to' => array('type' => SchemaProperty::TYPE_BIGINT, 'notnull' => true),
        ),
        'PRIMARY_KEY' => array("object_type_id", "object_id"),
    ),

    /**
     * Store multi-dim references between objects (related to / associated with)
     */
    "object_associations" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'type_id' => array('type' => SchemaProperty::TYPE_INT),
            'object_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'assoc_type_id' => array('type' => SchemaProperty::TYPE_INT),
            'assoc_object_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'field_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("assoc_type_id", "assoc_object_id", "field_id")),
            array('properties' => array("type_id", "object_id", "field_id")),
            array('properties' => array("type_id", "assoc_type_id", "field_id")),
            array('properties' => array("object_id")),
            array('properties' => array("type_id")),
        )
    ),

    /**
     * Store indexe data for initialization and schema updates mostly
     */
    "object_indexes" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
        )
    ),


    /**
     * @depriacted
     * We are leaving these for reference in case we decide to use oracle which is
     * a whole lot better at very thin and long tables index queries and sorts.
     *
     * "object_index" => array(
     * "PROPERTIES" => array(
     * 'object_id'        => array('type'=>SchemaProperty::TYPE_BIGINT, 'notnull'=>true),
     * 'object_type_id'=> array('type'=>SchemaProperty::TYPE_INT, 'notnull'=>true),
     * 'field_id'        => array('type'=>SchemaProperty::TYPE_INT),
     * 'val_text'        => array('type'=>SchemaProperty::TYPE_CHAR_TEXT),
     * 'val_tsv'        => array('type'=>SchemaProperty::TYPE_TEXT_TOKENS),
     * 'val_number'    => array('type'=>SchemaProperty::TYPE_NUMERIC),
     * 'val_bool'        => array('type'=>SchemaProperty::TYPE_BOOL),
     * 'val_timestamp'    => array('type'=>SchemaProperty::TYPE_TIMESTAMP),
     * 'f_deleted'        => array('type'=>SchemaProperty::TYPE_BOOL),
     * )
     * ),
     *
     * "object_index_cachedata" => array(
     * "PROPERTIES" => array(
     * 'object_type_id'=> array('type'=>SchemaProperty::TYPE_INT, 'notnull'=>true),
     * 'object_id'        => array('type'=>SchemaProperty::TYPE_BIGINT, 'notnull'=>true),
     * 'revision'        => array('type'=>SchemaProperty::TYPE_INT),
     * 'data'            => array('type'=>SchemaProperty::TYPE_CHAR_TEXT),
     * ),
     * 'PRIMARY_KEY'        => array('object_type_id', 'object_id'),
     * "KEYS" => array(
     * 'object_type_id'=> array("FKEY", "object_type_id", "app_object_types", "id"),
     * ),
     * ),
     *
     * // No keys because this is an abstract table inherited
     * "object_index_fulltext" => array(
     * "PROPERTIES" => array(
     * 'object_type_id'=> array('type'=>SchemaProperty::TYPE_INT, 'notnull'=>true),
     * 'object_id'        => array('type'=>SchemaProperty::TYPE_BIGINT, 'notnull'=>true),
     * 'object_revision'=> array('type'=>SchemaProperty::TYPE_INT),
     * 'f_deleted'        => array('type'=>SchemaProperty::TYPE_BOOL),
     * 'snippet'        => array('type'=>SchemaProperty::TYPE_CHAR_512),
     * 'private_owner_id'=> array('type'=>SchemaProperty::TYPE_INT),
     * 'ts_entered'    => array('type'=>SchemaProperty::TYPE_TIMESTAMP),
     * 'tsv_keywords'    => array('type'=>SchemaProperty::TYPE_TEXT_TOKENS),
     * )
     * ),
     *
     * "object_index_fulltext_act" => array(
     * 'COLUMNS' => array(), // inherited
     * 'INHERITS' => 'object_index_fulltext',
     * 'PRIMARY_KEY' => array('object_type_id', 'object_id'),
     * "CONSTRAINTS" => array(
     * 'actcheck'=> "f_deleted = false",
     * ),
     * "KEYS" => array(
     * 'object_type_id'=> array("INDEX", "object_type_id", "app_object_types", "id"),
     * 'private_owner'=> array("INDEX", "private_owner_id", "users", "id"),
     * 'keywords'    => array("INDEX", "tsv_keywords"),
     * ),
     * );
     *
     * "object_index_fulltext_del" => array(
     * 'COLUMNS' => array(), // inherited
     * 'INHERITS' => 'object_index_fulltext',
     * 'PRIMARY_KEY'        => array('object_type_id', 'object_id'),
     * "CONSTRAINTS" => array(
     * 'actcheck'=> "f_deleted = true",
     * ),
     * "KEYS" => array(
     * 'object_type_id'=> array("INDEX", "object_type_id", "app_object_types", "id"),
     * 'private_owner'    => array("INDEX", "private_owner_id", "users", "id"),
     * 'keywords'    => array("INDEX", "tsv_keywords"),
     * ),
     * ),
     */

    /**
     * Historical log used to indicate when an object has been indexed so that
     * we can reconsile with a background script to make sure we did not miss anything.
     */
    "object_indexed" => array(
        "PROPERTIES" => array(
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'object_id' => array('type' => SchemaProperty::TYPE_BIGINT, 'notnull' => true),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'index_type' => array('type' => SchemaProperty::TYPE_SMALLINT),
        ),
        "INDEXES" => array(
            array('properties' => array("index_type")),
            array('properties' => array("object_id")),
            array('properties' => array("object_type_id")),
        )
    ),

    "object_recurrence" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'object_type' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'type' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'interval' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'date_processed_to' => array('type' => SchemaProperty::TYPE_DATE),
            'date_start' => array('type' => SchemaProperty::TYPE_DATE),
            'date_end' => array('type' => SchemaProperty::TYPE_DATE),
            't_start' => array('type' => SchemaProperty::TYPE_TIME_WITH_TIME_ZONE),
            't_end' => array('type' => SchemaProperty::TYPE_TIME_WITH_TIME_ZONE),
            'all_day' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'ep_locked' => array('type' => SchemaProperty::TYPE_INT),
            'dayofmonth' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'dayofweekmask' => array('type' => SchemaProperty::TYPE_BOOL_ARRAY),
            'duration' => array('type' => SchemaProperty::TYPE_INT),
            'instance' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'monthofyear' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'parent_object_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'type_id' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'f_active' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "true"),
        ),
        'PRIMARY_KEY' => array('id'),
        "INDEXES" => array(
            array('properties' => array("date_processed_to")),
        )
    ),

    "object_revisions" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'object_id' => array('type' => SchemaProperty::TYPE_BIGINT, 'notnull' => true),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIME_WITH_TIME_ZONE),
            'data' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => array('id'),
        "INDEXES" => array(
            array('properties' => array("object_type_id", "object_id")),
        )
    ),

    "object_revision_data" => array(
        "PROPERTIES" => array(
            'revision_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'field_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'field_value' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        "INDEXES" => array(
            array('properties' => array('revision_id', "object_revisions", "id")),
        )
    ),

    "object_unames" => array(
        "PROPERTIES" => array(
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'object_id' => array('type' => SchemaProperty::TYPE_BIGINT, 'notnull' => true),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512),
        ),
        "INDEXES" => array(
            array('properties' => array("object_type_id", "name")),
        )
    ),

    "object_groupings" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT),
            'field_id' => array('type' => SchemaProperty::TYPE_INT),
            'parent_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'f_system' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_closed' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => array('id'),
        "INDEXES" => array(
            array('properties' => array("object_type_id")),
            array('properties' => array("field_id")),
            array('properties' => array("parent_id")),
            array('properties' => array("user_id")),
        )
    ),

    "object_grouping_mem" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'object_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'field_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'grouping_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("object_type_id", "object_id")),
            array('properties' => array("field_id")),
            array('properties' => array("grouping_id", "object_groupings", "id")),
        )
    ),

    "printing_papers_labels" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'cols' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'y_start_pos' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'y_interval' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'x_pos' => array('type' => SchemaProperty::TYPE_CHAR_32),
        ),
        'PRIMARY_KEY' => array('id'),
        "INDEXES" => array(),
    ),

    "product_categories" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "product_categories_mem" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'product_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'category_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("product_id", "products", "id")),
            array('properties' => array("category_id", "product_categories", "id")),
        )
    ),

    "product_families" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "products" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'price' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'f_available' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'rating' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'family' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'image_id' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("image_id")),
            array('properties' => array("family", "product_families", "id")),
        )
    ),

    "product_reviews" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'creator_id' => array('type' => SchemaProperty::TYPE_INT),
            'rating' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'product' => array('type' => SchemaProperty::TYPE_BIGINT),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'product_id',
                'references_bucket'=>'products',
                'references_property'=>'id',
            )
        ),
        "INDEXES" => array(
        )
    ),

    /**
     * The project_bug* tables are where cases are stored for legacy reasons
     */
    "project_bug_severity" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "project_bug_status" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'f_closed' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "project_bug_types" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "project_bugs" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'related_bug' => array('type' => SchemaProperty::TYPE_INT),
            'status_id' => array('type' => SchemaProperty::TYPE_INT),
            'severity_id' => array('type' => SchemaProperty::TYPE_INT),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'type_id' => array('type' => SchemaProperty::TYPE_INT),
            'project_id' => array('type' => SchemaProperty::TYPE_INT),
            'customer_id' => array('type' => SchemaProperty::TYPE_INT),
            'title' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'description' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'solution' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'date_reported' => array('type' => SchemaProperty::TYPE_DATE),
            'created_by' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'related_bug',
                'references_bucket'=>'project_bugs',
                'references_property'=>'id',
            ),
            array(
                "property"=>'status_id',
                'references_bucket'=>'project_bug_status',
                'references_property'=>'id',
            ),
            array(
                "property"=>'severity_id',
                'references_bucket'=>'project_bug_severity',
                'references_property'=>'id',
            ),
            array(
                "property"=>'owner_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'type_id',
                'references_bucket'=>'project_bug_types',
                'references_property'=>'id',
            ),
            array(
                "property"=>'customer_id',
                'references_bucket'=>'customers',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "project_bug_types" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "project_priorities" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "project_templates" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'time_created' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'custom_fields' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'user_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "project_template_members" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'template_id' => array('type' => SchemaProperty::TYPE_INT),
            'accepted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'user_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'template_id',
                'references_bucket'=>'project_templates',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "project_template_share" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'template_id' => array('type' => SchemaProperty::TYPE_INT),
            'accepted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'user_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'template_id',
                'references_bucket'=>'project_templates',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "project_template_tasks" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'template_id' => array('type' => SchemaProperty::TYPE_INT),
            'start_interval' => array('type' => SchemaProperty::TYPE_INT),
            'due_interval' => array('type' => SchemaProperty::TYPE_INT),
            'start_count' => array('type' => SchemaProperty::TYPE_INT),
            'due_count' => array('type' => SchemaProperty::TYPE_INT),
            'timeline' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'type' => array('type' => SchemaProperty::TYPE_INT),
            'file_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'user_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'position_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'timeline_date_begin' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'timeline_date_due' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'cost_estimated' => array('type' => SchemaProperty::TYPE_NUMERIC),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'user_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'template_id',
                'references_bucket'=>'project_templates',
                'references_property'=>'id',
            ),
            array(
                "property"=>'position_id',
                'references_bucket'=>'project_positions',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "projects" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'parent' => array('type' => SchemaProperty::TYPE_INT),
            'priority' => array('type' => SchemaProperty::TYPE_INT),
            'customer_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'template_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'news' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'date_deadline' => array('type' => SchemaProperty::TYPE_DATE),
            'date_completed' => array('type' => SchemaProperty::TYPE_DATE),
            'ts_created' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'parent',
                'references_bucket'=>'projects',
                'references_property'=>'id',
            ),
            array(
                "property"=>'priority',
                'references_bucket'=>'project_priorities',
                'references_property'=>'id',
            ),
            array(
                "property"=>'customer_id',
                'references_bucket'=>'customers',
                'references_property'=>'id',
            ),
            array(
                "property"=>'user_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'template_id',
                'references_bucket'=>'project_templates',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
            array('properties' => array("date_completed")),
        )
    ),

    "project_files" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'file_id' => array('type' => SchemaProperty::TYPE_INT),
            'project_id' => array('type' => SchemaProperty::TYPE_INT),
            'bug_id' => array('type' => SchemaProperty::TYPE_INT),
            'task_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("project_id", "projects", "id")),
            array('properties' => array("project_id", "projects", "id")),
            array('properties' => array("bug_id", "project_bugs", "id")),
            array('properties' => array("task_id", "project_tasks", "id")),
        )
    ),

    "project_groups" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "project_group_mem" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'project_id' => array('type' => SchemaProperty::TYPE_BIGINT, 'notnull' => true),
            'group_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("project_id", "projects", "id")),
            array('properties' => array("group_id", "project_groups", "id")),
        )
    ),

    "project_positions" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'project_id' => array('type' => SchemaProperty::TYPE_INT),
            'template_id' => array('type' => SchemaProperty::TYPE_INT),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("project_id", "projects", "id")),
            array('properties' => array("template_id", "project_templates", "id")),
        )
    ),

    "project_membership" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'project_id' => array('type' => SchemaProperty::TYPE_INT),
            'position_id' => array('type' => SchemaProperty::TYPE_INT),
            'title' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'accepted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'invite_by' => array('type' => SchemaProperty::TYPE_CHAR_128),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
            array('properties' => array("project_id", "projects", "id")),
            array('properties' => array("position_id", "project_positions", "id")),
        )
    ),

    "project_milestones" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'project_id' => array('type' => SchemaProperty::TYPE_INT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'position_id' => array('type' => SchemaProperty::TYPE_INT),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'deadline' => array('type' => SchemaProperty::TYPE_DATE),
            'f_completed' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'date_completed' => array('type' => SchemaProperty::TYPE_DATE),
            'creator_name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'creator_id' => array('type' => SchemaProperty::TYPE_INT),
            'ts_created' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'project_id',
                'references_bucket'=>'projects',
                'references_property'=>'id',
            ),
            array(
                "property"=>'position_id',
                'references_bucket'=>'project_positions',
                'references_property'=>'id',
            ),
            array(
                "property"=>'user_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'creator_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "project_tasks" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'done' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'date_entered' => array('type' => SchemaProperty::TYPE_DATE),
            'date_completed' => array('type' => SchemaProperty::TYPE_DATE),
            'start_date' => array('type' => SchemaProperty::TYPE_DATE),
            'priority' => array('type' => SchemaProperty::TYPE_INT),
            'project' => array('type' => SchemaProperty::TYPE_INT),
            'entered_by' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'deadline' => array('type' => SchemaProperty::TYPE_DATE),
            'cost_estimated' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'cost_actual' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'type' => array('type' => SchemaProperty::TYPE_INT),
            'customer_id' => array('type' => SchemaProperty::TYPE_INT),
            'template_task_id' => array('type' => SchemaProperty::TYPE_INT),
            'position_id' => array('type' => SchemaProperty::TYPE_INT),
            'creator_id' => array('type' => SchemaProperty::TYPE_INT),
            'milestone_id' => array('type' => SchemaProperty::TYPE_INT),
            'depends_task_id' => array('type' => SchemaProperty::TYPE_INT),
            'case_id' => array('type' => SchemaProperty::TYPE_INT),
            'recurrence_pattern' => array('type' => SchemaProperty::TYPE_INT),
            'ts_created' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'user_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'project',
                'references_bucket'=>'projects',
                'references_property'=>'id',
            ),
            array(
                "property"=>'customer_id',
                'references_bucket'=>'customers',
                'references_property'=>'id',
            ),
            array(
                "property"=>'position_id',
                'references_bucket'=>'project_positions',
                'references_property'=>'id',
            ),
            array(
                "property"=>'creator_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'milestone_id',
                'references_bucket'=>'project_milestones',
                'references_property'=>'id',
            ),
            array(
                "property"=>'depends_task_id',
                'references_bucket'=>'project_tasks',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
            array('properties' => array("date_entered")),
            array('properties' => array("deadline")),
        )
    ),

    "project_time" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'creator_id' => array('type' => SchemaProperty::TYPE_INT),
            'task_id' => array('type' => SchemaProperty::TYPE_INT),
            'date_applied' => array('type' => SchemaProperty::TYPE_DATE),
            'hours' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'ts_created' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'owner_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'creator_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'task_id',
                'references_bucket'=>'project_tasks',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "reports" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'description' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'obj_type' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'chart_type' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'chart_measure' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'chart_measure_agg' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'chart_dim1' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'chart_dim1_grp' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'chart_dim2' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'chart_dim2_grp' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'chart_type' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'chart_type' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'f_display_table' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "true"),
            'f_display_chart' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "true"),
            'scope' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'custom_report' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'dataware_cube' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'ts_created' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'table_type' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'f_row_totals' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_column_totals' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_sub_totals' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'owner_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "sales_orders" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'number' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'created_by' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'tax_rate' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'amount' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'ship_to' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'ship_to_cship' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'status_id' => array('type' => SchemaProperty::TYPE_INT),
            'customer_id' => array('type' => SchemaProperty::TYPE_INT),
            'invoice_id' => array('type' => SchemaProperty::TYPE_INT),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'owner_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'status_id',
                'references_bucket'=>'sales_order_status',
                'references_property'=>'id',
            ),
            array(
                "property"=>'customer_id',
                'references_bucket'=>'customers',
                'references_property'=>'id',
            ),
            array(
                "property"=>'invoice_id',
                'references_bucket'=>'customer_invoices',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "sales_order_detail" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'order_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'product_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'quantity' => array('type' => SchemaProperty::TYPE_NUMERIC),
            'amount' => array('type' => SchemaProperty::TYPE_NUMERIC),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("order_id", "sales_orders", "id")),
            array('properties' => array("product_id", "products", "id")),
        )
    ),

    "sales_order_status" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array()
    ),

    "security_dacl" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'inherit_from' => array('type' => SchemaProperty::TYPE_BIGINT),
            'inherit_from_old' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("name")),
            array('properties' => array("inherit_from")),
            array('properties' => array("inherit_from")),
        )
    ),

    "security_acle" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'pname' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'dacl_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'user_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'group_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
            array('properties' => array("group_id", "user_groups", "id")),
            array('properties' => array("dacl_id", "security_dacl", "id")),
        )
    ),

    "stocks" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'symbol' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'price' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'price_change' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'percent_change' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'last_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("name")),
            array('properties' => array("symbol")),
        )
    ),

    "stocks_membership" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'user_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'stock_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
            array('properties' => array("stock_id", "stocks", "id")),
        )
    ),

    "system_registry" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGINT, 'subtype' => '', 'default' => 'auto_increment'),
            'key_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'key_val' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id")),
            array('properties' => array("key_name", "user_id"), 'type' => 'UNIQUE'),
        )
    ),

    "user_dashboard_layout" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGINT, 'subtype' => '', 'default' => 'auto_increment'),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'col' => array('type' => SchemaProperty::TYPE_INT),
            'position' => array('type' => SchemaProperty::TYPE_INT),
            'widget_id' => array('type' => SchemaProperty::TYPE_INT),
            'type' => array('type' => SchemaProperty::TYPE_CHAR_32, 'default' => 'system'),
            'data' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'dashboard' => array('type' => SchemaProperty::TYPE_CHAR_128),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
            array('properties' => array("dashboard")),
        )
    ),

    /**
     * User dictionary is used for spell checking
     */
    "user_dictionary" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'user_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'word' => array('type' => SchemaProperty::TYPE_CHAR_128),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
            array('properties' => array("word")),
        )
    ),

    "user_teams" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'subtype' => '', 'default' => 'auto_increment'),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256, 'notnull' => true),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("parent_id")),
        )
    ),

    "user_timezones" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'subtype' => '', 'default' => 'auto_increment'),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_64, 'notnull' => true),
            'code' => array('type' => SchemaProperty::TYPE_CHAR_8, 'notnull' => true),
            'loc_name' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'offs' => array('type' => SchemaProperty::TYPE_REAL),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("code")),
        )
    ),

    "users" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'password' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'full_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'title' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'last_login' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'theme' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'timezone' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'country_code' => array('type' => SchemaProperty::TYPE_CHAR_2),
            'active' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "true"),
            'phone' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'checkin_timestamp' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'active_timestamp' => array('type' => SchemaProperty::TYPE_TIMESTAMP), // this might be the same as above...
            'status_text' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'quota_size' => array('type' => SchemaProperty::TYPE_BIGINT),
            'last_login_from' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'image_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'team_id' => array('type' => SchemaProperty::TYPE_INT),
            'manager_id' => array('type' => SchemaProperty::TYPE_INT),
            'customer_number' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("name", "password", "active")),
            array('properties' => array("name"), 'type' => "UNIQUE"),
        )
    ),

    "user_groups" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'subtype' => '', 'default' => 'auto_increment'),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512, 'notnull' => true),
            'f_system' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_admin' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("name")),
        )
    ),

    "user_group_mem" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'user_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'group_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("user_id", "users", "id")),
            array('properties' => array("group_id", "user_groups", "id")),
        )
    ),

    "user_notes" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'body' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'date_added' => array('type' => SchemaProperty::TYPE_DATE),
            'body_type' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("owner_id", "users", "id")),
        )
    ),

    "user_notes_categories" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
            'user_id' => array('type' => SchemaProperty::TYPE_INT),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("parent_id")),
            array('properties' => array("user_id", "users", "id")),
        )
    ),

    "user_notes_cat_mem" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'note_id' => array('type' => SchemaProperty::TYPE_INT),
            'category_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("note_id")),
            array('properties' => array("category_id")),
        )
    ),

    "worker_jobs" => array(
        "PROPERTIES" => array(
            'job_id' => array('type' => SchemaProperty::TYPE_CHAR_512, 'notnull' => true),
            'function_name' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'ts_started' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_completed' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'status_numerator' => array('type' => SchemaProperty::TYPE_INT, 'default' => '-1'),
            'status_denominator' => array('type' => SchemaProperty::TYPE_INT, 'default' => '100'),
            'log' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'retval' => array('type' => SchemaProperty::TYPE_BINARY_STRING),
        ),
        'PRIMARY_KEY' => 'job_id',
        "INDEXES" => array()
    ),

    "workflows" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'object_type' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'f_on_create' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_on_update' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_on_delete' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_singleton' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_allow_manual' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_active' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_on_daily' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_condition_unmet' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_processed_cond_met' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),

            /*
             * This column is being depreciated with the V2 version of WorkFlow
             * and we will be using ts_lastrun below instead.
             */
            'ts_on_daily_lastrun' => array('type' => SchemaProperty::TYPE_TIMESTAMP),

            /*
             * When the workflow was last run. This is particularly useful
             * for keeping track of 'periodic' workflows that run at an interval
             * and look for all matching entities.
             */
            'ts_lastrun' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("object_type", "f_active")),
            array('properties' => array("uname")),
        )
    ),

    "workflow_actions" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'when_interval' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'when_unit' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'send_email_fid' => array('type' => SchemaProperty::TYPE_BIGINT),
            'update_field' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'update_to' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'create_object' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'start_wfid' => array('type' => SchemaProperty::TYPE_INT),
            'stop_wfid' => array('type' => SchemaProperty::TYPE_INT),
            'workflow_id' => array('type' => SchemaProperty::TYPE_INT),
            'type' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'type_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'parent_action_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'parent_action_event' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'data' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("workflow_id", "workflows", "id")),
            array('properties' => array("start_wfid", "workflows", "id")),
            array('properties' => array("stop_wfid", "workflows", "id")),
            array('properties' => array("parent_action_id")),
            array('properties' => array("uname")),
        )
    ),

    "workflow_action_schedule" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'action_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'ts_execute' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'instance_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'inprogress' => array('type' => SchemaProperty::TYPE_INT, 'default' => '0'),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("ts_execute")),
        )
    ),

    "workflow_conditions" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'blogic' => array('type' => SchemaProperty::TYPE_CHAR_64),
            'field_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'operator' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'cond_value' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'workflow_id' => array('type' => SchemaProperty::TYPE_INT),
            'wf_action_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("workflow_id", "workflows", "id")),
            array('properties' => array("wf_action_id", "workflow_actions", "id")),
        )
    ),

    "workflow_instances" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT, 'notnull' => true),
            'object_type' => array('type' => SchemaProperty::TYPE_CHAR_128),
            'object_uid' => array('type' => SchemaProperty::TYPE_BIGINT, 'notnull' => true),
            'workflow_id' => array('type' => SchemaProperty::TYPE_INT),
            'ts_started' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_completed' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_completed' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("object_type_id", "app_object_types", "id")),
            array('properties' => array("object_type")),
            array('properties' => array("object_uid")),
            array('properties' => array("workflow_id", "workflows", "id")),
        )
    ),

    "workflow_object_values" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'field' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'value' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'f_array' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
            'action_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("parent_id")),
            array('properties' => array("action_id", "workflow_actions", "id")),
        )
    ),

    "workflow_approvals" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'notes' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'workflow_action_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'status' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'requested_by' => array('type' => SchemaProperty::TYPE_INT),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'obj_reference' => array('type' => SchemaProperty::TYPE_CHAR_512),
            'ts_status_change' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'owner_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'requested_by',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'workflow_action_id',
                'references_bucket'=>'workflow_actions',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "xml_feeds" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'user_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'title' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'sort_by' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'limit_num' => array('type' => SchemaProperty::TYPE_CHAR_8),
            'ts_created' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'user_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "xml_feed_groups" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("parent_id")),
        )
    ),

    "xml_feed_group_mem" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'feed_id' => array('type' => SchemaProperty::TYPE_INT),
            'group_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("feed_id")),
            array('properties' => array("group_id")),
        )
    ),

    "xml_feed_publish" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'feed_id' => array('type' => SchemaProperty::TYPE_INT),
            'publish_to' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'furl' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("feed_id", "xml_feeds", "id")),
        )
    ),

    "xml_feed_posts" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'time_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'title' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'data' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'feed_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'f_publish' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'time_expires' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'user_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'f_deleted' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'path' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'user_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
            array(
                "property"=>'feed_id',
                'references_bucket'=>'xml_feeds',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "xml_feed_post_categories" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_INT, 'default' => 'auto_increment'),
            'parent_id' => array('type' => SchemaProperty::TYPE_INT),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_128, 'notnull' => true),
            'color' => array('type' => SchemaProperty::TYPE_CHAR_6),
            'sort_order' => array('type' => SchemaProperty::TYPE_SMALLINT, 'default' => '0'),
            'feed_id' => array('type' => SchemaProperty::TYPE_INT),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("parent_id")),
            array('properties' => array("feed_id", "xml_feeds", "id")),
        )
    ),

    "xml_feed_post_cat_mem" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'post_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'category_id' => array('type' => SchemaProperty::TYPE_INT),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("post_id", "xml_feed_posts", "id")),
            array('properties' => array("category_id", "xml_feed_post_categories", "id")),
        )
    ),

    "report_filters" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'report_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'blogic' => array("type" => SchemaProperty::TYPE_CHAR_64, "notnull" => true),
            'field_name' => array("type" => SchemaProperty::TYPE_CHAR_256, "notnull" => true),
            'operator' => array("type" => SchemaProperty::TYPE_CHAR_128, "notnull" => true),
            'value' => array("type" => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'report_id',
                'references_bucket'=>'reports',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "report_table_dims" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'report_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'table_type' => array("type" => "character varying(32)", "notnull" => true),
            'name' => array("type" => SchemaProperty::TYPE_CHAR_256, "notnull" => true),
            'sort' => array("type" => "character varying(32)", "notnull" => true),
            'format' => array("type" => "character varying(32)", "notnull" => true),
            'f_column' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'f_row' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'report_id',
                'references_bucket'=>'reports',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "report_table_measures" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'report_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'table_type' => array("type" => "character varying(32)", "notnull" => true),
            'name' => array("type" => SchemaProperty::TYPE_CHAR_256, "notnull" => true),
            'aggregate' => array("type" => "character varying(32)", "notnull" => true),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'report_id',
                'references_bucket'=>'reports',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "dashboard" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'description' => array("type" => SchemaProperty::TYPE_CHAR_TEXT),
            'scope' => array("type" => "character varying(32)", "notnull" => true),
            'groups' => array("type" => SchemaProperty::TYPE_CHAR_TEXT),
            'owner_id' => array("type" => "integer"),
            'f_deleted' => array("type" => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'path' => array("type" => SchemaProperty::TYPE_CHAR_TEXT),
            'payout' => array("type" => SchemaProperty::TYPE_CHAR_TEXT),
            'revision' => array("type" => "integer"),
            'uname' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'ts_updated' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'dacl' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'owner_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "dashboard_widgets" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'dashboard_id' => array("type" => "integer"),
            'widget_id' => array("type" => "integer"),
            'widget' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'col' => array("type" => "integer"),
            'pos' => array("type" => "integer"),
            'data' => array("type" => SchemaProperty::TYPE_CHAR_TEXT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'dashboard_id',
                'references_bucket'=>'dashboard',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
            array('properties' => array("widget")),
        )
    ),

    "dataware_olap_cubes" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_512),
        ),
        'PRIMARY_KEY' => 'id',
        "INDEXES" => array(
            array('properties' => array("name")),
        )
    ),

    "dataware_olap_cube_dims" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'type' => array('type' => SchemaProperty::TYPE_CHAR_32),
            'cube_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'cube_id',
                'references_bucket'=>'dataware_olap_cubes',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "dataware_olap_cube_measures" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'cube_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'cube_id',
                'references_bucket'=>'dataware_olap_cubes',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    /**
     * Store history of commit heads
     */
    "object_sync_commit_heads" => array(
        "PROPERTIES" => array(
            'type_key' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'head_commit_id' => array('type' => SchemaProperty::TYPE_BIGINT, 'notnull' => true),
        ),
        'PRIMARY_KEY' => 'type_key',
        "INDEXES" => array()
    ),

    "object_sync_partners" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'pid' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'owner_id' => array('type' => SchemaProperty::TYPE_INT),
            'ts_last_sync' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'owner_id',
                'references_bucket'=>'users',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
            array('properties' => array("pid")),
        )
    ),

    "object_sync_partner_collections" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'type' => array('type' => SchemaProperty::TYPE_INT),
            'partner_id' => array('type' => SchemaProperty::TYPE_INT),
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT),
            'object_type' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'field_id' => array('type' => SchemaProperty::TYPE_INT),
            'field_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'ts_last_sync' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
            'conditions' => array('type' => SchemaProperty::TYPE_CHAR_TEXT),
            'f_initialized' => array('type' => SchemaProperty::TYPE_BOOL, "default" => "false"),
            'revision' => array('type' => SchemaProperty::TYPE_BIGINT),
            'last_commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'partner_id',
                'references_bucket'=>'object_sync_partners',
                'references_property'=>'id',
            ),
            array(
                "property"=>'field_id',
                'references_bucket'=>'app_object_type_fields',
                'references_property'=>'id',
            ),
            array(
                "property"=>'object_type_id',
                'references_bucket'=>'app_object_types',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
        )
    ),

    "object_sync_partner_collection_init" => array(
        "PROPERTIES" => array(
            'collection_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'parent_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'ts_completed' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
        ),
        "KEYS" => array(
            array(
                "property"=>'collection_id',
                'references_bucket'=>'object_sync_partner_collections',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
            array('properties' => array("parent_id")),
        )
    ),


    "object_sync_stats" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'collection_id' => array('type' => SchemaProperty::TYPE_INT),
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT),
            'object_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'parent_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'field_id' => array('type' => SchemaProperty::TYPE_INT),
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            'field_name' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'field_val' => array('type' => SchemaProperty::TYPE_CHAR_256),
            'action' => array('type' => SchemaProperty::TYPE_CHAR),
            'ts_entered' => array('type' => SchemaProperty::TYPE_TIMESTAMP),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'collection_id',
                'references_bucket'=>'object_sync_partner_collections',
                'references_property'=>'id',
            ),
            array(
                "property"=>'object_type_id',
                'references_bucket'=>'app_object_types',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
            array('properties' => array("object_type_id", "object_id")),
            array('properties' => array("field_id", "field_val")),
            array('properties' => array("ts_entered")),
        )
    ),

    "object_sync_import" => array(
        "PROPERTIES" => array(
            'id' => array('type' => SchemaProperty::TYPE_BIGSERIAL),
            'collection_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'object_type_id' => array('type' => SchemaProperty::TYPE_INT),
            // Local object id once imported
            'object_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            // Revision of the local object
            'revision' => array('type' => SchemaProperty::TYPE_INT),
            // This field is depricated and should eventually be deleted
            'parent_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            // This field is depricated and should eventually be deleted
            'field_id' => array('type' => SchemaProperty::TYPE_INT),
            // A revision (usually modified epoch) of the remote object
            'remote_revision' => array('type' => SchemaProperty::TYPE_INT),
            // The unique id of the remote object we have imported
            'unique_id' => array('type' => SchemaProperty::TYPE_CHAR_512),
        ),
        'PRIMARY_KEY' => 'id',
        "KEYS" => array(
            array(
                "property"=>'collection_id',
                'references_bucket'=>'object_sync_partner_collections',
                'references_property'=>'id',
            ),
            array(
                "property"=>'object_type_id',
                'references_bucket'=>'app_object_types',
                'references_property'=>'id',
            ),
            array(
                "property"=>'field_id',
                'references_bucket'=>'app_object_type_fields',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
            array('properties' => array("object_type_id", "object_id")),
            array('properties' => array("field_id", "unique_id")),
            array('properties' => array("parent_id")),
        )
    ),

    "object_sync_export" => array(
        "PROPERTIES" => array(
            'collection_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'collection_type' => array('type' => SchemaProperty::TYPE_SMALLINT),
            'commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'new_commit_id' => array('type' => SchemaProperty::TYPE_BIGINT),
            'unique_id' => array('type' => SchemaProperty::TYPE_BIGINT),
        ),
        "KEYS" => array(
            array(
                "property"=>'collection_id',
                'references_bucket'=>'object_sync_partner_collections',
                'references_property'=>'id',
            ),
        ),
        "INDEXES" => array(
            array('properties' => array("collection_id")),
            array('properties' => array("unique_id")),
            array('properties' => array("new_commit_id", "new_commit_id IS NOT NULL")),
            array('properties' => array("collection_type", "commit_id")),
            array('properties' => array("collection_type", "new_commit_id")),
        )
    ),
);