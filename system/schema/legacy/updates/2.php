<?php
	/*******************************************************************************
	*	9/3/2007 - Add timeline_date to template tasks to set interval by
	********************************************************************************/
	$thisrev = 2;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_template_tasks ADD COLUMN 
						timeline_date_begin character varying(32) DEFAULT 'date_deadline';
					  ALTER TABLE project_template_tasks ADD COLUMN 
						timeline_date_due character varying(32) DEFAULT 'date_deadline';";

		
	}

	/*******************************************************************************
	*	9/10/2007 - Add direct-push field to webfeeds
	********************************************************************************/
	$thisrev = 2.111;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE xml_feed_publish
						(
						   id serial, 
						   feed_id integer NOT NULL, 
						   publish_to text, 
						   furl text, 
						   CONSTRAINT xml_feed_publish_pkey PRIMARY KEY (id), 
						   CONSTRAINT xml_feed_publish_fid_fkey FOREIGN KEY (feed_id) REFERENCES xml_feeds (id)    
						   	ON UPDATE CASCADE ON DELETE CASCADE
						) WITHOUT OIDS;";

		
	}

	/*******************************************************************************
	*	9/11/2007 - Add timestamp for last time an action was regiested (to get idle)
	********************************************************************************/
	$thisrev = 2.112;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE users ADD COLUMN active_timestamp timestamp with time zone;
					  COMMENT ON COLUMN users.active_timestamp IS 'Last time an action was registered.';";

		
	}

	/*******************************************************************************
	*	9/25/2007 - Add comments to quality control/bugs
	********************************************************************************/
	$thisrev = 2.113;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE project_bug_comments
						(
						  id bigserial NOT NULL,
						  user_id integer,
						  bug_id bigint,
						  title character varying(256),
						  body text,
						  time_posted timestamp with time zone,
						  CONSTRAINT project_bug_comments_pkey PRIMARY KEY (id),
						  CONSTRAINT project_bug_comments_bid_fkey FOREIGN KEY (bug_id)
							  REFERENCES project_bugs (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT project_bug_comments_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL
						)";
	}

	/*******************************************************************************
	*	10/10/2007 - Cache the name of the commentor for quality control
	********************************************************************************/
	$thisrev = 2.114;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_bug_comments add column user_name_cache varchar(128);";
		$queries[] = "ALTER TABLE project_bugs add column created_by varchar(128);";

		
	}


	/*******************************************************************************
	*	11/14/2007 - Add purged flag to userfiles. If file has been deleted from backup.
	********************************************************************************/
	$thisrev = 2.115;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE user_files add column f_bpurged bool DEFAULT false;";

		
	}
	
	/*******************************************************************************
	*	11/21/2007 - Add additional notify email to projects
	********************************************************************************/
	$thisrev = 2.116;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_bugs add column notify_email text;";

		
	}

	/*******************************************************************************
	*	1/3/2008 - Alter email_domains to pkey do account
	********************************************************************************/
	$thisrev = 2.117;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_domains DROP CONSTRAINT email_domains_pkey;";
		$queries[] = "ALTER TABLE email_domains ADD CONSTRAINT email_domains_pkey PRIMARY KEY (\"domain\", account_id);";

		
	}

	/*******************************************************************************
	*	1/15/2008 - Add infocenter tables
	********************************************************************************/
	$thisrev = 2.118;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE ic_groups
						(
						  id serial NOT NULL,
						  parent_id integer,
						  name character varying(128),
						  account_id integer,
						  color character varying(8),
						  CONSTRAINT id_groups_pkey PRIMARY KEY (id),
						  CONSTRAINT ic_groups_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";
		$queries[] = "CREATE TABLE ic_documents
						(
						  id serial NOT NULL,
						  title character varying(128),
						  keywords text,
						  author_name character varying(64),
						  body text,
						  account_id integer,
						  CONSTRAINT ic_documents_pkey PRIMARY KEY (id),
						  CONSTRAINT ic_documents_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";

		$queries[] = "CREATE TABLE ic_document_group_mem
						(
						  id serial NOT NULL,
						  document_id integer NOT NULL,
						  group_id integer NOT NULL,
						  CONSTRAINT ic_document_group_mem_pkey PRIMARY KEY (id),
						  CONSTRAINT ic_document_group_mem_did_fkey FOREIGN KEY (document_id)
							  REFERENCES ic_documents (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT ic_document_group_mem_gid_fkey FOREIGN KEY (group_id)
							  REFERENCES ic_groups (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";


		
	}


	/*******************************************************************************
	*	1/15/2008 - Add keywords to the thread
	********************************************************************************/
	$thisrev = 2.119;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_threads ADD COLUMN keywords text;";
		$queries[] = "ALTER TABLE email_messages ADD COLUMN keywords text;";
		$queries[] = "ALTER TABLE email_messages ADD COLUMN f_indexed boolean DEFAULT 'f';";

		
	}

	/*******************************************************************************
	*	2/26/2008 - Track history of who project comments were sent to
	********************************************************************************/
	$thisrev = 2.120;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_bug_comments ADD COLUMN notified_log text;";
		$queries[] = "ALTER TABLE project_message_comments ADD COLUMN notified_log text;";

		
	}

	/*******************************************************************************
	*	2/27/2008 - Add special flag to customer labels
	********************************************************************************/
	$thisrev = 2.121;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_labels ADD COLUMN f_special boolean DEFAULT 'f';";
		$queries[] = "ALTER TABLE contacts_personal_labels ADD COLUMN f_special boolean DEFAULT 'f';";

		
	}

	/*******************************************************************************
	*	3/21/2008 - Clean up the users table a bit
	********************************************************************************/
	$thisrev = 2.121;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE users DROP COLUMN groups;";
		$queries[] = "ALTER TABLE users DROP COLUMN extension;";
		$queries[] = "ALTER TABLE users DROP COLUMN reports;";
		$queries[] = "ALTER TABLE users DROP COLUMN stock_viewed;";

		
	}

	/*******************************************************************************
	*	4/25/2008 - Change chat functionality a LOT
	********************************************************************************/
	$thisrev = 2.122;
	if ($revision < $thisrev)
	{

		
		$queries[] = "CREATE TABLE chat_sessions
						(
						  id serial NOT NULL,
						  user_id integer,
						  ts_last_updated timestamp without time zone,
						  f_read boolean DEFAULT false,
						  notes text,
						  CONSTRAINT chat_sessions_pkey PRIMARY KEY (id),
						  CONSTRAINT chat_sessions_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";

		$queries[] = "CREATE TABLE chat_friends
						(
						  id serial NOT NULL,
						  user_id integer NOT NULL,
						  friend_name character varying(256),
						  friend_server character varying(128),
						  session_id integer,
						  f_online boolean DEFAULT false,
						  local_name character varying(128),
						  status text,
						  CONSTRAINT chat_friends_pkey PRIMARY KEY (id),
						  CONSTRAINT chat_friends_sid_fkey FOREIGN KEY (session_id)
							  REFERENCES chat_sessions (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT chat_friends_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";


		$queries[] = "CREATE TABLE chat_session_remotes
						(
						  id serial NOT NULL,
						  server character varying(128),
						  session_id integer,
						  color character varying(64),
						  f_typing boolean DEFAULT false,
						  friend_id integer,
						  remote_session_id character varying(128) NOT NULL,
						  token_id character varying(128),
						  name character varying(64),
						  CONSTRAINT chat_session_remotes_pkey PRIMARY KEY (id),
						  CONSTRAINT chat_session_remotes_fid_fkey FOREIGN KEY (friend_id)
							  REFERENCES chat_friends (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT chat_session_remotes_sid_fkey FOREIGN KEY (session_id)
							  REFERENCES chat_sessions (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";


		$queries[] = "CREATE TABLE chat_session_content
						(
						  id serial NOT NULL,
						  session_id integer,
						  ts_entered timestamp with time zone,
						  body text,
						  remote_id integer,
						  CONSTRAINT chat_session_content_pkey PRIMARY KEY (id),
						  CONSTRAINT chat_session_content_rid_fkey FOREIGN KEY (remote_id)
							  REFERENCES chat_session_remotes (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT chat_session_content_sid_fkey FOREIGN KEY (session_id)
							  REFERENCES chat_sessions (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";

		$queries[] = "CREATE TABLE chat_queues
						(
						  id serial NOT NULL,
						  name character varying(128),
						  save_action_url text,
						  save_action_title character varying(64),
						  CONSTRAINT chat_queues_pkey PRIMARY KEY (id)
						)
						WITHOUT OIDS;";

		$queries[] = "CREATE TABLE chat_queue_entries
						(
						  id serial NOT NULL,
						  name character varying(256),
						  ts_created timestamp with time zone,
						  session_id integer,
						  token_id character varying(128),
						  queue_id integer,
						  notes text,
						  CONSTRAINT chat_queue_entries_pkey PRIMARY KEY (id),
						  CONSTRAINT chat_queue_entries_qid_fkey FOREIGN KEY (queue_id)
							  REFERENCES chat_queues (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT chat_queue_entries_sid_fkey FOREIGN KEY (session_id)
							  REFERENCES chat_sessions (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";

		$queries[] = "CREATE TABLE chat_queue_agents
						(
						  id serial NOT NULL,
						  queue_id integer,
						  user_id integer,
						  CONSTRAINT chat_queue_agents_pkey PRIMARY KEY (id),
						  CONSTRAINT chat_queue_agents_qid_fkey FOREIGN KEY (queue_id)
							  REFERENCES chat_queues (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT chat_queue_agents_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";

		$queries[] = "CREATE TABLE chat_queue_agents
						(
						  id serial NOT NULL,
						  queue_id integer,
						  user_id integer,
						  CONSTRAINT chat_queue_agents_pkey PRIMARY KEY (id),
						  CONSTRAINT chat_queue_agents_qid_fkey FOREIGN KEY (queue_id)
							  REFERENCES chat_queues (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT chat_queue_agents_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";

		// Copy friends over
		$queries[] = "insert into chat_friends(friend_name, local_name, user_id)
						select users.name, users.full_name, user_friends.user_id 
						from users, user_friends where user_friends.friend_id=users.id;";

		$queries[] = "DROP TABLE user_chat cascade;";

		
	}

	/*******************************************************************************
	*	6/23/2008 - Add account settings table
	********************************************************************************/
	$thisrev = 2.123;
	if ($revision < $thisrev)
	{

		$queries[] = "CREATE TABLE account_settings
						(
						   id serial, 
						   account_id integer NOT NULL, 
						   name character varying(256), 
						   value text, 
						   CONSTRAINT account_settings_pkey PRIMARY KEY (id), 
						   CONSTRAINT account_settings_aid_fkey FOREIGN KEY (account_id) REFERENCES accounts (id)    ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITHOUT OIDS;";

		
	}

	/*******************************************************************************
	*	9/11/2008 - God bless America!!!!! (add created timestamp)
	********************************************************************************/
	$thisrev = 2.124;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE projects ADD COLUMN ts_created timestamp with time zone;";

		
	}

	/*******************************************************************************
	*	9/25/2008 - Add account settings table
	********************************************************************************/
	$thisrev = 2.125;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE calendar_events_attendees ADD COLUMN \"position\" character varying(256);";

		
	}

	/*******************************************************************************
	*	9/28/2008 - Update company field size (was 32 char - way too small)
	********************************************************************************/
	$thisrev = 2.126;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE contacts_personal ALTER company TYPE character varying(256);
					  ALTER TABLE contacts_personal ALTER COLUMN company SET STATISTICS -1;";

		$queries[] = "ALTER TABLE customers ALTER company TYPE character varying(256);
					  ALTER TABLE customers ALTER COLUMN company SET STATISTICS -1;";

		
		
	}

	/*******************************************************************************
	*	9/05/2008 - Update name field size for file categories (was 64 char - way too small)
	********************************************************************************/
	$thisrev = 2.127;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE user_file_categories ALTER \"name\" TYPE character varying(256);";

		
	}


	/*******************************************************************************
	*	10/22/2008 - Add comments to calendar events
	********************************************************************************/
	$thisrev = 2.128;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE calendar_event_comments
						(
						  id serial NOT NULL,
						  event_id integer,
						  ts_entered timestamp with time zone,
						  entered_by character varying(256),
						  \"comment\" text,
						  in_response_to integer,
						  CONSTRAINT calendar_event_comments_pkey PRIMARY KEY (id),
						  CONSTRAINT calendar_event_comments_eid_fkey FOREIGN KEY (event_id)
							  REFERENCES calendar_events (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)";
	}

	/*******************************************************************************
	*	1/8/2009 - Add timestamp to project issues/bugs
	********************************************************************************/
	$thisrev = 2.129;
	if ($revision < $thisrev)
	{
		$queries[] = "alter table project_bugs add column ts_entered timestamp with time zone;";
		$queries[] = "CREATE TABLE project_task_bug_mem
						(
						   id serial, 
						   task_id integer, 
						   bug_id integer, 
						   CONSTRAINT project_task_bug_mem_pkey PRIMARY KEY (id), 
						   CONSTRAINT project_task_bug_mem_bid_fkey FOREIGN KEY (bug_id) REFERENCES project_bugs (id)    ON UPDATE CASCADE ON DELETE CASCADE, 
						   CONSTRAINT project_task_bug_mem_tid_fkey FOREIGN KEY (task_id) REFERENCES project_tasks (id)    ON UPDATE CASCADE ON DELETE CASCADE
						) ";

		
	}

	/*******************************************************************************
	*	12/05/2008 - Add leads and opportunities
	********************************************************************************/
	$thisrev = 2.130;
	if ($revision < $thisrev)
	{
		// TODO: Put leads and opportunities here

		// Customer Opp Types
		$queries[] = "CREATE TABLE customer_opportunity_types
						(
						  id serial NOT NULL,
						  \"name\" character varying(128),
						  account_id integer,
						  sort_order smallint DEFAULT 1::smallint,
						  CONSTRAINT customer_opportunity_types_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_opportunity_types_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";


		// Customer Opp Types
		$queries[] = "CREATE TABLE customer_opportunity_stages
						(
						  id serial NOT NULL,
						  account_id integer,
						  \"name\" character varying(128),
						  sort_order smallint DEFAULT 1::smallint,
						  f_closed boolean DEFAULT false,
						  f_won boolean DEFAULT false,
						  CONSTRAINT customer_opportunity_stages_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_opportunity_stages_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";
		// Lead queues
		$queries[] = "CREATE TABLE customer_lead_queues
					(
					  id serial NOT NULL,
					  account_id integer,
					  \"name\" character varying(256),
					  dacl_edit integer,
					  CONSTRAINT customer_lead_queues_pkey PRIMARY KEY (id),
					  CONSTRAINT customer_lead_queues_aid_fkey FOREIGN KEY (account_id)
						  REFERENCES accounts (id) MATCH SIMPLE
						  ON UPDATE CASCADE ON DELETE CASCADE
					)
					WITHOUT OIDS;";

		// Lead rating
		$queries[] = "CREATE TABLE customer_lead_rating
						(
						  id serial NOT NULL,
						  account_id integer,
						  \"name\" character varying(128),
						  sort_order smallint DEFAULT 1::smallint,
						  CONSTRAINT customer_lead_rating_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_lead_rating_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";
		// Lead sources
		$queries[] = "CREATE TABLE customer_lead_sources
						(
						  id serial NOT NULL,
						  account_id integer,
						  \"name\" character varying(128),
						  sort_order smallint DEFAULT 1::smallint,
						  CONSTRAINT customer_lead_sources_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_lead_sources_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";
		// Lead sources
		$queries[] = "CREATE TABLE customer_lead_status
						(
						  id serial NOT NULL,
						  account_id integer,
						  \"name\" character varying(128),
						  sort_order smallint,
						  f_closed boolean DEFAULT false,
						  f_converted boolean DEFAULT false,
						  CONSTRAINT customer_lead_status_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_lead_status_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";
		// Leads 
		$queries[] = "CREATE TABLE customer_leads
						(
						  id serial NOT NULL,
						  queue_id integer,
						  owner_id integer,
						  ts_entered timestamp with time zone,
						  ts_updated timestamp with time zone,
						  ts_contacted timestamp with time zone,
						  first_name character varying(128),
						  last_name character varying(128),
						  email character varying(256),
						  phone character varying(128),
						  street text,
						  city character varying(256),
						  state character varying(64),
						  zip character varying(32),
						  notes text,
						  source_id integer,
						  rating_id integer,
						  status_id integer,
						  company character varying(256),
						  title character varying(256),
						  website character varying(512),
						  account_id integer,
						  country character varying(512),
						  customer_id integer,
						  opportunity_id integer,
						  CONSTRAINT customer_leads_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_leads_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT customer_leads_custid_fkey FOREIGN KEY (customer_id)
							  REFERENCES customers (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT customer_leads_qid_fkey FOREIGN KEY (queue_id)
							  REFERENCES customer_lead_queues (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT customer_leads_rid_fkey FOREIGN KEY (rating_id)
							  REFERENCES customer_lead_rating (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT customer_leads_sid_fkey FOREIGN KEY (source_id)
							  REFERENCES customer_lead_sources (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT customer_leads_status_fkey FOREIGN KEY (status_id)
							  REFERENCES customer_lead_status (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT customer_leads_uid_fkey FOREIGN KEY (owner_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL
						)
						WITHOUT OIDS;";
		// Opportunities
		$queries[] = "CREATE TABLE customer_opportunities
						(
						  id serial NOT NULL,
						  owner_id integer,
						  ts_entered timestamp with time zone,
						  ts_updated timestamp with time zone,
						  notes text,
						  stage_id integer,
						  \"name\" text,
						  expected_close_date date,
						  amount real,
						  account_id integer,
						  customer_id integer,
						  lead_id integer,
						  lead_source_id integer,
						  probability_per smallint,
						  created_by character varying(256),
						  type_id integer,
						  updated_by character varying(256),
						  CONSTRAINT customer_opportunities_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_opportunities_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT customer_opportunities_cid_fkey FOREIGN KEY (customer_id)
							  REFERENCES customers (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT customer_opportunities_lid_fkey FOREIGN KEY (lead_id)
							  REFERENCES customer_leads (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT customer_opportunities_lsid_fkey FOREIGN KEY (lead_source_id)
							  REFERENCES customer_lead_sources (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT customer_opportunities_stage_fkey FOREIGN KEY (stage_id)
							  REFERENCES customer_opportunity_stages (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT customer_opportunities_tid_fkey FOREIGN KEY (type_id)
							  REFERENCES customer_opportunity_types (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT customer_opportunities_uid_fkey FOREIGN KEY (owner_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL
						)
						WITHOUT OIDS;";
		
		// customer events
		$queries[] = "CREATE TABLE customer_events
						(
						  id serial NOT NULL,
						  event_id integer NOT NULL,
						  lead_id integer,
						  opportunity_id integer,
						  CONSTRAINT customer_events_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_events_eid_fkey FOREIGN KEY (event_id)
							  REFERENCES calendar_events (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT customer_events_lid_fkey FOREIGN KEY (lead_id)
							  REFERENCES customer_leads (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT customer_events_oid_fkey FOREIGN KEY (opportunity_id)
							  REFERENCES customer_opportunities (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";

		// customer tasks
		$queries[] = "CREATE TABLE customer_tasks
						(
						  id serial NOT NULL,
						  task_id integer,
						  lead_id integer,
						  opportunity_id integer,
						  CONSTRAINT customer_tasks_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_tasks_lid_fkey FOREIGN KEY (lead_id)
							  REFERENCES customer_leads (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT customer_tasks_oid_fkey FOREIGN KEY (opportunity_id)
							  REFERENCES customer_opportunities (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT customer_tasks_tid_fkey FOREIGN KEY (task_id)
							  REFERENCES project_tasks (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";

		// Link leads to activities
		$queries[] = "ALTER TABLE customer_activity ADD COLUMN lead_id integer;
					  ALTER TABLE customer_activity ADD CONSTRAINT customer_activity_lid_fkey FOREIGN KEY (lead_id) REFERENCES customer_leads (id)    
						ON UPDATE CASCADE ON DELETE CASCADE;";
		
		// Add name to customers
		$queries[] = "alter table customers add column name character varying(256);";

		// Add type to customer
		$queries[] = "alter table customers add column type_id smallint;";
		$queries[] = "update customers set type_id='1';";

		// Add default address
		$queries[] = "ALTER TABLE customers ADD COLUMN address_default character varying(16);";

		// Add DNC Fields
		$queries[] = "ALTER TABLE customers ADD COLUMN f_nocall boolean DEFAULT false;
					  ALTER TABLE customers ADD COLUMN f_noemailspam boolean DEFAULT false;
					  ALTER TABLE customers ADD COLUMN f_nocontact boolean DEFAULT false;";

		// Add Status
		$queries[] = "CREATE TABLE customer_status
						(
						   id serial, 
						   \"name\" character varying(64), 
						   account_id integer, 
						   sort_order smallint, 
						   f_closed boolean DEFAULT false, 
						   CONSTRAINT customer_status_pkey PRIMARY KEY (id), 
						   CONSTRAINT customer_status_aid_fkey FOREIGN KEY (account_id) REFERENCES accounts (id)    ON UPDATE CASCADE ON DELETE CASCADE
						);";
		$queries[] = "ALTER TABLE customers ADD COLUMN status_id integer;
					  ALTER TABLE customers ADD CONSTRAINT customers_sid_fkey FOREIGN KEY (status_id) REFERENCES customer_status (id) ON UPDATE CASCADE ON DELETE SET NULL;";

		// Add primary account and contacts to customer associations
		$queries[] = "ALTER TABLE customers ADD COLUMN accoc_primary_contact integer;
					  ALTER TABLE customers ADD COLUMN accoc_primary_account integer;
					  ALTER TABLE customers ADD CONSTRAINT customers_assoc_pri_acc_fkey FOREIGN KEY (accoc_primary_account) 
						  	REFERENCES customer_associations (id)    ON UPDATE CASCADE ON DELETE SET NULL;
					  ALTER TABLE customers ADD CONSTRAINT customers_assoc_con_fkey FOREIGN KEY (accoc_primary_contact) 
						  	REFERENCES customer_associations (id)    ON UPDATE CASCADE ON DELETE SET NULL;";

		// Add relationship name
		$queries[] = "ALTER TABLE customer_associations ADD COLUMN relationship_name character varying(256);";
					  
		
	}
	

	/*******************************************************************************
	*	4/1/2009 - Restrict user names to be unique to acounts
	********************************************************************************/
	$thisrev = 2.131;
	if ($revision < $thisrev)
	{

		$queries[] = "ALTER TABLE users ADD CONSTRAINT user_name_acc_uni UNIQUE (\"name\", account_id);";

		
	}

	/*******************************************************************************
	*	4/10/2009 - Add customer stages
	********************************************************************************/
	$thisrev = 2.132;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE customer_stages
						(
						  id serial NOT NULL,
						  \"name\" character varying(64) NOT NULL,
						  account_id integer,
						  sort_order smallint DEFAULT 1,
						  CONSTRAINT customer_stages_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_stages_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT customer_stages_uni UNIQUE (name, account_id)
						)
						WITHOUT OIDS;";

			$queries[] = "ALTER TABLE customers ADD COLUMN stage_id integer;
							ALTER TABLE customers ADD CONSTRAINT customers_stageid_fkey FOREIGN KEY (stage_id) REFERENCES customer_stages (id)    
								ON UPDATE CASCADE ON DELETE SET NULL;";
		

		
	}


	/*******************************************************************************
	*	4/11/2009 - Update the length of content type 
	*	(MS attachments were 100+k and old limit was 64)
	********************************************************************************/
	$thisrev = 2.133;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_message_attachments ALTER content_type TYPE character varying(256);";
		$queries[] = "ALTER TABLE email_messages ALTER content_type TYPE character varying(256);";

		
	}

	/*******************************************************************************
	*	4/16/2009 - Update name lengths for customers
	********************************************************************************/
	$thisrev = 2.134;
	if ($revision < $thisrev)
	{

		$queries[] = "ALTER TABLE customers ALTER first_name TYPE character varying(256);
					  ALTER TABLE customers ALTER last_name TYPE character varying(256);
					  ALTER TABLE customers ALTER salutation TYPE character varying(512);
					  ALTER TABLE customers ALTER nick_name TYPE character varying(256);
					  ALTER TABLE customers ALTER name TYPE character varying(512);
					  ALTER TABLE customers ALTER COLUMN first_name SET STATISTICS -1;
					  ALTER TABLE customers ALTER COLUMN last_name SET STATISTICS -1;
					  ALTER TABLE customers ALTER COLUMN salutation SET STATISTICS -1;
					  ALTER TABLE customers ALTER COLUMN nick_name SET STATISTICS -1;
					  ALTER TABLE customers ALTER COLUMN name SET STATISTICS -1;";

		
	}

	/*******************************************************************************
	*	4/20/2009 - Update activities, and add rank to contacts
	********************************************************************************/
	$thisrev = 2.135;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_activity ADD COLUMN f_readonly boolean DEFAULT false;
					  ALTER TABLE customer_activity ADD COLUMN email_id integer;
					  ALTER TABLE customer_activity ADD CONSTRAINT customer_activity_emailid_fkey 
						FOREIGN KEY (email_id) REFERENCES email_messages (id)    ON UPDATE CASCADE ON DELETE SET NULL;";
		$queries[] = "ALTER TABLE customer_activity add column opportunity_id integer;";
		$queries[] = "ALTER TABLE customer_activity ADD CONSTRAINT customer_activity_oid_fkey FOREIGN KEY (opportunity_id) REFERENCES customer_opportunities (id)    
						  	ON UPDATE CASCADE ON DELETE CASCADE;";
		$queries[] = "ALTER TABLE customer_activity ALTER time_entered TYPE timestamp with time zone;";
		$queries[] = "ALTER TABLE contacts_personal ADD COLUMN i_rank integer DEFAULT '1';";
		$queries[] = "ALTER TABLE customer_activity_types ADD COLUMN direction character(1);";
		$queries[] = "ALTER TABLE customer_activity ADD COLUMN direction character(1);";

		
	}

	/*******************************************************************************
	*	4/27/2009 - Update customers to add analytics
	********************************************************************************/
	$thisrev = 2.136;
	if ($revision < $thisrev)
	{
		// Add colors
		$queries[] = "ALTER TABLE customer_lead_queues ADD COLUMN color character(6);";
		$queries[] = "ALTER TABLE customer_lead_sources ADD COLUMN color character(6);";
		$queries[] = "ALTER TABLE customer_lead_status ADD COLUMN color character(6);";
		$queries[] = "ALTER TABLE customer_opportunity_types ADD COLUMN color character(6);";
		$queries[] = "ALTER TABLE customer_opportunity_stages ADD COLUMN color character(6);";

		// Add closed timestamp for reporting
		$queries[] = "ALTER TABLE customer_opportunities ADD COLUMN ts_closed timestamp with time zone;";

		
	}

	/*******************************************************************************
	*	5/7/2009 - Update contacted fields and add timestamps to calendar events
	********************************************************************************/
	$thisrev = 2.137;
	if ($revision < $thisrev)
	{
		// Leads
		$queries[] = "ALTER TABLE customer_leads DROP COLUMN ts_contacted;";
		$queries[] = "ALTER TABLE customer_leads ADD COLUMN ts_first_contacted timestamp with time zone;";
		$queries[] = "ALTER TABLE customer_leads ADD COLUMN ts_last_contacted timestamp with time zone;";
		// Opportunities
		$queries[] = "ALTER TABLE customer_opportunities ADD COLUMN ts_first_contacted timestamp with time zone;";
		$queries[] = "ALTER TABLE customer_opportunities ADD COLUMN ts_last_contacted timestamp with time zone;";
		// Activities contacted flag
		$queries[] = "ALTER TABLE customer_activity_types ADD COLUMN f_contacted boolean DEFAULT 'f';";
		
		// Add timestamps to events
		$queries[] = "ALTER TABLE calendar_events ADD COLUMN ts_start timestamp with time zone;";
		$queries[] = "ALTER TABLE calendar_events ADD COLUMN ts_end timestamp with time zone;";
		$queries[] = "ALTER TABLE calendar_events_recurring ADD COLUMN ts_start timestamp with time zone;";
		$queries[] = "ALTER TABLE calendar_events_recurring ADD COLUMN ts_end timestamp with time zone;";

		
	}
	

	/*******************************************************************************
	*	5/13/2009 - Update contacted fields and add timestamps to calendar events
	********************************************************************************/
	$thisrev = 2.138;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_leads ADD COLUMN street2 character varying(128);";
		$queries[] = "ALTER TABLE customer_activity ALTER \"name\" TYPE character varying(128);";
		$queries[] = "CREATE TABLE customer_lead_classes
						(
						   id serial, 
						   account_id integer, 
						   \"name\" character varying(256), 
						   color character(6), 
						   sort_order smallint DEFAULT '1', 
						   CONSTRAINT customer_lead_classes_pkey PRIMARY KEY (id), 
						   CONSTRAINT customer_lead_classes_aid_fkey FOREIGN KEY (account_id) REFERENCES accounts (id)    ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITHOUT OIDS;";
		$queries[] = "ALTER TABLE customer_leads ADD COLUMN class_id integer;";
		$queries[] = "ALTER TABLE customer_leads ADD CONSTRAINT customer_leads_clsid_fkey FOREIGN KEY (class_id) REFERENCES 
						customer_lead_classes (id)    ON UPDATE CASCADE ON DELETE SET NULL;";

		
	}

	/*******************************************************************************
	*	6/4/2009 - Customers
	********************************************************************************/
	$thisrev = 2.139;
	if ($revision < $thisrev)
	{
		// Add owner_id
		$queries[] = "ALTER TABLE customers ADD COLUMN owner_id integer;";
		$queries[] = "ALTER TABLE customers ADD CONSTRAINT customers_ownerid_fkey FOREIGN KEY (owner_id) REFERENCES users (id)    
						ON UPDATE CASCADE ON DELETE SET NULL;";

		
	}

	/*******************************************************************************
	*	6/11/2009 - Customers
	********************************************************************************/
	$thisrev = 2.140;
	if ($revision < $thisrev)
	{
		// Add address type
		$queries[] = "ALTER TABLE customers ADD COLUMN address_billing character varying(16);";
	}

	/*******************************************************************************
	*	6/11/2009 - Customers
	********************************************************************************/
	$thisrev = 2.141;
	if ($revision < $thisrev)
	{
		// Add address type
		$queries[] = "CREATE TABLE customer_invoice_templates
						(
						  id serial NOT NULL,
						  account_id integer,
						  \"name\" character varying(128),
						  company_logo integer,
						  company_name character varying(128),
						  company_slogan character varying(256),
						  notes_line1 text,
						  notes_line2 text,
						  footer_line1 text,
						  CONSTRAINT customer_invoice_templates_id_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_invoice_templates_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE NO ACTION ON DELETE NO ACTION
						)
						WITHOUT OIDS;";

		$queries[] = "CREATE TABLE customer_invoice_status
						(
						  id serial NOT NULL,
						  account_id integer,
						  \"name\" character varying(128),
						  sort_order smallint,
						  CONSTRAINT customer_invoice_status_id_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_invoice_status_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE NO ACTION ON DELETE NO ACTION
						)
						WITHOUT OIDS;";
		$queries[] = "CREATE TABLE customer_invoices
						(
						  id serial NOT NULL,
						  \"number\" character varying(512),
						  owner_id integer,
						  customer_id integer,
						  ts_entered timestamp with time zone,
						  ts_updated timestamp with time zone,
						  \"name\" character varying(256),
						  status_id integer,
						  created_by character varying(256),
						  updated_by character varying(256),
						  date_due date,
						  template_id integer,
						  notes_line1 text,
						  notes_line2 text,
						  footer_line1 text,
						  payment_terms character varying(128),
						  send_to text,
						  account_id integer,
						  CONSTRAINT customer_invoices_id_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_invoices_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT customer_invoices_custid_fkey FOREIGN KEY (customer_id)
							  REFERENCES customers (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT customer_invoices_status_fkey FOREIGN KEY (status_id)
							  REFERENCES customer_invoice_status (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT customer_invoices_tid_fkey FOREIGN KEY (template_id)
							  REFERENCES customer_invoice_templates (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT customer_invoices_uid_fkey FOREIGN KEY (owner_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL
						)
						WITHOUT OIDS;";

		$queries[] = "CREATE TABLE customer_invoice_detail
						(
						  id serial NOT NULL,
						  invoice_id integer,
						  quantity real NOT NULL DEFAULT 1::real,
						  \"name\" text,
						  amount real NOT NULL DEFAULT 0::real,
						  CONSTRAINT customer_invoice_detail_id_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_invoice_detail_iid_fkey FOREIGN KEY (invoice_id)
							  REFERENCES customer_invoices (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";
	}

	/*******************************************************************************
	*	6/12/2009 - Calendar updates
	********************************************************************************/
	$thisrev = 2.142;
	if ($revision < $thisrev)
	{

		$queries[] = "CREATE TABLE calendar_event_associations
						(
						  id serial NOT NULL,
						  event_id integer,
						  customer_id integer,
						  contact_id integer,
						  opportunity_id integer,
						  lead_id integer,
						  event_recur_id integer,
						  CONSTRAINT calendar_event_associations_id_pkey PRIMARY KEY (id),
						  CONSTRAINT calendar_event_associations_cid_fkey FOREIGN KEY (contact_id)
							  REFERENCES contacts_personal (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT calendar_event_associations_custid_fkey FOREIGN KEY (customer_id)
							  REFERENCES customers (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT calendar_event_associations_eid_fkey FOREIGN KEY (event_id)
							  REFERENCES calendar_events (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT calendar_event_associations_erid_fkey FOREIGN KEY (event_recur_id)
							  REFERENCES calendar_events_recurring (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT calendar_event_associations_lid_fkey FOREIGN KEY (lead_id)
							  REFERENCES customer_leads (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT calendar_event_associations_oid_fkey FOREIGN KEY (opportunity_id)
							  REFERENCES customer_opportunities (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";

		// Move customer_id to calendar_event_associations
		$queries[] = "insert into calendar_event_associations(event_id, customer_id)
						select id, customer_id from calendar_events where customer_id is not null;";
		$queries[] = "insert into calendar_event_associations(event_recur_id, customer_id)
						select id, customer_id from calendar_events_recurring where customer_id is not null;";

		// Move contact_id over to calendar_event_associations
		$queries[] = "insert into calendar_event_associations(event_id, contact_id)
						select id, contact_id from calendar_events where contact_id is not null;";
		$queries[] = "insert into calendar_event_associations(event_recur_id, contact_id)
						select id, contact_id from calendar_events_recurring where contact_id is not null;";

		// Drop customer_events
		$queries[] = "drop table customer_events;";
	}

	/*******************************************************************************
	*	6/18/2009 - Calendar updates
	********************************************************************************/
	$thisrev = 2.143;
	if ($revision < $thisrev)
	{
		// Add color to calendars
		$queries[] = "ALTER TABLE calendars ADD COLUMN color character varying(6);";
		$queries[] = "ALTER TABLE calendar_sharing ADD COLUMN color character varying(6);";
		$queries[] = "update app_topnav set action='top.webMenu.ChangeTitle(''Calendar'');top.Ant.Execute(''/calendar/calendar.js'', ''CCalendar'');' where name='calendar';";
	}

	/*******************************************************************************
	*	6/18/2009 -  User updates and general clean-up
	********************************************************************************/
	$thisrev = 2.144;
	if ($revision < $thisrev)
	{
		// Add color to calendars
		$queries[] = "drop table user_groups";

		$queries[] = "CREATE TABLE user_groups
						(
						   id serial, 
						   \"name\" character varying(512), 
						   account_id integer, 
						   f_default boolean DEFAULT 'f', 
						   f_admin boolean DEFAULT 'f', 
						   CONSTRAINT user_groups_pkey PRIMARY KEY (id), 
						   CONSTRAINT user_groups_aid_fkey FOREIGN KEY (account_id) REFERENCES accounts (id)    ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITHOUT OIDS;";

		$queries[] = "CREATE TABLE user_group_mem
						(
						   id serial, 
						   group_id integer, 
						   user_id integer, 
						   CONSTRAINT user_group_mem_pkey PRIMARY KEY (id), 
						   CONSTRAINT user_group_mem_gid_fkey FOREIGN KEY (group_id) REFERENCES user_groups (id)    ON UPDATE CASCADE ON DELETE CASCADE, 
						   CONSTRAINT user_group_mem_uid_fkey FOREIGN KEY (user_id) REFERENCES users (id)    ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITHOUT OIDS;";

		$queries[] = "insert into user_groups (name) select name from groups;";
		$queries[] = "insert into user_group_mem (user_id, group_id) select user_id, group_id from group_membership;";
		
		$queries[] = "drop table acces_control_special;";
		$queries[] = "drop table acces_control;";
		$queries[] = "drop table group_membership;";
		$queries[] = "drop table groups;";

		$queries[] = "ALTER TABLE users DROP COLUMN groups;";
		$queries[] = "ALTER TABLE users DROP COLUMN stock_ticker;";
		$queries[] = "ALTER TABLE users DROP COLUMN reports;";
		$queries[] = "ALTER TABLE users DROP COLUMN stock_viewed;";
		$queries[] = "ALTER TABLE users DROP COLUMN timezone;";

		// Add image
		$queries[] = "ALTER TABLE users ADD COLUMN image_id integer;";
		$queries[] = "ALTER TABLE users ADD CONSTRAINT users_imgid_fkey FOREIGN KEY (image_id) REFERENCES user_files (id)    ON UPDATE CASCADE ON DELETE SET NULL;";
			
	}

	/*******************************************************************************
	*	7/9/2009 - Update user files
	********************************************************************************/
	$thisrev = 2.145;
	if ($revision < $thisrev)
	{
		$queries[] = "";
	}
	
	/*******************************************************************************
	*	6/18/2009 - Calendar updates
	********************************************************************************/
	$thisrev = 2.146;
	if ($revision < $thisrev)
	{
		// Add color to calendars
		$queries[] = "CREATE TABLE security_dacl
						(
						  id serial NOT NULL,
						  \"name\" text,
						  account_id integer,
						  CONSTRAINT security_dacl_pkey PRIMARY KEY (id),
						  CONSTRAINT security_dacl_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT security_dacl_name_uni UNIQUE (name, account_id)
						)
						WITHOUT OIDS;";
		$queries[] = "CREATE TABLE security_aclp
						(
						  id serial NOT NULL,
						  dacl_id integer,
						  \"name\" character varying(128),
						  parent_id integer,
						  CONSTRAINT security_aclp_pkey PRIMARY KEY (id),
						  CONSTRAINT security_aclp_aclid_fkey FOREIGN KEY (dacl_id)
							  REFERENCES security_dacl (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";
		$queries[] = "CREATE TABLE security_acle
						(
						  id serial NOT NULL,
						  aclp_id integer,
						  user_id integer,
						  group_id integer,
						  CONSTRAINT security_acle_pkey PRIMARY KEY (id),
						  CONSTRAINT security_acle_aclp_fkey FOREIGN KEY (aclp_id)
							  REFERENCES security_aclp (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT security_acle_gid_fkey FOREIGN KEY (group_id)
							  REFERENCES user_groups (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT security_acle_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";
	}

	/*******************************************************************************
	*	7/11/2009 - Update user_group_mem
	********************************************************************************/
	$thisrev = 2.147;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into user_groups (name, id) values('Administrators', '-1');";
		$queries[] = "insert into user_groups (name, id) values('Creator Owner', '-2');";
		$queries[] = "update user_group_mem set group_id='-1' where group_id='1';";
	}

	/*******************************************************************************
	*	7/12/2009 - Update users
	********************************************************************************/
	$thisrev = 2.148;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE users ALTER COLUMN theme_id DROP DEFAULT;";
		$queries[] = "ALTER TABLE users ALTER status_text TYPE character varying(128);";
		$queries[] = "ALTER TABLE users ALTER COLUMN status_text SET DEFAULT 'Available';";
		$queries[] = "ALTER TABLE users ALTER COLUMN account_id DROP DEFAULT;";
		$queries[] = "ALTER TABLE users ALTER COLUMN account_id SET NOT NULL;";
		$queries[] = "ALTER TABLE users ALTER COLUMN quota_size SET DEFAULT 1000;";
	}

	/*******************************************************************************
	*	7/12/2009 - Update users
	********************************************************************************/
	$thisrev = 2.149;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE themes DROP COLUMN flash;";
		$queries[] = "ALTER TABLE themes ADD COLUMN f_default boolean DEFAULT 'f';";
		$queries[] = "update themes set f_default='t' where app_name='ant_os';";
	}

	/*******************************************************************************
	*	7/22/2009 - Update contacts
	********************************************************************************/
	$thisrev = 2.150;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE contacts_personal ADD COLUMN street_2 text;";
		$queries[] = "ALTER TABLE contacts_personal ADD COLUMN business_street_2 text;";
	}

	/*******************************************************************************
	*	7/23/2009 - Update contacts
	********************************************************************************/
	$thisrev = 2.151;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE calendar_events_recurring ALTER COLUMN \"interval\" SET DEFAULT '1';";
		$queries[] = "update calendar_events_recurring set interval='1' where interval is null;";
	}

	/*******************************************************************************
	*	7/24/2009 - Update contacts
	********************************************************************************/
	$thisrev = 2.152;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_invoice_status DROP CONSTRAINT customer_invoice_status_aid_fkey;";
		$queries[] = "ALTER TABLE customer_invoice_status ADD CONSTRAINT customer_invoice_status_aid_fkey FOREIGN KEY (account_id) REFERENCES accounts (id) 
						ON UPDATE CASCADE ON DELETE CASCADE;";
		$queries[] = "ALTER TABLE calendar_event_associations ADD CONSTRAINT calendar_event_associations_evnt_uni UNIQUE (event_id);";
		$queries[] = "ALTER TABLE calendar_event_associations ADD CONSTRAINT calendar_event_associations_revnt_uni UNIQUE (event_recur_id);";
	}

	/*******************************************************************************
	*	7/28/2009 - Update activity contact type
	********************************************************************************/
	$thisrev = 2.153;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_activity_types DROP COLUMN f_contacted;";
		$queries[] = "ALTER TABLE customer_activity_types ADD COLUMN contacted_flag character(1);";
		$queries[] = "ALTER TABLE customers ADD COLUMN ts_first_contacted time with time zone;";
		$queries[] = "update customer_activity_types set contacted_flag='a' where name='Phone Call';";
 		$queries[] = "update customer_activity_types set contacted_flag='o' where name='Fax';";
 		$queries[] = "update customer_activity_types set contacted_flag='o' where name='Email';";
 		$queries[] = "update customer_activity_types set contacted_flag='o' where name='Sent Letter';";
 		$queries[] = "update customer_activity_types set contacted_flag='a' where name='Met in Person';";
		$queries[] = "update customer_activity_types set name='Mail/Letter' where name='Sent Mail';";
		$queries[] = "ALTER TABLE email_threads	ALTER COLUMN num_messages SET DEFAULT (0)::smallint;";
		$queries[] = "ALTER TABLE email_threads ALTER COLUMN num_attachments SET DEFAULT '0';";	
		$queries[] = "CREATE TABLE email_filters
					  (
					   id serial, 
					   \"name\" character varying(256) NOT NULL, 
					   kw_subject text, 
					   kw_to text, 
					   kw_from text, 
					   kw_body text, 
					   email_user integer, 
					   f_active boolean DEFAULT 't', 
					   act_mark_read boolean DEFAULT 'f', 
					   act_move_to integer, 
					   CONSTRAINT email_filters_id_pkey PRIMARY KEY (id), 
					   CONSTRAINT email_filters_euid_fkey FOREIGN KEY (email_user) REFERENCES email_users (id)    ON UPDATE CASCADE ON DELETE CASCADE
					  ) 
					  WITHOUT OIDS;";	
	}

	/*******************************************************************************
	*	8/4/2009 -Log errors
	********************************************************************************/
	$thisrev = 2.154;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE app_log_error
						(
						  id serial NOT NULL,
						  ts_entered timestamp with time zone,
						  user_name character varying(128),
						  page text,
						  error text,
						  orig_query text,
						  CONSTRAINT app_log_error_pkey PRIMARY KEY (id)
						)
						WITHOUT OIDS;";	
	}

	/*******************************************************************************
	*	8/6/2009 - Fix event security
	********************************************************************************/
	$thisrev = 2.155;
	if ($revision < $thisrev)
	{
		$queries[] = "update security_aclp set name='View Public Events' where name='View Events'";	
	}


	/*******************************************************************************
	*	8/6/2009 - Fix event security
	********************************************************************************/
	$thisrev = 2.156;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE customer_publish
						(
						   customer_id integer, 
						   \"password\" character varying(128), 
						   f_files_view boolean, 
						   f_files_upload boolean DEFAULT 'f', 
						   f_files_modify boolean DEFAULT 'f', 
						   f_modify_contact boolean DEFAULT 'f', 
						   f_update_image boolean DEFAULT 'f', 
						   CONSTRAINT customer_publish_pkey PRIMARY KEY (customer_id), 
						   CONSTRAINT customer_publish_cid_fkey FOREIGN KEY (customer_id) REFERENCES customers (id) ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITHOUT OIDS;";	
	}

	/*******************************************************************************
	*	8/12/2009 - Create some database indexes to speed things up a bit
	********************************************************************************/
	$thisrev = 2.157;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE INDEX email_threads_mailbox_idx ON email_threads (mailbox_id);
						analyze email_threads;";	

		$queries[] = "CREATE INDEX email_messages_mailbox_idx ON email_messages (mailbox_id);
					CREATE INDEX email_messages_thread_idx ON email_messages (thread);
					CREATE INDEX email_messages_msgid_idx ON email_messages (message_id);
					CREATE INDEX email_messages_fseen_idx ON email_messages (flag_seen);
					CREATE INDEX email_messages_fstar_idx ON email_messages (flag_star);
					CREATE INDEX email_messages_in_reply_to_idx ON email_messages (in_reply_to);
					analyze email_messages;";	

		$queries[] = "CREATE INDEX email_message_attachments_mid_idx ON email_message_attachments (message_id);
						CREATE INDEX email_message_attachments_dsp_idx ON email_message_attachments (disposition);
						analyze email_message_attachments;";	

		$queries[] = "CREATE INDEX email_email_message_original_mid_idx ON email_message_original (message_id);
						analyze email_message_original;";	
		
		$queries[] = "CREATE INDEX email_mailboxes_email_user_idx ON email_mailboxes (email_user);
						CREATE INDEX email_mailboxes_email_userspecial_idx ON email_mailboxes (email_user,flag_special);
						analyze email_mailboxes;";	

		$queries[] = "CREATE INDEX calendar_events_calendar_idx ON calendar_events (calendar);
						CREATE INDEX calendar_events_sharing_idx ON calendar_events (sharing);
						CREATE INDEX calendar_events_recur_id_idx ON calendar_events (recur_id);
						analyze calendar_events;";	
	

		$queries[] = "CREATE INDEX calendar_events_recurring_calendar_idx ON calendar_events_recurring (calendar);
						CREATE INDEX calendar_events_recurring_sharing_idx ON calendar_events_recurring (sharing);
						analyze calendar_events;";	

		$queries[] = "CREATE INDEX customer_activity_email_id_idx ON customer_activity (email_id);
						CREATE INDEX customer_activity_customer_id_idx ON customer_activity (customer_id);
						CREATE INDEX customer_activity_user_id_idx ON customer_activity (user_id);
						analyze customer_activity;";	

		$queries[] = "CREATE INDEX customers_account_id_idx ON customers (account_id);
						CREATE INDEX customers_status_id_idx ON customers (status_id);
						CREATE INDEX customers_stage_id_idx ON customers (stage_id);
						analyze customers;";	
	}

	/*******************************************************************************
	*	8/13/2009 - Fix problems with project bug default data
	********************************************************************************/
	$thisrev = 2.158;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_bug_status ADD COLUMN f_closed boolean DEFAULT false;";
	}

	/*******************************************************************************
	*	8/13/2009 - Fix problems with project bug default data
	********************************************************************************/
	$thisrev = 2.159;
	if ($revision < $thisrev)
	{
		$queries[] = "SELECT setval('public.project_bug_types_id_seq', 500, true);";
		$queries[] = "SELECT setval('public.project_bug_status_id_seq', 500, true);";
		$queries[] = "SELECT setval('public.project_bug_severity_id_seq', 500, true);";
		$queries[] = "ALTER TABLE calendar_events ADD COLUMN inv_eid integer;";
		$queries[] = "ALTER TABLE calendar_events ADD COLUMN inv_rev integer;";
		$queries[] = "ALTER TABLE calendar_events ADD COLUMN inv_uid text;";
	}


	/*******************************************************************************
	*	8/20/2009 - Add parent reference to email mailboxes
	********************************************************************************/
	$thisrev = 2.160;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_mailboxes ADD COLUMN parent_box integer;";
		$queries[] = "ALTER TABLE email_mailboxes ADD CONSTRAINT email_mailboxes_pid_fkey FOREIGN KEY (parent_box) 
						REFERENCES email_mailboxes (id) ON UPDATE CASCADE ON DELETE CASCADE;";
	}

	/*******************************************************************************
	*	8/26/2009 - Event Coordination
	********************************************************************************/
	$thisrev = 2.161;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE calendar_event_coord
						(
						  id serial NOT NULL,
						  \"name\" character varying(128),
						  notes text,
						  f_closed boolean DEFAULT false,
						  user_id integer,
						  CONSTRAINT calendar_event_coord_id_fkey PRIMARY KEY (id),
						  CONSTRAINT calendar_event_coord_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (OIDS=FALSE);";
		$queries[] = "CREATE TABLE calendar_event_coord_times
						(
						  id serial NOT NULL,
						  cec_id integer,
						  ts_start timestamp with time zone,
						  ts_end timestamp with time zone,
						  CONSTRAINT calendar_event_coord_times_id_pkey PRIMARY KEY (id),
						  CONSTRAINT calendar_event_coord_times_ccid_fkey FOREIGN KEY (cec_id)
							  REFERENCES calendar_event_coord (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (OIDS=FALSE);";

		$queries[] = "CREATE TABLE calendar_event_coord_att_times
						(
						  id serial NOT NULL,
						  att_id integer,
						  time_id integer,
						  response integer,
						  CONSTRAINT calendar_event_coord_att_times_id_pkey PRIMARY KEY (id),
						  CONSTRAINT calendar_event_coord_att_times_aid_fkey FOREIGN KEY (att_id)
							  REFERENCES calendar_events_attendees (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT calendar_event_coord_att_times_tid_fkey FOREIGN KEY (time_id)
							  REFERENCES calendar_event_coord_times (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (OIDS=FALSE);";

		$queries[] = "ALTER TABLE calendar_events_attendees ADD COLUMN cec_id integer;
					  ALTER TABLE calendar_events_attendees ADD CONSTRAINT calendar_events_attendees_cecid_fkey 
						FOREIGN KEY (cec_id) REFERENCES calendar_event_coord (id)    ON UPDATE CASCADE ON DELETE CASCADE;";

		$queries[] = "ALTER TABLE calendar_event_comments ADD COLUMN cec_id integer;
					  ALTER TABLE calendar_event_comments ADD CONSTRAINT calendar_event_comments_cecid_fkey FOREIGN KEY (cec_id) 
						REFERENCES calendar_event_coord (id)    ON UPDATE CASCADE ON DELETE CASCADE;";
	}

	/*******************************************************************************
	*	8/31/2009 - More on event collaboration
	********************************************************************************/
	$thisrev = 2.162;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE calendar_event_coord ADD COLUMN \"location\" text;";
		$queries[] = "ALTER TABLE calendar_event_coord ADD COLUMN event_id integer;
					  ALTER TABLE calendar_event_coord ADD CONSTRAINT calendar_event_coord_eid_fkey FOREIGN KEY (event_id) REFERENCES calendar_events (id)    
						ON UPDATE CASCADE ON DELETE SET NULL;";
	}

	/*******************************************************************************
	*	9/2/2009 - Add messaging backend & lead/opportunity objections
	********************************************************************************/
	$thisrev = 2.163;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE messages
						(
						  id serial NOT NULL,
						  subject text,
						  body text,
						  user_id integer,
						  ts_entered timestamp with time zone,
						  CONSTRAINT messages_id_pkey PRIMARY KEY (id),
						  CONSTRAINT messages_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL
						)
						WITHOUT OIDS;";

		$queries[] = "CREATE TABLE message_comments
						(
						  id serial NOT NULL,
						  subject text,
						  body text,
						  ts_entered timestamp with time zone,
						  user_id integer,
						  content_type character varying(64),
						  CONSTRAINT message_comments_id_pkey PRIMARY KEY (id),
						  CONSTRAINT message_comments_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";


			$queries[] = "CREATE TABLE customer_objections
							(
							   id serial, 
							   account_id integer, 
							   \"name\" character varying(256), 
							   description text, 
							   color character(8),
							   sort_order smallint DEFAULT 1, 
							   CONSTRAINT customer_objections_id_pkey PRIMARY KEY (id), 
							   CONSTRAINT customer_objections_aid_fkey FOREIGN KEY (account_id) REFERENCES accounts (id) ON UPDATE CASCADE ON DELETE CASCADE
							) 
							WITHOUT OIDS;";

			$queries[] = "ALTER TABLE customer_opportunities ADD COLUMN objection_id integer;
						  ALTER TABLE customer_opportunities ADD CONSTRAINT customer_opportunities_objid_fkey FOREIGN KEY (objection_id) 
							REFERENCES customer_objections (id) ON UPDATE CASCADE ON DELETE CASCADE;";





	}


	/*******************************************************************************
	*	9/4/2009 - Add messaging backend & lead/opportunity objections
	********************************************************************************/
	$thisrev = 2.164;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE project_tasks_recurring
						(
						  id serial NOT NULL,
						  \"interval\" smallint DEFAULT 1::smallint,
						  \"day\" smallint,
						  relative_type smallint,
						  relative_section smallint,
						  week_days boolean[],
						  \"name\" character varying(128),
						  notes text,
						  \"type\" smallint,
						  recur_start date,
						  recur_end date,
						  \"month\" integer,
						  priority integer,
						  user_id bigint,
						  user_status smallint DEFAULT 1,
						  date_start date,
						  date_end date,
						  CONSTRAINT project_tasks_recurring_recurring_pkey PRIMARY KEY (id),
						  CONSTRAINT project_tasks_recurring_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL
						)
						WITH (OIDS=FALSE);";

		$queries[] = "CREATE TABLE project_tasks_recurring_ex
						(
						  id serial NOT NULL,
						  recurring_id integer,
						  exception_date date,
						  task_id integer,
						  CONSTRAINT project_tasks_recurring_ex_pkey PRIMARY KEY (id),
						  CONSTRAINT project_tasks_recurring_ex_tid_fkey FOREIGN KEY (task_id)
							  REFERENCES project_tasks (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL,
						  CONSTRAINT project_tasks_recurring_ex_event_fkey FOREIGN KEY (recurring_id)
							  REFERENCES project_tasks_recurring (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (OIDS=FALSE);";

		$queries[] = "ALTER TABLE project_tasks ADD COLUMN recur_id integer;
					  ALTER TABLE project_tasks ADD CONSTRAINT project_tasks_rid_fkey FOREIGN KEY (recur_id) REFERENCES project_tasks_recurring (id)    
						ON UPDATE CASCADE ON DELETE CASCADE;";

		$queries[] = "ALTER TABLE email_threads ADD COLUMN f_flagged boolean DEFAULT false;";
		$queries[] = "ALTER TABLE email_messages RENAME flag_star  TO flag_flagged;";
	}

	/*******************************************************************************
	*	9/5/2009 - Add project to recurring tasks
	********************************************************************************/
	$thisrev = 2.165;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_tasks_recurring ADD COLUMN project integer;
					  ALTER TABLE project_tasks_recurring ADD CONSTRAINT project_tasks_recurring_pid_fkey FOREIGN KEY (project) 
				 		REFERENCES projects (id) ON UPDATE CASCADE ON DELETE CASCADE;";
	}

	/*******************************************************************************
	*	9/11/2009 - Fix thread index problem
	********************************************************************************/
	$thisrev = 2.166;
	if ($revision < $thisrev)
	{
		$queries[] = "update email_messages set f_indexed='f'";
	}

	/*******************************************************************************
	*	9/22/2009 - Add modified time
	********************************************************************************/
	$thisrev = 2.167;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE calendar_events ADD COLUMN ts_updated timestamp with time zone;";
		$queries[] = "update calendar_events set ts_updated = date_start;";
	}

	/*******************************************************************************
	*	10/12/2009 - Add ts_updated to messages and tasks
	********************************************************************************/
	$thisrev = 2.168;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_tasks ADD COLUMN ts_updated timestamp with time zone;";
		$queries[] = "update project_tasks set ts_updated = date_entered;";
		$queries[] = "ALTER TABLE email_messages ADD COLUMN ts_updated timestamp with time zone;";
		$queries[] = "update email_messages set ts_updated = message_date;";
	}

	/*******************************************************************************
	*	10/19/2009 - Add line numbers and boundary to parsed emails
	********************************************************************************/
	$thisrev = 2.169;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_messages ADD COLUMN bcc text;";
	}

	/*******************************************************************************
	*	10/27/2009 - Add line numbers and boundary to parsed emails
	********************************************************************************/
	$thisrev = 2.170;
	if ($revision < $thisrev)
	{
		$queries[] = "alter table customer_leads add column phone2 character varying(128);";
		$queries[] = "alter table customer_leads add column phone3 character varying(128);";
	}

	/*******************************************************************************
	*	10/29/2009 - Create custom customer custom tabs
	********************************************************************************/
	$thisrev = 2.171;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE customer_tabs
						(
						  id serial NOT NULL,
						  account_id integer,
						  name character varying(128),
						  iframe_src text,
						  CONSTRAINT customer_tabs_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_tabs_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITHOUT OIDS;";
	}

	/*******************************************************************************
	*	10/29/2009 - Create custom customer custom tabs
	********************************************************************************/
	$thisrev = 2.172;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE accounts add COLUMN ts_started timestamp;";
	}

	/*******************************************************************************
	*	11/4/2009 - Add customer to tickets
	********************************************************************************/
	$thisrev = 2.173;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_bugs ADD COLUMN customer_id integer;";
		$queries[] = "ALTER TABLE project_bugs ADD CONSTRAINT project_bugs_custid_fkey FOREIGN KEY (customer_id) REFERENCES customers (id)
						ON UPDATE CASCADE ON DELETE SET NULL;";
	}

	/*******************************************************************************
	*	11/11/2009 - Add customer to tickets
	********************************************************************************/
	$thisrev = 2.174;
	if ($revision < $thisrev)
	{
		$queries[] = "drop table project_files;";
		$queries[] = "drop table project_file_categories;";
		$queries[] = "CREATE TABLE project_files
						(
						   id serial, 
						   file_id integer, 
						   project_id integer, 
						   bug_id integer, 
						   task_id integer, 
						   CONSTRAINT project_files_pkey PRIMARY KEY (id), 
						   CONSTRAINT project_files_pid_fkey FOREIGN KEY (project_id) REFERENCES projects (id) ON UPDATE CASCADE ON DELETE CASCADE, 
						   CONSTRAINT project_files_bid_fkey FOREIGN KEY (bug_id) REFERENCES project_bugs (id) ON UPDATE CASCADE ON DELETE CASCADE, 
						   CONSTRAINT project_files_tid_fkey FOREIGN KEY (task_id) REFERENCES project_tasks (id) ON UPDATE CASCADE ON DELETE CASCADE, 
						   CONSTRAINT project_files_fid_fkey FOREIGN KEY (file_id) REFERENCES user_files (id) ON UPDATE CASCADE ON DELETE CASCADE
						) ;";
	}

	/*******************************************************************************
	*	11/12/2009 - Add user teams and delete archive capabilities
	********************************************************************************/
	$thisrev = 2.175;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE user_teams
						(
						   id serial, 
						   \"name\" character varying(256), 
						   account_id integer, 
						   parent_id integer,
						   CONSTRAINT user_teams_pkey PRIMARY KEY (id), 
						   CONSTRAINT user_teams_aid_fkey FOREIGN KEY (account_id) REFERENCES accounts (id) ON UPDATE CASCADE ON DELETE CASCADE,
						   CONSTRAINT user_teams_parent_fkey FOREIGN KEY (parent_id) REFERENCES user_teams (id) ON UPDATE CASCADE ON DELETE CASCADE
						) ";
		$queries[] = "ALTER TABLE users ADD COLUMN team_id integer;";
		$queries[] = "ALTER TABLE users ADD CONSTRAINT users_teamid_fkey FOREIGN KEY (team_id) 
						REFERENCES user_teams (id) ON UPDATE CASCADE ON DELETE SET NULL;";

		$queries[] = "ALTER TABLE user_files ADD COLUMN revision integer DEFAULT 1;
					  ALTER TABLE user_files ADD COLUMN f_deleted boolean DEFAULT false;";
		$queries[] = "ALTER TABLE user_files ADD COLUMN ts_deleted timestamp with time zone;";

		$queries[] = "ALTER TABLE user_file_categories ADD COLUMN f_deleted boolean DEFAULT false;";
	}
	
	/*******************************************************************************
	*	12/11/2009 - Update the length of calendar event name and location fields
	********************************************************************************/
	$thisrev = 2.176;
	if ($revision < $thisrev)
	{

		$queries[] = "ALTER TABLE calendar_events ALTER \"name\" TYPE character varying(512);";
		$queries[] = "ALTER TABLE calendar_events ALTER \"location\" TYPE character varying(512);";
	}

	/*******************************************************************************
	*	12/17/2009 - Add workflow vars
	********************************************************************************/
	$thisrev = 2.177;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE workflows
						(
						  id serial NOT NULL,
						  account_id integer,
						  \"name\" character varying(256),
						  notes text,
						  object_type character varying(256),
						  f_on_create boolean DEFAULT false,
						  f_on_update boolean DEFAULT false,
						  f_on_delete boolean DEFAULT false,
						  f_singleton boolean DEFAULT true,
						  f_allow_manual boolean DEFAULT true,
						  CONSTRAINT workflows_id_pkey PRIMARY KEY (id),
						  CONSTRAINT workflows_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE NO ACTION ON DELETE NO ACTION
						)
						WITH (
						  OIDS=FALSE
						);";
		$queries[] = "CREATE TABLE workflow_actions
						(
						  id serial NOT NULL,
						  \"name\" character varying(256),
						  when_interval smallint,
						  when_unit smallint,
						  send_email_fid integer,
						  update_field character varying(128),
						  update_to text,
						  create_object character varying(256),
						  start_wfid integer,
						  stop_wfid integer,
						  workflow_id integer,
						  \"type\" smallint NOT NULL,
						  CONSTRAINT workflow_actions_id_pkey PRIMARY KEY (id),
						  CONSTRAINT workflow_actions_send_email_fid_fkey FOREIGN KEY (send_email_fid)
							  REFERENCES user_files (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT workflow_actions_start_wfid_fkey FOREIGN KEY (start_wfid)
							  REFERENCES workflows (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT workflow_actions_stop_wfid_fkey FOREIGN KEY (stop_wfid)
							  REFERENCES workflows (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT workflow_actions_workflow_id_fkey FOREIGN KEY (workflow_id)
							  REFERENCES workflows (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "CREATE TABLE workflow_object_values
						(
						  id serial NOT NULL,
						  field character varying(128),
						  \"value\" text,
						  f_array boolean DEFAULT false,
						  parent_id integer,
						  action_id integer,
						  CONSTRAINT workflow_object_values_id_pkey PRIMARY KEY (id),
						  CONSTRAINT workflow_object_values_aid_fkey FOREIGN KEY (action_id)
							  REFERENCES workflow_actions (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT workflow_object_values_parent_fkey FOREIGN KEY (parent_id)
							  REFERENCES workflow_object_values (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "CREATE TABLE workflow_conditions
						(
						  id serial NOT NULL,
						  blogic character varying(64),
						  field_name character varying(256),
						  \"operator\" character varying(128),
						  cond_value text,
						  workflow_id integer,
						  wf_action_id integer,
						  CONSTRAINT workflow_conditions_pkey PRIMARY KEY (id),
						  CONSTRAINT workflow_conditions_afid_fkey FOREIGN KEY (wf_action_id)
							  REFERENCES workflow_actions (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT workflow_conditions_wfid_fkey FOREIGN KEY (workflow_id)
							  REFERENCES workflows (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";		

		$queries[] = "CREATE TABLE workflow_action_schedule
						(
						  id serial NOT NULL,
						  action_id integer,
						  ts_execute timestamp with time zone NOT NULL,
						  CONSTRAINT workflow_action_schedule_pkey PRIMARY KEY (id),
						  CONSTRAINT workflow_action_schedule_aid_fkey FOREIGN KEY (action_id)
							  REFERENCES workflow_actions (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";		
	}

	/*******************************************************************************
	*	12/23/2009 - Update Workflow and ANT Objects
	********************************************************************************/
	$thisrev = 2.178;
	if ($revision < $thisrev)
	{
		$queries[] = "alter table workflows add column f_active boolean default false;";
	}

	/*******************************************************************************
	*	12/28/2009 - Add ANT Objects
	********************************************************************************/
	$thisrev = 2.179;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE app_object_types
						(
						  id serial NOT NULL,
						  \"name\" character varying(256),
						  title character varying(256),
						  object_table character varying(260),
						  CONSTRAINT app_object_types_pkey PRIMARY KEY (id),
						  CONSTRAINT app_object_types_uname UNIQUE (name)
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "CREATE TABLE app_object_type_fields
						(
						  id serial NOT NULL,
						  type_id integer,
						  \"name\" character varying(128),
						  title character varying(128),
						  \"type\" character varying(32),
						  subtype character varying(32),
						  fkey_table_key character varying(128),
						  fkey_multi_tbl character varying(256),
						  fkey_multi_this character varying(128),
						  fkey_multi_ref character varying(128),
						  fkey_table_title character varying(128),
						  CONSTRAINT app_object_type_fields_pkey PRIMARY KEY (id),
						  CONSTRAINT app_object_type_fields_tid_fkey FOREIGN KEY (type_id)
							  REFERENCES app_object_types (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "CREATE TABLE workflow_instances
						(
						  id serial NOT NULL,
						  object_type_id integer,
						  object_uid integer NOT NULL,
						  ts_started timestamp with time zone,
						  ts_completed timestamp with time zone,
						  f_completed boolean,
						  workflow_id integer,
						  CONSTRAINT workflow_instances_pkey PRIMARY KEY (id),
						  CONSTRAINT workflow_instances_type_fkey FOREIGN KEY (object_type_id)
							  REFERENCES app_object_types (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT workflow_instances_wid_fkey FOREIGN KEY (workflow_id)
							  REFERENCES workflows (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "ALTER TABLE workflow_action_schedule ADD COLUMN instance_id integer;";
		$queries[] = "ALTER TABLE workflow_action_schedule ADD CONSTRAINT workflow_action_schedule_inst_fkey 
						FOREIGN KEY (instance_id) REFERENCES workflow_instances (id) ON UPDATE CASCADE ON DELETE CASCADE;";
	}

	/*******************************************************************************
	*	12/28/2009 - Add ANT Objects
	********************************************************************************/
	$thisrev = 2.180;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('customer', 'Customer', 'customers');";
	}

	/*******************************************************************************
	*	12/28/2009 - Add mote ANT Objects
	********************************************************************************/
	$thisrev = 2.181;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('opportunity', 'Opportunity', 'customer_opportunities');";
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('lead', 'Leads', 'customer_leads');";
	};

	/*******************************************************************************
	*	1/14/2009 - Expand the size of the fields for contacts and activities
	********************************************************************************/
	$thisrev = 2.182;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE contacts_personal ALTER first_name TYPE character varying(128);
					  ALTER TABLE contacts_personal ALTER last_name TYPE character varying(128);
					  ALTER TABLE contacts_personal ALTER phone_home TYPE character varying(128);
					  ALTER TABLE contacts_personal ALTER phone_work TYPE character varying(128);
					  ALTER TABLE contacts_personal ALTER phone_cell TYPE character varying(128);";
		$queries[] = "ALTER TABLE contacts_personal_act ALTER \"name\" TYPE character varying(512);";
	}	

	/*******************************************************************************
	*	1/22/2010 - add video email
	********************************************************************************/
	$thisrev = 2.183;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE email_video_messages
						(
						  id serial NOT NULL,
						  user_id integer,
						  file_id integer,
						  title text,
						  subtitle text,
						  message text,
						  footer text,
						  theme character varying(64),
						  \"name\" character varying(256),
						  CONSTRAINT email_video_messages_id_fkey PRIMARY KEY (id),
						  CONSTRAINT email_video_messages_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "CREATE TABLE email_video_message_buttons
					(
					  id serial NOT NULL,
					  label text,
					  link text,
					  message_id integer,
					  CONSTRAINT email_video_message_buttons_id_pkey PRIMARY KEY (id),
					  CONSTRAINT email_video_message_buttons_mid_fkey FOREIGN KEY (message_id)
						  REFERENCES email_video_messages (id) MATCH SIMPLE
						  ON UPDATE CASCADE ON DELETE CASCADE
					)
					WITH (
					  OIDS=FALSE
					);";
	}	

	/*******************************************************************************
	*	1/23/2010 - Added logo to video email
	********************************************************************************/
	$thisrev = 2.184;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_video_messages ADD COLUMN logo_file_id integer;";
		$queries[] = "ALTER TABLE email_video_messages ADD CONSTRAINT email_video_messages_lfid_fkey 
						FOREIGN KEY (logo_file_id) REFERENCES user_files (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE SET NULL;";
		$queries[] = "ALTER TABLE email_video_messages ADD CONSTRAINT email_video_messages_fid_fkey 
						FOREIGN KEY (file_id) REFERENCES user_files (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE SET NULL;";
		$queries[] = "ALTER TABLE email_video_messages ADD COLUMN f_template_video boolean DEFAULT false;";
	}	


	/*******************************************************************************
	*	1/23/2010 - Add folder ID
	********************************************************************************/
	$thisrev = 2.185;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customers ADD COLUMN folder_id bigint;";
		$queries[] = "ALTER TABLE customers ADD CONSTRAINT customers_folder_fkey FOREIGN KEY (folder_id) 
							REFERENCES user_file_categories (id) ON UPDATE CASCADE ON DELETE SET NULL;";

		$queries[] = "CREATE TABLE dc_database_objects
						(
						   id bigint, 
						   \"name\" character varying(256) NOT NULL, 
						   database_id integer, 
						   CONSTRAINT dc_database_objects_pkey PRIMARY KEY (id), 
						   CONSTRAINT dc_database_objects_dbid_fkey FOREIGN KEY (database_id) REFERENCES dc_databases (id) ON UPDATE CASCADE ON DELETE RESTRICT
						) 
						WITH (
						  OIDS = FALSE
						);";
	}	

	/*******************************************************************************
	*	1/23/2010 - Fix above - missing serial type for id
	********************************************************************************/
	$thisrev = 2.186;
	if ($revision < $thisrev)
	{
		$queries[] = "DROP TABLE dc_database_objects;";
		$queries[] = "CREATE TABLE dc_database_objects
						(
						   id serial, 
						   \"name\" character varying(256) NOT NULL, 
						   database_id integer, 
						   CONSTRAINT dc_database_objects_pkey PRIMARY KEY (id), 
						   CONSTRAINT dc_database_objects_dbid_fkey FOREIGN KEY (database_id) REFERENCES dc_databases (id) ON UPDATE CASCADE ON DELETE RESTRICT
						) 
						WITH (
						  OIDS = FALSE
						);";
	}	

	/*******************************************************************************
	*	1/29/2010 - Datacenter updates
	********************************************************************************/
	$thisrev = 2.188;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE dc_databases ADD COLUMN f_publish boolean DEFAULT false;";
		$queries[] = "ALTER TABLE dc_databases ADD COLUMN scope character varying(32);";
	}	

	/*******************************************************************************
	*	2/6/2010 - Add sort order to fields
	********************************************************************************/
	$thisrev = 2.189;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE app_object_type_fields ADD COLUMN sort_order integer DEFAULT '0';";
		$queries[] = "CREATE TABLE dc_database_folders
						(
						   id serial, 
						   \"name\" character varying(256) NOT NULL, 
						   folder_id integer, 
						   database_id integer, 
						   CONSTRAINT dc_database_folders_pkey PRIMARY KEY (id), 
						   CONSTRAINT dc_database_folders_fid_fkey FOREIGN KEY (folder_id) REFERENCES user_file_categories (id) ON UPDATE CASCADE ON DELETE SET NULL,
						   CONSTRAINT dc_database_objects_dbid_fkey FOREIGN KEY (database_id) REFERENCES dc_databases (id) ON UPDATE CASCADE ON DELETE RESTRICT
						) 
						WITH (
						  OIDS = FALSE
					    );";

		$queries[] = "CREATE TABLE dc_database_calendars
						(
						   id serial, 
						   calendar_id integer, 
						   database_id integer, 
						   CONSTRAINT dc_database_calendars_pkey PRIMARY KEY (id), 
						   CONSTRAINT dc_database_calendars_fid_fkey FOREIGN KEY (calendar_id) REFERENCES calendars (id) ON UPDATE CASCADE ON DELETE SET NULL,
						   CONSTRAINT dc_database_objects_dbid_fkey FOREIGN KEY (database_id) REFERENCES dc_databases (id) ON UPDATE CASCADE ON DELETE RESTRICT
						) 
						WITH (
						  OIDS = FALSE
						);";
	}	

	/*******************************************************************************
	*	2/8/2010 - Add customer association types
	********************************************************************************/
	$thisrev = 2.190;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE customer_association_types
						(
						   id serial, 
						   \"name\" character varying(256), 
						   f_child boolean DEFAULT false,
						   CONSTRAINT customer_association_types_pkey PRIMARY KEY (id)
						) 
						WITH (
						  OIDS = FALSE
						)
						;";

		$queries[] = "ALTER TABLE customer_associations ADD COLUMN type_id integer;
					  ALTER TABLE customer_associations ADD CONSTRAINT customer_associations_tid_fkey FOREIGN KEY (type_id) 
						REFERENCES customer_association_types (id) ON UPDATE CASCADE ON DELETE SET NULL;";
	}	

	/*******************************************************************************
	*	2/8/2010 - Add customer association types
	********************************************************************************/
	$thisrev = 2.191;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_labels ADD COLUMN parent_id integer;";
	}	

	/*******************************************************************************
	*	2/9/2010 - Add objects to cutom customer tabs and parent_field
	********************************************************************************/
	$thisrev = 2.192;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_tabs ADD COLUMN obj_type character varying(256);";
		$queries[] = "ALTER TABLE customer_tabs ADD COLUMN obj_ref character varying(256);";
		// Used to define heiarchy for fkey_multi and fkey fields
		$queries[] = "ALTER TABLE app_object_type_fields ADD COLUMN parent_field character varying(128);";
	}	

	/*******************************************************************************
	*	2/13/2010 - Add views
	********************************************************************************/
	$thisrev = 2.193;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE app_object_type_fields ADD COLUMN f_readonly boolean default false";
		$queries[] = "ALTER TABLE app_object_types ADD COLUMN revision integer DEFAULT 1;";
		$queries[] = "ALTER TABLE app_object_type_fields ADD COLUMN autocreate boolean DEFAULT false;";
		$queries[] = "ALTER TABLE app_object_type_fields ADD COLUMN autocreatebase text;";
		$queries[] = "ALTER TABLE app_object_type_fields ADD COLUMN autocreatename character varying(256);";

		$queries[] = "CREATE TABLE app_object_views
						(
						   id serial, 
						   \"name\" character varying(256), 
						   description text, 
						   f_default boolean DEFAULT false, 
						   user_id integer, 
						   object_type_id integer, 
						   CONSTRAINT app_object_views_pkey PRIMARY KEY (id), 
						   CONSTRAINT app_object_views_otid_fkey FOREIGN KEY (object_type_id) REFERENCES app_object_types (id) ON UPDATE CASCADE ON DELETE CASCADE, 
						   CONSTRAINT app_object_views_uid_fkey FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITH (
						  OIDS = FALSE
					  );";

		$queries[] = "CREATE TABLE app_object_view_conditions
						(
						  id serial NOT NULL,
						  view_id integer,
						  field_id integer,
						  blogic character varying(128) NOT NULL,
						  \"operator\" character varying(128) NOT NULL,
						  \"value\" text,
						  CONSTRAINT app_object_view_conditions_conditions_pkey PRIMARY KEY (id),
						  CONSTRAINT app_object_view_conditions_conditions_fid_fkey FOREIGN KEY (field_id)
							  REFERENCES app_object_type_fields (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT app_object_view_conditions_conditions_vid_fkey FOREIGN KEY (view_id)
							  REFERENCES app_object_views (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "CREATE TABLE app_object_view_orderby
						(
						   id serial, 
						   view_id integer, 
						   field_id integer, 
						   \"order\" character varying(32) NOT NULL, 
						   sort_order smallint NOT NULL DEFAULT 0, 
						   CONSTRAINT app_object_view_orderby_pkey PRIMARY KEY (id), 
						   CONSTRAINT app_object_view_orderby_vid_fkey FOREIGN KEY (view_id) REFERENCES app_object_views (id) ON UPDATE CASCADE ON DELETE CASCADE, 
						   CONSTRAINT app_object_view_orderby_fid_fkey FOREIGN KEY (field_id) REFERENCES app_object_type_fields (id) ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITH (
						  OIDS = FALSE
						);";

		$queries[] = "CREATE TABLE app_object_view_fields
						(
						   id serial, 
						   view_id integer NOT NULL, 
						   field_id integer NOT NULL, 
						   sort_order smallint NOT NULL DEFAULT 0, 
						   CONSTRAINT app_object_view_fields_pkey PRIMARY KEY (id), 
						   CONSTRAINT app_object_view_fields_vid_fkey FOREIGN KEY (view_id) REFERENCES app_object_views (id) ON UPDATE CASCADE ON DELETE CASCADE, 
						   CONSTRAINT app_object_view_fields_fid_fkey FOREIGN KEY (field_id) REFERENCES app_object_type_fields (id) ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITH (
						  OIDS = FALSE
						);";

		$queries[] = "CREATE TABLE app_object_field_defaults
						(
						   id serial, 
						   field_id integer NOT NULL, 
						   on_event character varying(32) NOT NULL, 
						   \"value\" text, 
						   \"coalesce\" text, 
						   CONSTRAINT app_object_field_defaults_pkey PRIMARY KEY (id), 
						   CONSTRAINT app_object_field_defaults_fid_fkey FOREIGN KEY (field_id) REFERENCES app_object_type_fields (id) 
						   	ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITH (
						  OIDS = FALSE
						);";
	}	

	/*******************************************************************************
	*	2/17/2010 - Add async_states - move from file system to db
	********************************************************************************/
	$thisrev = 2.194;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE async_states
						(
						   id serial, 
						   \"key\" text, 
						   \"value\" text, 
						   CONSTRAINT async_states_pkey PRIMARY KEY (id)
						) 
						WITH (
						  OIDS = FALSE
						);";
	}	

	/*******************************************************************************
	*	2/21/2010 - Add async_states - move from file system to db
	********************************************************************************/
	$thisrev = 2.195;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE app_object_field_options
						(
						   id serial, 
						   field_id integer NOT NULL, 
						   key text, 
						   \"value\" text, 
						   CONSTRAINT app_object_field_options_pkey PRIMARY KEY (id), 
						   CONSTRAINT app_object_field_options_fid_fkey FOREIGN KEY (field_id) REFERENCES app_object_type_fields (id) 
						   	ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITH (
						  OIDS = FALSE
						);";
		$queries[] = "ALTER TABLE app_object_type_fields ADD COLUMN f_system boolean DEFAULT false;";
	}	

	/*******************************************************************************
	*	2/22/2010 - Add email accounts
	********************************************************************************/
	$thisrev = 2.196;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE email_accounts
						(
						   id serial, 
						   \"name\" character varying(512) NOT NULL, 
						   address character varying(512) NOT NULL, 
						   reply_to character varying(512), 
						   user_id integer NOT NULL, 
						   f_default boolean default false,
						   CONSTRAINT email_accounts_pkey PRIMARY KEY (id), 
						   CONSTRAINT email_accounts_uid_fkey FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITH (
						  OIDS = FALSE
						);";
	}	
	$thisrev = 2.197;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_accounts ADD COLUMN signature_id integer;
					  ALTER TABLE email_accounts ADD CONSTRAINT signature_id_sig_fkey FOREIGN KEY (signature_id) REFERENCES email_signatures (id) ON UPDATE CASCADE ON DELETE SET NULL;";
	}	

	/*******************************************************************************
	*	2/26/2010 - Add import templates
	********************************************************************************/
	$thisrev = 2.198;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE app_object_imp_templates
					(
					   id serial, 
					   type_id integer, 
					   \"name\" character varying(256), 
					   user_id integer, 
					   CONSTRAINT app_object_imp_templates_pkey PRIMARY KEY (id), 
					   CONSTRAINT app_object_imp_templates_tid_fkey FOREIGN KEY (type_id) REFERENCES app_object_types (id) ON UPDATE NO ACTION ON DELETE NO ACTION, 
					   CONSTRAINT app_object_imp_templates_uid_fkey FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE
					) 
					WITH (
					  OIDS = FALSE
					)
					;";

		$queries[] = "CREATE TABLE app_object_imp_maps
					(
					  id serial NOT NULL,
					  template_id integer,
					  col_name character varying(256) NOT NULL,
					  property_name character varying(256) NOT NULL,
					  CONSTRAINT app_object_imp_maps_pkey PRIMARY KEY (id),
					  CONSTRAINT app_object_imp_maps_tid_fkey FOREIGN KEY (template_id)
						  REFERENCES app_object_imp_templates (id) MATCH SIMPLE
						  ON UPDATE NO ACTION ON DELETE NO ACTION
					)
					WITH (
					  OIDS=FALSE
					);";
	}

	/*******************************************************************************
	*	2/26/2010 - Add import templates
	********************************************************************************/
	$thisrev = 2.199;
	if ($revision < $thisrev)
	{
	}

	/*******************************************************************************
	*	2/26/2010 - Add import templates
	********************************************************************************/
	$thisrev = 2.200;
	if ($revision < $thisrev)
	{
		$queries[] = "alter table user_files add column f_ans_skipped boolean default false;";
	}

	/*******************************************************************************
	*	3/8/2010 - Add flag for cleaned up antfs
	********************************************************************************/
	$thisrev = 2.201;
	if ($revision < $thisrev)
	{
		$queries[] = "alter table user_files add column f_ans_cleaned boolean default false;";
	}

	/*******************************************************************************
	*	3/9/2010 - Add inherit id for dacls
	********************************************************************************/
	$thisrev = 2.202;
	if ($revision < $thisrev)
	{
		$queries[] = "alter table security_dacl add column inherit_from bigint;";
		$queries[] = "alter table security_dacl add column inherit_from_old bigint;";
	}

	/*******************************************************************************
	*	3/10/2010 - Modify contacts
	********************************************************************************/
	$thisrev = 2.203;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE contacts_personal ADD COLUMN ts_entered timestamp with time zone;";
		$queries[] = "ALTER TABLE contacts_personal ADD COLUMN ts_changed timestamp with time zone;";
		$queries[] = "insert into app_object_types(name, title, object_table, revision) values('contact_personal', 'Personal Contact', 'contacts_personal', '0');";
	}

	/*******************************************************************************
	 *	3/11/2010 - Add infocenter document attachments and relations
	 *				Add required flag to object fields
	********************************************************************************/
	$thisrev = 2.204;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE ic_document_relation_mem
							(
							  id serial NOT NULL,
							  document_id integer NOT NULL,
							  related_id integer NOT NULL,
							  CONSTRAINT ic_document_relation_mem_pkey PRIMARY KEY (id),
							  CONSTRAINT ic_document_relation_mem_did_fkey FOREIGN KEY (document_id)
								  REFERENCES ic_documents (id) MATCH SIMPLE
								  ON UPDATE CASCADE ON DELETE CASCADE,
							  CONSTRAINT ic_document_relation_mem_toid_fkey FOREIGN KEY (related_id)
								  REFERENCES ic_documents (id) MATCH SIMPLE
								  ON UPDATE CASCADE ON DELETE CASCADE
							)
							WITHOUT OIDS;";

		$queries[] = "ALTER TABLE ic_documents ADD COLUMN video_file_id integer;";
		$queries[] = "ALTER TABLE ic_documents ADD CONSTRAINT video_file_id_fid_fkey 
						FOREIGN KEY (video_file_id) REFERENCES user_files (id) MATCH SIMPLE ON UPDATE CASCADE ON DELETE SET NULL;";
	}

	/*******************************************************************************
	 *	3/17/2010 - Add fax number to leads
	********************************************************************************/
	$thisrev = 2.205;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_leads ADD COLUMN fax character varying(64);";
	}

	/*******************************************************************************
	 *	3/18/2010 - Add required and view mask to ANT Objects
	********************************************************************************/
	$thisrev = 2.206;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE app_object_type_fields ADD COLUMN f_required bool DEFAULT false;";
		$queries[] = "ALTER TABLE app_object_type_fields ADD COLUMN mask character varying(64);";

		$queries[] = "insert into app_object_types(name, title, object_table)
						values('contact', 'Contact', 'contacts_personal');";
	}

	/*******************************************************************************
	 *	3/22/2010 - Add some attributes to users
	********************************************************************************/
	$thisrev = 2.207;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE users ADD COLUMN title character varying(512);";
		$queries[] = "ALTER TABLE users ALTER phone TYPE character varying(64);
						ALTER TABLE users ALTER COLUMN phone SET STATISTICS -1;";
		$queries[] = "ALTER TABLE users ADD COLUMN manager_id integer;";
		$queries[] = "ALTER TABLE users ADD CONSTRAINT users_mid_fkey FOREIGN KEY (manager_id) REFERENCES users (id)    
						ON UPDATE CASCADE ON DELETE SET NULL;";
	}


	/*******************************************************************************
	 *	3/22/2010 - Add object references and add tasks
	********************************************************************************/
	$thisrev = 2.208;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE app_object_associations
						(
						  id serial NOT NULL,
						  type_id integer,
						  object_id bigint,
						  assoc_type_id integer,
						  assoc_object_id bigint,
						  CONSTRAINT app_object_associations_pkey PRIMARY KEY (id),
						  CONSTRAINT app_object_associations_tid_fkey FOREIGN KEY (type_id)
							  REFERENCES app_object_types (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT app_object_associations_atid_fkey FOREIGN KEY (assoc_type_id)
							  REFERENCES app_object_types (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						);";

		$queries[] = "insert into app_object_types(name, title, object_table)
						values('task', 'Task', 'project_tasks');";	

		$queries[] = "insert into app_object_types(name, title, object_table)
						values('case', 'Case', 'project_bugs');";	
	}

	/*******************************************************************************
	 *	3/24/2010 - Add object field filter which is an array serialized
	********************************************************************************/
	$thisrev = 2.209;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE app_object_type_fields ADD COLUMN filter text;";
	}


	/*******************************************************************************
	 *	3/24/2010 - Add view filters
	********************************************************************************/
	$thisrev = 2.210;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE app_object_views ADD COLUMN filter_key text;";
	}

	/*******************************************************************************
	 *	3/26/2010 - Add view filters
	********************************************************************************/
	$thisrev = 2.211;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('user', 'User', 'users');";	
	}

	/*******************************************************************************
	 *	3/26/2010 - Add view filters
	********************************************************************************/
	$thisrev = 2.212;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_lead_rating ADD COLUMN color character(6);";
	}


	/*******************************************************************************
	 *	4/3/2010 - Fix type id - set to integer for customers
	********************************************************************************/
	$thisrev = 2.213;
	if ($revision < $thisrev)
	{
		$queries[] = "alter TABLE customers ALTER type_id TYPE integer;";
		$queries[] = "ALTER TABLE customers ALTER COLUMN type_id SET STATISTICS -1;";
	}

	/*******************************************************************************
	 *	4/3/2010 - Add customer_id to recurring
	********************************************************************************/
	$thisrev = 2.214;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_tasks_recurring ADD COLUMN customer_id integer;";
		$queries[] = "ALTER TABLE project_tasks_recurring ADD CONSTRAINT project_tasks_recurring_custid_fkey FOREIGN KEY (customer_id) 
						REFERENCES customers (id)
						ON UPDATE CASCADE ON DELETE SET NULL;";
	}

	/*******************************************************************************
	 *	4/9/2010 - Add video mail templates
	********************************************************************************/
	$thisrev = 2.215;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE email_video_message_themes
						(
						   id serial, 
						   name character varying(256) NOT NULL, 
						   html text, 
						   header_file_id integer, 
						   footer_file_id integer, 
						   button_off_file_id integer,
						   background_color character varying(8),
						   scope character varying(32),
						   user_id integer, 
						   CONSTRAINT email_video_message_themes_pkey PRIMARY KEY (id), 
						   CONSTRAINT email_video_message_themes_fidhdr_fkey FOREIGN KEY (header_file_id) 
						   	REFERENCES user_files (id) ON UPDATE CASCADE ON DELETE SET NULL,
						   CONSTRAINT email_video_message_themes_fidftr_fkey FOREIGN KEY (footer_file_id) 
						   	REFERENCES user_files (id) ON UPDATE CASCADE ON DELETE SET NULL,
						   CONSTRAINT email_video_message_themes_fidbtn_fkey FOREIGN KEY (button_off_file_id) 
						   	REFERENCES user_files (id) ON UPDATE CASCADE ON DELETE SET NULL,
						   CONSTRAINT email_video_message_themes_uid_fkey FOREIGN KEY (user_id) 
						   	REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE
						);";
	}

	/*******************************************************************************
	 *	4/12/2010 - Purge app-log-error
	********************************************************************************/
	$thisrev = 2.216;
	if ($revision < $thisrev)
	{
		$queries[] = "delete from app_log_error;";
	}

	/*******************************************************************************
	 *	4/14/2010 - Add customer_number to accounts table
	********************************************************************************/
	$thisrev = 2.217;
	if ($revision < $thisrev)
	{
		$queries[] = "alter table accounts add column customer_number character varying(512);";
	}

	/*******************************************************************************
	 *	4/14/2010 - Add customer_number to accounts table
	********************************************************************************/
	$thisrev = 2.218;
	if ($revision < $thisrev)
	{
		$queries[] = "alter table users add column customer_number character varying(512);";
		$queries[] = "alter table accounts add column f_active boolean DEFAULT 't';";
	}

	/*******************************************************************************
	 *	4/22/2010 - Move assigned_to to owner for cases (project_bugs)
	********************************************************************************/
	$thisrev = 2.219;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_bugs RENAME COLUMN assigned_to TO owner_id;";
		$queries[] = "delete from app_object_type_fields where name='assigned_to' and type_id in 
						(select id from app_object_types where name='case');";
		$queries[] = "update user_groups set name='Creator Owner' where id='-2';";
	}

	/*******************************************************************************
	 *	4/23/2010 - Change the size of the file names in user_files
	********************************************************************************/
	$thisrev = 2.220;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE user_files ALTER file_name TYPE character varying(512);";
		$queries[] = "ALTER TABLE user_files ALTER file_title TYPE character varying(512);";
	}

	/*******************************************************************************
	 *	4/23/2010 - Add label_fields column
	********************************************************************************/
	$thisrev = 2.221;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE app_object_types ADD COLUMN label_fields character varying(512);";
	}

	/*******************************************************************************
	 *	4/26/2010 - Add inherit_fields column so relationships can inherit certain
	 *				fields which will be stored in field:filed:field format
	********************************************************************************/
	$thisrev = 2.223;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_association_types ADD COLUMN inherit_fields text;";
	}

	/*******************************************************************************
	 *	4/27/2010 - Add inherit_fields column so relationships can inherit certain
	 *				fields which will be stored in field:filed:field format
	********************************************************************************/
	$thisrev = 2.224;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE app_object_types ADD COLUMN form_layout_xml text;";
	}

	/*******************************************************************************
	 *	4/30/2010 - Add report object
	********************************************************************************/
	$thisrev = 2.225;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('report', 'Report', 'reports');";	

		$queries[] = "CREATE TABLE reports
						(
						  id serial NOT NULL,
						  \"name\" character varying(512),
						  description text,
						  obj_type character varying(256),
						  chart_type character varying(128),
						  f_display_table boolean DEFAULT true,
						  f_display_chart boolean DEFAULT true,
						  f_calculate boolean DEFAULT true,
						  dim_one_fld character varying(256),
						  dim_one_grp character varying(32),
						  dim_two_fld character varying(256),
						  dim_two_grp character varying(32),
						  measure_one_fld character varying(256),
						  measure_one_agg character varying(32),
						  ts_created timestamp with time zone,
						  ts_updated timestamp with time zone,
						  scope character varying(32),
						  owner_id integer,
						  CONSTRAINT reports_pkey PRIMARY KEY (id),
						  CONSTRAINT reports_uid_fkey FOREIGN KEY (owner_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL
						)";

		$queries[] = "ALTER TABLE app_object_views ADD COLUMN report_id integer;";	
		$queries[] = "ALTER TABLE app_object_views ADD CONSTRAINT app_object_views_rid_fkey FOREIGN KEY (report_id) 
						REFERENCES reports (id) ON UPDATE CASCADE ON DELETE CASCADE;";	
	}


	/*******************************************************************************
	 *	4/30/2010 - Add shipping and billing address
	********************************************************************************/
	$thisrev = 2.226;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customers ADD COLUMN shipping_street character varying(256);";
		$queries[] = "ALTER TABLE customers ADD COLUMN shipping_street2 character varying(256);";
		$queries[] = "ALTER TABLE customers ADD COLUMN shipping_city character varying(128);";
		$queries[] = "ALTER TABLE customers ADD COLUMN shipping_state character varying(64);";
		$queries[] = "ALTER TABLE customers ADD COLUMN shipping_zip character varying(32);";

		$queries[] = "ALTER TABLE customers ADD COLUMN billing_street character varying(256);";
		$queries[] = "ALTER TABLE customers ADD COLUMN billing_street2 character varying(256);";
		$queries[] = "ALTER TABLE customers ADD COLUMN billing_city character varying(128);";
		$queries[] = "ALTER TABLE customers ADD COLUMN billing_state character varying(64);";
		$queries[] = "ALTER TABLE customers ADD COLUMN billing_zip character varying(32);";
	}

	/*******************************************************************************
	 *	5/14/2010 - Remove primary contact field
	********************************************************************************/
	$thisrev = 2.227;
	if ($revision < $thisrev)
	{
		$queries[] = "delete from app_object_type_fields where name='accoc_primary_contact' and type_id in (select id from app_object_types where name='customer')";
	}

	/*******************************************************************************
	 *	5/18/2010 - Add user form
	********************************************************************************/
	$thisrev = 2.228;
	if ($revision < $thisrev)
	{
		$queries[] = "delete from app_object_type_fields where name='accoc_primary_contact' and type_id in (select id from app_object_types where name='customer')";
	}

	/*******************************************************************************
	 *	5/22/2010 - Add login_name to customers for external authentication
	 ********************************************************************************/
	$thisrev = 2.229;
	if ($revision < $thisrev)
	{
		$queries[] = "DELETE FROM xml_feed_publish where publish_to like '%CPageCache%' or publish_to like '%//%';";
	}

	/*******************************************************************************
	 *	5/22/2010 - Add login_name to customers for external authentication
	 ********************************************************************************/
	$thisrev = 2.230;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_publish add column username character varying(256);";
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('activity', 'Activity', 'activity');";
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('calendar_events', 'Event', 'calendar_events');";
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('email_message', 'Email', 'email_messages');";
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('invoice', 'Invoice', 'customer_invoices');";

		$queries[] = "CREATE TABLE customer_ccards
						(
						  id serial NOT NULL,
						  ccard_name character varying(512),
						  ccard_number character varying(256),
						  ccard_exp_month smallint,
						  ccard_exp_year smallint,
						  ccard_type character varying(32),
						  customer_id integer,
						  enc_ver character varying(16),
						  f_default bool DEFAULT false,
						  CONSTRAINT customer_ccards_pkey PRIMARY KEY (id),
						  CONSTRAINT customer_ccards_cid_fkey FOREIGN KEY (customer_id)
							  REFERENCES customers (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL
						)";

		$queries[] = "ALTER TABLE email_messages alter column message_date type timestamp with time zone;";
		$queries[] = "ALTER TABLE users alter column checkin_timestamp type timestamp with time zone;";
		$queries[] = "ALTER TABLE app_object_associations ADD COLUMN field character varying(256);";


		$queries[] = "CREATE TABLE activity_types
						(
						  id serial NOT NULL,
						  \"name\" character varying(256),
						  obj_type character varying(256),
						  CONSTRAINT activity_types_pkey PRIMARY KEY (id)
						)";

		$queries[] = "CREATE TABLE activity
						(
						  id bigserial NOT NULL,
						  \"name\" character varying(256),
						  type_id integer,
						  notes text,
						  ts_entered timestamp with time zone,
						  ts_updated timestamp with time zone,
						  user_id integer,
						  user_name character varying(256),
						  f_readonly boolean DEFAULT false,
						  direction character(1),
						  CONSTRAINT activity_pkey PRIMARY KEY (id),
						  CONSTRAINT activity_tid_fkey FOREIGN KEY (type_id)
							  REFERENCES activity_types (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT activity_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE NO ACTION ON DELETE SET NULL
						)";	
	}

	/*******************************************************************************
	 *	6/04/2010 - Add colors to groups
	 ********************************************************************************/
	$thisrev = 2.232;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_labels ADD COLUMN color character(6);";
		
	}

	/*******************************************************************************
	 *	6/04/2010 - Add sort order and color
	 ********************************************************************************/
	$thisrev = 2.233;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_status ADD COLUMN color character(6);";
		$queries[] = "ALTER TABLE customer_stages ADD COLUMN color character(6);";
		$queries[] = "ALTER TABLE customer_lead_queues ADD COLUMN sort_order smallint;";
	}

	/*******************************************************************************
	 *	6/07/2010 - Add sort order and color
	 ********************************************************************************/
	$thisrev = 2.234;
	if ($revision < $thisrev)
	{
		$queries[] = "update app_object_types set name='calendar_event' where name='calendar_events';";
	}


	/*******************************************************************************
	 *	6/07/2010 - Add sort order and color
	 ********************************************************************************/
	$thisrev = 2.235;
	if ($revision < $thisrev)
	{
		$routines[] = "realtodouble.php";
	}

	/*******************************************************************************
	 *	6/08/2010 - Add discissions and comments
	 ********************************************************************************/
	$thisrev = 2.237;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE comments
						(
						  id bigserial NOT NULL,
						  ts_entered timestamp with time zone,
						  entered_by character varying(512),
						  owner_id integer,
						  \"comment\" text,
						  CONSTRAINT comments_pkey PRIMARY KEY (id),
						  CONSTRAINT comments_uid_fkey FOREIGN KEY (owner_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL
						  )";

	}

	/*******************************************************************************
	 *	6/11/2010
	 ********************************************************************************/
	$thisrev = 2.239;
	if ($revision < $thisrev)
	{
	}

	/*******************************************************************************
	 *	6/18/2010
	 ********************************************************************************/
	$thisrev = 2.240;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE workflow_action_schedule ADD COLUMN inprogress smallint DEFAULT '0';";
		$queries[] = "CREATE INDEX app_object_associations_type_idx
   						ON app_object_associations (type_id ASC NULLS LAST);";
		$queries[] = "CREATE INDEX app_object_associations_oid_idx
   						ON app_object_associations (object_id ASC NULLS LAST);";
		/*
		$queries[] = "CREATE INDEX app_object_associations_fld_idx
   						ON app_object_associations (type_id ASC NULLS LAST, object_id ASC NULLS LAST, field ASC NULLS LAST);";
		$queries[] = "CREATE INDEX app_object_associations_assocobj_idx
   						ON app_object_associations (assoc_type_id ASC NULLS LAST, assoc_object_id ASC NULLS LAST);";
		 */
	}

	/*******************************************************************************
	 *	6/18/2010
	 ********************************************************************************/
	$thisrev = 2.241;
	if ($revision < $thisrev)
	{
		/*
		$queries[] = "DROP INDEX app_object_associations_assocobj_idx;";
		$queries[] = "CREATE INDEX app_object_associations_assocobj_idx
						  ON app_object_associations
						  USING btree
						  (assoc_type_id, assoc_object_id, field);";
		 */
	}

	/*******************************************************************************
	 *	6/22/2010 - add f_public col for publishing comments
	 ********************************************************************************/
	$thisrev = 2.242;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_bug_comments ADD COLUMN f_public boolean DEFAULT false;";
	}

	/*******************************************************************************
	 *	6/23/2010 - add f_public col for publishing comments
	 ********************************************************************************/
	$thisrev = 2.244;
	if ($revision < $thisrev)
	{
		$queries[] = "delete from app_object_type_fields where (name='user_name' or name='f_readonly') and type_id in 
						(select id from app_object_types where name='calendar_event');";
		$queries[] = "ALTER TABLE app_object_associations ADD COLUMN field character varying(128);";
		$queries[] = "ALTER TABLE app_object_associations ADD COLUMN field_id bigint;";
		$queries[] = "ALTER TABLE app_object_associations ADD CONSTRAINT app_object_associations_fid_fkey 
						FOREIGN KEY (field_id) REFERENCES app_object_type_fields (id) ON UPDATE CASCADE ON DELETE CASCADE;";
		$queries[] = "DROP INDEX app_object_associations_fld_idx;";
		$queries[] = "CREATE INDEX app_object_associations_fld_idx
   						ON app_object_associations (type_id ASC NULLS LAST, object_id ASC NULLS LAST, field_id ASC NULLS LAST);";
		$queries[] = "CREATE INDEX app_object_associations_assocobj_idx
   						ON app_object_associations (assoc_type_id ASC NULLS LAST, assoc_object_id ASC NULLS LAST, field_id ASC NULLS LAST);";
		$queries[] = "CREATE INDEX app_object_associations_refobj_idx
					  ON app_object_associations USING btree (type_id, field_id, assoc_type_id);";
	}

	/*******************************************************************************
	 *	6/23/2010 - remove text field
	 ********************************************************************************/
	$thisrev = 2.245;
	if ($revision < $thisrev)
	{
		$queries[] = "delete from app_object_type_fields where (name='user_name' or name='f_readonly') and type_id in 
						(select id from app_object_types where name='calendar_event');";

		$queries[] = "ALTER TABLE app_object_associations DROP COLUMN field;";

		$queries[] = "CREATE INDEX user_files_catid_idx
						  ON user_files
						  USING btree
						  (category_id);";
		$queries[] = "CREATE INDEX user_files_deleted_idx
						  ON user_files
						  USING btree
						  (f_deleted);";

		$routines[] = "eventcopyassoc.php";
	}

	/*******************************************************************************
	 *	6/23/2010 - copy customer activities to system activities
	 ********************************************************************************/
	$thisrev = 2.246;
	if ($revision < $thisrev)
	{
		$routines[] = "custacttosysact.php";
	}

	/*******************************************************************************
	 *	6/23/2010 - copy optional field values
	 ********************************************************************************/
	$thisrev = 2.247;
	if ($revision < $thisrev)
	{
		$routines[] = "custfldoptvals.php";
	}

	/*******************************************************************************
	 *	6/23/2010 - Purge bad fields
	 ********************************************************************************/
	$thisrev = 2.248;
	if ($revision < $thisrev)
	{
		$queries[] = "delete from app_object_type_fields where (name='inv_eid' or name='inv_uid') and type_id in 
						(select id from app_object_types where name='email_message');";
	}

	/*******************************************************************************
	 *	6/23/2010 - Purge bad fields
	 ********************************************************************************/
	$thisrev = 2.249;
	if ($revision < $thisrev)
	{
		$queries[] = "update themes set f_default='f';";
		$queries[] = "update themes set f_default='t' where app_name='skygrey';";
	}

	/*******************************************************************************
	 *	6/23/2010 - Purge bad fields
	 ********************************************************************************/
	$thisrev = 2.250;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE app_object_type_frm_layouts
						(
						   id serial, 
						   team_id integer, 
						   form_layout_xml text, 
						   type_id integer, 
						   CONSTRAINT app_object_type_frm_layouts_pkey PRIMARY KEY (id), 
						   CONSTRAINT app_object_type_frm_layouts_team_fkey FOREIGN KEY (team_id) 
						   		REFERENCES user_teams (id) ON UPDATE CASCADE ON DELETE CASCADE, 
						   CONSTRAINT app_object_type_frm_layouts_type_fkey FOREIGN KEY (type_id) 
								REFERENCES app_object_types (id) ON UPDATE CASCADE ON DELETE CASCADE
						)";
	}

	/*******************************************************************************
	 *	7/2/2010 - Fix labels
	 ********************************************************************************/
	$thisrev = 2.251;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_labels DROP COLUMN account_id;";
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('project', 'Project', 'projects');";
	}


	/*******************************************************************************
	 *	7/3/2010 - Fix labels
	 ********************************************************************************/
	$thisrev = 2.253;
	if ($revision < $thisrev)
	{
		$queries[] = "update themes set title='Social' where app_name='wireframe'";
	}

	/*******************************************************************************
	 *	7/6/2010 - Fix labels
	 ********************************************************************************/
	$thisrev = 2.254;
	if ($revision < $thisrev)
	{
		$queries[] = "update calendar_events_recurring set type='4' where type='6'";
	}

	/*******************************************************************************
	 *	7/12/2010 - Add comment object type
	 ********************************************************************************/
	$thisrev = 2.255;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('comment', 'Comment', 'comments');";
	}

	/*******************************************************************************
	 *	7/20/2010 - copy case comments
	 ********************************************************************************/
	$thisrev = 2.257;
	if ($revision < $thisrev)
	{
		$queries[] = "alter table comments add column notified text;";
	}

	/*******************************************************************************
	 *	7/23/2010 - Update foreign constraints for personal contacts
	 ********************************************************************************/
	$thisrev = 2.258;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE contacts_personal DROP CONSTRAINT contacts_personal_birthday_spouse_evnt_fkey;";
		$queries[] = "ALTER TABLE contacts_personal DROP CONSTRAINT contacts_personal_birthday_evnt_fkey;";
		$queries[] = "ALTER TABLE contacts_personal DROP CONSTRAINT contacts_personal_anniversary_evnt_fkey;";
		$queries[] = "ALTER TABLE contacts_personal ADD CONSTRAINT contacts_personal_birthday_evnt_fkey FOREIGN KEY (birthday_evnt) REFERENCES calendar_events_recurring (id) ON UPDATE CASCADE ON DELETE SET NULL;";
		$queries[] = "ALTER TABLE contacts_personal ADD CONSTRAINT contacts_personal_birthday_sp_evnt_fkey FOREIGN KEY (birthday_spouse_evnt) REFERENCES calendar_events_recurring (id) ON UPDATE CASCADE ON DELETE SET NULL;";
		$queries[] = "ALTER TABLE contacts_personal ADD CONSTRAINT contacts_personal_ann_evnt_fkey FOREIGN KEY (anniversary_evnt) REFERENCES calendar_events_recurring (id) ON UPDATE CASCADE ON DELETE SET NULL;";
	}

	/*******************************************************************************
	 *	7/23/2010 - Add discission
	 ********************************************************************************/
	$thisrev = 2.259;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE discussions
						(
						  id bigserial NOT NULL,
						  \"name\" character varying(512),
						  message text,
						  notified text,
						  ts_entered timestamp with time zone,
						  ts_updated timestamp with time zone,
						  owner_id integer,
						  CONSTRAINT discussions_pkey PRIMARY KEY (id),
						  CONSTRAINT discussions_uid_fkey FOREIGN KEY (owner_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE SET NULL
						);";

		$queries[] = "insert into app_object_types(name, title, object_table)
						values('discussion', 'Discussion', 'discussions');";
	}

	/*******************************************************************************
	 *	7/24/2010 - Copy all discussions
	 ********************************************************************************/
	$thisrev = 2.261;
	if ($revision < $thisrev)
	{
		$routines[] = "projectmsgtoobj.php";
	}

	/*******************************************************************************
	 *	7/23/2010 - Add discission
	 ********************************************************************************/
	$thisrev = 2.262;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE workflows ADD COLUMN f_on_daily boolean DEFAULT false;";

		$queries[] = "CREATE TABLE project_groups
						(
						  id serial NOT NULL,
						  parent_id integer,
						  \"name\" character varying(128),
						  color character varying(8),
						  CONSTRAINT project_groups_pkey PRIMARY KEY (id)
						)";

		$queries[] = "CREATE TABLE project_group_mem
						(
						  id serial NOT NULL,
						  project_id integer NOT NULL,
						  group_id integer NOT NULL,
						  CONSTRAINT project_group_mem_pkey PRIMARY KEY (id),
						  CONSTRAINT project_group_mem_gid_fkey FOREIGN KEY (group_id)
							  REFERENCES project_groups (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT project_group_mem_pid_fkey FOREIGN KEY (project_id)
							  REFERENCES projects (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)";
	}

	/*******************************************************************************
	 *	7/28/2010 - Add discission
	 ********************************************************************************/
	$thisrev = 2.263;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_bug_types ADD COLUMN sort_order smallint DEFAULT 1::smallint;";
		$queries[] = "ALTER TABLE project_bug_types ADD COLUMN color character(6);";
		$queries[] = "ALTER TABLE project_bug_status ADD COLUMN sort_order smallint DEFAULT 1::smallint;";
		$queries[] = "ALTER TABLE project_bug_status ADD COLUMN color character(6);";
		$queries[] = "ALTER TABLE project_bug_severity ADD COLUMN sort_order smallint DEFAULT 1::smallint;";
		$queries[] = "ALTER TABLE project_bug_severity ADD COLUMN color character(6);";
		$queries[] = "ALTER TABLE projects ADD COLUMN folder_id bigint;";

		$routines[] = "projectmovecodes.php";
	}

	/*******************************************************************************
	 *	7/29/2010 - Add project milestones
	 ********************************************************************************/
	$thisrev = 2.264;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('project_milestone', 'Milestone', 'project_milestones');";
	}

	/*******************************************************************************
	 *	7/30/2010 - Add project milestones
	 ********************************************************************************/
	$thisrev = 2.265;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE comments ADD COLUMN user_name_cache character varying(256);";
	}

	/*******************************************************************************
	 *	8/3/2010 - Update calendar events
	 ********************************************************************************/
	$thisrev = 2.266;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE calendar_events_attendees ADD COLUMN attendee_obj character varying(256);";
		$queries[] = "ALTER TABLE calendar_events_attendees ADD COLUMN last_invitation_rev integer DEFAULT '0';";
		$queries[] = "update calendar_events_attendees set attendee_obj='user:'||user_id where user_id is not null and attendee_obj is null;";
		$queries[] = "update calendar_events_attendees set attendee_obj='contact_personal:'||contact_id where contact_id is not null and attendee_obj is null;";
		$queries[] = "update calendar_events_attendees set attendee_obj=user_email where user_email is not null and attendee_obj is null;";
	}

	/*******************************************************************************
	 *	08/05/2010 - Add object index for searching
	 ********************************************************************************/
	$thisrev = 2.267;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE app_object_index
						(
						  id bigserial NOT NULL,
						  type_id integer,
						  object_id bigint,
						  object_revision integer,
						  keywords text,
						  snippet character varying(512),
						  private_owner_id integer,
						  ts_entered timestamp with time zone,
						  CONSTRAINT app_object_index_pkey PRIMARY KEY (id),
						  CONSTRAINT app_object_index_tid_fkey FOREIGN KEY (type_id)
							  REFERENCES app_object_types (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT app_object_index_ownerid_fkey FOREIGN KEY (private_owner_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						);";

		$queries[] = "CREATE INDEX app_object_index_type_idx
   						ON app_object_index (type_id ASC NULLS LAST);";

		$queries[] = "CREATE INDEX app_object_index_oid_idx
   						ON app_object_index (object_id ASC NULLS LAST);";
	}

	/*******************************************************************************
	 *	8/9/2010 - Set event timestamps
	 ********************************************************************************/
	$thisrev = 2.268;
	if ($revision < $thisrev)
	{
		$routines[] = "eventsetts.php";
	}

	/*******************************************************************************
	 *	8/9/2010 - Set event timestamps
	 ********************************************************************************/
	$thisrev = 2.270;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('note', 'Note', 'user_notes');";
		$queries[] = "ALTER TABLE user_notes_categories ADD COLUMN parent_id integer;";
	}

	/*******************************************************************************
	 *	8/10/2010 - Add color
	 ********************************************************************************/
	$thisrev = 2.271;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE user_notes_categories ADD COLUMN color character(6);";
		$queries[] = "ALTER TABLE user_notes ADD COLUMN ts_entered timestamp with time zone;";
		$queries[] = "update user_notes set ts_entered=date_added;";
	}

	/*******************************************************************************
	 *	8/10/2010 - Add color
	 ********************************************************************************/
	$thisrev = 2.272;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE contacts_personal_labels ADD COLUMN parent_id integer;";
		$queries[] = "ALTER TABLE contacts_personal_labels ADD COLUMN color character(6);";

		$queries[] = "ALTER TABLE contacts_personal ADD COLUMN folder_id bigint;";
		$queries[] = "ALTER TABLE contacts_personal ADD CONSTRAINT contacts_personal_folder_fkey FOREIGN KEY (folder_id) 
							REFERENCES user_file_categories (id) ON UPDATE CASCADE ON DELETE SET NULL;";
	}

	/*******************************************************************************
	 *	8/29/2010 - email changes
	 ********************************************************************************/
	$thisrev = 2.273;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_message_attachments ADD COLUMN \"header\" text;";
		$queries[] = "ALTER TABLE email_message_attachments ADD COLUMN body bytea;";

		$queries[] = "ALTER TABLE email_message_attachments ADD COLUMN boundary character varying(512);";
		$queries[] = "ALTER TABLE email_messages ADD COLUMN body text;";
		$queries[] = "ALTER TABLE email_messages ADD COLUMN parse_rev smallint DEFAULT '0';";

		$queries[] = "insert into app_object_types(name, title, object_table)
						values('email_thread', 'Email Thread', 'email_threads');";
	}

	/*******************************************************************************
	 *	8/29/2010 - email changes
	 ********************************************************************************/
	$thisrev = 2.274;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_mailboxes ADD COLUMN color character(6);";
	}

	/*******************************************************************************
	 *	9/8/2010 - email changes
	 ********************************************************************************/
	$thisrev = 2.275;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_threads ADD COLUMN ts_delivered timestamp with time zone;";
		$queries[] = "UPDATE email_threads set ts_delivered=time_updated where ts_delivered is null;";
	}

	/*******************************************************************************
	 *	9/10/2010 - email changes
	 ********************************************************************************/
	$thisrev = 2.276;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_threads ADD COLUMN revision integer;";
		$queries[] = "ALTER TABLE email_threads ADD COLUMN owner_id integer;";
		$queries[] = "ALTER TABLE email_threads ADD CONSTRAINT email_thread_oid_fkey 
						FOREIGN KEY (owner_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE;";
		$queries[] = "ALTER TABLE email_messages ADD COLUMN owner_id integer;";
		$queries[] = "ALTER TABLE email_messages ADD CONSTRAINT email_message_oid_fkey 
						FOREIGN KEY (owner_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE;";
		$queries[] = "CREATE INDEX email_threads_owner_idx ON email_threads (owner_id);
						analyze email_threads;";	
		$routines[] = "emailsetowner.php";
	}

	/*******************************************************************************
	 *	9/11/2010 - email changes
	 ********************************************************************************/
	$thisrev = 2.277;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('content_feed', 'Content Feed', 'xml_feeds');";

		$queries[] = "insert into app_object_types(name, title, object_table)
						values('content_feed_post', 'Feed Post', 'xml_feed_posts');";

		$queries[] = "ALTER TABLE xml_feed_posts ALTER time_entered TYPE timestamp with time zone;";
		$queries[] = "ALTER TABLE xml_feed_posts ALTER time_expires TYPE timestamp with time zone;";
		$queries[] = "CREATE TABLE xml_feed_groups
						(
						  id serial NOT NULL,
						  parent_id integer,
						  \"name\" character varying(128),
						  color character varying(8),
						  CONSTRAINT xml_feed_groups_pkey PRIMARY KEY (id)
						);";

		$queries[] = "CREATE TABLE xml_feed_group_mem
						(
						  id serial NOT NULL,
						  feed_id integer NOT NULL,
						  group_id integer NOT NULL,
						  CONSTRAINT xml_feed_group_mem_pkey PRIMARY KEY (id),
						  CONSTRAINT xml_feed_group_mem_gid_fkey FOREIGN KEY (group_id)
							  REFERENCES xml_feed_groups (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT xml_feed_group_mem_pid_fkey FOREIGN KEY (feed_id)
							  REFERENCES xml_feeds (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						);";
	}

	/*******************************************************************************
	 *	9/11/2010 - email changes
	 ********************************************************************************/
	$thisrev = 2.278;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_threads add column f_deleted bool default 'f';";
		$queries[] = "ALTER TABLE email_messages add column f_deleted bool default 'f';";
		
		$queries[] = "update email_threads set f_deleted='t' 
						where mailbox_id in (select id from email_mailboxes where flag_special='t' and name='Trash');";
		$queries[] = "update email_messages set f_deleted='t' 
						where mailbox_id in (select id from email_mailboxes where flag_special='t' and name='Trash');";

		$queries[] = "CREATE INDEX email_threads_deleted_idx
						  ON email_threads (f_deleted) where f_deleted is not true;";
	
		$queries[] = "CREATE INDEX email_messages_deleted_idx
						  ON email_messages (f_deleted) where f_deleted is not true;";
	}

	/*******************************************************************************
	 *	9/14/2010 - Add fields to feeds
	 ********************************************************************************/
	$thisrev = 2.279;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE xml_feeds add column sort_by character varying(128)";
		$queries[] = "ALTER TABLE xml_feeds add column limit_num character varying(8)";
		$queries[] = "alter table customer_invoices add column revision integer default '1';";
		$queries[] = "update customer_invoices set revision=revision+1;";
	}

	/*******************************************************************************
	 *	9/16/2010 - Create indexes
	 ********************************************************************************/
	$thisrev = 2.281;
	if ($revision < $thisrev)
	{
		$queries[] = "DROP INDEX email_threads_deleted_idx;";
		$queries[] = "CREATE INDEX email_threads_deleted_idx
						ON email_threads (f_deleted) where f_deleted is not true;";
		$queries[] = "DROP INDEX email_messages_deleted_idx;";
		$queries[] = "CREATE INDEX email_messages_deleted_idx
						ON email_messages (f_deleted) where f_deleted is not true;";
		$queries[] = "DROP INDEX email_messages_fseen_idx;";
		$queries[] = "DROP INDEX email_messages_fstar_idx;";
		$queries[] = "CREATE INDEX email_messages_fflag_idx
 						ON email_messages (flag_flagged) where flag_flagged is true;";
		$queries[] = "CREATE INDEX email_messages_new_idx
					   ON email_messages (mailbox_id, flag_seen, f_deleted) 
					   WHERE f_deleted is not true and flag_seen is not true;";
		$queries[] = "ALTER TABLE customers add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX customers_deleted_idx
						ON customers (f_deleted) where f_deleted is not true;";
		$queries[] = "ALTER TABLE customer_opportunities add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX customer_opportunities_deleted_idx
						ON customer_opportunities (f_deleted) where f_deleted is not true;";
		$queries[] = "ALTER TABLE customer_leads add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX customer_leads_deleted_idx
						ON customer_leads (f_deleted) where f_deleted is not true;";
		$queries[] = "ALTER TABLE contacts_personal add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX contacts_personal_deleted_idx
						ON contacts_personal (f_deleted) where f_deleted is not true;";
		$queries[] = "ALTER TABLE project_tasks add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX project_tasks_deleted_idx
						ON project_tasks (f_deleted) where f_deleted is not true;";
		$queries[] = "ALTER TABLE project_bugs add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX project_bugs_deleted_idx
						ON project_bugs (f_deleted) where f_deleted is not true;";
		$queries[] = "ALTER TABLE reports add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX reports_deleted_idx
						ON reports (f_deleted) where f_deleted is not true;";
		$queries[] = "ALTER TABLE calendar_events add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX calendar_events_deleted_idx
						ON calendar_events (f_deleted) where f_deleted is not true;";
		$queries[] = "ALTER TABLE customer_invoices add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX customer_invoices_deleted_idx
						ON customer_invoices (f_deleted) where f_deleted is not true;";
		$queries[] = "ALTER TABLE projects add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX projects_deleted_idx
						ON projects (f_deleted) where f_deleted is not true;";
		$queries[] = "ALTER TABLE comments add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX comments_deleted_idx
						ON comments (f_deleted) where f_deleted is not true;";
		$queries[] = "ALTER TABLE discussions add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX discussions_deleted_idx
						ON discussions (f_deleted) where f_deleted is not true;";
		$queries[] = "ALTER TABLE user_notes add column f_deleted bool default 'f';";
		$queries[] = "CREATE INDEX user_notes_deleted_idx
						ON user_notes (f_deleted) where f_deleted is not true;";
		$queries[] = "DROP INDEX user_files_deleted_idx";
		$queries[] = "CREATE INDEX user_files_deleted_idx
						ON user_files (f_deleted) where f_deleted is not true;";
		$queries[] = "CREATE INDEX user_file_categories_deleted_idx
						ON user_file_categories (f_deleted) where f_deleted is not true;";
		$queries[] = "CREATE INDEX user_file_categories_uid_idx
						ON user_file_categories (user_id);";
		$queries[] = "CREATE INDEX user_file_categories_pid_idx
						ON user_file_categories (parent_id);";
		$queries[] = "CREATE INDEX security_aclp_daclid_idx
						ON security_aclp (dacl_id);";
	}


	/*******************************************************************************
	 *	9/21/2010 - Add version flag for retro processing of old messages
	 ********************************************************************************/
	$thisrev = 2.282;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_message_original ADD COLUMN antmail_version integer;";
	}

	/*******************************************************************************
	 *	9/23/2010 - Update users
	 ********************************************************************************/
	$thisrev = 2.283;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE activity DROP CONSTRAINT activity_uid_fkey;";
		$queries[] = "ALTER TABLE activity ADD CONSTRAINT activity_uid_fkey 
						FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL;";

		$queries[] = "CREATE INDEX users_active_auth_idx
						   ON users (name, password) 
						   WHERE active is true;";

		$queries[] = "ALTER TABLE users DROP CONSTRAINT user_name_acc_uni;
					  ALTER TABLE users ADD CONSTRAINT users_name_uni UNIQUE (\"name\");";

		$queries[] = "update users set id='-1' where name='administrator' and id!='-1';";
		$queries[] = "ALTER TABLE user_timezones ADD COLUMN loc_name character varying(64);";
		$queries[] = "ALTER TABLE user_timezones ADD COLUMN offs real;";
	}


	/*******************************************************************************
	 *	9/30/2010 - Update users
	 ********************************************************************************/
	$thisrev = 2.284;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE email_thread_mailbox_mem
						(
						  thread_id bigint NOT NULL,
						  mailbox_id integer NOT NULL,
						  CONSTRAINT email_thread_mailbox_mem_pkey PRIMARY KEY (thread_id, mailbox_id),
						  CONSTRAINT email_thread_mailbox_mem_mid_fkey FOREIGN KEY (mailbox_id)
							  REFERENCES email_mailboxes (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT email_thread_mailbox_mem_tid_fkey FOREIGN KEY (thread_id)
							  REFERENCES email_threads (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)";
	}

	/*******************************************************************************
	 *	10/03/2010 - Update email owner
	 ********************************************************************************/
	$thisrev = 2.285;
	if ($revision < $thisrev)
	{
		$queries[] = "update email_threads set owner_id=(select user_id from email_users, 
						email_mailboxes where email_mailboxes.email_user=email_users.id and 
						email_threads.mailbox_id=email_mailboxes.id) where owner_id is null and mailbox_id is not null;";

		$queries[] = "update email_messages set owner_id=(select user_id from email_users, 
						email_mailboxes where email_mailboxes.email_user=email_users.id and 
						email_messages.mailbox_id=email_mailboxes.id) where owner_id is null and mailbox_id is not null;";	
	}

	/*******************************************************************************
	 *	10/04/2010 - Fix constraints
	 ********************************************************************************/
	$thisrev = 2.286;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_mailboxes DROP CONSTRAINT email_mailboxes_uni_name;";
		$queries[] = "ALTER TABLE email_mailboxes ADD CONSTRAINT email_mailboxes_uni UNIQUE (email_user, \"name\", parent_box);";
	}

	/*******************************************************************************
	 *	10/04/2010 - Fix mailbox membership
	 ********************************************************************************/
	$thisrev = 2.287;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into email_thread_mailbox_mem (thread_id, mailbox_id)
						select id, mailbox_id from email_threads where mailbox_id is not null
						and not EXISTS(select 1 from email_thread_mailbox_mem where email_thread_mailbox_mem.thread_id=email_threads.id);";
	}

	/*******************************************************************************
	 *	10/13/2010 - Fix mailbox membership
	 ********************************************************************************/
	$thisrev = 2.288;
	if ($revision < $thisrev)
	{
		// Clean orphaned groups
		$queries[] = "delete from customer_labels where parent_id not in (select id from customer_labels);";
		$queries[] = "delete from customer_labels where parent_id not in (select id from customer_labels);";
		$queries[] = "ALTER TABLE customer_labels ADD CONSTRAINT customer_labels_parent_fkey 
						FOREIGN KEY (parent_id) REFERENCES customer_labels (id) ON UPDATE CASCADE ON DELETE CASCADE;";

		$queries[] = "delete from contacts_personal_labels where parent_id not in (select id from contacts_personal_labels);";
		$queries[] = "delete from contacts_personal_labels where parent_id not in (select id from contacts_personal_labels);";
		$queries[] = "ALTER TABLE contacts_personal_labels ADD CONSTRAINT contacts_personal_labels_parent_fkey 
						FOREIGN KEY (parent_id) REFERENCES contacts_personal_labels (id) ON UPDATE CASCADE ON DELETE CASCADE;";

		$queries[] = "delete from project_groups where parent_id not in (select id from project_groups);";
		$queries[] = "delete from project_groups where parent_id not in (select id from project_groups);";
		$queries[] = "ALTER TABLE project_groups ADD CONSTRAINT project_groups_parent_fkey FOREIGN KEY (parent_id) 
						REFERENCES project_groups (id) ON UPDATE CASCADE ON DELETE CASCADE;";
	}

	/*******************************************************************************
	 *	10/19/2010 - Fix mailbox membership
	 ********************************************************************************/
	$thisrev = 2.289;
	if ($revision < $thisrev)
	{
		// Clean orphaned groups
		$queries[] = "CREATE INDEX email_threads_mailbox_mem_mbox_idx
					  ON email_thread_mailbox_mem
					  USING btree
					  (mailbox_id);";

		$queries[] = "CREATE INDEX email_threads_mailbox_mem_thdx_idx
					  ON email_thread_mailbox_mem
					  USING btree
					  (thread_id);";
	}

	/*******************************************************************************
	 *	10/19/2010 - Add products
	 ********************************************************************************/
	$thisrev = 2.291;
	if ($revision < $thisrev)
	{
		// Clean orphaned groups

		$queries[] = "create table products
						(
						  id bigserial not null,
						  \"name\" text,
						  notes text,
						  price double precision,
						  ts_entered timestamp with time zone,
						  ts_updated timestamp with time zone,
						  f_deleted boolean default false,
						  revision integer,
						  path text,
						  constraint products_pkey primary key (id)
						)";

		$queries[] = "insert into app_object_types(name, title, object_table)
						values('product', 'Product', 'products');";
		

		$queries[] = "ALTER TABLE customer_invoice_detail ADD COLUMN product_id bigint;";
		$queries[] = "ALTER TABLE customer_invoice_detail ADD CONSTRAINT customer_invoice_detail_pid_fkey 
						FOREIGN KEY (product_id) REFERENCES products (id) ON UPDATE CASCADE ON DELETE SET NULL;";
		
		$queries[] = "ALTER TABLE customer_invoice_status ADD COLUMN f_paid boolean DEFAULT false;";
		$queries[] = "update customer_invoice_status set f_paid='t' where name='Paid';";
	}

	/*******************************************************************************
	*	11/03/2010 - Add invoice template
	********************************************************************************/
	$thisrev = 2.292;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('invoice_template', 'Invoice Template', 'customer_invoice_templates');";
	}

	/*******************************************************************************
	*	11/09/2010 - Add invoice template
	********************************************************************************/
	$thisrev = 2.293;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('calendar_event_proposal', 'Meeting Proposal', 'calendar_event_coord');";
	}

	/*******************************************************************************
	*	11/14/2010 - Minor updates
	********************************************************************************/
	$thisrev = 2.294;
	if ($revision < $thisrev)
	{
		$queries[] = "update themes set title='Social' where app_name='wireframe'";
		$queries[] = "update themes set css_file='ant_softblue.css', app_name='softblue' where app_name='skygrey'";
		$queries[] = "update themes set css_file='ant_social.css', app_name='social' where app_name='wireframe'";
		$queries[] = "update themes set title='Cheery', css_file='ant_cheery.css', app_name='cheery' where app_name='ant_os'";
		$queries[] = "ALTER TABLE customers DROP CONSTRAINT customers_account_fkey;";
	}

	/*******************************************************************************
	*	11/15/2010 - Themes
	********************************************************************************/
	$thisrev = 2.295;
	if ($revision < $thisrev)
	{
		$queries[] = "update users set theme_id='12' where theme_id='2'";
		$queries[] = "update themes set title='Overcast' where app_name='softblue'";
		$queries[] = "update themes set title='Earth', css_file='ant_earth.css', app_name='earth' where app_name='future2'";
		$queries[] = "update themes set title='Green', css_file='ant_green.css', app_name='green' where app_name='blue'";
	}

	/*******************************************************************************
	*	11/17/2010 - System Users
	********************************************************************************/
	$thisrev = 2.296;
	if ($revision < $thisrev)
	{
		$queries[] = "update users set theme_id='12' where theme_id='2'";
		$queries[] = "update themes set title='Overcast' where app_name='softblue'";
		$queries[] = "update themes set title='Earth', css_file='ant_earth.css', app_name='earth' where app_name='future2'";
		$queries[] = "update themes set title='Green', css_file='ant_green.css', app_name='green' where app_name='blue'";
	}

	/*******************************************************************************
	*	11/22/2010 - System Indexes
	********************************************************************************/
	$thisrev = 2.298;
	if ($revision < $thisrev)
	{
		// delete unneeded indexes
		$queries[] = "DROP INDEX email_threads_deleted_idx;";
		$queries[] = "DROP INDEX email_threads_mailbox_idx;";
		$queries[] = "DROP INDEX email_threads_owner_idx;";
		$queries[] = "DROP INDEX email_messages_deleted_idx;";

		$queries[] = "DROP TABLE app_object_index;";
		$queries[] = "CREATE TABLE app_object_index_fulltext
						(
						  id bigserial NOT NULL,
						  type_id integer,
						  object_id bigint,
						  object_revision integer,
						  snippet character varying(512),
						  private_owner_id integer,
						  ts_entered timestamp with time zone,
						  tsv_keywords tsvector,
						  CONSTRAINT app_object_index_fulltext_pkey PRIMARY KEY (id),
						  CONSTRAINT app_object_index_fulltext_ownerid_fkey FOREIGN KEY (private_owner_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT app_object_index_fulltext_tid_fkey FOREIGN KEY (type_id)
							  REFERENCES app_object_types (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";
		$queries[] = "CREATE INDEX app_object_index_fulltext_oid_idx
					  ON app_object_index_fulltext
					  USING btree
					  (object_id);";
		$queries[] = "CREATE INDEX app_object_index_fulltext_tsv_idx
					  ON app_object_index_fulltext
					  USING gin
					  (tsv_keywords);";
		$queries[] = "CREATE INDEX app_object_index_fulltext_type_idx
					  ON app_object_index_fulltext
					  USING btree
					  (type_id);";
	}

	/*******************************************************************************
	*	11/22/2010 - System Indexes
	********************************************************************************/
	$thisrev = 2.302;
	if ($revision < $thisrev)
	{
		
	}

	/*******************************************************************************
	*	11/29/2010 - System index queue
	********************************************************************************/
	$thisrev = 2.303;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE app_object_index_queue
						(
						  id serial NOT NULL,
						  object_type_id integer,
						  object_id bigint,
						  ts_entered timestamp with time zone,
						  object_type character varying(256),
						  CONSTRAINT app_object_index_queue_pkey PRIMARY KEY (id),
						  CONSTRAINT app_object_index_queue_otid_fket FOREIGN KEY (object_type_id)
							  REFERENCES app_object_types (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";
	}

	/*******************************************************************************
	*	12/08/2010 - Add categories to feeds
	********************************************************************************/
	$thisrev = 2.304;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE xml_feed_post_categories
						(
						  id serial NOT NULL,
						  \"name\" character varying(64),
						  feed_id integer,
						  parent_id integer,
						  color character(6),
						  CONSTRAINT xml_feed_post_categories_pkey PRIMARY KEY (id),
						  CONSTRAINT xml_feed_post_categories_fid_fkey FOREIGN KEY (feed_id)
							  REFERENCES xml_feeds (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "CREATE TABLE xml_feed_post_cat_mem
						(
						  id serial NOT NULL,
						  post_id integer,
						  category_id integer,
						  CONSTRAINT xml_feed_post_cat_mem_pkey PRIMARY KEY (id),
						  CONSTRAINT xml_feed_post_cat_mem_catid_fkey FOREIGN KEY (category_id)
							  REFERENCES xml_feed_post_categories (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT xml_feed_post_cat_mem_pid_fkey FOREIGN KEY (post_id)
							  REFERENCES xml_feed_posts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";
	}

	/*******************************************************************************
	*	12/10/2010 - Add infocenter_document object
	********************************************************************************/
	$thisrev = 2.305;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('infocenter_document', 'IC Document', 'ic_documents');";
	}

	/*******************************************************************************
	*	12/14/2010 - Add fulltext query
	********************************************************************************/
	$thisrev = 2.307;
	if ($revision < $thisrev)
	{
		if ($dbh_acc->TableExists("app_object_index")) // only delete if exists
			$queries[] = "DROP TABLE app_object_index CASCADE;";

		$queries[] = "CREATE TABLE app_object_index
						(
						  object_id integer NOT NULL,
						  object_type_id integer NOT NULL,
						  field_id integer,
						  val_text text,
						  val_tsv tsvector,
						  val_number numeric,
						  val_bool boolean,
						  val_timestamp timestamp with time zone,
						  f_deleted boolean
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "alter table user_files add column f_purged boolean default false;";
	}

	/*******************************************************************************
	*	12/21/2010 - Add estimated cost to template task
	********************************************************************************/
	$thisrev = 2.308;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE project_template_tasks ADD COLUMN 
						cost_estimated numeric;";
	}

	/*******************************************************************************
	*	1/10/2011 - Moving original email to large objects
	********************************************************************************/
	$thisrev = 2.309;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE INDEX email_message_attachments_ctype_idx ON email_message_attachments (content_type);";
		$queries[] = "ALTER TABLE email_message_original add column lo_message oid;";
		$queries[] = "CREATE INDEX email_message_attachments_paid_idx
						  ON email_message_attachments
						  USING btree
						  (parent_id);";
		$queries[] = "ALTER TABLE email_message_attachments ADD COLUMN offset_start bigint;";
		$queries[] = "ALTER TABLE email_message_attachments ADD COLUMN offset_end bigint;";
	}
	
	/*******************************************************************************
	*	1/17/2010 - Add Facebook and Twitter column in email_video_messages
	********************************************************************************/
	$thisrev = 2.310;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_video_messages ADD COLUMN facebook text;";
		$queries[] = "ALTER TABLE email_video_messages ADD COLUMN twitter text;";
	}

	/*******************************************************************************
	*	1/17/2010 - Fix full text index (again)
	********************************************************************************/
	$thisrev = 2.314;
	if ($revision < $thisrev)
	{
		$queries[] = "DROP TABLE app_object_index_fulltext CASCADE;";
		$queries[] = "CREATE TABLE app_object_index_fulltext
						(
						  id bigserial NOT NULL,
						  object_type_id integer,
						  object_id bigint,
						  object_revision integer,
						  f_deleted bool,
						  snippet character varying(512),
						  private_owner_id integer,
						  ts_entered timestamp with time zone,
						  tsv_keywords tsvector,
						  CONSTRAINT app_object_index_fulltext_pkey PRIMARY KEY (id),
						  CONSTRAINT app_object_index_fulltext_ownerid_fkey FOREIGN KEY (private_owner_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT app_object_index_fulltext_tid_fkey FOREIGN KEY (object_type_id)
							  REFERENCES app_object_types (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";

		$tbl = "app_object_index_fulltext_act";
		$queries[] = "CREATE TABLE $tbl(CHECK(f_deleted='f')) INHERITS (app_object_index_fulltext);";
		$queries[] = "CREATE INDEX ".$tbl."_oid_idx ON ".$tbl." USING btree (object_id);";
		$queries[] = "CREATE INDEX ".$tbl."_tsv_idx ON ".$tbl." USING gin (tsv_keywords);";
		$queries[] = "CREATE INDEX ".$tbl."_type_idx ON ".$tbl." USING btree (object_type_id);";
		$queries[] = "CREATE INDEX ".$tbl."_owner_idx ON ".$tbl." USING btree (private_owner_id);";

		$tbl = "app_object_index_fulltext_del";
		$queries[] = "CREATE TABLE $tbl(CHECK(f_deleted='t')) INHERITS (app_object_index_fulltext);";
		$queries[] = "CREATE INDEX ".$tbl."_oid_idx ON ".$tbl." USING btree (object_id);";
		$queries[] = "CREATE INDEX ".$tbl."_tsv_idx ON ".$tbl." USING gin (tsv_keywords);";
		$queries[] = "CREATE INDEX ".$tbl."_type_idx ON ".$tbl." USING btree (object_type_id);";
		$queries[] = "CREATE INDEX ".$tbl."_owner_idx ON ".$tbl." USING btree (private_owner_id);";

		$routines[] = "obj_index.php";
	}

	/*******************************************************************************
	*	1/18/2010 - Added uname alias table
	********************************************************************************/
	$thisrev = 2.315;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE object_unames
						(
						  object_type_id integer,
						  object_id bigint,
						  \"name\" character varying(512),
						  CONSTRAINT object_unames_otid_fkey FOREIGN KEY (object_type_id)
							  REFERENCES app_object_types (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT object_unames_uni UNIQUE (object_type_id, name)
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "CREATE INDEX object_unames_oidwt_idx
					  ON object_unames
					  USING btree
					  (object_type_id, object_id);";

		$queries[] = "CREATE INDEX object_unames_oname_idx
					  ON object_unames
					  USING btree
					  (name, object_type_id);";
	}

	/*******************************************************************************
	*	1/18/2010 - Added uname alias table
	********************************************************************************/
	$thisrev = 2.316;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE app_object_indexed
						(
						  object_type_id integer,
						  object_id bigint,
						  revision integer,
						  index_type smallint
						)
						WITH (
						  OIDS=FALSE
						);";
		$queries[] = "CREATE INDEX app_object_indexed_obj_idx
					  ON app_object_indexed
					  USING btree
					  (object_type_id, object_id);";

		$queries[] = "CREATE INDEX app_object_indexed_indtype_idx
					  ON app_object_indexed
					  USING btree
					  (index_type);";
	}

	/*******************************************************************************
	*	2/7/2011 - Add query cache
	********************************************************************************/
	$thisrev = 2.317;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE app_object_list_cache
						(
						  id serial NOT NULL,
						  ts_created time without time zone,
						  query character varying(512),
						  total_num integer,
						  CONSTRAINT app_object_list_cache_pkey PRIMARY KEY (id)
						)
						WITH (
						  OIDS=FALSE
						);";
		$queries[] = "CREATE TABLE app_object_list_cache_flds
						(
						  list_id integer,
						  field_id integer,
						  \"value\" character varying(512),
						  CONSTRAINT app_object_list_cache_flds_lid_fkey FOREIGN KEY (list_id)
							  REFERENCES app_object_list_cache (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "CREATE INDEX app_object_list_cache_flds_fid_idx
					  ON app_object_list_cache_flds
					  USING btree
					  (field_id);";

		$queries[] = "CREATE INDEX app_object_list_cache_flds_lid_idx
					  ON app_object_list_cache_flds
					  USING btree
					  (list_id);";

		$queries[] = "CREATE INDEX app_object_list_cache_flds_val_idx
					  ON app_object_list_cache_flds
					  USING btree
					  (value);";

		$queries[] = "CREATE TABLE app_object_list_cache_res
						(
						  list_id integer NOT NULL,
						  results text,
						  CONSTRAINT app_object_list_cache_res_lid_fkey FOREIGN KEY (list_id)
							  REFERENCES app_object_list_cache (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "CREATE INDEX app_object_list_cache_res_lid_idx
					  ON app_object_list_cache_res
					  USING btree
					  (list_id);";

		$queries[] = "CREATE TABLE object_recurrence
						(
						  id bigserial NOT NULL,
						  object_type_id integer,
						  date_processed_to date,
						  \"type\" smallint,
						  \"interval\" smallint,
						  date_start date,
						  date_end date,
						  t_start time with time zone,
						  t_end time with time zone,
						  all_day boolean,
						  dayofmonth smallint,
						  dayofweekmask boolean[],
						  duration integer,
						  instance smallint,
						  monthofyear smallint,
						  parent_object_id bigint,
						  type_id character varying(256),
						  object_type character varying(256),
						  f_active boolean DEFAULT true,
						  CONSTRAINT object_recurrence_pkey PRIMARY KEY (id)
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "CREATE INDEX object_recurrence_proto_idx
					  ON object_recurrence
					  USING btree
					  (date_processed_to)
					  WHERE f_active = true;";
	}

	/*******************************************************************************
	*	3/3/2011 - Add object revision history
	********************************************************************************/
	$thisrev = 2.318;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE object_revisions
						(
						  object_id bigint,
						  object_type_id integer,
						  revision integer,
						  ts_updated timestamp with time zone,
						  id bigserial NOT NULL,
						  CONSTRAINT object_revisions_pkey PRIMARY KEY (id)
						)";

		$queries[] = "CREATE INDEX object_revisions_objref_idx
					  ON object_revisions
					  USING btree
					  (object_id, object_type_id);";

		$queries[] = "CREATE TABLE object_revision_data
						(
						  revision_id bigint,
						  field_value text,
						  field_name character varying(256),
						  CONSTRAINT object_revision_data_rid_fkey FOREIGN KEY (revision_id)
							  REFERENCES object_revisions (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						);";
	}

	/*******************************************************************************
	*	3/3/2011 - Link active sync devices to users
	********************************************************************************/
	$thisrev = 2.319;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE async_states ADD COLUMN user_id integer;";

		$queries[] = "ALTER TABLE async_states ADD CONSTRAINT async_states_uid_fkey FOREIGN KEY (user_id) 
						REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE;";
	}

	/*******************************************************************************
	*	3/16/2011 - Link active sync devices to users
	********************************************************************************/
	$thisrev = 2.322;
	if ($revision < $thisrev)
	{
		$queries[] = "delete from app_object_indexed where 
					   object_type_id in (select id from app_object_types where name='task')";

		$queries[] = "ALTER TABLE user_files ADD COLUMN f_temp boolean DEFAULT false;";

		$queries[] = "CREATE TABLE worker_jobs
						(
						  job_id character varying(512) NOT NULL,
						  function_name character varying(512), 
						  ts_started timestamp with time zone,
						  ts_completed timestamp with time zone,
						  status_numerator integer DEFAULT -1, 
						  status_denominator integer DEFAULT 100, 
						  log text,
						  retval bytea, 
						  CONSTRAINT worker_jobs_pkey_id PRIMARY KEY (job_id)
						)
						WITH (
						  OIDS=FALSE
						);";
	}

	/*******************************************************************************
	*	4/4/2011 - Add use_when filter and move feed fields to objects from outer table
	********************************************************************************/
	$thisrev = 2.323;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE app_object_type_fields ADD COLUMN use_when text;";

		$routines[] = "feeds_movecustflds.php";
	}

	/*******************************************************************************
	*	4/14/2011 
	********************************************************************************/
	$thisrev = 2.324;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE user_files ADD COLUMN content_type character varying(256);";
		$queries[] = "ALTER TABLE user_files ADD COLUMN content_id character varying(256);";
		$queries[] = "ALTER TABLE user_files ADD COLUMN content_disposition character varying(128);";
	}

	/*******************************************************************************
	*	4/18/2011 - Add use_id to app_object_type_frm_layouts
	********************************************************************************/
	$thisrev = 2.325;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE app_object_type_frm_layouts ADD COLUMN user_id integer;";
	}
	
	/*******************************************************************************
	*	4/20/2011 - Add scope to app_object_type_frm_layouts
	********************************************************************************/
	$thisrev = 2.326;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE app_object_type_frm_layouts ADD COLUMN scope text;";
	}

	/*******************************************************************************
	*	4/20/2011 - Add email attachments
	********************************************************************************/
	$thisrev = 2.327;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('email_message_attachment', 'Email Attachment', 'email_message_attachments');";
	}

	/*******************************************************************************
	*	4/20/2011 - Add email_message_queue to be used by the new antmail system
	********************************************************************************/
	$thisrev = 2.328;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE email_message_queue
						(
						  id bigserial NOT NULL,
						  user_id integer NOT NULL,
						  lo_message oid,
						  ts_delivered time with time zone,
						  CONSTRAINT email_message_queue_id_pkey PRIMARY KEY (id)
						)";
	}

	/*******************************************************************************
	*	5/8/2011 - Add time object for time tracking
	********************************************************************************/
	$thisrev = 2.329;
	if ($revision < $thisrev)
	{
		$queries[] = "insert into app_object_types(name, title, object_table)
						values('time', 'Time Log', 'project_time');";

		$queries[] = "CREATE TABLE project_time
						(
						  id serial NOT NULL,
						  \"name\" character varying(512),
						  notes text,
						  owner_id integer,
						  ts_entered timestamp with time zone,
						  date_applied date,
						  ts_updated timestamp with time zone,
						  creator_id integer,
						  hours numeric,
						  task_id integer,
						  CONSTRAINT project_time_id_pkey PRIMARY KEY (id)
						)
						WITH (
						  OIDS=FALSE
						);";
	}

	/*******************************************************************************
	*	5/22/2011 - Add applications table and insert system applications
	********************************************************************************/
	$thisrev = 2.331;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE applications
						(
						  id serial NOT NULL,
						  \"name\" character varying(256) NOT NULL,
						  short_title character varying(256) NOT NULL,
						  title character varying(512) NOT NULL,
						  scope character varying,
						  xml_navigation text,
						  f_system boolean DEFAULT false,
						  CONSTRAINT applications_id_pkey PRIMARY KEY (id),
						  CONSTRAINT applications_name_uni UNIQUE (name)
						)";

		$queries[] = "CREATE INDEX applications_uname_idx
					  ON applications
					  USING btree
					  (name);";

		$queries[] = "CREATE TABLE application_objects
					(
					  id serial NOT NULL,
					  application_id integer,
					  object_type_id integer,
					  CONSTRAINT application_objects_pkey PRIMARY KEY (id),
					  CONSTRAINT application_objects_aid_fkey FOREIGN KEY (application_id)
						  REFERENCES applications (id) MATCH SIMPLE
						  ON UPDATE CASCADE ON DELETE CASCADE,
					  CONSTRAINT application_objects_otid_fkey FOREIGN KEY (object_type_id)
						  REFERENCES app_object_types (id) MATCH SIMPLE
						  ON UPDATE CASCADE ON DELETE CASCADE
					)";

		$queries[] = "CREATE TABLE application_calendars
					(
					  id serial NOT NULL,
					  calendar_id integer,
					  application_id integer,
					  CONSTRAINT application_calendars_pkey PRIMARY KEY (id),
					  CONSTRAINT application_objects_aid_fkey FOREIGN KEY (application_id)
								  REFERENCES applications (id) MATCH SIMPLE
								  ON UPDATE CASCADE ON DELETE CASCADE
					)";

		$queries[] = "insert into applications(name, short_title, title, scope, f_system) 
						values('crm', 'CRM', 'Customer Relationship Management', 'system', 't');";
		$queries[] = "insert into applications(name, short_title, title, scope, f_system) 
						values('email', 'Email', 'Email', 'system', 't');";
		$queries[] = "insert into applications(name, short_title, title, scope, f_system) 
						values('contacts', 'Personal Contacts', 'Personal Contacts', 'system', 't');";
		$queries[] = "insert into applications(name, short_title, title, scope, f_system) 
						values('calendar', 'Calendar', 'Calendar', 'system', 't');";
		$queries[] = "insert into applications(name, short_title, title, scope, f_system) 
						values('projects', 'Projects', 'Project & Task Manager', 'system', 't');";
		$queries[] = "insert into applications(name, short_title, title, scope, f_system) 
						values('files', 'Files', 'Online Files', 'system', 't');";
		$queries[] = "insert into applications(name, short_title, title, scope, f_system) 
						values('notes', 'Notes', 'Personal Notes', 'system', 't');";
		$queries[] = "insert into applications(name, short_title, title, scope, f_system) 
						values('cms', 'Content', 'Content Management System', 'system', 't');";
		$queries[] = "insert into applications(name, short_title, title, scope, f_system) 
						values('infocenter', 'Infocenter', 'Infocenter Knowledge Base', 'system', 't');";
	}


	/*******************************************************************************
	*	5/30/2011 - add f_parent_app column to link custom objects to applications
	********************************************************************************/
	$thisrev = 2.332;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE application_objects ADD COLUMN f_parent_app boolean DEFAULT false;";
		$queries[] = "ALTER TABLE app_object_types ADD COLUMN f_system boolean DEFAULT false;";
		$queries[] = "update app_object_types SET f_system='t' WHERE name not like '%.%';"; // set non-datacenter objects to system


		// Add default forms to new table - forgot to migrate on initial publication
		$queries[] = "insert into app_object_type_frm_layouts(type_id, form_layout_xml, scope)
						select id, form_layout_xml, 'default' as scope from app_object_types where form_layout_xml is not null;";
	}

	/*******************************************************************************
	*	6/2/2011 - add where to field defaults
	********************************************************************************/
	$thisrev = 2.333;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE app_object_field_defaults ADD COLUMN where_cond text;";
	}
	$thisrev = 2.334; // Reindex due to type change
	if ($revision < $thisrev)
	{
		$queries[] = "delete from app_object_indexed where 
					   object_type_id in (select id from app_object_types where name='case')";
	}
	$thisrev = 2.335; // Add application specifier to object types
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE app_object_types ADD COLUMN application_id integer;";
	}


	/*******************************************************************************
	*	6/26/2011 - add ans_key to user files. Used for migration to V2
	********************************************************************************/
	$thisrev = 2.336;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE user_files ADD COLUMN ans_key character varying(640);"; // 640 = 512 (title) + 128 (path)
	}

	/*******************************************************************************
	*	7/3/2011 - add product familiy and groups
	********************************************************************************/
	$thisrev = 2.337;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE product_families
						(
						  id serial NOT NULL,
						  \"name\" character varying(128),
						  notes text,
						  ts_entered timestamp with time zone,
						  ts_updated timestamp with time zone,
						  CONSTRAINT product_families_pkey PRIMARY KEY (id)
						)"; 

		$queries[] = "CREATE TABLE product_categories
						(
						  id serial NOT NULL,
						  parent_id integer,
						  \"name\" character varying(128),
						  color character varying(8),
						  CONSTRAINT product_categories_pkey PRIMARY KEY (id)
						)";

		$queries[] = "CREATE TABLE product_categories_mem
						(
						  id serial NOT NULL,
						  product_id integer NOT NULL,
						  category_id integer NOT NULL,
						  CONSTRAINT product_categories_mem_pkey PRIMARY KEY (id),
						  CONSTRAINT product_categories_mem_cid_fkey FOREIGN KEY (category_id)
							  REFERENCES product_categories (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT product_categories_mem_pid_fkey FOREIGN KEY (product_id)
							  REFERENCES products (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						)";

		$queries[] = "insert into app_object_types(name, title, object_table, f_system)
						values('product_family', 'Product Family', 'product_families', 't');";

		$queries[] = "update app_object_types SET f_system='t' WHERE name not like '%.%';"; // set non-datacenter objects to system
	}


	/*******************************************************************************
	*	7/3/2011 - account id is no longer needed for users but we are leaving it for legacy
	********************************************************************************/
	$thisrev = 2.338;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE users	ALTER COLUMN account_id DROP NOT NULL;";
	}

	/*******************************************************************************
	*	8/10/2011 - real size was not being stored for userfiles, added column to fix
	********************************************************************************/
	$thisrev = 2.339;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE user_files RENAME file_size  TO file_size_old;";
		$queries[] = "ALTER TABLE user_files ADD COLUMN file_size bigint;";
	}

	/*******************************************************************************
	*	8/15/2011 - Add customer order object
	********************************************************************************/
	$thisrev = 2.341;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE sales_order_status
						(
						  id serial NOT NULL,
						  \"name\" character varying(128),
						  sort_order smallint,
						  color character(6),
						  CONSTRAINT sales__order_status_id_pkey PRIMARY KEY (id)
						);";

		$queries[] = "CREATE TABLE sales_orders
						(
						  id serial NOT NULL,
						  \"number\" character varying(256),
						  created_by character varying(256),
						  tax_rate integer,
						  amount numeric,
						  ship_to text,
						  ship_to_cship boolean,
						  ts_updated timestamp with time zone,
						  ts_entered timestamp with time zone,
						  owner_id integer,
						  status_id integer,
						  customer_id integer,
						  invoice_id integer,
						  f_deleted boolean DEFAULT false,
						  CONSTRAINT sales_order_pkey PRIMARY KEY (id)
						);";

		$queries[] = "CREATE TABLE sales_order_detail
						(
						   id serial, 
						   order_id integer, 
						   quantity real, 
						   amount numeric, 
						   \"name\" text, 
						   product_id integer, 
						   CONSTRAINT sales_order_detail_pkey PRIMARY KEY (id), 
						   CONSTRAINT sales_order_detail_oid_fkey FOREIGN KEY (order_id) REFERENCES sales_orders (id) ON UPDATE CASCADE ON DELETE CASCADE
						);";

		$queries[] = "insert into app_object_types(name, title, object_table)
						values('sales_order', 'Order', 'sales_orders');";
	}

	/*******************************************************************************
	*	8/22/2011 - Add product_review object type
	********************************************************************************/
	$thisrev = 2.342;
	if ($revision < $thisrev)
	{

		$queries[] = "CREATE TABLE product_reviews
						(
						  id serial NOT NULL,
						  \"name\" character varying(512),
						  notes text,
						  ts_entered timestamp with time zone,
						  ts_updated timestamp with time zone,
						  creator_id integer,
						  rating numeric,
						  product integer,
						  CONSTRAINT product_review_id_pkey PRIMARY KEY (id)
						)
						WITH (
						  OIDS=FALSE
						);";

		$queries[] = "insert into app_object_types(name, title, object_table)
						values('product_review', 'Product Review', 'product_reviews');";
	}

	/*******************************************************************************
	*	9/20/2011 - Add members table
	********************************************************************************/
	$thisrev = 2.343;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE members
						(
						  id bigserial not null,
						  \"name\" character varying(512),
						  role character varying(512),
						  ts_entered timestamp with time zone,
						  ts_updated timestamp with time zone,
						  f_accepted boolean default true,
						  f_deleted boolean default false,
						  revision integer,
						  path text,
						  constraint members_pkey primary key (id)
						)";

		$queries[] = "insert into app_object_types(name, title, object_table)
						values('member', 'Member', 'members');";

		// TODO: run query to convert all \n or <br /> in calendar_events notes
	}
    
    /*******************************************************************************
    *    9/28/2011 - Add description column in app_widgets table
    ********************************************************************************/
    $thisrev = 2.344;
    if ($revision < $thisrev)
    {
        $queries[] = "ALTER TABLE app_widgets
                        ADD COLUMN description text;";
    }

    /*******************************************************************************
    *    10/21/2011 - New table for ant chat server
    ********************************************************************************/
    $thisrev = 2.345;
    if ($revision < $thisrev)
    {
		$queries[] = "CREATE TABLE chat_server
                    (
                      id serial NOT NULL,
                      user_id integer,
                      user_name character varying(256),
                      friend_name character varying(256),
                      friend_server character varying(256),
                      message text,
                      ts_last_message time without time zone,
                      f_read boolean DEFAULT false,
                      message_timestamp integer,
                      CONSTRAINT chat_server_pkey PRIMARY KEY (id ),
                      CONSTRAINT chat_server_uid_fkey FOREIGN KEY (user_id)
                          REFERENCES users (id) MATCH SIMPLE
                          ON UPDATE CASCADE ON DELETE CASCADE
                    )
                    WITH (
                      OIDS=FALSE
                    );";


 		$queries[] = "CREATE INDEX chat_server_f_read_idx
                      ON chat_server
                      USING btree
					  (f_read);";

        $queries[] = "CREATE INDEX chat_server_friend_name_idx
                      ON chat_server
                      USING btree
                      (friend_name);";

        $queries[] = "CREATE INDEX chat_server_message_timestamp
                      ON chat_server
                      USING btree
                      (message_timestamp );";

        $queries[] = "CREATE INDEX chat_server_user_name_idx
                      ON chat_server
                      USING btree
                      (user_name);";
        
        $queries[] = "CREATE TABLE chat_server_session
                        (
                          id serial NOT NULL,
                          user_id integer,
                          user_name character varying(256),
                          friend_name character varying(256),
                          friend_server character varying(256),
                          f_typing boolean DEFAULT false,
                          f_popup boolean DEFAULT false,
                          f_online boolean DEFAULT false,
                          CONSTRAINT chat_server_session_pkey PRIMARY KEY (id ),
                          CONSTRAINT chat_server_session_uid_fkey FOREIGN KEY (user_id)
                              REFERENCES users (id) MATCH SIMPLE
                              ON UPDATE CASCADE ON DELETE CASCADE
                        )
                        WITH (
                          OIDS=FALSE
                        );";

        $queries[] = "CREATE INDEX chat_server_session_f_popup_idx
                          ON chat_server_session
                          USING btree
                          (f_popup );";

        $queries[] = "CREATE INDEX chat_server_session_friend_name_idx
                          ON chat_server_session
                          USING btree
                          (friend_name);";

        $queries[] = "CREATE INDEX chat_server_session_user_name_idx
                          ON chat_server_session
                          USING btree
                          (user_name);"; 
        
    }


    /*******************************************************************************
    *	11/8/2011 - Move email_mailboxes to standard grouping schema with f_system
    ********************************************************************************/
    $thisrev = 2.346;
    if ($revision < $thisrev)
    {
		$queries[] = "ALTER TABLE email_mailboxes ADD COLUMN f_system boolean DEFAULT false;";
		$queries[] = "UPDATE email_mailboxes set f_system='t' where flag_special is true;";
		$queries[] = "ALTER TABLE email_mailboxes ADD COLUMN sort_order smallint DEFAULT 1;";

		// Set default sort orders for mailboxes
		$queries[] = "update email_mailboxes set sort_order='-100' where name='Inbox' and f_system='t';";
		$queries[] = "update email_mailboxes set sort_order='-90' where name='Drafts' and f_system='t';";
		$queries[] = "update email_mailboxes set sort_order='-80' where name='Sent' and f_system='t';";
		$queries[] = "update email_mailboxes set sort_order='-70' where name='Trash' and f_system='t';";
		$queries[] = "update email_mailboxes set sort_order='-60' where name='Junk Mail' and f_system='t';";
		$queries[] = "update email_mailboxes set sort_order='1' where sort_order is null;";
	}

	/*******************************************************************************
	 *	11/21/2011 - move recurring events
	 ********************************************************************************/
	$thisrev = 2.347;
	if ($revision < $thisrev)
	{
		$routines[] = "recur_event_move.php";
	}

    /*******************************************************************************
    *	11/25/2011 - Update workflow and actions
    ********************************************************************************/
    $thisrev = 2.348;
    if ($revision < $thisrev)
    {
		$queries[] = "ALTER TABLE workflows ADD COLUMN ts_on_daily_lastrun timestamp with time zone;";
	}

    /*******************************************************************************
    *	12/7/2011 - Update applications
    ********************************************************************************/
    $thisrev = 2.349;
    if ($revision < $thisrev)
    {
		$queries[] = "ALTER TABLE applications ADD COLUMN user_id integer;";
		$queries[] = "ALTER TABLE applications ADD COLUMN team_id integer;";
		$queries[] = "ALTER TABLE applications ADD COLUMN sort_order smallint;";

		// Add home
		$queries[] = "insert into applications(name, short_title, title, scope, f_system, sort_order) 
						values('home', 'Home', 'Home Dashboard', 'system', 't', '1');";

		$queries[] = "UPDATE applications SET sort_order='2' WHERE name='email';";
		$queries[] = "UPDATE applications SET sort_order='3' WHERE name='contacts';";
		$queries[] = "UPDATE applications SET sort_order='4' WHERE name='crm';";
		$queries[] = "UPDATE applications SET sort_order='5' WHERE name='calendar';";
		$queries[] = "UPDATE applications SET sort_order='6' WHERE name='projects';";
		$queries[] = "UPDATE applications SET sort_order='7' WHERE name='files';";
		$queries[] = "UPDATE applications SET sort_order='9' WHERE name='notes';";
		$queries[] = "UPDATE applications SET sort_order='10' WHERE name='cms';";
		$queries[] = "UPDATE applications SET sort_order='11' WHERE name='infocenter';";

	}

	/*******************************************************************************
	 *	12/7/2011 - Process recurring events
	 ********************************************************************************/
	$thisrev = 2.350;
	if ($revision < $thisrev)
	{
		$routines[] = "recur_processto.php";
	}

	/*******************************************************************************
	 *	12/7/2011 - Create index for acl
	 ********************************************************************************/
	$thisrev = 2.351;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE INDEX idx_security_dacl_name
   						ON security_dacl (name ASC NULLS LAST);";
	}

	/*******************************************************************************
	 *	12/28/2011 - Create index for acle
	 ********************************************************************************/
	$thisrev = 2.352;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE INDEX idx_security_acle_permission
   						ON security_acle (aclp_id ASC NULLS LAST);";
	}

	/*******************************************************************************
	 *	12/28/2011 - Create index for acle
	 ********************************************************************************/
	$thisrev = 2.353;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE object_index_cachedata
						(
						   object_type_id integer, 
						   object_id bigint, 
						   revision integer, 
						   data text, 
						   CONSTRAINT object_index_cachedata_pkey PRIMARY KEY (object_type_id, object_id)
						) ";

		$queries[] = "ALTER TABLE app_object_index RENAME TO object_index;";
		$queries[] = "ALTER TABLE app_object_indexed RENAME TO object_indexed;";
		$queries[] = "ALTER TABLE app_object_index_queue RENAME TO object_index_queue;";
		$queries[] = "ALTER TABLE app_object_index_fulltext RENAME TO object_index_fulltext;";
		$queries[] = "ALTER TABLE app_object_index_fulltext_act RENAME TO object_index_fulltext_act;";
		$queries[] = "ALTER TABLE app_object_index_fulltext_del RENAME TO object_index_fulltext_del;";

		// Rename dynamically created indexes
		$routines[] = "renameindexes.php";

		$queries[] = "ALTER TABLE app_object_associations RENAME TO object_associations;";
	}

	/*******************************************************************************
	 *	1/3/2012 - Remove constraint
	 ********************************************************************************/
	$thisrev = 2.354;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE account_settings DROP COLUMN account_id;";
	}

	/*******************************************************************************
	 *	1/19/2012 - Move datacenter apps to new simpler applications table
	 ********************************************************************************/
	$thisrev = 2.355;
	if ($revision < $thisrev)
	{
		$routines[] = "movedatacentertoapps.php";
	}

	/*******************************************************************************
	 *	1/3/2012 - Remove constraint
	 ********************************************************************************/
	$thisrev = 2.356;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_mailboxes ADD COLUMN user_id integer;";
		$queries[] = "update email_mailboxes set user_id=(select user_id from email_users where email_users.id=email_mailboxes.email_user);";

		$queries[] = "ALTER TABLE email_signatures ADD COLUMN user_id integer;";
		$queries[] = "UPDATE email_signatures set user_id=(select user_id from email_users where email_users.id=email_signatures.email_user);";
		
		$queries[] = "ALTER TABLE email_filters ADD COLUMN user_id integer;";
		$queries[] = "update email_filters set user_id=(select user_id from email_users where email_users.id=email_filters.email_user);";

		$queries[] = "ALTER TABLE email_settings_spam ADD COLUMN user_id integer;";
		$queries[] = "update email_settings_spam set user_id=(select user_id from email_users where email_users.id=email_settings_spam.email_user);";
	}


	/*
	ALTER TABLE customer_invoice_detail ADD CONSTRAINT customer_invoice_detail_pid_fkey FOREIGN KEY (product_id) REFERENCES products (id) ON UPDATE CASCADE ON DELETE SET NULL;

	// The below will move all attachments to the body
	 update email_message_attachments set body=(decode(replace(attached_data, '\\', '\\\\'), 'escape')) where body is null;
	*/
    
    
    /*******************************************************************************
     *    1/27/2012 - Added new column in chat server session to handle the new chat message trigger
     ********************************************************************************/
    $thisrev = 2.357;
    if ($revision < $thisrev)
    {
        $queries[] = "ALTER TABLE chat_server_session ADD COLUMN f_newmessage boolean;";
        $queries[] = "ALTER TABLE chat_server_session ALTER COLUMN f_newmessage SET DEFAULT false;";
    }
    
    /*******************************************************************************
     *    1/30/2012 - Added new column in chat server session to handle the user availability
     ********************************************************************************/
    $thisrev = 2.358;
    if ($revision < $thisrev)
    {
        $queries[] = "ALTER TABLE chat_server_session ADD COLUMN last_timestamp integer;";
        $queries[] = "ALTER TABLE chat_server_session ALTER COLUMN last_timestamp SET DEFAULT 0;";
    }
    
    /*******************************************************************************
     *    1/30/2012 - Added new column in chat friends to determine that the friend is already in team
     ********************************************************************************/
    $thisrev = 2.359;
    if ($revision < $thisrev)
    {
        $queries[] = "ALTER TABLE chat_friends ADD COLUMN team_id integer;";
        $queries[] = "ALTER TABLE chat_friends ALTER COLUMN team_id SET DEFAULT 0;";
    }

    /*******************************************************************************
     *  2/2/2012 - Add child parent workflow action fields
     ********************************************************************************/
    $thisrev = 2.360;
    if ($revision < $thisrev)
    {
        $queries[] = "ALTER TABLE workflow_actions ADD COLUMN parent_action_id integer;";
        $queries[] = "ALTER TABLE workflow_actions ADD COLUMN parent_action_event character varying(32);";
    }

    /*******************************************************************************
     *  2/16/2012 - Add approval object type
     ********************************************************************************/
    $thisrev = 2.361;
    if ($revision < $thisrev)
    {

		$queries[] = "CREATE TABLE workflow_approvals
					(
					   id serial, 
					   name character varying(256), 
					   notes text, 
					   workflow_action_id integer, 
					   status character varying(32), 
					   ts_entered timestamp with time zone, 
					   ts_updated timestamp with time zone, 
					   requested_by bigint, 
					   owner_id bigint, 
					   obj_reference character varying(512),
					   CONSTRAINT workflow_approvals_pkey PRIMARY KEY (id)
					)";

		$queries[] = "insert into app_object_types(name, title, object_table, f_system)
						values('approval', 'Approval Request', 'workflow_approvals', 't');";
    }
    
    /*******************************************************************************
     *  2/16/2012 - Add index to revision data fkey
     ********************************************************************************/
    $thisrev = 2.362;
    if ($revision < $thisrev)
    {
		$queries[] = "CREATE INDEX object_revision_data_rid_idx
   						ON object_revision_data (revision_id ASC NULLS LAST);";
	}

	/*******************************************************************************
     *  2/16/2012 - Clean up some mistakes on the grouping code
	 ********************************************************************************/
    $thisrev = 2.363;
    if ($revision < $thisrev)
    {
		$queries[] = "ALTER TABLE email_threads DROP CONSTRAINT email_threads_boxid_fkey;";
		$queries[] = "ALTER TABLE email_messages DROP CONSTRAINT email_messages_mailbox_fkey;";

		$checkfor = array("Inbox", "Trash", "Junk Mail", "Sent");
		foreach ($checkfor as $chk)
			$queries[] = "DELETE FROM email_mailboxes WHERE name='$chk' AND parent_box is null AND f_system is not true;";
	}

	/*******************************************************************************
     *  3/19/2012 - Clean up in prep for v3 schema
	 ********************************************************************************/
    $thisrev = 2.364;
    if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE dataware_olap_cubes
						(
						  id serial NOT NULL,
						  name character varying(512),
						  CONSTRAINT dataware_olap_cubes_id_pkey PRIMARY KEY (id )
						)";
		$queries[] = "CREATE TABLE dataware_olap_cube_measures
						(
						  id serial NOT NULL,
						  name character varying(256),
						  cube_id bigint,
						  CONSTRAINT dataware_olap_cube_measures_pkey PRIMARY KEY (id ),
						  CONSTRAINT dataware_olap_cube_measures_cube_fkey FOREIGN KEY (cube_id)
							  REFERENCES dataware_olap_cubes (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT dataware_olap_cube_measures_uni_name UNIQUE (cube_id , name )
						)";

		$queries[] = "CREATE TABLE dataware_olap_cube_dims
						(
						  id bigserial NOT NULL,
						  name character varying(512),
						  type character varying(64),
						  cube_id bigint,
						  CONSTRAINT dataware_olap_cube_dims_id_pkey PRIMARY KEY (id ),
						  CONSTRAINT dataware_olap_cube_dims_cube_fkey FOREIGN KEY (cube_id)
							  REFERENCES dataware_olap_cubes (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT dataware_olap_cube_dims_name_uni UNIQUE (cube_id , name )
						)";

		$queries[] = "ALTER TABLE users ADD COLUMN country_code character varying(2);";
		$queries[] = "ALTER TABLE users ADD COLUMN timezone character varying(64);";
		$queries[] = "ALTER TABLE users ADD COLUMN theme character varying(32);";

		// Move everyone to cheery
		$queries[] = "UPDATE users SET theme='cheery';";
	}

	/*******************************************************************************
     *  3/19/2012 - Clean up in prep for v3 schema
	 ********************************************************************************/
    $thisrev = 2.365;
    if ($revision < $thisrev)
	{
		// Remove account id column which is no longer needed
		$queries[] = "ALTER TABLE system_registry DROP COLUMN account_id;";

		// move all account_settings entries to system_registry because they do the same thing
		$queries[] = "INSERT INTO system_registry(key_name, key_val) select name, value from account_settings;";

		// create index ok key name
		$queries[] = "CREATE INDEX system_registry_keyname_idx ON system_registry (key_name ASC NULLS LAST);";
		$queries[] = "CREATE UNIQUE INDEX system_registry_key_name_idx ON system_registry (key_name, user_id);";

		// rename to system compatible column name - order is a reserved name
		$queries[] = "ALTER TABLE app_object_view_orderby RENAME \"order\"  TO order_dir;";

		/**
		 *  These are tables no longer used, should we delete or just leave?
			DROP TABLE access_control;
			DROP TABLE account_settings;
			DROP TABLE accounts;
			DROP TABLE address_book;
			DROP TABLE address_book_global;	
			DROP TABLE ant_system;
			DROP TABLE app_topnav;
			DROP TABLE calendar_event_associations;
			DROP TABLE calendar_event_comments;
			DROP TABLE blog_article_comments;
			DROP TABLE blog_articles;
			DROP TABLE blog_themes;
			DROP TABLE blogs;
			DROP TABLE calendar_events_attendees;
			DROP TABLE calendar_event_invitations;
			DROP TABLE calendar_events_sharing;
			DROP TABLE calendar_events_status;
			DROP TABLE calendar_events_field_vals;
			DROP TABLE calendar_fields;
			DROP TABLE calendar_events_holidays;
			DROP TABLE calendar_events_labels;
			DROP TABLE calendar_events_recurring;
			DROP TABLE calendar_events_recurring_ex;
			DROP TABLE calendar_sharing_types;
			DROP TABLE chat_queues;
			DROP TABLE chat_session_content;
			DROP TABLE chat_session_remotes;
			DROP TABLE chat_sessions;
			DROP TABLE color_schemes;
			DROP TABLE community_profiles;
			DROP TABLE company;
			DROP TABLE contact_files;
			DROP TABLE contacts_company;
			DROP TABLE contacts_employee_lists_mem;
			DROP TABLE contacts_personal_act_types;
			DROP TABLE contacts_personal_act;
			DROP TABLE contacts_personal_fields;
			DROP TABLE contacts_personal_field_options;
			DROP TABLE contacts_personal_lists_mem;
			DROP TABLE contacts_personal_lists;
			DROP TABLE contacts_personal_label_share;
			DROP TABLE countries;
			DROP TABLE customer_activity_types;
			DROP TABLE customer_activity;
			DROP TABLE customer_field_values;
			DROP TABLE customer_field_optioins;
			DROP TABLE customer_fields;
			DROP TABLE customer_files;
			DROP TABLE customer_tabs;
			DROP TABLE customer_tasks;
			DROP TABLE customer_testimonials;
			DROP TABLE dc_databases;
			DROP TABLE dc_database_users;
			DROP TABLE dc_database_report_graphs;
			DROP TABLE dc_database_report_graph_options;
			DROP TABLE dc_database_report_graph_cols;
			DROP TABLE dc_database_queries;
			DROP TABLE dc_database_objects;
			DROP TABLE dc_database_folders;
			DROP TABLE dc_database_calendars;
			DROP TABLE dc_dashboard;S
			DROP TABLE email_alias;
			DROP TABLE email_delivery_log;
			DROP TABLE email_domains;
			DROP TABLE email_users;
			DROP TABLE employee;
			DROP TABLE employee_departments;
			DROP TABLE employee_locations;
			DROP TABLE groups;
			DROP TABLE message_comments;
			DROP TABLE messages;
			DROP TABLE object_index_queue;
			DROP TABLE project_bug_comments;
			DROP TABLE project_categories;
			DROP TABLE project_fields_values;
			DROP TABLE project_fields_custom;
			DROP TABLE project_fields;
			DROP TABLE project_log;
			DROP TABLE project_message_comments;
			DROP TABLE project_messages;
			DROP TABLE project_task_bug_mem;
			DROP TABLE project_tasks_recurring_ex;
			DROP TABLE project_tasks_recurring;
			DROP TABLE rss_favorites;
			DROP TABLE themes;
			DROP TABLE user_files_playlist_mem;
			DROP TABLE user_friends;
			DROP TABLE userpref;
			DROP TABLE xml_feed_members;
			DROP TABLE xml_feed_fields;
			DROP TABLE xml_feed_post_values;
			

		 */
	}

	/*******************************************************************************
     *  3/19/2012 - Update schema_revision to 3 to move to new schema update manager
	 ********************************************************************************/
    $thisrev = 2.366;
    if ($revision < $thisrev)
	{
		$queries[] = "INSERT into system_registry(key_name, key_val) VALUES('system/schema_version', '3.1.0');";
	}
/**
 * WARINGIN: This script is no longer used, please use the new SchemaUpdater in /system/schema/updates to make schema changes.
 */

?>
