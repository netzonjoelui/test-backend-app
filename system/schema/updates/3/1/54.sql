-- Add cms objects
insert into app_object_types(name, title, object_table, revision, f_system)
	values('cms_page', 'Page', null, '0', 't');
insert into app_object_types(name, title, object_table, revision, f_system)
	values('cms_page_template', 'Page Template', null, '0', 't');
insert into app_object_types(name, title, object_table, revision, f_system)
	values('cms_snippet', 'Snippet', null, '0', 't');
