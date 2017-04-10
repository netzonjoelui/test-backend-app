-- Make some changes to the dashboard classes
ALTER TABLE dashboard_widgets ADD COLUMN widget character varying(256);

CREATE INDEX dashboard_widgets_dash_idx
   ON dashboard_widgets (dashboard_id ASC NULLS LAST);

CREATE INDEX dashboard_widgets_widget_idx
   ON dashboard_widgets (widget);

-- Add layout, not needed, object def will add
--ALTER TABLE dashboard ADD COLUMN layout text;
