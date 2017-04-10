-- index for owner_id
CREATE INDEX objects_email_message_act_owner_idx
  ON objects_email_message_act
  USING btree
  (owner_id);

CREATE INDEX objects_email_message_del_owner_idx
  ON objects_email_message_del
  USING btree
  (owner_id);

update app_object_type_fields set f_indexed='t' where name='owner_id' and type_id=(select id from app_object_types where name='email_message');

-- index for mailbox
CREATE INDEX objects_email_message_act_mailbox_idx
  ON objects_email_message_act
  USING btree
  (mailbox_id);

CREATE INDEX objects_email_message_del_mailbox_idx
  ON objects_email_message_del
  USING btree
  (mailbox_id);

update app_object_type_fields set f_indexed='t' where name='mailbox_id' and type_id=(select id from app_object_types where name='email_message');

-- index for thread
CREATE INDEX objects_email_message_act_thread_idx
  ON objects_email_message_act
  USING btree
  (thread);

CREATE INDEX objects_email_message_del_thread_idx
  ON objects_email_message_del
  USING btree
  (thread);

update app_object_type_fields set f_indexed='t' where name='thread' and type_id=(select id from app_object_types where name='email_message');

-- index for message date - used mostly for sorting
CREATE INDEX objects_email_message_act_message_date_idx
  ON objects_email_message_act
  USING btree
  (message_date);

CREATE INDEX objects_email_message_del_message_date_idx
  ON objects_email_message_del
  USING btree
  (message_date);

update app_object_type_fields set f_indexed='t' where name='message_date' and type_id=(select id from app_object_types where name='email_message');
