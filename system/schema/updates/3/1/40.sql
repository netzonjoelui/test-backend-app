-- New Columns for IMAP Authentication
ALTER TABLE email_accounts ADD COLUMN type character varying(16);
ALTER TABLE email_accounts ADD COLUMN username character varying(128);
ALTER TABLE email_accounts ADD COLUMN password character varying(128);
ALTER TABLE email_accounts ADD COLUMN host character varying(128);
