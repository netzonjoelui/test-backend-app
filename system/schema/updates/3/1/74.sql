-- Add index to time enterd for sync
CREATE INDEX object_sync_stats_tsentered_idx
   ON object_sync_stats (ts_entered ASC NULLS LAST);
