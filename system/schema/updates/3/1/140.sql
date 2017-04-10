-- Purge import stats since we lost email store
DELETE FROM
  object_sync_import
WHERE
  collection_id IN (
    SELECT id FROM object_sync_partner_collections WHERE partner_id IN (
      SELECT id FROM object_sync_partners WHERE pid LIKE 'EmailAccounts/%'
    )
  );