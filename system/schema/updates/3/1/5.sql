-- Create indexes
CREATE INDEX email_threads_owner_idx
   ON email_threads (owner_id ASC NULLS LAST);

CREATE INDEX email_threads_timeup_idx
   ON email_threads (time_updated ASC NULLS LAST);

CREATE INDEX email_message_owner_idx
   ON email_messages (owner_id ASC NULLS LAST);

CREATE INDEX email_message_deldate_idx
   ON email_messages (message_date ASC NULLS LAST);

CREATE INDEX activity_owner_idx
   ON activity (user_id ASC NULLS LAST);

CREATE INDEX activity_tsentered_idx
   ON activity (ts_entered ASC NULLS LAST);

