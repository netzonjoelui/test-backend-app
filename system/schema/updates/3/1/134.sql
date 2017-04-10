-- Add export log table to use as a historical reference for flagging previously exported
-- objects as moved or deleted without having collections replay all changes throughout the
-- entire system to find moved/deleted objects outside the conditions of a specific collection.
CREATE TABLE object_sync_export
(
  collection_id bigint,
  collection_type smallint,
  commit_id bigint,
  new_commit_id integer,
  unique_id bigint,
  CONSTRAINT object_sync_export_colid_fkey FOREIGN KEY (collection_id)
      REFERENCES object_sync_partner_collections (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

-- Index: collection_id is queried for every sync
CREATE INDEX object_sync_export_collection_idx
  ON object_sync_export
  USING btree
  (collection_id );

-- Index: unique_id is often merged with collection_id
CREATE INDEX object_sync_export_uid_idx
  ON object_sync_export
  USING btree
  (unique_id );

-- Index: collections search for stale exports where new_commit_id is not null
CREATE INDEX object_sync_export_newcommitnotnull_idx
  ON object_sync_export
  USING btree
  (new_commit_id)
  WHERE new_commit_id IS NOT NULL;

-- Index: used for cross-collection updates of commit_id
CREATE INDEX object_sync_export_commituni_idx
  ON object_sync_export
  USING btree
  (collection_type, commit_id);

-- Index: used for cross-collection updates of new_commit_id
CREATE INDEX object_sync_export_newcommituni_idx
  ON object_sync_export
  USING btree
  (collection_type, new_commit_id);