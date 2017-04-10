-- cleanup partner collections
ALTER TABLE object_sync_partner_collection_init DROP CONSTRAINT IF EXISTS object_sync_partner_collection_init_pid_fkey;

DELETE FROM object_sync_partner_collection_init WHERE collection_id not in (select id from object_sync_partner_collections);
ALTER TABLE object_sync_partner_collection_init ADD CONSTRAINT object_sync_partner_collection_init_pid_fkey FOREIGN KEY (collection_id)
      REFERENCES object_sync_partner_collections (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
