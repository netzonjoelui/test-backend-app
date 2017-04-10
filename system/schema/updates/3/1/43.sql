-- Need to add new column for imap email message id and will be used for sync
ALTER TABLE email_messages ADD COLUMN message_uid integer;