CREATE TABLE dashboard_widgets
(
  id serial NOT NULL,    
  dashboard_id integer,
  widget_id integer,
  col integer,
  pos integer,  
  data text,  
  CONSTRAINT dashboard_widgets_pkey PRIMARY KEY (id ),
  CONSTRAINT dashboard_id_fkey FOREIGN KEY (dashboard_id)
      REFERENCES dashboard (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT widget_id_fkey FOREIGN KEY (widget_id)
      REFERENCES app_widgets (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);