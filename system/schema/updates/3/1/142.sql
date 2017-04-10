-- Add columns to store conditions, fields, and order by
ALTER TABLE app_object_views ADD COLUMN conditions_data text;
ALTER TABLE app_object_views ADD COLUMN order_by_data text;
ALTER TABLE app_object_views ADD COLUMN table_columns_data text;