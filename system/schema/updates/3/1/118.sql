-- Clean old revision data
DELETE FROM object_revisions WHERE object_type_id in (
	SELECT id FROM app_object_types WHERE name='email_message' OR name='email_thread' OR name='activity' OR name='comment'
);
