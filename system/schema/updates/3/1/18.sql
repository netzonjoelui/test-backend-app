-- Add folder and file objects
insert into app_object_types(name, title, object_table, revision, f_system)
	values('folder', 'Folder', null, '0', 't');

insert into app_object_types(name, title, object_table, revision, f_system)
	values('file', 'File', null, '0', 't');
