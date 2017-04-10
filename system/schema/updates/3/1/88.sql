UPDATE app_object_type_fields SET type='object', subtype='file' WHERE type='fkey' AND subtype='user_files';
UPDATE app_object_type_fields SET type='object', subtype='folder' WHERE type='fkey' AND subtype='user_file_categories';
