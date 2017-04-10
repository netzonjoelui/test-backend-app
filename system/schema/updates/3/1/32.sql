-- Need to create scope table so we can determine if the user_id is set as user
ALTER TABLE app_object_views ADD COLUMN scope character varying(16);