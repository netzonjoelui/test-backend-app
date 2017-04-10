-- Add local path to user files
ALTER TABLE user_files ADD COLUMN local_path character varying(512);
