-- Add system field to email accounts
ALTER TABLE email_accounts ADD COLUMN f_system boolean DEFAULT false;
-- Update existing
UPDATE email_accounts SET f_system='f';
-- Update existing system to true
UPDATE email_accounts SET f_system='t' WHERE type is not NULL and type!='';
-- Add outgoing auth option for telling the system to send username and password
ALTER TABLE email_accounts ADD COLUMN f_outgoing_auth boolean DEFAULT false;
UPDATE email_accounts SET f_outgoing_auth='f';
