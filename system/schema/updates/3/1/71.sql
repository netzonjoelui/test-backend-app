-- Add revision to import table
ALTER TABLE object_sync_import ADD COLUMN revision integer;

-- Add parent id to stats for heiarchy
ALTER TABLE object_sync_stats ADD COLUMN parent_id bigint;

-- Add parent_id index
CREATE INDEX object_sync_stats_pid_idx
   ON object_sync_stats (parent_id ASC NULLS LAST);
