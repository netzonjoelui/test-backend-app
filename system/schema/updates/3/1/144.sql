-- Add type name and data to actions
ALTER TABLE
  workflow_actions
ADD COLUMN
  type_name CHARACTER VARYING(32);

ALTER TABLE
  workflow_actions
ADD COLUMN
  data TEXT;