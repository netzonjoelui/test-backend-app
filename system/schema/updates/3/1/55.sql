ALTER TABLE email_messages DROP COLUMN message_uid;
ALTER TABLE email_messages ADD COLUMN message_uid character varying(128);