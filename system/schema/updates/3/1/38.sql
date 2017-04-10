-- Need to set the dashboard object to use 'dashboard' table
update app_object_types set object_table = name where name = 'dashboard'