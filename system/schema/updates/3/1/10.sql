-- created generic objects tables
CREATE TABLE objects
(
  id bigserial NOT NULL,
  revision integer,
  ts_entered timestamp with time zone,
  ts_updated timestamp with time zone,
  owner_id bigint,
  creator_id bigint,
  f_deleted boolean DEFAULT false,
  uname character varying(256),
  path text,
  object_type_id integer
);

-- Index used to track if an object was moved or merged to another object id
CREATE TABLE objects_moved
(
   object_type_id integer, 
   object_id bigint, 
   moved_to bigint, 
   CONSTRAINT objects_moved_pkey PRIMARY KEY (object_type_id, object_id)
);

CREATE INDEX objects_moved_to_idx
  ON objects_moved
  USING btree
  (object_type_id ASC NULLS LAST, moved_to ASC NULLS LAST);
