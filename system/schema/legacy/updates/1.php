<?php
	/*******************************************************************************
	*	3/5/2007 - Std Updates
	********************************************************************************/
	$thisrev = 1.1;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER SEQUENCE groups_id_seq RESTART WITH 1000;";
		$queries[] = "ALTER SEQUENCE app_home_layout_id_seq RESTART WITH 1000;";
		$queries[] = "ALTER SEQUENCE blog_themes_id_seq RESTART WITH 1000;";
		$queries[] = "ALTER SEQUENCE calendar_events_sharing_id_seq RESTART WITH 1000;";
		$queries[] = "ALTER SEQUENCE calendar_sharing_types_id_seq RESTART WITH 1000;";
		$queries[] = "ALTER SEQUENCE project_bug_types_id_seq RESTART WITH 1000;";
		$queries[] = "ALTER SEQUENCE project_bug_status_id_seq RESTART WITH 1000;";
		$queries[] = "ALTER SEQUENCE project_bug_severity_id_seq RESTART WITH 1000;";
	}
	
	/*******************************************************************************
	*	4/2/2007 - Create threads table for improved performance of email threads
	********************************************************************************/
	$thisrev = 1.2;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE email_threads
						(
						  id bigserial NOT NULL,
						  mailbox_id integer,
						  subject character varying(512),
						  body text,
						  senders text,
						  time_updated timestamp with time zone,
						  num_attachments smallint,
						  num_messages smallint DEFAULT 1::smallint,
						  f_seen boolean DEFAULT 'f',
						  f_bodycached boolean DEFAULT 'f',
						  f_processed boolean DEFAULT 'f',
						  CONSTRAINT email_threads_pkey PRIMARY KEY (id),
						  CONSTRAINT email_threads_boxid_fkey FOREIGN KEY (mailbox_id)
							  REFERENCES email_mailboxes (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITHOUT OIDS;";

		$queries[] = "ALTER SEQUENCE email_threads_id_seq
			  		  RESTART WITH 100000;";

	}


	
	/*******************************************************************************
	*	Add email_message_original for cache of original message text. 
	*	This will be moved to a file on first process or through a schedule job
	*	similar to how email attachements are handled.
	********************************************************************************/
	$thisrev = 1.3;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE email_message_original
						(
						   id bigserial, 
						   message_id bigint, 
						   file_id bigint, 
						   message text, 
						   CONSTRAINT email_message_original_pkey PRIMARY KEY (id), 
						   CONSTRAINT email_message_original_mid_fkey FOREIGN KEY (message_id) REFERENCES email_messages (id)    
						   		ON UPDATE CASCADE ON DELETE CASCADE, 
						   CONSTRAINT email_message_original_fid_fkey FOREIGN KEY (file_id) REFERENCES user_files (id)    
								ON UPDATE CASCADE ON DELETE RESTRICT
						) WITHOUT OIDS;";

	}

	/*******************************************************************************
	*	5/8/2007 - Add special acces_control table to handle things like admin tools
	********************************************************************************/
	$thisrev = 1.4;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE acces_control_special
						(
						  id serial NOT NULL,
						  name character varying(256),
						  acl_id bigint,
						  CONSTRAINT acces_control_special_pkey PRIMARY KEY (id)
						) 
						WITHOUT OIDS;";

	}

	/*******************************************************************************
	*	5/31/2007 - Add the ability to customise the location of customer fields
	********************************************************************************/
	$thisrev = 1.5;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE customer_fields ADD COLUMN fieldset int;";

	}


	/*******************************************************************************
	*	6/7/2007 - Add receivers to threads
	********************************************************************************/
	$thisrev = 1.6;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE email_threads ADD COLUMN receivers text;";

		
	}

	/*******************************************************************************
	*	6/27/2007 - Added last_login_from to users table to track IP
	********************************************************************************/
	$thisrev = 1.7;
	if ($revision < $thisrev)
	{
		$queries[] = "ALTER TABLE users ADD COLUMN last_login_from character varying(32);;";

		
	}


	/*******************************************************************************
	*	7/9/2007 - Added customer associations, system_registry, and delete users_sett..
	********************************************************************************/
	$thisrev = 1.8;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE customer_associations
						(
						   id bigserial, 
						   parent_id int8 NOT NULL, 
						   customer_id int8 NOT NULL, 
						   CONSTRAINT customer_associations_pkey PRIMARY KEY (id), 
						   CONSTRAINT customer_associations_pcid_fkey FOREIGN KEY (parent_id) REFERENCES customers (id)    
						   		ON UPDATE CASCADE ON DELETE CASCADE, 
							CONSTRAINT customer_associations_cid_fkey FOREIGN KEY (customer_id) REFERENCES customers (id)    
								ON UPDATE CASCADE ON DELETE CASCADE, 
						   CONSTRAINT customer_associations_uni UNIQUE (parent_id, customer_id)
					   ) WITHOUT OIDS;";
		
		$queries[] = "CREATE TABLE system_registry
						(
						   id bigserial, 
						   key_name varchar(256) NOT NULL, 
						   key_val text NOT NULL, 
						   account_id int4, 
						   user_id int4, 
						   CONSTRAINT system_registry_pkey PRIMARY KEY (id), 
						   CONSTRAINT system_registry_aid_fkey FOREIGN KEY (account_id) REFERENCES accounts (id)    
						   		ON UPDATE CASCADE ON DELETE CASCADE, 
						   CONSTRAINT system_registry_uid_fkey FOREIGN KEY (user_id) REFERENCES users (id)    
								ON UPDATE CASCADE ON DELETE CASCADE,
						   CONSTRAINT system_registry_user_unique UNIQUE (user_id, key_name)
						) WITHOUT OIDS;";
		
		$queries[] = "insert into system_registry(key_name, key_val, user_id) 
					  select key_name, key_val, user_id from users_settings;";
		

		$queries[] = "drop table users_settings";

		
	}

	/*******************************************************************************
	*	8/10/2007 - Update user_home_layout for custom/foreign widgets
	********************************************************************************/
	$thisrev = 1.9;
	if ($revision < $thisrev)
	{
		$queries[] = "CREATE TABLE app_widgets
						(
						   id serial, 
						   title character varying(64), 
						   class_name character varying(64), 
						   file_name character varying(64), 
						   type character varying(32) DEFAULT 'system', 
						   CONSTRAINT app_widgets_pkey PRIMARY KEY (id)
						) WITHOUT OIDS;";

		$queries[] = "CREATE TABLE user_dashboard_layout
						(
						  id serial,
						  user_id integer NOT NULL,
						  col integer,
						  \"position\" integer,
						  widget_id integer,
						  \"type\" character varying(32) DEFAULT 'system'::character varying,
						  data text,
						  dashboard character varying(64),
						  account_id integer,
						  CONSTRAINT user_dashboard_layout_pkey PRIMARY KEY (id),
						  CONSTRAINT user_dashboard_layout_aid_fkey FOREIGN KEY (account_id)
							  REFERENCES accounts (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT user_dashboard_layout_uid_fkey FOREIGN KEY (user_id)
							  REFERENCES users (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE,
						  CONSTRAINT user_dashboard_layout_widid_fkey FOREIGN KEY (widget_id)
							  REFERENCES app_widgets (id) MATCH SIMPLE
							  ON UPDATE CASCADE ON DELETE CASCADE
						) 
						WITH OIDS;";
		
		$queries[] = "DROP TABLE app_home_layout";

		// Update topnav to use new class for home application
		//$act = $dbh->Escape("top.webMenu.ChangeTitle('');top.Ant.Execute('/home/home.js', 'CHome');");
		//$queries[] = "update app_topnav set action='$act' where title='Home';";

		// Change default layout to user_dashboard_layout
		$queries[] = "DROP TABLE app_home_layout";
		$queries[] = "DROP TABLE user_home_layout";
		$queries[] = "DROP TABLE home_windows";
		
		
	}
?>
