-- Add missing foreign key constraints for automatic cleanup
DELETE FROm object_sync_stats WHERE collection_id NOT IN (select id from object_sync_partner_collections);
ALTER TABLE object_sync_stats ADD CONSTRAINT object_sync_stats_collection_fkey 
	FOREIGN KEY (collection_id) REFERENCES object_sync_partner_collections (id) ON UPDATE CASCADE ON DELETE CASCADE;

-- Add missing foreign key constraints for automatic cleanup
DELETE FROm object_sync_import WHERE collection_id NOT IN (select id from object_sync_partner_collections);
ALTER TABLE object_sync_import ADD CONSTRAINT object_sync_import_collection_fkey 
	FOREIGN KEY (collection_id) REFERENCES object_sync_partner_collections (id) ON UPDATE CASCADE ON DELETE CASCADE;
