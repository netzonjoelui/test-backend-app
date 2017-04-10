-- add object grouping tables and indexes

CREATE TABLE object_groupings
(
  id bigserial NOT NULL,
  name character varying(256),
  parent_id bigint,
  color character varying(6),
  sort_order smallint,
  f_system boolean DEFAULT false,
  f_closed boolean DEFAULT false,
  object_type_id integer,
  user_id integer,
  CONSTRAINT object_groupings_pkey PRIMARY KEY (id )
);

-- Index: object_groupings_otyp_idx
CREATE INDEX object_groupings_otyp_idx
  ON object_groupings
  USING btree
  (object_type_id );

-- Index: object_groupings_uid_idx
CREATE INDEX object_groupings_uid_idx
  ON object_groupings
  USING btree
  (user_id );


CREATE TABLE object_grouping_mem
(
  id bigserial NOT NULL,
  object_type_id integer NOT NULL,
  object_id bigint NOT NULL,
  grouping_id bigint NOT NULL,
  CONSTRAINT object_grouping_mem_pkey PRIMARY KEY (id )
);

-- Index: object_grouping_mem_grp_idx
CREATE INDEX object_grouping_mem_grp_idx
  ON object_grouping_mem
  USING btree
  (grouping_id );

-- Index: object_grouping_mem_obj_idx
CREATE INDEX object_grouping_mem_obj_idx
  ON object_grouping_mem
  USING btree
  (object_type_id , object_id );
