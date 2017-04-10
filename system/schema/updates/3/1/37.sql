-- add indexed flag to the object fields meta-data to preparation for dynamic field indexes
ALTER TABLE app_object_type_fields ADD COLUMN f_indexed boolean DEFAULT false;

-- Flag already indexed fields
update app_object_type_fields set f_indexed='t' where name='id' or name='uname';
