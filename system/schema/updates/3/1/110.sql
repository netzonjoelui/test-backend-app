CREATE TABLE object_sync_partner_collection_init
(
  collection_id bigint,
  parent_id bigint DEFAULT 0,
  ts_completed timestamp with time zone,
  CONSTRAINT object_sync_partner_collection_init_pid_fkey FOREIGN KEY (parent_id)
      REFERENCES object_sync_partners (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX object_sync_partner_collection_init_idx
  ON object_sync_partner_collection_init
  USING btree
  (collection_id , parent_id );
