-- Add import sync table to be used for tracking imported changes from devices that don't send
-- incremental updates but rather supply only the 'current' listing of paths or objects
CREATE TABLE object_sync_import
(
  id serial NOT NULL,
  object_type_id integer,
  object_id bigint,
  field_id integer,
  unique_id character varying(512),
  device_id integer,
  CONSTRAINT object_sync_import_pkey PRIMARY KEY (id ),
  CONSTRAINT object_sync_import_dev_fkey FOREIGN KEY (device_id)
      REFERENCES object_sync_devices (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT object_sync_import_fid_fkey FOREIGN KEY (field_id)
      REFERENCES app_object_type_fields (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT object_sync_import_otid_fkey FOREIGN KEY (object_type_id)
      REFERENCES app_object_types (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

-- Index: object_sync_import_dev_idx
CREATE INDEX object_sync_import_dev_idx
  ON object_sync_import
  USING btree
  (device_id );

-- Index: object_sync_import_fld_idx
CREATE INDEX object_sync_import_fld_idx
  ON object_sync_import
  USING btree
  (field_id );

-- Index: object_sync_import_obj_idx
CREATE INDEX object_sync_import_obj_idx
  ON object_sync_import
  USING btree
  (object_type_id , object_id );

-- Index: object_sync_import_object_idx
CREATE INDEX object_sync_import_object_idx
  ON object_sync_import
  USING btree
  (object_type_id );

-- Index: object_sync_import_uid_idx
CREATE INDEX object_sync_import_uid_idx
  ON object_sync_import
  USING btree
  (field_id , unique_id);
