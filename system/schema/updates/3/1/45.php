<?php
/**
 * This update moves email threads to the new object partitioned table structure
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	die("Update 45 failed because $ ant is not defined");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);

// Make sure DACL col exists
if (!$dbh->ColumnExists("email_threads", "dacl"))
	$dbh->Query("ALTER TABLE email_threads ADD COLUMN dacl text");
if (!$dbh->ColumnExists("objects", "dacl"))
	$dbh->Query("ALTER TABLE objects ADD COLUMN dacl text");

// Remove constraint
$dbh->Query("ALTER TABLE email_thread_mailbox_mem DROP CONSTRAINT email_thread_mailbox_mem_tid_fkey;");

$query = "select id from app_object_types where name='email_thread'";
$results = $dbh->Query($query);
if ($results)
	$typeId = $dbh->GetValue($results, 0, "id");

if ($typeId)
{
	$query = "CREATE TABLE objects_email_thread
				(
				   subject text, 
				   body text, 
				   senders text, 
				   receivers text, 
				   time_updated timestamp with time zone, 
				   ts_delivered timestamp with time zone, 
				   num_attachments smallint DEFAULT 0, 
				   num_messages smallint DEFAULT 0, 
				   f_seen boolean DEFAULT false, 
				   f_flagged boolean DEFAULT false, 
				   activity text, 
				   activity_fval text, 
				   comments text, 
				   comments_fval text, 
				   dacl text,
				   CHECK(object_type_id='$typeId')
				) 
				INHERITS (objects)";
	$dbh->Query($query);


	$query = "CREATE TABLE objects_email_thread_act
				(
					CONSTRAINT objects_email_thread_act_pkey PRIMARY KEY (id),
					CHECK(object_type_id='$typeId' and f_deleted='f')
				)
				INHERITS (objects_email_thread);";
	$dbh->Query($query);

	$query = "CREATE TABLE objects_email_thread_del
				(
					CONSTRAINT objects_email_thread_del_pkey PRIMARY KEY (id),
					CHECK(object_type_id='$typeId' and f_deleted='t')
				)
				INHERITS (objects_email_thread);";
	$dbh->Query($query);

	// copy undeleted
	echo "\tcopying undeleted email_threads...\t\t";
	$query = "INSERT INTO objects_email_thread_act(
				id, object_type_id, revision, ts_entered, 
				ts_updated, owner_id, owner_id_fval, f_deleted, 
				uname, path, subject, body, senders, receivers, 
				time_updated, ts_delivered, num_attachments, num_messages, 
				f_seen, f_flagged, activity, activity_fval, comments, comments_fval, dacl
				)
			  SELECT 
				id, '$typeId' as object_type_id, revision, ts_delivered as ts_entered, 
				time_updated as ts_updated, owner_id, owner_id_fval, 'f' as f_deleted, 
				uname, path, subject, body, senders, receivers, 
				time_updated, ts_delivered, num_attachments, num_messages, 
				f_seen, f_flagged, activity, activity_fval, comments, comments_fval, dacl
				FROM email_threads WHERE f_deleted is not true";
	$dbh->Query($query);
	echo "[done]\n";

	// copy deleted
	echo "\tcopying deleted email_threads...\t\t";
	$query = "INSERT INTO objects_email_thread_del(
				id, object_type_id, revision, ts_entered, 
				ts_updated, owner_id, owner_id_fval, f_deleted, 
				uname, path, subject, body, senders, receivers, 
				time_updated, ts_delivered, num_attachments, num_messages, 
				f_seen, f_flagged, activity, activity_fval, comments, comments_fval, dacl
				)
			  SELECT 
				id, '$typeId' as object_type_id, revision, ts_delivered as ts_entered, 
				time_updated as ts_updated, owner_id, owner_id_fval, 't' as f_deleted, 
				uname, path, subject, body, senders, receivers, 
				time_updated, ts_delivered, num_attachments, num_messages, 
				f_seen, f_flagged, activity, activity_fval, comments, comments_fval, dacl
				FROM email_threads WHERE f_deleted is true";
	$dbh->Query($query);
	echo "[done]\n";

	// Now update the email_thread object to use standard table rather than custom
	$dbh->Query("UPDATE app_object_types SET object_table=NULL WHERE name='email_thread'");
}
