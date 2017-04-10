-- alter object groupings table to add field_id
ALTER TABLE object_groupings ADD COLUMN field_id integer;

-- Add index
CREATE INDEX object_groupings_field_idx
  ON object_groupings
  USING btree
  (field_id );

CREATE INDEX object_groupings_parent_idx
  ON object_groupings
  USING btree
  (parent_id );

