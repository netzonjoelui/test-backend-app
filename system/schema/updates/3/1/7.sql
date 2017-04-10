-- more indexes
CREATE INDEX calendar_events_tsstart_idx
   ON calendar_events (ts_start ASC NULLS LAST);

CREATE INDEX calendar_events_tsend_idx
   ON calendar_events (ts_end ASC NULLS LAST);

CREATE INDEX calendar_events_ts_updated_idx
   ON calendar_events (ts_updated ASC NULLS LAST);

CREATE INDEX contacts_personal_date_changed_idx
   ON contacts_personal (date_changed ASC NULLS LAST);
