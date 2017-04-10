-- delete all task categories without a grouping id
delete from object_groupings where object_type_id=(select id from app_object_types where name='task') and user_id is null;
