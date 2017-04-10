-- Add last sync values to accounts
ALTER TABLE email_accounts ADD COLUMN ts_last_full_sync integer;
