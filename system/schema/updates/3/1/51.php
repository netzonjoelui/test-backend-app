<?php
/**
 * This update moves activity to the new object partitioned table structure
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	die("Update 51 failed because $ ant is not defined");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);

// Make sure DACL col exists
if (!$dbh->ColumnExists("activity", "dacl"))
	$dbh->Query("ALTER TABLE activity ADD COLUMN dacl text");

if (!$dbh->ColumnExists("objects", "dacl"))
	$dbh->Query("ALTER TABLE objects ADD COLUMN dacl text");


$query = "select id from app_object_types where name='activity'";
$results = $dbh->Query($query);
if ($results)
	$typeId = $dbh->GetValue($results, 0, "id");

if ($typeId)
{
	$query = "CREATE TABLE objects_activity
				(
				  name character varying(256),
				  type_id integer,
				  type_id_fval text,
				  notes text,
				  user_id integer,
				  user_id_fval text,
				  user_name character varying(256),
				  f_readonly boolean DEFAULT false,
				  direction character(1),
				  associations text,
				  associations_fval text,
				  activity text,
				  activity_fval text,
				  comments text,
				  comments_fval text,
				  obj_reference character varying(512),
				  obj_reference_fval text,
				   CHECK(object_type_id='$typeId')
				) 
				INHERITS (objects)";
	$dbh->Query($query);


	$query = "CREATE TABLE objects_activity_act
				(
					CONSTRAINT objects_activity_act_pkey PRIMARY KEY (id),
					CHECK(object_type_id='$typeId' and f_deleted='f')
				)
				INHERITS (objects_activity);";
	$dbh->Query($query);

	$query = "CREATE TABLE objects_activity_del
				(
					CONSTRAINT objects_activity_del_pkey PRIMARY KEY (id),
					CHECK(object_type_id='$typeId' and f_deleted='t')
				)
				INHERITS (objects_activity);";
	$dbh->Query($query);

	// Now update the activity object to use standard table rather than custom
	$dbh->Query("UPDATE app_object_types SET object_table=NULL WHERE name='activity'");

	// Update the object definition - this will create any missing columns so the query does not fail
	$obj = CAntObject::factory($dbh, "activity");

	// copy undeleted
	echo "\tcopying undeleted activity...\t\t";
	$query = "INSERT INTO objects_activity_act(
				id, object_type_id, revision, ts_entered, 
				ts_updated, owner_id, owner_id_fval, f_deleted, 
				uname, path, name, type_id, type_id_fval, notes,
				user_id, user_id_fval, user_name, f_readonly,
				direction, associations, associations_fval,
				activity, activity_fval, comments, comments_fval,
				obj_reference, obj_reference_fval, dacl
				)
			  SELECT 
				id, '$typeId' as object_type_id, revision, ts_entered, 
				ts_updated, user_id, user_id_fval, 'f' as f_deleted, 
				uname, path, name, type_id, type_id_fval, notes,
				user_id, user_id_fval, user_name, f_readonly,
				direction, associations, associations_fval,
				activity, activity_fval, comments, comments_fval,
				obj_reference, obj_reference_fval, dacl
				FROM activity WHERE f_deleted is not true";
	$dbh->Query($query);
	echo "[done]\n";

	// copy deleted
	echo "\tcopying deleted activity...\t\t";
	$query = "INSERT INTO objects_activity_del(
				id, object_type_id, revision, ts_entered, 
				ts_updated, owner_id, owner_id_fval, f_deleted, 
				uname, path, name, type_id, type_id_fval, notes,
				user_id, user_id_fval, user_name, f_readonly,
				direction, associations, associations_fval,
				activity, activity_fval, comments, comments_fval,
				obj_reference, obj_reference_fval, dacl
				)
			  SELECT 
				id, '$typeId' as object_type_id, revision, ts_entered, 
				ts_updated, user_id, user_id_fval, 't' as f_deleted, 
				uname, path, name, type_id, type_id_fval, notes,
				user_id, user_id_fval, user_name, f_readonly,
				direction, associations, associations_fval,
				activity, activity_fval, comments, comments_fval,
				obj_reference, obj_reference_fval, dacl
				FROM activity WHERE f_deleted is true";
	$dbh->Query($query);
	echo "[done]\n";
}
