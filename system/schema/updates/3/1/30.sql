-- update all old user_file_categories references in fields
update app_object_type_fields set type='object', subtype='folder' where type='fkey' and subtype='user_file_categories';
