-- Add index for activity level
CREATE INDEX objects_activity_act_level_idx
   ON objects_activity_act (level ASC NULLS LAST);
CREATE INDEX objects_activity_del_level_idx
   ON objects_activity_del (level ASC NULLS LAST);

-- Update object definiton indicating that the fields have been indexed
UPDATE app_object_type_fields SET f_indexed='t' WHERE name='level' AND type_id=(SELECT id FROM app_object_types WHERE name='activity');
