CREATE INDEX object_sync_partner_collections_otid_idx
  ON object_sync_partner_collections
  USING btree
  (object_type_id );
