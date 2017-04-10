<?php
/**
 * This is the legacy schema file for netric. All changes to the schema will be entered here
 * and each time schema update is run it will go through every table and make sure
 * every column exists and matches the type.
 *
 * Column drops will need to be handled in the update deltas found in ./updates/* but now
 * all deltas must assume the newest schema so they will be used for post-update processing,
 * to migrate data, and to clean-up after previous changes.
 */

return array(
	/**
	 * Activity types are groupings used to track types
	 */
	"activity_types" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'obj_type'		=> array('type'=>'character varying(256)'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'obj_type'		=> array("INDEX", "obj_type"),
		)
	),

	"app_object_field_defaults" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'field_id'		=> array('type'=>'integer', 'notnull'=>true),
			'on_event'		=> array('type'=>'character varying(32)', 'notnull'=>true),
			'value'			=> array('type'=>'text', 'notnull'=>true),
			'coalesce'		=> array('type'=>'text'),
			'where_cond'	=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'field_id'		=> array("INDEX", "field_id"),
		)
	),

	"app_object_field_options" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'field_id'		=> array('type'=>'integer', 'notnull'=>true),
			'key'			=> array('type'=>'text', 'notnull'=>true),
			'value'			=> array('type'=>'text', 'notnull'=>true),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'field_id'		=> array("INDEX", "field_id"),
		)
	),


	"app_object_imp_maps" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'template_id'	=> array('type'=>'integer', 'notnull'=>true),
			'col_name'		=> array('type'=>'character varying(256)', 'notnull'=>true),
			'property_name'	=> array('type'=>'character varying(256)', 'notnull'=>true),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'template_id'	=> array("INDEX", "template_id"),
		)
	),

	"app_object_imp_templates" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'type_id'		=> array('type'=>'integer'),
			'name'			=> array('type'=>'character varying(256)'),
			'user_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'type_id'		=> array("INDEX", "type_id"),
			'user_id'		=> array("INDEX", "user_id"),
		)
	),

	"app_object_list_cache" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'ts_created'	=> array('type'=>'timestamp without time zone'),
			'query'			=> array('type'=>'character varying(512)'),
			'total_num'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'query'			=> array("INDEX", "query"),
		)
	),

	"app_object_list_cache_flds" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'list_id'		=> array('type'=>'integer'),
			'field_id'		=> array('type'=>'integer'),
			'value'			=> array('type'=>'character varying(512)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'list_id'		=> array("INDEX", "list_id"),
			'field_id'		=> array("INDEX", "field_id"),
			'value'			=> array("INDEX", "value"),
		)
	),

	"app_object_list_cache_res" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'list_id'		=> array('type'=>'integer'),
			'results'		=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'list_id'		=> array("INDEX", "list_id"),
		)
	),

	"app_object_type_fields" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'type_id'		=> array('type'=>'integer'),
			'name'			=> array('type'=>'character varying(128)'),
			'title'			=> array('type'=>'character varying(128)'),
			'type'			=> array('type'=>'character varying(32)'),
			'subtype'		=> array('type'=>'character varying(32)'),
			'fkey_table_key'		=> array('type'=>'character varying(128)'),
			'fkey_multi_tbl'		=> array('type'=>'character varying(256)'),
			'fkey_multi_this'		=> array('type'=>'character varying(128)'),
			'fkey_multi_ref'		=> array('type'=>'character varying(128)'),
			'fkey_table_title'		=> array('type'=>'character varying(128)'),
			'sort_order'	=> array('type'=>'integer', "default"=>'0'),
			'parent_field'	=> array('type'=>'character varying(128)'),
			'autocreatebase'=> array("type"=>"text"),
			'autocreatename'=> array('type'=>'character varying(256)'),
			'mask'			=> array('type'=>'character varying(64)'),
			'f_readonly'	=> array('type'=>'boolean', "default"=>"false"),
			'autocreate'	=> array('type'=>'boolean', "default"=>"false"),
			'f_system'		=> array('type'=>'boolean', "default"=>"false"),
			'f_required'	=> array('type'=>'boolean', "default"=>"false"),
			'filter'		=> array("type"=>"text"),
	        'use_when'      => array("type"=>"text"),
	        'f_indexed'     => array("type"=>"boolean"),        
	        'f_unique'      => array("type"=>"boolean"),        
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'type_id'		=> array("INDEX", "type_id"),
		)
	),

	"app_object_type_frm_layouts" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'type_id'		=> array('type'=>'integer'),
			'team_id'		=> array('type'=>'integer'),
			'user_id'		=> array('type'=>'integer'),
			'scope'			=> array("type"=>"character varying(128)"),
			'form_layout_xml'=> array("type"=>"text"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'type_id'		=> array("INDEX", "type_id"),
			'team_id'		=> array("INDEX", "team_id"),
			'user_id'		=> array("INDEX", "user_id"),
			'scope'			=> array("INDEX", "scope"),
		)
	),

	"app_object_types" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array("type"=>"character varying(256)"),
			'title'			=> array("type"=>"character varying(256)"),
			'object_table'	=> array("type"=>"character varying(260)"),
			'revision'		=> array('type'=>'integer', 'default'=>'1'),
			//'label_fields'	=> array("type"=>"character varying(512)"),
			'f_system'		=> array('type'=>'boolean', "default"=>"false"),
			'f_table_created'=> array('type'=>'boolean', "default"=>"false"),
			'application_id'=> array('type'=>'integer'),
			'capped'		=> array('type'=>'integer'),
			'head_commit_id'=> array("type"=>"bigint"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'name'			=> array("UNIQUE", "name"),
			'application_id'=> array("INDEX", "application_id"),
		)
	),

	"app_object_view_conditions" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'view_id'		=> array('type'=>'integer'),
			'field_id'		=> array('type'=>'integer'),
			'blogic'		=> array("type"=>"character varying(128)", "notnull"=>true),
			'operator'		=> array("type"=>"character varying(128)", "notnull"=>true),
			'value'			=> array("type"=>"text"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'view_id'=> array("INDEX", "view_id"),
			'field_id'=> array("INDEX", "field_id"),
		)
	),

	"app_object_view_fields" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'view_id'		=> array('type'=>'integer'),
			'field_id'		=> array('type'=>'integer'),
			'sort_order'	=> array('type'=>'integer', 'default'=>'0'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'view_id'=> array("INDEX", "view_id"),
			'field_id'=> array("INDEX", "field_id"),
		)
	),

	"app_object_view_orderby" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'view_id'		=> array('type'=>'integer'),
			'field_id'		=> array('type'=>'integer'),
			'order_dir'			=> array('type'=>'character varying(32)', 'notnull'=>true),
			'sort_order'	=> array('type'=>'integer', 'default'=>'0'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'view_id'=> array("INDEX", "view_id"),
			'field_id'=> array("INDEX", "field_id"),
		)
	),

	"app_object_views" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)', 'notnull'=>true),
			'scope'			=> array('type'=>'character varying(16)'),
			'description'	=> array('type'=>'text'),
			'filter_key'	=> array('type'=>'text'),
			'f_default'		=> array('type'=>'boolean', "default"=>"false"),
			'user_id'		=> array('type'=>'integer'),
			'team_id'		=> array('type'=>'integer'),
			'object_type_id'=> array('type'=>'integer'),
	        'report_id'     => array('type'=>'integer'),
			'owner_id'		=> array('type'=>'integer'),
			'conditions_data'=> array('type'=>'text'),
			'order_by_data'=> array('type'=>'text'),
			'table_columns_data'=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'=> array("INDEX", "user_id"),
			'object_type_id'=> array("INDEX", "object_type_id"),
			'report_id'=> array("INDEX", "report_id"),
		)
	),

	"app_us_zipcodes" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'zipcode'		=> array('type'=>'integer', 'notnull'=>true),
			'city'			=> array('type'=>'character varying(64)', 'notnull'=>true),
			'state'			=> array('type'=>'character varying(2)'),
			'latitude'		=> array('type'=>'real', 'notnull'=>true),
			'longitude'		=> array('type'=>'real', 'notnull'=>true),
			'dst'			=> array('type'=>'smallint', 'notnull'=>true),
			'timezone'		=> array('type'=>'double precision', 'notnull'=>true),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'zipcode'=> array("INDEX", "zipcode"),
		)
	),

	"app_widgets" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'title'			=> array('type'=>'character varying(64)', 'notnull'=>true),
			'class_name'	=> array('type'=>'character varying(64)', 'notnull'=>true),
			'file_name'		=> array('type'=>'character varying(64)', 'notnull'=>true),
			'type'			=> array('type'=>'character varying(32)', 'default'=>'system'),
			'description'	=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
	),

	"application_calendars" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'calendar_id'	=> array('type'=>'integer', 'notnull'=>true),
			'application_id'=> array('type'=>'integer', 'notnull'=>true),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'calendar_id'=> array("INDEX", "calendar_id"),
			'application_id'=> array("INDEX", "application_id"),
		)
	),

	"application_objects" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'application_id'=> array('type'=>'integer', 'notnull'=>true),
			'object_type_id'=> array('type'=>'integer', 'notnull'=>true),
			'f_parent_app'	=> array('type'=>'boolean', "default"=>"false"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'application_id'=> array("INDEX", "application_id"),
			'object_type_id'=> array("INDEX", "object_type_id"),
		)
	),

	"applications" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)', 'notnull'=>true),
			'short_title'	=> array('type'=>'character varying(256)', 'notnull'=>true),
			'title'			=> array('type'=>'character varying(512)', 'notnull'=>true),
			'scope'			=> array('type'=>'character varying(32)'),
			'settings'		=> array('type'=>'character varying(128)'),
			'xml_navigation'=> array('type'=>'text'),
			'f_system'		=> array('type'=>'boolean', "default"=>"false"),
			'user_id'		=> array('type'=>'integer'),
			'team_id		'=> array('type'=>'integer'),
			'sort_order'	=> array('type'=>'smallint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'=> array("INDEX", "user_id"),
			'team_id'=> array("INDEX", "team_id"),
		)
	),

	"async_states" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'key'			=> array('text'),
			'value'			=> array('text'),
			'user_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'=> array("INDEX", "user_id"),
			'key'=> array("INDEX", "key"),
		)
	),

	"calendar_event_coord" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'notes'			=> array('type'=>'text'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'path'			=> array('type'=>'text'),
			'uname'			=> array('type'=>'character varying(256)'),
			'ts_entered'	=> array('type'=>"timestamp with time zone"),
			'ts_updated'	=> array('type'=>"timestamp with time zone"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(),
	),

	"async_states" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'att_id'		=> array('type'=>'integer'),
			'time_id'		=> array('type'=>'integer'),
			'response'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'att_id'=> array("INDEX", "att_id"),
			'time_id'=> array("INDEX", "time_id"),
		)
	),

	"calendar_event_coord_times" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'cec_id'		=> array('type'=>'integer'),
			'ts_start'		=> array('type'=>"timestamp with time zone"),
			'ts_end'		=> array('type'=>"timestamp with time zone"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'cec_id'		=> array("INDEX", "cec_id"),
		)
	),

	"calendar_event_coord_att_times" => array(
		"COLUMNS" => array(
			'att_id'		=> array('type'=>'integer'),
			'time_id'		=> array('type'=>"integer"),
			'response'		=> array('type'=>"integer"),
		),
		"KEYS" => array(
			'attend'		=> array("INDEX", "att_id"),
			'time'			=> array("INDEX", "time_id"),
		)
	),

	"calendar_event_coord_times" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'cec_id'		=> array('type'=>'integer'),
			'ts_start'		=> array('type'=>"timestamp with time zone"),
			'ts_end'		=> array('type'=>"timestamp with time zone"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'cec_id'		=> array("INDEX", "cec_id"),
		)
	),


	"calendar_events" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(512)'),
			'location'		=> array('type'=>'character varying(512)'),
			'notes'			=> array('type'=>'text'),
			'sharing'		=> array('type'=>'integer'),
			'all_day'		=> array('type'=>'boolean', "default"=>"false"),
			'calendar'		=> array('type'=>'integer'),
			'user_id'		=> array('type'=>'integer'),
			'ts_start'		=> array('type'=>"timestamp with time zone"),
			'ts_end'		=> array('type'=>"timestamp with time zone"),
			'inv_rev'		=> array('type'=>'integer'), // event invitation revision
			'inv_uid'		=> array('type'=>'text'), // remove invitattion id
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'path'			=> array('type'=>'text'),
			'uname'			=> array('type'=>'character varying(256)'),
			'ts_entered'	=> array('type'=>"timestamp with time zone"),
	        'ts_updated'    => array('type'=>"timestamp with time zone"),
			'user_status'	=> array('type'=>"integer"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("FKEY", "user_id", "users", "id"),
			'ts_updated'	=> array("INDEX", "ts_updated"),
			'ts_start'		=> array("INDEX", "ts_start"),
			'ts_end'		=> array("INDEX", "ts_end"),
		)
	),

	"calendar_events_reminders" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'complete'		=> array('type'=>'boolean', "default"=>"false"),
			'event_id'		=> array('type'=>'integer'),
			'recur_id'		=> array('type'=>'integer'),
			'count'			=> array('type'=>'integer'),
			'interval'		=> array('type'=>'smallint'),
			'type'			=> array('type'=>'smallint'),
			'execute_time'	=> array('type'=>"timestamp without time zone"),
			'send_to'		=> array('type'=>'text'),
			'is_snooze'		=> array('type'=>'boolean', "default"=>"false"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'event_id'		=> array("INDEX", "event_id"),
			'execute_time'	=> array("INDEX", "execute_time"),
		)
	),

	"calendar_sharing" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'calendar'		=> array('type'=>'integer'),
			'user_id'		=> array('type'=>'integer'),
			'accepted'		=> array('type'=>'boolean', "default"=>"true"),
			'f_view'		=> array('type'=>'boolean', "default"=>"true"),
			'color'			=> array('type'=>'character varying(6)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'calendar_id'	=> array("INDEX", "calendar"),
			'user_id'		=> array("INDEX", "user_id"),
		)
	),

	"calendars" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'user_id'		=> array('type'=>'integer'),
			'def_cal'		=> array('type'=>'boolean', "default"=>"false"), // users default
			'f_view'		=> array('type'=>'boolean', "default"=>"true"),
			'color'			=> array('type'=>'character varying(6)'),
			'date_created'	=> array('type'=>'date'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id"),
			'user_view'		=> array("INDEX", array("user_id", "f_view")),
		)
	),

	"chat_friends" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'user_id'		=> array('type'=>'integer'),
			'friend_name'	=> array('type'=>'character varying(256)'),
			'friend_server'	=> array('type'=>'character varying(256)'),
			'session_id'	=> array('type'=>'integer'),
			'f_online'		=> array('type'=>'boolean', "default"=>"false"),
			'local_name'	=> array('type'=>'character varying(128)'),
			'status'		=> array('type'=>'text'),
			'team_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id"),
			'team_id'		=> array("INDEX", "team_id"),
		)
	),

	"chat_queue_agents" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'queue_id'		=> array('type'=>'integer'),
			'user_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'queue_id'		=> array("INDEX", "queue_id"),
			'user_id'		=> array("INDEX", "user_id"),
		)
	),

	"chat_queue_entries" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'ts_created'	=> array('type'=>'timestamp with time zone'),
			'session_id'	=> array('type'=>'integer'),
			'token_id'		=> array('type'=>'character varying(128)'),
			'queue_id'		=> array('type'=>'integer'),
			'notes'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'queue_id'		=> array("INDEX", "queue_id"),
			'session_id'	=> array("INDEX", "session_id"),
			'token_id'		=> array("INDEX", "token_id"),
		)
	),

	"chat_server" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'integer'),
			'user_name'		=> array('type'=>'character varying(256)'),
			'friend_name'	=> array('type'=>'character varying(256)'),
			'friend_server'	=> array('type'=>'character varying(256)'),
			'message'		=> array('type'=>'text'),
			'ts_last_message'=> array('type'=>'timestamp without time zone'),
			'f_read'		=> array('type'=>'boolean', "default"=>"false"),
			'message_timestamp'=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id"),
			'f_read'		=> array("INDEX", "f_read"),
			'friend_name'	=> array("INDEX", "friend_name"),
			'message_timestamp'	=> array("INDEX", "message_timestamp"),
			'user_name'		=> array("INDEX", "user_name"),
		)
	),

	"chat_server_session" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'integer'),
			'user_name'		=> array('type'=>'character varying(256)'),
			'friend_name'	=> array('type'=>'character varying(256)'),
			'friend_server'	=> array('type'=>'character varying(256)'),
			'f_typing'		=> array('type'=>'boolean', "default"=>"false"),
			'f_popup'		=> array('type'=>'boolean', "default"=>"false"),
			'f_online'		=> array('type'=>'boolean', "default"=>"false"),
			'f_newmessage'	=> array('type'=>'boolean', "default"=>"false"),
			'last_timestamp'=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id"),
			'user_name'		=> array("INDEX", "user_name"),
			'friend_name'	=> array("INDEX", "friend_name"),
			'friend_server'	=> array("INDEX", "friend_server"),
			'f_popup'		=> array("INDEX", "f_popup"),
		)
	),

	"comments" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'entered_by'	=> array('type'=>'character varying(512)'),
			'user_name_chache'=> array('type'=>'character varying(512)'),
			'owner_id'		=> array('type'=>'integer'),
			'comment'		=> array('type'=>'text'),
			'notified'		=> array('type'=>'text'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'path'			=> array('type'=>'text'),
			'uname'			=> array('type'=>'character varying(256)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'owner_id'		=> array("INDEX", "owner_id"),
		)
	),

	"contacts_personal_labels" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'integer'),
			'name'			=> array('type'=>'character varying(64)'),
			'color'			=> array('type'=>'character varying(6)'),
			'parent_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id"),
			'parent_id'		=> array("INDEX", "parent_id"),
		)
	),

	"contacts_personal_label_mem" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'contact_id'	=> array('type'=>'integer'),
			'label_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'membership'	=> array("UNIQUE", array("contact_id", "label_id")),
		)
	),

	"customer_association_types" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(64)'),
			'f_child'		=> array('type'=>'boolean', "default"=>"false"),
			'inherit_fields'=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array()
	),

	"customer_associations" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'parent_id'		=> array('type'=>'integer'),
			'customer_id'	=> array('type'=>'integer'),
			'type_id'		=> array('type'=>'integer'),
			'relationship_name'=> array('type'=>'character varying(256)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'customer_id'	=> array("INDEX", "customer_id"),
			'parent_id'		=> array("INDEX", "parent_id"),
			'type_id'		=> array("INDEX", "type_id"),
		)
	),

	"customer_ccards" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'customer_id'	=> array('type'=>'integer'),
			'ccard_name'	=> array('type'=>'character varying(512)'),
			'ccard_number'	=> array('type'=>'character varying(256)'),
			'ccard_type'	=> array('type'=>'character varying(32)'),
			'ccard_exp_month'=> array('type'=>'smallint'),
			'ccard_exp_year'=> array('type'=>'smallint'),
			'enc_ver'		=> array('type'=>'character varying(16)'),
			'f_default'		=> array('type'=>'boolean', "default"=>"false"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'customer_id'	=> array("INDEX", "customer_id"),
		)
	),

	"customer_invoice_templates" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(512)'),
			'company_logo'	=> array('type'=>'integer'),
			'company_name'	=> array('type'=>'character varying(128)'),
			'company_slogan'=> array('type'=>'character varying(256)'),
			'notes_line1'	=> array('type'=>'text'),
			'notes_line2'	=> array('type'=>'text'),
			'footer_line1'	=> array('type'=>'text'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"customer_invoices" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'number'		=> array('type'=>'character varying(512)'),
			'owner_id'		=> array('type'=>'integer'),
			'customer_id'	=> array('type'=>'integer'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'name'			=> array('type'=>'character varying(512)'),
			'status_id'		=> array('type'=>'integer'),
			'created_by'	=> array('type'=>'character varying(256)'),
			'updated_by'	=> array('type'=>'character varying(256)'),
			'date_due'		=> array('type'=>'date'),
			'template_id'	=> array('type'=>'integer'),
			'notes_line1'	=> array('type'=>'text'),
			'notes_line2'	=> array('type'=>'text'),
			'footer_line1'	=> array('type'=>'text'),
			'payment_terms'	=> array('type'=>'text'),
			'send_to'		=> array('type'=>'text'),
			'tax_rate'		=> array('type'=>'integer'),
			'amount'		=> array('type'=>'numeric'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'path'			=> array('type'=>'text'),
	        'uname'         => array('type'=>'character varying(256)'),
			'sales_order_id'=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'owner_id'		=> array("INDEX", "owner_id"),
			'customer_id'	=> array("INDEX", "customer_id"),
			'status_id'		=> array("INDEX", "status_id"),
			'template_id'	=> array("INDEX", "template_id"),
		)
	),

	"customer_invoice_detail" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'invoice_id'	=> array('type'=>'integer'),
			'product_id'	=> array('type'=>'integer'),
			'quantity'		=> array('type'=>'numeric', 'default'=>'1'),
			'amount'		=> array('type'=>'numeric', 'default'=>'0'),
			'name'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'invoice_id'	=> array("INDEX", "invoice_id"),
			'product_id'	=> array("INDEX", "product_id"),
		)
	),

	"customer_invoice_status" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'sort_order'	=> array('type'=>'smallint'),
			'f_paid'		=> array('type'=>'boolean', "default"=>"false"),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"customer_labels" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(64)'),
			'parent_id'		=> array('type'=>'integer'),
			'f_special'		=> array('type'=>'boolean', "default"=>"false"),
			'color'			=> array('type'=>'character varying(6)'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'parent_id'		=> array("INDEX", "parent_id"),
		)
	),

	"customer_label_mem" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'customer_id'	=> array('type'=>'integer'),
			'label_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'customer_id'	=> array("INDEX", "customer_id"),
			'label_id'		=> array("INDEX", "label_id"),
		)
	),

	"customer_leads" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'queue_id'		=> array('type'=>'integer'),
			'owner_id'		=> array('type'=>'integer'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'first_name'	=> array('type'=>'character varying(128)'),
			'last_name'		=> array('type'=>'character varying(128)'),
			'email'			=> array('type'=>'character varying(256)'),
			'phone'			=> array('type'=>'character varying(128)'),
			'street'		=> array('type'=>'text'),
			'street2'			=> array('type'=>'character varying(256)'),
			'city'			=> array('type'=>'character varying(256)'),
			'state'			=> array('type'=>'character varying(128)'),
			'zip'			=> array('type'=>'character varying(32)'),
			'notes'			=> array('type'=>'text'),
			'source_id'		=> array('type'=>'integer'),
			'rating_id'		=> array('type'=>'integer'),
			'status_id'		=> array('type'=>'integer'),
			'company'		=> array('type'=>'character varying(256)'),
			'title'			=> array('type'=>'character varying(256)'),
			'website'		=> array('type'=>'character varying(512)'),
			'country'		=> array('type'=>'character varying(512)'),
			'customer_id'	=> array('type'=>'integer'),
			'opportunity_id'=> array('type'=>'integer'),
			'ts_first_contacted'=> array('type'=>'timestamp with time zone'),
			'ts_last_contacted'=> array('type'=>'timestamp with time zone'),
			'class_id'		=> array('type'=>'integer'),
			'phone2'		=> array('type'=>'character varying(128)'),
			'phone3'		=> array('type'=>'character varying(128)'),
			'fax'			=> array('type'=>'character varying(64)'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'path'			=> array('type'=>'text'),
			'uname'			=> array('type'=>'character varying(256)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'customer_id'	=> array("INDEX", "customer_id"),
			'source_id'		=> array("INDEX", "source_id"),
			'rating_id'		=> array("INDEX", "rating_id"),
			'status_id'		=> array("INDEX", "status_id"),
			'opportunity_id'=> array("INDEX", "opportunity_id"),
		)
	),

	"customer_lead_status" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'f_closed'		=> array('type'=>'boolean', "default"=>"false"),
			'f_converted'	=> array('type'=>'boolean', "default"=>"false"),
			'color'			=> array('type'=>'character varying(6)'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"customer_lead_sources" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'color'			=> array('type'=>'character varying(6)'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"customer_lead_rating" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'color'			=> array('type'=>'character varying(6)'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"customer_lead_classes" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'color'			=> array('type'=>'character varying(6)'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"customer_lead_queues" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'color'			=> array('type'=>'character varying(6)'),
			'dacl_edit'		=> array('type'=>'integer'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"customer_objections" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'description'	=> array('type'=>'text'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'color'			=> array('type'=>'character varying(6)'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"customer_opportunities" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'owner_id'		=> array('type'=>'integer'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'notes'			=> array('type'=>'text'),
			'stage_id'		=> array('type'=>'integer'),
			'name'			=> array('type'=>'text'),
			'expected_close_date'=> array('type'=>'date'),
			'amount'		=> array('type'=>'numeric'),
			'customer_id'	=> array('type'=>'integer'),
			'lead_id'		=> array('type'=>'integer'),
			'lead_source_id'=> array('type'=>'integer'),
			'probability_per'=> array('type'=>'smallint'),
			'created_by'	=> array('type'=>'character varying(256)'),
			'type_id'		=> array('type'=>'integer'),
			'updated_by'	=> array('type'=>'character varying(256)'),
			'ts_closed'		=> array('type'=>'timestamp with time zone'),
			'ts_first_contacted'=> array('type'=>'timestamp with time zone'),
			'ts_last_contacted'=> array('type'=>'timestamp with time zone'),
			'objection_id'	=> array('type'=>'integer'),
			'closed_lost_reson'=> array('type'=>'text'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'path'			=> array('type'=>'text'),
			'uname'			=> array('type'=>'character varying(256)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'stage_id'		=> array("INDEX", "stage_id"),
			'customer_id'	=> array("INDEX", "customer_id"),
			'lead_id'		=> array("INDEX", "lead_id"),
			'lead_source_id'=> array("INDEX", "lead_source_id"),
			'type_id'		=> array("INDEX", "type_id"),
		)
	),

	"customer_opportunity_stages" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'color'			=> array('type'=>'character varying(6)'),
			'f_closed'		=> array('type'=>'boolean', "default"=>"false"),
			'f_won'			=> array('type'=>'boolean', "default"=>"false"),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"customer_opportunity_types" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'color'			=> array('type'=>'character varying(6)'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"customer_publish" => array(
		"COLUMNS" => array(
			'customer_id'	=> array('type'=>'bigint', 'notnull'=>true),
			'username'		=> array('type'=>'character varying(256)'),
			'password'		=> array('type'=>'character varying(128)'),
			'f_files_view'	=> array('type'=>'boolean', "default"=>"false"),
			'f_files_upload'=> array('type'=>'boolean', "default"=>"false"),
			'f_files_modify'=> array('type'=>'boolean', "default"=>"false"),
			'f_modify_contact'=> array('type'=>'boolean', "default"=>"false"),
			'f_update_image'=> array('type'=>'boolean', "default"=>"false"),
		),
		'PRIMARY_KEY'		=> 'customer_id',
		"KEYS" => array(
			'username'		=> array("INDEX", "username"),
			'password'		=> array("INDEX", "password"),
		)
	),

	"customer_stages" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'color'			=> array('type'=>'character varying(6)'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"customer_status" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'color'			=> array('type'=>'character varying(6)'),
			'f_closed'		=> array('type'=>'boolean', "default"=>"false"),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"customers" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(512)'),
			'first_name'	=> array('type'=>'character varying(256)'),
			'last_name'		=> array('type'=>'character varying(256)'),
			'company'		=> array('type'=>'character varying(256)'),
			'salutation'	=> array('type'=>'character varying(512)'),
			'email'			=> array('type'=>'character varying(128)'),
			'email2'		=> array('type'=>'character varying(128)'),
			'email3'		=> array('type'=>'character varying(128)'),
			'phone_home'	=> array('type'=>'character varying(64)'),
			'phone_work'	=> array('type'=>'character varying(64)'),
			'phone_cell'	=> array('type'=>'character varying(64)'),
			'phone_other'	=> array('type'=>'character varying(64)'),
			'street'		=> array('type'=>'character varying(128)'),
			'street_2'		=> array('type'=>'character varying(128)'),
			'city'			=> array('type'=>'character varying(128)'),
			'zip'			=> array('type'=>'character varying(32)'),
			'time_entered'	=> array('type'=>'timestamp with time zone'),
			'job_title'		=> array('type'=>'character varying(64)'),
			'phone_fax'		=> array('type'=>'character varying(64)'),
			'phone_pager'	=> array('type'=>'character varying(64)'),
			'middle_name'	=> array('type'=>'character varying(64)'),
			'time_changed'	=> array('type'=>'timestamp with time zone'),
			'email_default'	=> array('type'=>'character varying(16)'),
			'spouse_name'	=> array('type'=>'character varying(128)'),
			'business_street'=> array('type'=>'character varying(128)'),
			'business_street_2'=> array('type'=>'character varying(128)'),
			'business_city'	=> array('type'=>'character varying(64)'),
			'business_state'=> array('type'=>'character varying(64)'),
			'business_zip'	=> array('type'=>'character varying(16)'),
			'phone_ext'		=> array('type'=>'character varying(16)'),
			'website'		=> array('type'=>'character varying(128)'),
			'birthday'		=> array('type'=>'date'),
			'birthday_spouse'=> array('type'=>'date'),
			'anniversary'	=> array('type'=>'date'),
			'last_contacted'=> array('type'=>'timestamp with time zone'),
			'nick_name'		=> array('type'=>'character varying(256)'),
			'source'		=> array('type'=>'character varying(128)'),
			'status'		=> array('type'=>'character varying(64)'),
			'email_spouse'	=> array('type'=>'character varying(128)'),
			'source_notes'	=> array('type'=>'text'),
			'contacted'		=> array('type'=>'text'),
			'notes'			=> array('type'=>'text'),
			'type_id'		=> array('type'=>'integer'),
			'address_default'=> array('type'=>'character varying(16)'),
			'f_nocall'		=> array('type'=>'boolean', "default"=>"false"),
			'f_noemailspam'	=> array('type'=>'boolean', "default"=>"false"),
			'f_nocontact'	=> array('type'=>'boolean', "default"=>"false"),
			'status_id'		=> array('type'=>'integer'),
			'stage_id'		=> array('type'=>'integer'),
			'owner_id'		=> array('type'=>'integer'),
			'address_billing'=> array('type'=>'character varying(16)'),
			'ts_first_contacted'=> array('type'=>'timestamp with time zone'),
			'shipping_street'=> array('type'=>'character varying(128)'),
			'shipping_street2'=> array('type'=>'character varying(128)'),
			'shipping_city'	=> array('type'=>'character varying(64)'),
			'shipping_state'=> array('type'=>'character varying(64)'),
			'shipping_zip'	=> array('type'=>'character varying(16)'),
			'billing_street'=> array('type'=>'character varying(128)'),
			'billing_street2'=> array('type'=>'character varying(128)'),
			'billing_city'	=> array('type'=>'character varying(64)'),
			'billing_state'=> array('type'=>'character varying(64)'),
			'billing_zip'	=> array('type'=>'character varying(16)'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'path'			=> array('type'=>'text'),
			'uname'			=> array('type'=>'character varying(256)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'owner_id'		=> array("INDEX", "owner_id"),
			'stage_id'		=> array("INDEX", "stage_id"),
			'status_id'		=> array("INDEX", "status_id"),
			'type_id'		=> array("INDEX", "type_id"),
			'email'			=> array("INDEX", "email"),
			'email2'		=> array("INDEX", "email2"),
		)
	),

	"discussions" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(512)'),
			'message'		=> array('type'=>'text'),
			'notified'		=> array('type'=>'text'),
			'ts_entered'=> array('type'=>'timestamp with time zone'),
			'ts_updated'=> array('type'=>'timestamp with time zone'),
			'owner_id'		=> array('type'=>'integer'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'path'			=> array('type'=>'text'),
			'uname'			=> array('type'=>'character varying(256)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'owner_id'		=> array("INDEX", "owner_id"),
		)
	),

	"email_accounts" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(512)', 'notnull'=>true),
			'address'		=> array('type'=>'character varying(512)', 'notnull'=>true),
			'reply_to'		=> array('type'=>'character varying(512)'),
			'user_id'		=> array('type'=>'integer'),
			'ts_last_full_sync'=> array('type'=>'integer'),
	        'f_default'     => array('type'=>'boolean', "default"=>"false"),
	        'signature'     => array('type'=>'text'),
	        'type'          => array('type'=>'character varying(16)'),
	        'username'      => array('type'=>'character varying(128)'),
	        'password'      => array('type'=>'character varying(128)'),
	        'host'          => array('type'=>'character varying(128)'),
	        'port'          => array('type'=>'character varying(8)'),
	        'ssl'           => array('type'=>'character varying(8)'),
	        'sync_data'     => array('type'=>'text'),
	        'f_ssl' 	   	=> array('type'=>'boolean', "default"=>"false"),
	        'f_system'    	=> array('type'=>'boolean', "default"=>"false"),
	        'f_outgoing_auth'=> array('type'=>'boolean', "default"=>"false"),
	        'host_out'      => array('type'=>'character varying(128)'),
	        'port_out'      => array('type'=>'character varying(8)'),
	        'f_ssl_out'	   	=> array('type'=>'boolean', "default"=>"false"),
	        'username_out'  => array('type'=>'character varying(128)'),
	        'password_out'  => array('type'=>'character varying(128)'),
	        'forward'       => array('type'=>'character varying(256)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id", "users", "id"),
			'address'		=> array("INDEX", "address"),
		)
	),


	"email_video_message_themes" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)', 'notnull'=>true),
			'html'			=> array('type'=>'text'),
			'header_file_id'=> array('type'=>'integer'),
			'footer_file_id'=> array('type'=>'integer'),
			'user_id'		=> array('type'=>'integer'),
			'background_color'=> array('type'=>'character varying(6)'),
			'scope'			=> array('type'=>'character varying(32)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id", "users", "id"),
			'scope'			=> array("INDEX", "scope"),
		)
	),

	"email_filters" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(512)', 'notnull'=>true),
			'kw_subject'	=> array('type'=>'text'),
			'kw_to'			=> array('type'=>'text'),
			'kw_from'		=> array('type'=>'text'),
			'kw_body'		=> array('type'=>'text'),
			'f_active'		=> array('type'=>'boolean', "default"=>"true"),
			'act_mark_read'	=> array('type'=>'boolean', "default"=>"false"),
			'act_move_to'	=> array('type'=>'integer'),
			'user_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id", "users", "id"),
		)
	),

	"email_settings_spam" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'preference'	=> array('type'=>'character varying(32)', 'notnull'=>true),
			'value'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'user_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id", "users", "id"),
		)
	),

	"email_mailboxes" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(64)'),
			'parent_box'	=> array('type'=>'integer'),
			'flag_special'	=> array('type'=>'boolean', "default"=>"false"),
			'f_system'		=> array('type'=>'boolean', "default"=>"false"),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'color'			=> array('type'=>'character varying(6)'),
	        'user_id'       => array('type'=>'integer'),
	        'type'          => array('type'=>'character varying(16)'),
			'mailbox'		=> array('type'=>'character varying(128)'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id", "users", "id"),
			'parent_box'	=> array("INDEX", "parent_box"),
		)
	),


	"email_message_original" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'message_id'	=> array('type'=>'bigint'),
			'file_id'		=> array('type'=>'bigint'),
			'lo_message'	=> array('type'=>'oid'),
			'antmail_version'=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'message_id'	=> array("INDEX", "message_id", "objects_email_messages", "id"),
		)
	),

	"email_message_queue" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'integer'),
			'lo_message'	=> array('type'=>'oid'),
			'ts_delivered'	=> array('type'=>'timestamp with time zone'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id", "users", "id"),
		)
	),

	"email_thread_mailbox_mem" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'thread_id'		=> array('type'=>'bigint'),
			'mailbox_id'	=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'mailbox_id'	=> array("INDEX", "mailbox_id", "email_mailboxes", "id"),
			'thread_id'		=> array("INDEX", "thread_id", "email_threads", "id"),
		)
	),

	"email_video_messages" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'integer'),
			'file_id'		=> array('type'=>'bigint'),
			'title'			=> array('type'=>'text'),
			'subtitle'		=> array('type'=>'text'),
			'message'		=> array('type'=>'text'),
			'footer'		=> array('type'=>'text'),
			'theme'			=> array('type'=>'character varying(64)'),
			'name'			=> array('type'=>'character varying(256)'),
			'logo_file_id'	=> array('type'=>'bigint'),
			'f_template_video'=> array('type'=>'boolean', "default"=>"false"),
			'facebook'		=> array('type'=>'text'),
			'twitter'		=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id", "users", "id"),
		)
	),

	"favorites_categories" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'user_id'		=> array('type'=>'integer'),
			'parent_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id", "users", "id"),
			'parent_id'		=> array("INDEX", "parent_id", "favorites_categories", "id"),
		)
	),

	"favorites" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(64)'),
			'user_id'		=> array('type'=>'integer'),
			'favorite_category'	=> array('type'=>'integer'),
			'url'			=> array('type'=>'text'),
			'notes'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id", "users", "id"),
			'favorite_category'	=> array("INDEX", "favorite_category", "favorites_categories", "id"),
		)
	),

	"ic_groups" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'parent_id'		=> array('type'=>'integer'),
			'name'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'parent_id'		=> array("INDEX", "parent_id"),
		)
	),

	"ic_document_group_mem" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'document_id'	=> array('type'=>'bigint'),
			'group_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'document_id'	=> array("INDEX", "document_id", "ic_documents", "id"),
			'group_id'		=> array("INDEX", "group_id", "ic_groups", "id"),
		),
	),


	"ic_documents" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'title'			=> array('type'=>'character varying(512)'),
			'keywords'		=> array('type'=>'text'),
			'body'			=> array('type'=>'text'),
			'video_file_id'	=> array('type'=>'bigint'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'owner_id'		=> array('type'=>'integer'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'path'			=> array('type'=>'text'),
			'uname'			=> array('type'=>'character varying(256)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'owner_id'		=> array("FKEY", "owner_id", "users", "id"),
		)
	),


	"members" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(512)'),
			'role'			=> array('type'=>'character varying(512)'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_accepted'	=> array('type'=>'boolean', "default"=>"true"),
			'f_required'	=> array('type'=>'boolean', "default"=>"false"),
			'f_invsent'		=> array('type'=>'boolean', "default"=>"false"),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'path'			=> array('type'=>'text'),
			'uname'			=> array('type'=>'character varying(256)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	/**
	 * Based table where all object tables inherit from
	 */
	"objects" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'object_type_id'=> array('type'=>'integer'),
			'revision'		=> array('type'=>'integer'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'owner_id'		=> array('type'=>'bigint'),
			'owner_id_fval'	=> array('type'=>'text'),
			'creator_id'	=> array('type'=>'bigint'),
			'creator_id_fval'=> array('type'=>'text'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
			'dacl'			=> array('type'=>'text'),
			'tsv_fulltext'	=> array('type'=>'tsvector'),
			'num_comments'	=> array('type'=>'integer'),
			'commit_id'		=> array('type'=>'bigint'),
		)
	),

	/**
	 * Table stores refrence of moved objects to another object (like when merged)
	 */
	"objects_moved" => array(
		"COLUMNS" => array(
			'object_type_id'=> array('type'=>'bigint', 'notnull'=>true),
			'object_id'		=> array('type'=>'bigint', 'notnull'=>true),
			'moved_to'		=> array('type'=>'bigint', 'notnull'=>true),
		),
		'PRIMARY_KEY'		=> array("object_type_id", "object_id"),
		"KEYS" => array(
			'movedto'		=> array("INDEX", array("object_type_id", "moved_to"))
		)
	),

	/**
	 * Store multi-dim references between objects (related to / associated with)
	 */
	"object_associations" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'type_id'		=> array('type'=>'integer'),
			'object_id'		=> array('type'=>'bigint'),
			'assoc_type_id'	=> array('type'=>'integer'),
			'assoc_object_id'=> array('type'=>'bigint'),
			'field_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'assocobj'		=> array("INDEX", array("assoc_type_id", "assoc_object_id", "field_id")),
			'fld'			=> array("INDEX", array("type_id", "object_id", "field_id")),
			'refobj'		=> array("INDEX", array("type_id", "assoc_type_id", "field_id")),
			'oid'			=> array("INDEX", array("object_id")),
			'type'			=> array("INDEX", array("type_id")),
		)
	),

	/**
	 * Store indexe data for initialization and schema updates mostly
	 */
	"object_indexes" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'notnull'=>true),
			'revision'		=> array('type'=>'integer'),
		)
	),


	/**
	 * @depriacted
	 * We are leaving these for reference in case we decide to use oracle which is 
	 * a whole lot better at very thin and long tables index queries and sorts.
	 *
		"object_index" => array(
		"COLUMNS" => array(
			'object_id'		=> array('type'=>'bigint', 'notnull'=>true),
			'object_type_id'=> array('type'=>'integer', 'notnull'=>true),
			'field_id'		=> array('type'=>'integer'),
			'val_text'		=> array('type'=>'text'),
			'val_tsv'		=> array('type'=>'tsvector'),
			'val_number'	=> array('type'=>'numeric'),
			'val_bool'		=> array('type'=>'boolean'),
			'val_timestamp'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean'),
		)
	),

		"object_index_cachedata" => array(
		"COLUMNS" => array(
			'object_type_id'=> array('type'=>'integer', 'notnull'=>true),
			'object_id'		=> array('type'=>'bigint', 'notnull'=>true),
			'revision'		=> array('type'=>'integer'),
			'data'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> array('object_type_id', 'object_id'),
		"KEYS" => array(
			'object_type_id'=> array("FKEY", "object_type_id", "app_object_types", "id"),
		),
	),

	// No keys because this is an abstract table inherited
		"object_index_fulltext" => array(
		"COLUMNS" => array(
			'object_type_id'=> array('type'=>'integer', 'notnull'=>true),
			'object_id'		=> array('type'=>'bigint', 'notnull'=>true),
			'object_revision'=> array('type'=>'integer'),
			'f_deleted'		=> array('type'=>'boolean'),
			'snippet'		=> array('type'=>'character varying(512)'),
			'private_owner_id'=> array('type'=>'integer'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'tsv_keywords'	=> array('type'=>'tsvector'),
		)
	),

		"object_index_fulltext_act" => array(
		'COLUMNS' => array(), // inherited
		'INHERITS' => 'object_index_fulltext',
		'PRIMARY_KEY' => array('object_type_id', 'object_id'),
		"CONSTRAINTS" => array(
			'actcheck'=> "f_deleted = false",
		),
		"KEYS" => array(
			'object_type_id'=> array("INDEX", "object_type_id", "app_object_types", "id"),
			'private_owner'=> array("INDEX", "private_owner_id", "users", "id"),
			'keywords'	=> array("INDEX", "tsv_keywords"),
		),
	);

		"object_index_fulltext_del" => array(
		'COLUMNS' => array(), // inherited
		'INHERITS' => 'object_index_fulltext',
		'PRIMARY_KEY'		=> array('object_type_id', 'object_id'),
		"CONSTRAINTS" => array(
			'actcheck'=> "f_deleted = true",
		),
		"KEYS" => array(
			'object_type_id'=> array("INDEX", "object_type_id", "app_object_types", "id"),
			'private_owner'	=> array("INDEX", "private_owner_id", "users", "id"),
			'keywords'	=> array("INDEX", "tsv_keywords"),
		),
	),
	*/

	/**
	 * Historical log used to indicate when an object has been indexed so that
	 * we can reconsile with a background script to make sure we did not miss anything.
	 */
	"object_indexed" => array(
		"COLUMNS" => array(
			'object_type_id'=> array('type'=>'integer', 'notnull'=>true),
			'object_id'		=> array('type'=>'bigint', 'notnull'=>true),
			'revision'		=> array('type'=>'integer'),
			'index_type'	=> array('type'=>'smallint'),
		),
		'PRIMARY_KEY'		=> array('object_type_id', 'object_id'),
		"KEYS" => array(
			'index_type'	=> array("INDEX", "index_type"),
			'object_id'		=> array("INDEX", "object_id"),
			'object_type_id'=> array("INDEX", "object_type_id"),
		),
	),

	"object_recurrence" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'object_type'	=> array('type'=>'character varying(256)'),
			'object_type_id'=> array('type'=>'integer', 'notnull'=>true),
			'type'			=> array('type'=>'smallint'),
			'interval'		=> array('type'=>'smallint'),
			'date_processed_to'	=> array('type'=>'date'),
			'date_start'	=> array('type'=>'date'),
			'date_end'		=> array('type'=>'date'),
			't_start'		=> array('type'=>'time with time zone'),
			't_end'			=> array('type'=>'time with time zone'),
			'all_day'		=> array('type'=>'boolean', "default"=>"false"),
			'ep_locked'		=> array('type'=>'integer'),
			'dayofmonth'	=> array('type'=>'smallint'),
			'dayofweekmask'	=> array('type'=>'boolean[]'),
			'duration'		=> array('type'=>'integer'),
			'instance'		=> array('type'=>'smallint'),
			'monthofyear'	=> array('type'=>'smallint'),
			'parent_object_id'	=> array('type'=>'bigint'),
			'type_id'		=> array('type'=>'character varying(256)'),
			'f_active'		=> array('type'=>'boolean', "default"=>"true"),
		),
		'PRIMARY_KEY'		=> array('id'),
		"KEYS" => array(
			'date_processed_to'	=> array("INDEX", "date_processed_to"),
		),
	),

	"object_revisions" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'object_type_id'=> array('type'=>'integer', 'notnull'=>true),
			'object_id'		=> array('type'=>'bigint', 'notnull'=>true),
			'revision'		=> array('type'=>'integer'),
			'ts_updated'	=> array('type'=>'time with time zone'),
			'data'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> array('id'),
		"KEYS" => array(
			'object'	=> array("INDEX", array("object_type_id", "object_id")),
		),
	),

	"object_revision_data" => array(
		"COLUMNS" => array(
			'revision_id'	=> array('type'=>'bigint'),
			'field_name'	=> array('type'=>'character varying(256)'),
			'field_value'	=> array('type'=>'text'),
		),
		"KEYS" => array(
			'revision_id'	=> array("INDEX", 'revision_id', "object_revisions", "id"),
		),
	),

	"object_unames" => array(
		"COLUMNS" => array(
			'object_type_id'=> array('type'=>'integer', 'notnull'=>true),
			'object_id'	=> array('type'=>'bigint', 'notnull'=>true),
			'name'		=> array('type'=>'character varying(512)'),
		),
		'PRIMARY_KEY'	=> array('object_type_id', 'object_id'),
		"KEYS" => array(
			'uname'		=> array("INDEX", array("object_type_id", "name")),
		),
	),

	"object_groupings" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'object_type_id'=> array('type'=>'integer'),
			'field_id'		=> array('type'=>'integer'),
			'parent_id'		=> array('type'=>'bigint'),
			'user_id'		=> array('type'=>'integer'),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'f_system'		=> array('type'=>'boolean', "default"=>"false"),
			'f_closed'		=> array('type'=>'boolean', "default"=>"false"),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> array('id'),
		"KEYS" => array(
			'object_type_id'=> array("INDEX", array("object_type_id")),
			'field'			=> array("INDEX", array("field_id")),
			'parent'		=> array("INDEX", array("parent_id")),
			'user'			=> array("INDEX", array("user_id")),
		)
	),

	"object_grouping_mem" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'object_type_id'=> array('type'=>'integer', 'notnull'=>true),
			'object_id'		=> array('type'=>'integer', 'notnull'=>true),
			'field_id'		=> array('type'=>'integer', 'notnull'=>true),
			'grouping_id'	=> array('type'=>'integer', 'notnull'=>true),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'object'		=> array("INDEX", array("object_type_id", "object_id")),
			'field_id'		=> array("INDEX", "field_id"),
			'group'			=> array("INDEX", "grouping_id", "object_groupings", "id"),
		)
	),

	"printing_papers_labels" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(32)'),
			'cols'			=> array('type'=>'smallint'),
			'y_start_pos'	=> array('type'=>'smallint'),
			'y_interval'	=> array('type'=>'smallint'),
			'x_pos'			=> array('type'=>'character varying(32)'),
		),
		'PRIMARY_KEY'		=> array('id'),
		"KEYS" => array(
		),
	),

	"product_categories" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'parent_id'		=> array('type'=>'integer'),
			'name'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"product_categories_mem" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'product_id'	=> array('type'=>'bigint'),
			'category_id'	=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'product_id'	=> array("INDEX", "product_id", "products", "id"),
			'category_id'	=> array("INDEX", "category_id", "product_categories", "id"),
		)
	),

	"product_families" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'notes'			=> array('type'=>'text'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"products" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'notes'			=> array('type'=>'text'),
			'price'			=> array('type'=>'numeric'),
			'f_available'	=> array('type'=>'boolean', "default"=>"false"),
			'rating'		=> array('type'=>'numeric'),
			'family'		=> array('type'=>'numeric'),
			'image_id'		=> array('type'=>'numeric'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'image_id'		=> array("INDEX", "image_id"),
			'family'		=> array("FKEY", "family", "product_families", "id"),
		)
	),

	"product_reviews" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'notes'			=> array('type'=>'text'),
			'creator_id'	=> array('type'=>'integer'),
			'rating'		=> array('type'=>'numeric'),
			'product'		=> array('type'=>'bigint'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'product_id'	=> array("FKEY", "product_id", "products", "id"),
		)
	),

	/**
	 * The project_bug* tables are where cases are stored for legacy reasons
	 */
	"project_bug_severity" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"project_bug_status" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'f_closed'		=> array('type'=>'boolean', "default"=>"false"),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"project_bug_types" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"project_bugs" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'related_bug'	=> array('type'=>'integer'),
			'status_id'		=> array('type'=>'integer'),
			'severity_id'	=> array('type'=>'integer'),
			'owner_id'		=> array('type'=>'integer'),
			'type_id'		=> array('type'=>'integer'),
			'project_id'	=> array('type'=>'integer'),
			'customer_id'	=> array('type'=>'integer'),
			'title'			=> array('type'=>'character varying(128)'),
			'description'	=> array('type'=>'text'),
			'solution'		=> array('type'=>'text'),
			'date_reported'	=> array('type'=>'date'),
			'created_by'	=> array('type'=>'character varying(128)'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'related_bug'	=> array("FKEY", "related_bug", "project_bugs", "id"),
			'status_id'		=> array("FKEY", "status_id", "project_bug_status", "id"),
			'severity_id'	=> array("FKEY", "severity_id", "project_bug_severity", "id"),
			'owner_id'		=> array("FKEY", "owner_id", "users", "id"),
			'type_id'		=> array("FKEY", "type_id", "project_bug_types", "id"),
			'customer_id'	=> array("FKEY", "customer_id", "customers", "id"),
		)
	),

	"project_bug_types" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"project_priorities" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"project_templates" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'notes'			=> array('type'=>'text'),
			'user_id'		=> array('type'=>'integer'),
			'time_created'	=> array('type'=>'timestamp with time zone'),
			'custom_fields'	=> array('type'=>'boolean', "default"=>"false"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("FKEY", "user_id", "users", "id"),
		)
	),

	"project_template_members" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'integer'),
			'template_id'	=> array('type'=>'integer'),
			'accepted'		=> array('type'=>'boolean', "default"=>"false"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("FKEY", "user_id", "users", "id"),
			'template_id'	=> array("FKEY", "template_id", "project_templates", "id"),
		)
	),

	"project_template_share" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'integer'),
			'template_id'	=> array('type'=>'integer'),
			'accepted'		=> array('type'=>'boolean', "default"=>"false"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("FKEY", "user_id", "users", "id"),
			'template_id'	=> array("FKEY", "template_id", "project_templates", "id"),
		)
	),

	"project_template_tasks" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'text'),
			'notes'			=> array('type'=>'text'),
			'template_id'	=> array('type'=>'integer'),
			'start_interval'=> array('type'=>'integer'),
			'due_interval'	=> array('type'=>'integer'),
			'start_count'	=> array('type'=>'integer'),
			'due_count'		=> array('type'=>'integer'),
			'timeline'		=> array('type'=>'character varying(32)'),
			'type'			=> array('type'=>'integer'),
			'file_id'		=> array('type'=>'bigint'),
			'user_id'		=> array('type'=>'bigint'),
			'position_id'	=> array('type'=>'bigint'),
			'timeline_date_begin'=> array('type'=>'character varying(32)'),
			'timeline_date_due'=> array('type'=>'character varying(32)'),
			'cost_estimated'=> array('type'=>'numeric'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("FKEY", "user_id", "users", "id"),
			'template_id'	=> array("FKEY", "template_id", "project_templates", "id"),
			'position_id'	=> array("FKEY", "position_id", "project_positions", "id"),
		)
	),

	"projects" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'user_id'		=> array('type'=>'integer'),
			'parent'		=> array('type'=>'integer'),
			'priority'		=> array('type'=>'integer'),
			'customer_id'	=> array('type'=>'bigint'),
			'template_id'	=> array('type'=>'bigint'),
			'notes'			=> array('type'=>'text'),
			'news'			=> array('type'=>'text'),
			'date_deadline'	=> array('type'=>'date'),
			'date_completed'=> array('type'=>'date'),
			'ts_created'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'parent'		=> array("FKEY", "parent", "projects", "id"),
			'priority'		=> array("FKEY", "priority", "project_priorities", "id"),
			'customer_id'	=> array("FKEY", "customer_id", "customers", "id"),
			'user_id'		=> array("FKEY", "user_id", "users", "id"),
			'template_id'	=> array("FKEY", "template_id", "project_templates", "id"),
			'date_completed'=> array("INDEX", "date_completed"),
		)
	),

	"project_files" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'file_id'		=> array('type'=>'integer'),
			'project_id'	=> array('type'=>'integer'),
			'bug_id'		=> array('type'=>'integer'),
			'task_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'project_id'	=> array("INDEX", "project_id", "projects", "id"),
			'project_id'	=> array("INDEX", "project_id", "projects", "id"),
			'bug_id'		=> array("INDEX", "bug_id", "project_bugs", "id"),
			'task_id'		=> array("INDEX", "task_id", "project_tasks", "id"),
		)
	),

	"project_groups" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'parent_id'		=> array('type'=>'integer'),
			'name'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"project_group_mem" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'project_id'	=> array('type'=>'bigint', 'notnull'=>true),
			'group_id'		=> array('type'=>'integer', 'notnull'=>true),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'project_id'	=> array("INDEX", "project_id", "projects", "id"),
			'group_id'		=> array("INDEX", "group_id", "project_groups", "id"),
		)
	),

	"project_positions" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'project_id'	=> array('type'=>'integer'),
			'template_id'	=> array('type'=>'integer'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'project_id'	=> array("INDEX", "project_id", "projects", "id"),
			'template_id'	=> array("INDEX", "template_id", "project_templates", "id"),
		)
	),

	"project_membership" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'integer'),
			'project_id'	=> array('type'=>'integer'),
			'position_id'	=> array('type'=>'integer'),
			'title'			=> array('type'=>'character varying(128)'),
			'notes'			=> array('type'=>'text'),
			'accepted'		=> array('type'=>'boolean', "default"=>"false"),
			'invite_by'		=> array('type'=>'character varying(128)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("INDEX", "user_id", "users", "id"),
			'project_id'	=> array("INDEX", "project_id", "projects", "id"),
			'position_id'	=> array("INDEX", "position_id", "project_positions", "id"),
		)
	),

	"project_milestones" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'project_id'	=> array('type'=>'integer'),
			'user_id'		=> array('type'=>'integer'),
			'position_id'	=> array('type'=>'integer'),
			'name'			=> array('type'=>'character varying(256)'),
			'notes'			=> array('type'=>'text'),
			'deadline'		=> array('type'=>'date'),
			'f_completed'	=> array('type'=>'boolean', "default"=>"false"),
			'date_completed'=> array('type'=>'date'),
			'creator_name'	=> array('type'=>'character varying(128)'),
			'creator_id'	=> array('type'=>'integer'),
			'ts_created'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'project_id'	=> array("FKEY", "project_id", "projects", "id"),
			'position_id'	=> array("FKEY", "position_id", "project_positions", "id"),
			'user_id'		=> array("FKEY", "user_id", "users", "id"),
			'creator_id'	=> array("FKEY", "creator_id", "users", "id"),
		)
	),

	"project_tasks" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'text'),
			'notes'			=> array('type'=>'text'),
			'user_id'		=> array('type'=>'integer'),
			'done'			=> array('type'=>'boolean', "default"=>"false"),
			'date_entered'	=> array('type'=>'date'),
			'date_completed'=> array('type'=>'date'),
			'start_date'=> array('type'=>'date'),
			'priority'		=> array('type'=>'integer'),
			'project'		=> array('type'=>'integer'),
			'entered_by'	=> array('type'=>'character varying(128)'),
			'deadline'		=> array('type'=>'date'),
			'cost_estimated'=> array('type'=>'numeric'),
			'cost_actual'	=> array('type'=>'numeric'),
			'type'			=> array('type'=>'integer'),
			'customer_id'	=> array('type'=>'integer'),
			'template_task_id'	=> array('type'=>'integer'),
			'position_id'	=> array('type'=>'integer'),
			'creator_id'	=> array('type'=>'integer'),
			'milestone_id'	=> array('type'=>'integer'),
			'depends_task_id'	=> array('type'=>'integer'),
			'case_id'	=> array('type'=>'integer'),
			'recurrence_pattern'=> array('type'=>'integer'),
			'ts_created'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("FKEY", "user_id", "users", "id"),
			'project'		=> array("FKEY", "project", "projects", "id"),
			'customer_id'	=> array("FKEY", "customer_id", "customers", "id"),
			'position_id'	=> array("FKEY", "position_id", "project_positions", "id"),
			'creator_id'	=> array("FKEY", "creator_id", "users", "id"),
			'milestone_id'	=> array("FKEY", "milestone_id", "project_milestones", "id"),
			'depends_task_id'=> array("FKEY", "depends_task_id", "project_tasks", "id"),
			'date_entered'	=> array("INDEX", "date_entered"),
			'deadline'		=> array("INDEX", "deadline"),
		)
	),

	"project_time" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'notes'			=> array('type'=>'text'),
			'owner_id'		=> array('type'=>'integer'),
			'creator_id'	=> array('type'=>'integer'),
			'task_id'		=> array('type'=>'integer'),
			'date_applied'	=> array('type'=>'date'),
			'hours'			=> array('type'=>'numeric'),
			'ts_created'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'owner_id'		=> array("FKEY", "owner_id", "users", "id"),
			'creator_id'	=> array("FKEY", "creator_id", "users", "id"),
			'task_id'		=> array("FKEY", "task_id", "project_tasks", "id"),
		)
	),

	"reports" => array(
		"COLUMNS" => array(
			'id'			    => array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			    => array('type'=>'character varying(512)'),
			'description'	    => array('type'=>'text'),
			'obj_type'		    => array('type'=>'character varying(256)'),
			'chart_type'	    => array('type'=>'character varying(128)'),
			'chart_measure'	    => array('type'=>'character varying(256)'),
			'chart_measure_agg'	=> array('type'=>'character varying(32)'),
			'chart_dim1'	    => array('type'=>'character varying(256)'),
			'chart_dim1_grp'	=> array('type'=>'character varying(32)'),
			'chart_dim2'	    => array('type'=>'character varying(256)'),
			'chart_dim2_grp'	=> array('type'=>'character varying(32)'),
			'chart_type'	    => array('type'=>'character varying(128)'),
			'chart_type'	    => array('type'=>'character varying(128)'),
			'f_display_table'   => array('type'=>'boolean', "default"=>"true"),
			'f_display_chart'   => array('type'=>'boolean', "default"=>"true"),
			'scope'			    => array('type'=>'character varying(32)'),
			'owner_id'		    => array('type'=>'integer'),
			'custom_report'	    => array('type'=>'character varying(512)'),
			'dataware_cube'	    => array('type'=>'character varying(512)'),
			'ts_created'	    => array('type'=>'timestamp with time zone'),
			'ts_updated'	    => array('type'=>'timestamp with time zone'),
			'f_deleted'		    => array('type'=>'boolean', "default"=>"false"),
			'revision'		    => array('type'=>'integer'),
			'uname'			    => array('type'=>'character varying(256)'),
	        'path'              => array('type'=>'text'),
	        'table_type'        => array('type'=>'character varying(32)'),
	        'f_row_totals'      => array('type'=>'boolean', "default"=>"false"),
	        'f_column_totals'   => array('type'=>'boolean', "default"=>"false"),
			'f_sub_totals'		=> array('type'=>'boolean', "default"=>"false"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'owner_id'		=> array("FKEY", "owner_id", "users", "id"),
		)
	),

	"sales_orders" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'number'		=> array('type'=>'character varying(256)'),
			'created_by'	=> array('type'=>'character varying(256)'),
			'tax_rate'		=> array('type'=>'numeric'),
			'amount'		=> array('type'=>'numeric'),
			'ship_to'		=> array('type'=>'text'),
			'ship_to_cship'	=> array('type'=>'boolean', "default"=>"false"),
			'owner_id'		=> array('type'=>'integer'),
			'status_id'		=> array('type'=>'integer'),
			'customer_id'	=> array('type'=>'integer'),
			'invoice_id'	=> array('type'=>'integer'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'owner_id'		=> array("FKEY", "owner_id", "users", "id"),
			'status_id'		=> array("FKEY", "status_id", "sales_order_status", "id"),
			'customer_id'	=> array("FKEY", "customer_id", "customers", "id"),
			'invoice_id'	=> array("FKEY", "invoice_id", "customer_invoices", "id"),
		)
	),

	"sales_order_detail" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'text'),
			'order_id'		=> array('type'=>'bigint'),
			'product_id'	=> array('type'=>'bigint'),
			'quantity'		=> array('type'=>'numeric'),
			'amount'		=> array('type'=>'numeric'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'order_id'		=> array("INDEX", "order_id", "sales_orders", "id"),
			'product_id'	=> array("INDEX", "product_id", "products", "id"),
		)
	),

	"sales_order_status" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'parent_id'		=> array('type'=>'integer'),
			'name'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
		)
	),

	"security_dacl" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(512)'),
			'inherit_from'	=> array('type'=>'bigint'),
			'inherit_from_old'	=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'name'			=> array("INDEX", "name"),
			'inherit_from'	=> array("INDEX", "inherit_from"),
			'inherit_from_old'	=> array("INDEX", "inherit_from"),
		)
	),

	"security_acle" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'pname'			=> array('type'=>'character varying(128)'),
			'dacl_id'		=> array('type'=>'bigint'),
			'user_id'		=> array('type'=>'bigint'),
			'group_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user'			=> array("INDEX", "user_id", "users", "id"),
			'group'			=> array("INDEX", "group_id", "user_groups", "id"),
			'dacl'			=> array("INDEX", "dacl_id", "security_dacl", "id"),
		)
	),

	"stocks" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'symbol'		=> array('type'=>'character varying(32)'),
			'name'			=> array('type'=>'character varying(256)'),
			'price'			=> array('type'=>'character varying(32)'),
			'price_change'	=> array('type'=>'character varying(32)'),
			'percent_change'=> array('type'=>'character varying(32)'),
			'last_updated'	=> array('type'=>'timestamp without time zone'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'name'			=> array("INDEX", "name"),
			'symbol'		=> array("INDEX", "symbol"),
		)
	),

	"stocks_membership" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'bigint'),
			'stock_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user'			=> array("INDEX", "user_id", "users", "id"),
			'stock'			=> array("INDEX", "stock_id", "stocks", "id"),
		)
	),

	"system_registry" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'subtype'=>'', 'default'=>'auto_increment'),
			'key_name'		=> array('type'=>'character varying(256)'),
			'key_val'		=> array('type'=>'text'),
			'user_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY' => 'id',
		"KEYS" => array(
			'user_id' 		=> array("INDEX", "user_id"),
			'key_name' 		=> array("UNIQUE", array("key_name", "user_id")),
		)
	),

	"user_dashboard_layout" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'subtype'=>'', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'integer'),
			'col'			=> array('type'=>'integer'),
			'position'		=> array('type'=>'integer'),
			'widget_id'		=> array('type'=>'integer'),
			'type'			=> array('type'=>'character varying(32)', 'default'=>'system'),
			'data'			=> array('type'=>'text'),
			'dashboard'		=> array('type'=>'character varying(128)'),
		),
		'PRIMARY_KEY'	=> 'id',
		"KEYS" => array(
			'user_id' 		=> array("INDEX", "user_id", "users", "id"),
			'dashboard' 	=> array("INDEX", "dashboard"),
		)
	),

	/**
	 * User dictionary is used for spell checking
	 */
	"user_dictionary" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'bigint'),
			'word'			=> array('type'=>'character varying(128)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id' 		=> array("INDEX", "user_id", "users", "id"),
			'word' 			=> array("INDEX", "word"),
		)
	),

	"user_teams" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'subtype'=>'', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)', 'notnull'=>true),
			'parent_id'		=> array('type'=>'integer'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY' => 'id',
		"KEYS" => array(
			'parent_id' 	=> array("INDEX", "parent_id"),
		)
	),

	"user_timezones" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'subtype'=>'', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(64)', 'notnull'=>true),
			'code'			=> array('type'=>'character varying(8)', 'notnull'=>true),
			'loc_name'		=> array('type'=>'character varying(64)'),
			'offs'			=> array('type'=>'real'),
		),
		'PRIMARY_KEY' => 'id',
		"KEYS" => array(
			'code'		 	=> array("INDEX", "code"),
		)
	),

	"users" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(128)'),
			'password'		=> array('type'=>'character varying(32)'),
			'full_name'		=> array('type'=>'character varying(256)'),
			'title'			=> array('type'=>'character varying(256)'),
			'last_login'	=> array('type'=>'timestamp with time zone'),
			'theme'			=> array('type'=>'character varying(32)'),
			'timezone'		=> array('type'=>'character varying(64)'),
			'country_code'	=> array('type'=>'character varying(2)'),
			'active'		=> array('type'=>'boolean', "default"=>"true"),
			'phone'			=> array('type'=>'character varying(32)'),
			'checkin_timestamp'	=> array('type'=>'timestamp with time zone'),
			'active_timestamp'	=> array('type'=>'timestamp with time zone'), // this might be the same as above...
			'status_text'	=> array('type'=>'character varying(256)'),
			'quota_size'	=> array('type'=>'bigint'),
			'last_login_from'=> array('type'=>'character varying(32)'),
			'image_id'		=> array('type'=>'bigint'),
			'team_id'		=> array('type'=>'integer'),
			'manager_id'	=> array('type'=>'integer'),
			'customer_number'=> array('type'=>'character varying(128)'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'name_pass_act'	=> array("INDEX", array("name", "password", "active")),
			'name'			=> array("UNIQUE", "name"),
		)
	),

	"user_groups" => array(
		"COLUMNS" => array(
			'id'		=> array('type'=>'integer', 'subtype'=>'', 'default'=>'auto_increment'),
			'name'		=> array('type'=>'character varying(512)', 'notnull'=>true),
			'f_system'	=> array('type'=>'boolean', "default"=>"false"),
			'f_admin'	=> array('type'=>'boolean', "default"=>"false"),
			'commit_id'	=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'	=> 'id',
		"KEYS" => array(
			'name' 	=> array("INDEX", "name"),
		)
	),

	"user_group_mem" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'bigint'),
			'group_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user'			=> array("INDEX", "user_id", "users", "id"),
			'group'			=> array("INDEX", "group_id", "user_groups", "id"),
		)
	),

	"user_notes" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'body'			=> array('type'=>'text'),
			'user_id'		=> array('type'=>'integer'),
			'date_added'	=> array('type'=>'date'),
			'body_type'		=> array('type'=>'character varying(32)'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("FKEY", "owner_id", "users", "id"),
		)
	),

	"user_notes_categories" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'parent_id'		=> array('type'=>'integer'),
			'user_id'		=> array('type'=>'integer'),
			'name'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'parent_id'		=> array("INDEX", "parent_id"),
			'user_id'		=> array("INDEX", "user_id", "users", "id"),
		)
	),

	"user_notes_cat_mem" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'note_id'		=> array('type'=>'integer'),
			'category_id'	=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'note_id'		=> array("INDEX", "note_id"),
			'category_id'	=> array("INDEX", "category_id"),
		)
	),

	"worker_jobs" => array(
		"COLUMNS" => array(
			'job_id'		=> array('type'=>'character varying(512)', 'notnull'=>true),
			'function_name'	=> array('type'=>'character varying(512)'),
			'ts_started'	=> array('type'=>'timestamp with time zone'),
			'ts_completed'	=> array('type'=>'timestamp with time zone'),
			'status_numerator'=> array('type'=>'integer', 'default'=>'-1'),
			'status_denominator'=> array('type'=>'integer', 'default'=>'100'),
			'log'			=> array('type'=>'text'),
			'retval'		=> array('type'=>'bytea'),
		),
		'PRIMARY_KEY'=> 'job_id',
		"KEYS" => array(
		)
	),

	"workflows" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'notes'			=> array('type'=>'text'),
			'object_type'	=> array('type'=>'character varying(256)'),
			'f_on_create'	=> array('type'=>'boolean', "default"=>"false"),
			'f_on_update'	=> array('type'=>'boolean', "default"=>"false"),
			'f_on_delete'	=> array('type'=>'boolean', "default"=>"false"),
			'f_singleton'	=> array('type'=>'boolean', "default"=>"false"),
			'f_allow_manual'=> array('type'=>'boolean', "default"=>"false"),
			'f_active'		=> array('type'=>'boolean', "default"=>"false"),
	        'f_on_daily'    => array('type'=>'boolean', "default"=>"false"),
	        'f_condition_unmet'    => array('type'=>'boolean', "default"=>"false"),
			'f_processed_cond_met'	=> array('type'=>'boolean', "default"=>"false"),

			/*
			 * This column is being depreciated with the V2 version of WorkFlow
			 * and we will be using ts_lastrun below instead.
			 */
	        'ts_on_daily_lastrun'=> array('type'=>'timestamp with time zone'),

			/*
			 * When the workflow was last run. This is particularly useful
			 * for keeping track of 'periodic' workflows that run at an interval
			 * and look for all matching entities.
			 */
			'ts_lastrun'	=> array('type'=>'timestamp with time zone'),
			'uname'         => array('type'=>'character varying(256)'),
			'revision'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'obj_and_active'=> array("INDEX", array("object_type", "f_active")),
			'unique_name'=> array("INDEX", "uname"),
		)
	),

	"workflow_actions" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'when_interval'	=> array('type'=>'smallint'),
			'when_unit'		=> array('type'=>'smallint'),
			'send_email_fid'=> array('type'=>'bigint'),
			'update_field'	=> array('type'=>'character varying(128)'),
			'update_to'		=> array('type'=>'text'),
			'create_object'	=> array('type'=>'character varying(256)'),
			'start_wfid'	=> array('type'=>'integer'),
			'stop_wfid'		=> array('type'=>'integer'),
			'workflow_id'	=> array('type'=>'integer'),
			'type'			=> array('type'=>'smallint'),
            'type_name'		=> array('type'=>'character varying(256)'),
			'parent_action_id'=> array('type'=>'bigint'),
			'parent_action_event'=> array('type'=>'character varying(32)'),
			'uname'         => array('type'=>'character varying(256)'),
			'data'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'workflow_id'	=> array("INDEX", "workflow_id", "workflows", "id"),
			'start_wfid'	=> array("INDEX", "start_wfid", "workflows", "id"),
			'stop_wfid'		=> array("INDEX", "stop_wfid", "workflows", "id"),
			'parent_action_id'=> array("INDEX", "parent_action_id"),
			'unique_name'	=> array("INDEX", "uname"),
		)
	),

	"workflow_action_schedule" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'action_id'		=> array('type'=>'bigint'),
			'ts_execute'	=> array('type'=>'timestamp with time zone'),
			'instance_id'	=> array('type'=>'bigint'),
			'inprogress'	=> array('type'=>'integer', 'default'=>'0'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'ts_execute'	=> array("INDEX", "ts_execute"),
		)
	),

	"workflow_conditions" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'blogic'		=> array('type'=>'character varying(64)'),
			'field_name'	=> array('type'=>'character varying(256)'),
			'operator'		=> array('type'=>'character varying(128)'),
			'cond_value'	=> array('type'=>'text'),
			'workflow_id'	=> array('type'=>'integer'),
			'wf_action_id'	=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'workflow'		=> array("INDEX", "workflow_id", "workflows", "id"),
			'action'		=> array("INDEX", "wf_action_id", "workflow_actions", "id"),
		)
	),

	"workflow_instances" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'object_type_id'=> array('type'=>'integer', 'notnull'=>true),
			'object_type'	=> array('type'=>'character varying(128)'),
			'object_uid'	=> array('type'=>'bigint', 'notnull'=>true),
			'workflow_id'	=> array('type'=>'integer'),
			'ts_started'	=> array('type'=>'timestamp with time zone'),
			'ts_completed'	=> array('type'=>'timestamp with time zone'),
			'f_completed'	=> array('type'=>'boolean', "default"=>"false"),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'object_type_id'=> array("INDEX", "object_type_id", "app_object_types", "id"),
			'object_type'	=> array("INDEX", "object_type"),
			'object_uid'	=> array("INDEX", "object_uid"),
			'workflow'		=> array("INDEX", "workflow_id", "workflows", "id"),
		)
	),

	"workflow_object_values" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'field'			=> array('type'=>'character varying(256)'),
			'value'			=> array('type'=>'text'),
			'f_array'		=> array('type'=>'boolean', "default"=>"false"),
			'parent_id'		=> array('type'=>'integer'),
			'action_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'parent_id'		=> array("INDEX", "parent_id"),
			'action_id'		=> array("INDEX", "action_id", "workflow_actions", "id"),
		)
	),

	"workflow_approvals" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'notes'			=> array('type'=>'text'),
			'workflow_action_id'=> array('type'=>'bigint'),
			'status'		=> array('type'=>'character varying(32)'),
			'requested_by'	=> array('type'=>'integer'),
			'owner_id'		=> array('type'=>'integer'),
			'obj_reference'	=> array('type'=>'character varying(512)'),
			'ts_status_change'=> array('type'=>'timestamp with time zone'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'owner_id'		=> array("FKEY", "owner_id", "users", "id"),
			'requested_by'	=> array("FKEY", "requested_by", "users", "id"),
			'workflow_action_id'=> array("FKEY", "workflow_action_id", "workflow_actions", "id"),
		)
	),

	"xml_feeds" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'user_id'		=> array('type'=>'bigint'),
			'title'			=> array('type'=>'character varying(256)'),
			'sort_by'		=> array('type'=>'character varying(256)'),
			'limit_num'		=> array('type'=>'character varying(8)'),
			'ts_created'	=> array('type'=>'timestamp with time zone'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("FKEY", "user_id", "users", "id"),
		)
	),

	"xml_feed_groups" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'parent_id'		=> array('type'=>'integer'),
			'name'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'parent_id'		=> array("INDEX", "parent_id"),
		)
	),

	"xml_feed_group_mem" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'feed_id'		=> array('type'=>'integer'),
			'group_id'		=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'feed_id'		=> array("INDEX", "feed_id"),
			'group_id'		=> array("INDEX", "group_id"),
		)
	),

	"xml_feed_publish" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'feed_id'		=> array('type'=>'integer'),
			'publish_to'	=> array('type'=>'text'),
			'furl'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'feed_id'		=> array("INDEX", "feed_id", "xml_feeds", "id"),
		)
	),

	"xml_feed_posts" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'time_entered'	=> array('type'=>'timestamp with time zone'),
			'title'			=> array('type'=>'text'),
			'data'			=> array('type'=>'text'),
			'feed_id'		=> array('type'=>'bigint'),
			'f_publish'		=> array('type'=>'boolean', "default"=>"false"),
			'time_expires'	=> array('type'=>'timestamp with time zone'),
			'user_id'		=> array('type'=>'bigint'),
			'ts_updated'	=> array('type'=>'timestamp with time zone'),
			'f_deleted'		=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'integer'),
			'uname'			=> array('type'=>'character varying(256)'),
			'path'			=> array('type'=>'text'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'user_id'		=> array("FKEY", "user_id", "users", "id"),
			'feed_id'		=> array("FKEY", "feed_id", "xml_feeds", "id"),
		)
	),

	"xml_feed_post_categories" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'integer', 'default'=>'auto_increment'),
			'parent_id'		=> array('type'=>'integer'),
			'name'			=> array('type'=>'character varying(128)', 'notnull'=>true),
			'color'			=> array('type'=>'character varying(6)'),
			'sort_order'	=> array('type'=>'smallint', 'default'=>'0'),
			'feed_id'		=> array('type'=>'integer'),
			'commit_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'parent_id'		=> array("INDEX", "parent_id"),
			'feed_id'		=> array("INDEX", "feed_id", "xml_feeds", "id"),
		)
	),

	"xml_feed_post_cat_mem" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'post_id'		=> array('type'=>'bigint'),
			'category_id'	=> array('type'=>'integer'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'post_id'		=> array("INDEX", "post_id", "xml_feed_posts", "id"),
			'category_id'	=> array("INDEX", "category_id", "xml_feed_post_categories", "id"),
		)
	),

	"report_filters" => array(
	    "COLUMNS" => array(
	        'id'            => array('type'=>'bigint', 'default'=>'auto_increment'),
	        'report_id'     => array('type'=>'bigint'),
	        'blogic'        => array("type"=>"character varying(64)", "notnull"=>true),
	        'field_name'    => array("type"=>"character varying(256)", "notnull"=>true),
	        'operator'      => array("type"=>"character varying(128)", "notnull"=>true),
	        'value'         => array("type"=>"text"),
	    ),
	    'PRIMARY_KEY'        => 'id',
	    "KEYS" => array(
	        'report_id'        => array("FKEY", "report_id", "reports", "id"),
	    )
	),

	"report_table_dims" => array(
	    "COLUMNS" => array(
	        'id'            => array('type'=>'bigint', 'default'=>'auto_increment'),
	        'report_id'     => array('type'=>'bigint'),
	        'table_type'    => array("type"=>"character varying(32)", "notnull"=>true),
	        'name'          => array("type"=>"character varying(256)", "notnull"=>true),
	        'sort'          => array("type"=>"character varying(32)", "notnull"=>true),
	        'format'        => array("type"=>"character varying(32)", "notnull"=>true),
	        'f_column'      => array('type'=>'boolean', "default"=>"false"),
	        'f_row'         => array('type'=>'boolean', "default"=>"false"),
	    ),
	    'PRIMARY_KEY'        => 'id',
	    "KEYS" => array(
	        'report_id'        => array("FKEY", "report_id", "reports", "id"),
	    )
	),

	"report_table_measures" => array(
	    "COLUMNS" => array(
	        'id'            => array('type'=>'bigint', 'default'=>'auto_increment'),
	        'report_id'     => array('type'=>'bigint'),
	        'table_type'    => array("type"=>"character varying(32)", "notnull"=>true),
	        'name'          => array("type"=>"character varying(256)", "notnull"=>true),
	        'aggregate'     => array("type"=>"character varying(32)", "notnull"=>true),
	    ),
	    'PRIMARY_KEY'        => 'id',
	    "KEYS" => array(
	        'report_id'        => array("FKEY", "report_id", "reports", "id"),
	    )
	),

	"dashboard" => array(
	    "COLUMNS" => array(
	        'id'            => array('type'=>'bigint', 'default'=>'auto_increment'),
	        'name'          => array('type'=>'character varying(256)'),
	        'description'   => array("type"=>"text"),
	        'scope'         => array("type"=>"character varying(32)", "notnull"=>true),
	        'groups'        => array("type"=>"text"),
	        'owner_id'      => array("type"=>"integer"),
	        'f_deleted'     => array("type"=>"boolean", "default"=>"false"),
	        'path'          => array("type"=>"text"),
	        'payout'        => array("type"=>"text"),
	        'revision'      => array("type"=>"integer"),
	        'uname'         => array('type'=>'character varying(256)'),
	        'ts_entered'    => array('type'=>'timestamp with time zone'),
	        'ts_updated'    => array('type'=>'timestamp with time zone'),
			'dacl'			=> array('type'=>'text'),
	    ),
	    'PRIMARY_KEY'       => 'id',
	    "KEYS" => array(
	        'owner_id'      => array("FKEY", "owner_id", "users", "id"),
	    )
	),

	"dashboard_widgets" => array(
	    "COLUMNS" => array(
	        'id'            => array('type'=>'bigint', 'default'=>'auto_increment'),        
	        'dashboard_id'  => array("type"=>"integer"),
	        'widget_id'     => array("type"=>"integer"),        
	        'widget'         => array('type'=>'character varying(256)'),
	        'col'           => array("type"=>"integer"),
	        'pos'           => array("type"=>"integer"),
	        'data'          => array("type"=>"text"),
	    ),
	    'PRIMARY_KEY'       => 'id',
	    "KEYS" => array(
	        'dashboard_id'  => array("FKEY", "dashboard_id", "dashboard", "id"),
	        'widget'		=> array("INDEX", "widget"),
	    )
	),

	"dataware_olap_cubes" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(512)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'uname'			=> array("INDEX", "name"),
		)
	),

	"dataware_olap_cube_dims" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'type'			=> array('type'=>'character varying(32)'),
			'cube_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'cube'		=> array("FKEY", "cube_id", "dataware_olap_cubes", "id"),
		)
	),

	"dataware_olap_cube_measures" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'name'			=> array('type'=>'character varying(256)'),
			'cube_id'		=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'cube'		=> array("FKEY", "cube_id", "dataware_olap_cubes", "id"),
		)
	),

	/**
	 * Store history of commit heads
	 */
	"object_sync_commit_heads" => array(
		"COLUMNS" => array(
			'type_key'		=> array('type'=>'character varying(256)'),
			'head_commit_id'=> array('type'=>'bigint', 'notnull'=>true),
		),
		'PRIMARY_KEY'		=> 'type_key',
		"KEYS" => array(
		)
	),

	"object_sync_partners" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'pid'			=> array('type'=>'character varying(256)'),
			'owner_id'		=> array('type'=>'integer'),
			'ts_last_sync'	=> array('type'=>'timestamp with time zone'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'owner_id'		=> array("FKEY", "owner_id", "users", "id"),
			'partid'		=> array("INDEX", "pid"),
		)
	),

	"object_sync_partner_collections" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'type'			=> array('type'=>'integer'),
			'partner_id'	=> array('type'=>'integer'),
			'object_type_id'=> array('type'=>'integer'),
			'object_type'	=> array('type'=>'character varying(256)'),
			'field_id'		=> array('type'=>'integer'),
			'field_name'	=> array('type'=>'character varying(256)'),
			'ts_last_sync'	=> array('type'=>'timestamp with time zone'),
			'conditions'	=> array('type'=>'text'),
			'f_initialized'	=> array('type'=>'boolean', "default"=>"false"),
			'revision'		=> array('type'=>'bigint'),
			'last_commit_id'=> array('type'=>'bigint'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'partner_id'	=> array("FKEY", "partner_id", "object_sync_partners", "id"),
			'field_id'		=> array("FKEY", "field_id", "app_object_type_fields", "id"),
			'object_type_id'=> array("FKEY", "object_type_id", "app_object_types", "id"),
		)
	),

	"object_sync_partner_collection_init" => array(
		"COLUMNS" => array(
			'collection_id'	=> array('type'=>'bigint'),
			'parent_id'		=> array('type'=>'bigint'),
			'ts_completed'	=> array('type'=>'timestamp with time zone'),
		),
		"KEYS" => array(
			'collection'	=> array("FKEY", "collection_id", "object_sync_partner_collections", "id"),
			'parent'		=> array("INDEX", "parent_id"),
		)
	),


	"object_sync_stats" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'collection_id'	=> array('type'=>'integer'),
			'object_type_id'=> array('type'=>'integer'),
			'object_id'		=> array('type'=>'bigint'),
			'parent_id'		=> array('type'=>'bigint'),
			'field_id'		=> array('type'=>'integer'),
	        'revision'		=> array('type'=>'integer'),
			'field_name'	=> array('type'=>'character varying(256)'),
			'field_val'		=> array('type'=>'character varying(256)'),
			'action'		=> array('type'=>'character(1)'),
			'ts_entered'	=> array('type'=>'timestamp with time zone'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'collection'	=> array("FKEY", "collection_id", "object_sync_partner_collections", "id"),
			'object_type_id'=> array("FKEY", "object_type_id", "app_object_types", "id"),
			'object'		=> array("INDEX", array("object_type_id", "object_id")),
			'fval'			=> array("INDEX", array("field_id", "field_val")),
			'tsentered'		=> array("INDEX", "ts_entered"),
		)
	),

	"object_sync_import" => array(
		"COLUMNS" => array(
			'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'),
			'collection_id'	=> array('type'=>'bigint'),
			'object_type_id'=> array('type'=>'integer'),
			// Local object id once imported
            'object_id'		=> array('type'=>'bigint'),
            // Revision of the local object
            'revision'		=> array('type'=>'integer'),
            // This field is depricated and should eventually be deleted
			'parent_id'		=> array('type'=>'bigint'),
            // This field is depricated and should eventually be deleted
            'field_id'		=> array('type'=>'integer'),
            // A revision (usually modified epoch) of the remote object
            'remote_revision' => array('type'=>'integer'),
			// The unique id of the remote object we have imported
			'unique_id'		=> array('type'=>'character varying(512)'),
		),
		'PRIMARY_KEY'		=> 'id',
		"KEYS" => array(
			'collection'	=> array("FKEY", "collection_id", "object_sync_partner_collections", "id"),
			'object_type_id'=> array("FKEY", "object_type_id", "app_object_types", "id"),
			'field_id'		=> array("FKEY", "field_id", "app_object_type_fields", "id"),
			'object'		=> array("INDEX", array("object_type_id", "object_id")),
			'unique_id'		=> array("INDEX", array("field_id", "unique_id")),
			'parent_id'		=> array("INDEX", "parent_id"),
		)
	),

	"object_sync_export" => array(
		"COLUMNS" => array(
			'collection_id'	=> array('type'=>'bigint'),
			'collection_type' => array('type'=>'smallint'),
			'commit_id'		=> array('type'=>'bigint'),
			'new_commit_id'	=> array('type'=>'bigint'),
			'unique_id'		=> array('type'=>'bigint'),
		),
		"KEYS" => array(
			'collection'	=> array("FKEY", "collection_id", "object_sync_partner_collections", "id"),
			'collecttionid'	=> array("INDEX", "collection_id"),
			'unique_id'		=> array("INDEX", "unique_id"),
			'new_commit_id'	=> array("INDEX", "new_commit_id", "new_commit_id IS NOT NULL"),
			'commituni'		=> array("INDEX", array("collection_type", "commit_id")),
			'newcommituni'	=> array("INDEX", array("collection_type", "new_commit_id")),
		)
	),
);
