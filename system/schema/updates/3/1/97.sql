-- Cap notifications to 200k rows
UPDATE app_object_types SET capped='200000' WHERE name='notification';
