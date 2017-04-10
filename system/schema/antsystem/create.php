<?php
/**
 * This file is included from /lib/Ant.php and used in Ant::sysSchemaCreate
 *
 * It is imeperative thay any and ALL schema changes be entered both here
 * and in the schema updates. This is applied only for new accounts while
 * updates are used to bring old schema versions current to match this file.
 */

/**
 * Global variable is required. Must be incremented with every change to keep
 * scripts in ./updates.php from running if not needed
 */
$sys_schema_version = "1.9";

$queries[] = "CREATE TABLE accounts
				(
				  id serial NOT NULL,
				  name character varying(256),
				  database character varying(128),
				  login_image character varying(1024),
				  f_use_ans boolean DEFAULT true,
				  ts_started timestamp without time zone,
				  server character varying(256),
				  customer_number integer,
				  billing_customer_number integer,
				  version text,
				  CONSTRAINT accounts_pkey PRIMARY KEY (id ),
				  CONSTRAINT accounts_aname_uni UNIQUE (name )
				)";

$queries[] = "CREATE TABLE email_alias
				(
				  address character varying(128) NOT NULL,
				  goto text,
				  active boolean DEFAULT true,
				  account_id integer,
				  CONSTRAINT emial_alias_pkey PRIMARY KEY (address ),
				  CONSTRAINT email_alias_aid_fkey FOREIGN KEY (account_id)
					  REFERENCES accounts (id) MATCH SIMPLE
					  ON UPDATE CASCADE ON DELETE CASCADE
				)";

$queries[] = "CREATE TABLE email_domains
				(
				  domain character varying(128) NOT NULL,
				  description character varying(128),
				  active boolean DEFAULT true,
				  account_id integer,
				  CONSTRAINT email_domains_pkey PRIMARY KEY (domain ),
				  CONSTRAINT email_domains_aid_fkey FOREIGN KEY (account_id)
					  REFERENCES accounts (id) MATCH SIMPLE
					  ON UPDATE CASCADE ON DELETE CASCADE
				)";

$queries[] = "CREATE TABLE email_users
				(
				  id serial NOT NULL,
				  email_address character varying(128) NOT NULL,
				  maildir character varying(128),
				  password character varying(64),
				  account_id integer,
				  CONSTRAINT email_users_pkey PRIMARY KEY (id ),
				  CONSTRAINT email_users_account_id_fkey FOREIGN KEY (account_id)
					  REFERENCES accounts (id) MATCH SIMPLE
					  ON UPDATE CASCADE ON DELETE CASCADE,
				  CONSTRAINT email_users_address UNIQUE (email_address )
				)";

$queries[] = "CREATE TABLE settings
				(
				  name character varying(256) NOT NULL,
				  value text,
				  CONSTRAINT settings_pkey PRIMARY KEY (name )
				)";

$queries[] = "CREATE TABLE worker_job_queue
				(
				  id bigserial NOT NULL,
				  account_id integer,
				  function_name character varying(512),
				  workload bytea,
				  f_running boolean,
				  ts_run timestamp with time zone,
				  ts_entered timestamp with time zone,
				  CONSTRAINT worker_job_queue_pkey PRIMARY KEY (id )
				)";

// Legacy
$queries[] = "CREATE TABLE workerpool
				(
				  id serial NOT NULL,
				  function_name character varying(256) NOT NULL,
				  progress integer NOT NULL,
				  data text,
				  CONSTRAINT workerpool_pkey PRIMARY KEY (id )
			  )";


$queries[] = "CREATE TABLE zipcodes
				(
				  id bigserial NOT NULL,
				  zipcode integer,
				  city character varying(64),
				  state character varying(2),
				  latitude real,
				  longitude real,
				  dst smallint,
				  timezone double precision,
				  CONSTRAINT zipcodes_pkey PRIMARY KEY (id )
				);";

$queries[] = "CREATE TABLE account_users
					(
					  id bigserial NOT NULL,
					  account_id integer,
					  email_address character varying(256),
					  username character varying(256),
					  CONSTRAINT account_users_pkey PRIMARY KEY (id ),
					  CONSTRAINT account_users_aid_fkey FOREIGN KEY (account_id)
						  REFERENCES accounts (id) MATCH SIMPLE
						  ON UPDATE CASCADE ON DELETE CASCADE,
					  CONSTRAINT account_users_uni UNIQUE (account_id , email_address )
					);";

$queries[] = "CREATE INDEX zipcodes_zip_idx
   				ON zipcodes (zipcode ASC NULLS LAST);";

$queries[] = "CREATE INDEX worker_job_queue_tsrun_idx
					ON worker_job_queue (ts_run ASC NULLS FIRST";

$queries[] = "CREATE INDEX account_users_email_idx ON account_users
				  USING btree (email_address);";

$queries[] = "CREATE INDEX accounts_version_idx
   					ON accounts (version ASC NULLS LAST);";

$queries[] = "CREATE INDEX accounts_name_idx ON accounts
				  	USING btree (name);";

$queries[] = "CREATE INDEX worker_job_queue_aid_idx
   				 	ON worker_job_queue (account_id ASC NULLS LAST);";

$queries[] = "CREATE INDEX address_idx
   					ON email_alias (address ASC NULLS LAST);";

$queries[] = "CREATE INDEX domain_idx
   					ON email_domains (domain ASC NULLS LAST);";

// Update schema revision
$queries[] = "INSERT INTO settings(name, value) VALUES('schema_revision', '$sys_schema_version');";
