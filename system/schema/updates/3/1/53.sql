-- index the time entered 
CREATE INDEX objects_activity_act_tse_idx
   ON objects_activity_act (ts_entered ASC NULLS LAST);
CREATE INDEX objects_activity_del_tse_idx
   ON objects_activity_del (ts_entered ASC NULLS LAST);

-- Update object definiton indicating that the fields have been indexed
update app_object_type_fields set f_indexed='t' where name='ts_entered' and type_id=(select id from app_object_types where name='activity');
