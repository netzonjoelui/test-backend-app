ALTER TABLE email_accounts ADD COLUMN signature text;

update app_object_types set object_table='dashboard' where name = 'dashboard';

CREATE TABLE dashboard
(
  id serial NOT NULL,    
  name character varying(256),
  description text,
  scope character varying(32),  
  groups integer,  
  owner_id integer,
  f_deleted boolean DEFAULT false,
  path text,
  revision integer,
  uname character varying(256),
  ts_entered timestamp with time zone,  
  ts_updated timestamp with time zone,    
  CONSTRAINT dashboard_pkey PRIMARY KEY (id ),
  CONSTRAINT owner_id_fkey FOREIGN KEY (owner_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);
