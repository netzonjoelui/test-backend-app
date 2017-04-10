-- Remove this constraint if it exists because we are moving to the new AntFs for attachments
ALTER TABLE email_message_attachments DROP CONSTRAINT IF EXISTS email_message_attachments_fid_fkey;
