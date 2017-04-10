-- Create index on email_thread body
update email_threads set body=substring(body, 1, 10000) where char_length(body) > 500000;
--CREATE INDEX email_threads_body_idx
--  ON email_threads
--  USING gin
--  (to_tsvector('english'::regconfig, body) );

-- Create index on email_messages body
update email_messages set body=substring(body, 1, 10000) where char_length(body) > 500000;
CREATE INDEX email_messages_body_idx
  ON email_messages
  USING gin
  (to_tsvector('english'::regconfig, body) );

-- Add capped column for object types
ALTER TABLE app_object_types ADD COLUMN capped integer;

-- Cap activities to 1m rows
UPDATE app_object_types SET capped='1000000' WHERE name='activity';

-- Add activity archive partition
--CREATE TABLE activity_del
--(
   --CONSTRAINT activity_del_pkey PRIMARY KEY (id)
--) 
--INHERITS (activity);

--INSERT INTO activity_del
--SELECT * FROM activity WHERE f_deleted='t';

--DELETE FROM ONLY activity WHERE f_deleted='t';

--ALTER TABLE activity_del ADD CONSTRAINT activity_del_chk CHECK (f_deleted='t');
