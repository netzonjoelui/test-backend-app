--
-- Create tables for object sync
--

CREATE TABLE object_sync_devices
(
  id serial NOT NULL,
  dev_id character varying(256),
  owner_id bigint,
  ts_last_sync timestamp with time zone,
  CONSTRAINT object_sync_devices_pkey PRIMARY KEY (id ),
  CONSTRAINT object_sync_devices_devid_uni UNIQUE (dev_id )
);

CREATE INDEX object_sync_devices_dev_idx
  ON object_sync_devices
  USING btree
  (dev_id);

CREATE TABLE object_sync_device_entities
(
  id serial NOT NULL,
  device_id integer,
  object_type_id integer,
  field_id integer,
  ts_last_sync timestamp with time zone,
  object_type character varying(256),
  field_name character varying(256),
  CONSTRAINT object_sync_device_entities_pkey PRIMARY KEY (id ),
  CONSTRAINT object_sync_device_entities_device_fkey FOREIGN KEY (device_id)
      REFERENCES object_sync_devices (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT object_sync_device_entities_field_fkey FOREIGN KEY (field_id)
      REFERENCES app_object_type_fields (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT object_sync_device_entities_otid_fkey FOREIGN KEY (object_type_id)
      REFERENCES app_object_types (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX object_sync_device_entities_dev_idx
  ON object_sync_device_entities
  USING btree
  (device_id );

CREATE INDEX object_sync_device_entities_fld_idx
  ON object_sync_device_entities
  USING btree
  (field_id );

CREATE TABLE object_sync_stats
(
  device_id integer,
  object_type_id integer,
  object_id bigint,
  action character(1),
  revision integer,
  field_id integer,
  field_name character varying(256),
  field_val character varying(256),
  CONSTRAINT object_sync_stats_device_fkey FOREIGN KEY (device_id)
      REFERENCES object_sync_devices (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT object_sync_stats_field_fkey FOREIGN KEY (field_id)
      REFERENCES app_object_type_fields (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT object_sync_stats_object_type_id_fkey FOREIGN KEY (object_type_id)
      REFERENCES app_object_types (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX object_sync_stats_device_idx
  ON object_sync_stats
  USING btree
  (device_id );

CREATE INDEX object_sync_stats_fval_idx
  ON object_sync_stats
  USING btree
  (field_id , field_val);

CREATE INDEX object_sync_stats_obj_idx
  ON object_sync_stats
  USING btree
  (object_type_id, object_id);

