-- Add forward param to email account
ALTER TABLE email_accounts ADD COLUMN forward CHARACTER VARYING(256);