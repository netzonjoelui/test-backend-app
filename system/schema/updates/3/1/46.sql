-- index the time delivered
CREATE INDEX objects_email_thread_act_tsd_idx
   ON objects_email_thread_act (ts_delivered ASC NULLS LAST);
CREATE INDEX objects_email_thread_del_tsd_idx
   ON objects_email_thread_del (ts_delivered ASC NULLS LAST);

-- index the owner_id of the email_thread
CREATE INDEX objects_email_thread_act_oid_idx
   ON objects_email_thread_act (owner_id ASC NULLS LAST);
CREATE INDEX objects_email_thread_del_oid_idx
   ON objects_email_thread_del (owner_id ASC NULLS LAST);

-- Update object definiton indicating that the fields have been indexed
update app_object_type_fields set f_indexed='t' where name='ts_delivered' and name='email_thread';
update app_object_type_fields set f_indexed='t' where name='owner_id' and name='email_thread';

