-- moving to objects table
DROP TABLE reminders;

-- Add object type
INSERT INTO app_object_types(name, title, object_table, revision, f_system)
	VALUES('reminder', 'Reminder', null, '0', 't');
