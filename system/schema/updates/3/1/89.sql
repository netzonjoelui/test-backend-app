ALTER TABLE applications ADD COLUMN settings character varying(128);

-- set email 
UPDATE applications SET settings='Plugin_Email_Settings' WHERE name='email';
-- set home to none
UPDATE applications SET settings='none' WHERE name='home';
