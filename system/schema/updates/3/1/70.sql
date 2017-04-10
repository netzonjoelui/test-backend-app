-- Remove the current group column that has type integer
ALTER TABLE dashboard DROP COLUMN groups;

-- Add a new groups column with type text
ALTER TABLE dashboard ADD COLUMN groups text;