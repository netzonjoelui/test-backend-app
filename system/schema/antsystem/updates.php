<?php
/**
 * This is a list of queries updated to update the antsystem schema
 */

/**
 *	3/16/2011 - Std Updates
 */
$thisrev = 1.2;
if ($revision < $thisrev)
{
	$queries[] = "CREATE TABLE settings
					(
					  \"name\" character varying(256) NOT NULL,
					  \"value\" text,
					  CONSTRAINT settings_pkey PRIMARY KEY (name)
					)
					WITH (
					  OIDS=FALSE
					);";

	$queries[] = "CREATE TABLE worker_job_queue
					(
					   id bigserial, 
					   account_id integer,
					   function_name character varying(512), 
					   workload bytea, 
					   f_running boolean, 
					   ts_entered timestamp with time zone, 
					   CONSTRAINT worker_job_queue_pkey PRIMARY KEY (id)
					) 
					WITH (
					  OIDS = FALSE
					);";
}

/**
 * 12/30/2011 - Add account id to email_alias table
 */
$thisrev = 1.3;
if ($revision < $thisrev)
{
	$queries[] = "ALTER TABLE email_domains ADD COLUMN account_id integer;
				  ALTER TABLE email_domains ADD CONSTRAINT email_domains_aid_fkey 
					FOREIGN KEY (account_id) REFERENCES accounts (id) ON UPDATE CASCADE ON DELETE CASCADE;";


	$queries[] = "ALTER TABLE email_alias ADD COLUMN account_id integer;
				  ALTER TABLE email_alias ADD CONSTRAINT email_alias_aid_fkey 
					FOREIGN KEY (account_id) REFERENCES accounts (id) ON UPDATE CASCADE ON DELETE CASCADE;";
}

/**
 * 12/30/2011 - Add password column to email_users
 */
$thisrev = 1.4;
if ($revision < $thisrev)
{
	$queries[] = "ALTER TABLE email_users ADD COLUMN password character varying(64)";
}

/**
 * 11/25/2012 - Add zipcode table
 */
$thisrev = 1.5;
if ($revision < $thisrev)
{
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

	$queries[] = "CREATE INDEX zipcodes_zip_idx
					ON zipcodes (zipcode ASC NULLS LAST);";
}

/**
 * 12/22/2012 - Add delayed ts_run column to worker job pool
 */
$thisrev = 1.6;
if ($revision < $thisrev)
{
	$queries[] = "ALTER TABLE worker_job_queue ADD COLUMN ts_run timestamp with time zone;";

	$queries[] = "CREATE INDEX worker_job_queue_tsrun_idx
					ON worker_job_queue (ts_run ASC NULLS FIRST);";
}

/**
 * 2/4/2013 - Added users table for universal login with email address
 */
$thisrev = 1.7;
if ($revision < $thisrev)
{
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

	$queries[] = "CREATE INDEX account_users_email_idx ON account_users
				  USING btree (email_address);";
}

/**
 * 8/17/2013 - Added indexes based on number of queries
 */
$thisrev = 1.8;
if ($revision < $thisrev)
{
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
}

/**
 * 1/6/2014 - Drop account_id not null constraint for system mailboxes
 */
$thisrev = 1.9;
if ($revision < $thisrev)
{
	$queries[] = "ALTER TABLE email_users
				   ALTER COLUMN account_id DROP NOT NULL;";
}
