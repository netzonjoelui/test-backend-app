-- cap the number of activties and notifications we can store
UPDATE app_object_types SET capped='1000000' WHERE name='activity';
UPDATE app_object_types SET capped='200000' WHERE name='notification';
