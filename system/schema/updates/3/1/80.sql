-- index for mesage id
CREATE INDEX objects_email_message_attachment_act_mid_idx
  ON objects_email_message_attachment_act
  USING btree
  (message_id);

CREATE INDEX objects_email_message_attachment_del_mid_idx
  ON objects_email_message_attachment_del
  USING btree
  (message_id);

update app_object_type_fields set f_indexed='t' where name='message_id' and 
	type_id=(select id from app_object_types where name='email_message_attachment');

-- index for content_type
CREATE INDEX objects_email_message_attachment_act_ctype_idx
  ON objects_email_message_attachment_act
  USING btree
  (content_type);

CREATE INDEX objects_email_message_attachment_del_ctype_idx
  ON objects_email_message_attachment_del
  USING btree
  (content_type);

update app_object_type_fields set f_indexed='t' where name='content_type' and 
	type_id=(select id from app_object_types where name='email_message_attachment');
