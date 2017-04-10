-- Create Report Filters Table
CREATE TABLE report_filters
(
  id serial NOT NULL,
  report_id integer,
  blogic character varying(64),
  field_name character varying(256),
  operator character varying(128),
  value text,
  CONSTRAINT report_filters_pkey PRIMARY KEY (id ),
  CONSTRAINT report_id_fkey FOREIGN KEY (report_id)
      REFERENCES reports (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);

CREATE INDEX fki_report_id_fkey
  ON report_filters
  USING btree
  (report_id );

-- Create Report Dims Table
CREATE TABLE report_table_dims
(
  id serial NOT NULL,
  report_id integer,
  table_type character varying(32),
  name character varying(128),
  sort character varying(256),
  format character varying(128),
  f_column boolean DEFAULT false,
  f_row boolean DEFAULT false,
  CONSTRAINT report_table_dims_pkey PRIMARY KEY (id ),
  CONSTRAINT reports_table_dims_fkey FOREIGN KEY (report_id)
      REFERENCES reports (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);

CREATE INDEX fki_reports_table_dims_fkey
  ON report_table_dims
  USING btree
  (report_id );

-- Create Report Measure Table
CREATE TABLE report_table_measures
(
  id serial NOT NULL,
  report_id integer,
  table_type character varying(32),
  name character varying(128),
  aggregate character varying(256),
  CONSTRAINT report_table_measures_pkey PRIMARY KEY (id ),
  CONSTRAINT report_table_measures_fkey FOREIGN KEY (report_id)
      REFERENCES reports (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);

CREATE INDEX fki_report_table_measures_fkey
  ON report_table_measures
  USING btree
  (report_id );
  
-- Alter Report Columns

-- NOTE: Below was commented out because the object code will automatically update from the odef

-- ALTER TABLE reports ADD COLUMN f_row_totals boolean;
-- ALTER TABLE reports ALTER COLUMN f_row_totals SET DEFAULT false;

-- ALTER TABLE reports ADD COLUMN f_column_totals boolean;
-- ALTER TABLE reports ALTER COLUMN f_column_totals SET DEFAULT false;

-- ALTER TABLE reports ADD COLUMN f_sub_totals boolean;
-- ALTER TABLE reports ALTER COLUMN f_sub_totals SET DEFAULT false;
