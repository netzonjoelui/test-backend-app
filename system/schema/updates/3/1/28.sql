-- update all old user_files references in fields
update app_object_type_fields set type='object', subtype='file' where type='fkey' and subtype='user_files';
