-- Needs revision and uname for editing existing workflow in always/workflow.php
ALTER TABLE workflows ADD COLUMN revision integer;
ALTER TABLE workflows ADD COLUMN uname character varying(256);
ALTER TABLE workflow_actions ADD COLUMN uname character varying(256);
