-- Create some indexes
CREATE INDEX calendar_events_uid_idx
   ON calendar_events (user_id ASC NULLS LAST);

CREATE INDEX contacts_personal_uid_idx
   ON contacts_personal (user_id ASC NULLS LAST);

CREATE INDEX user_notes_uid_idx
   ON user_notes (user_id ASC NULLS LAST);

CREATE INDEX projects_datecom_idx
   ON projects (date_completed ASC NULLS LAST);

CREATE INDEX project_membership_idx
   ON project_membership (user_id ASC NULLS LAST, project_id ASC NULLS LAST);

CREATE INDEX project_tasks_uid_idx
   ON project_tasks (user_id ASC NULLS LAST);

CREATE INDEX project_tasks_proj_idx
   ON project_tasks (project ASC NULLS LAST);

CREATE INDEX project_tasks_deadline_idx
   ON project_tasks (deadline ASC NULLS LAST);

CREATE INDEX project_tasks_date_enteredj_idx
   ON project_tasks (date_entered ASC NULLS LAST);

CREATE INDEX email_threads_timdelivered_idx
   ON email_threads (ts_delivered ASC NULLS LAST);

-- Remove constraints
ALTER TABLE email_message_original DROP CONSTRAINT email_message_original_mid_fkey;
ALTER TABLE email_message_original DROP CONSTRAINT email_message_original_fid_fkey;

