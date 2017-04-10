ALTER TABLE email_mailboxes ADD COLUMN type character varying(16);

-- This contains the actual script to connect to mailbox e.g. [Gmail]/Drafts
ALTER TABLE email_mailboxes ADD COLUMN mailbox character varying(128); 