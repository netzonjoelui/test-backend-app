-- Unpublish reports for now
UPDATE applications SET scope='draft' WHERE name='reports';
