-- New Columns for IMAP Authentication
ALTER TABLE email_accounts ADD COLUMN port character varying(8);
ALTER TABLE email_accounts ADD COLUMN ssl character varying(8);
