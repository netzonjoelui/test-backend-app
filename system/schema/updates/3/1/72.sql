CREATE TABLE object_sync_partners
(
  id serial NOT NULL,
  pid character varying(256),
  owner_id bigint,
  ts_last_sync timestamp with time zone,
  CONSTRAINT object_sync_partners_pkey PRIMARY KEY (id ),
  CONSTRAINT oobject_sync_partners_pid_uni UNIQUE (pid)
);

CREATE INDEX object_sync_partners_pid_idx
  ON object_sync_partners
  USING btree
  (pid);


CREATE TABLE object_sync_partner_collections
(
  id serial NOT NULL,
  partner_id integer,
  object_type_id integer,
  object_type character varying(256),
  field_id integer,
  field_name character varying(256),
  ts_last_sync timestamp with time zone,
  conditions text,
  CONSTRAINT object_sync_partner_collections_pkey PRIMARY KEY (id ),
  CONSTRAINT object_sync_partner_collections_field_fkey FOREIGN KEY (field_id)
      REFERENCES app_object_type_fields (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT object_sync_partner_collections_otid_fkey FOREIGN KEY (object_type_id)
      REFERENCES app_object_types (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT object_sync_partner_collections_partner_fkey FOREIGN KEY (partner_id)
      REFERENCES object_sync_partners (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX object_sync_partner_collections_fld_idx
  ON object_sync_partner_collections
  USING btree
  (field_id );

CREATE INDEX object_sync_partner_collections_pid_idx
  ON object_sync_partner_collections
  USING btree
  (partner_id );

-- Add collection id column to stats and import tables
ALTER TABLE object_sync_stats ADD COLUMN collection_id integer;
ALTER TABLE object_sync_import ADD COLUMN collection_id integer;

CREATE INDEX object_sync_stats_collection_idx
  ON object_sync_stats
  USING btree
  (collection_id);

CREATE INDEX object_sync_import_collection_idx
  ON object_sync_import
  USING btree
  (collection_id);

-- Add timestamp to stat table
ALTER TABLE object_sync_stats ADD COLUMN ts_entered timestamp with time zone;

-- Add initialized
ALTER TABLE object_sync_partner_collections ADD COLUMN f_initialized boolean;

-- Add parent to import
ALTER TABLE object_sync_import ADD COLUMN parent_id bigint;

CREATE INDEX object_sync_import_parent_idx
   ON object_sync_import (parent_id ASC NULLS LAST);

