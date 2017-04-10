CREATE TABLE reminders
(
   id bigserial, 
   obj_reference character varying(256), 
   "interval" smallint, 
   interval_unit character varying(32), 
   field_name character varying(256), 
   ts_execute timestamp with time zone, 
   user_id integer, 
   f_executed boolean, 
   send_to text, 
   notes text, 
   action_type character varying(32), 
   CONSTRAINT reminders_pkey PRIMARY KEY (id)
);

CREATE INDEX reminders_exectute_idx
   ON reminders (ts_execute ASC NULLS LAST);
