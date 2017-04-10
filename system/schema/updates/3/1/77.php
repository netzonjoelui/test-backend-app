<?php
/**
 * This update moves email_message to the new object partitioned table structure
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);

$query = "select id from app_object_types where name='email_message'";
$results = $dbh->Query($query);
if ($results)
	$typeId = $dbh->GetValue($results, 0, "id");

if ($typeId)
{
	// Now update the object to use standard table rather than custom
	$dbh->Query("UPDATE app_object_types SET object_table=NULL WHERE name='email_message'");

	// Use object definition to create table
	$obj = CAntObject::factory($dbh, "email_message");
	$obj->fields->clearCache();
	$obj = CAntObject::factory($dbh, "email_message"); // reload from table after update above
	$obj->fields->createObjectTable();
	$obj->fields->verifyAllFields();

	$cols = array();
	$fields = $obj->fields->getFields();
	foreach ($fields as $fname=>$fdef)
	{
		$cols[] = $fname;

		if ($fdef['type'] == 'fkey' || $fdef['type'] == 'fkey_multi' || $fdef['type'] == 'object' || $fdef['type'] == 'object_multi')
			$cols[] = $fname . "_fval";
	}

	// Copy undeleted
	// ------------------------------------------------------
	echo "\tcopying undeleted objects_email_message...\t\t";
	$query = "INSERT INTO objects_email_message_act(
				object_type_id ";
	foreach ($cols as $cname)
		$query .= ", " . $cname;
	$query .= "	) SELECT ";
	$query .= "	'$typeId' as object_type_id ";
	foreach ($cols as $cname)
		$query .= ", " . $cname;
	$query .= " FROM email_messages WHERE f_deleted is false";
	$dbh->Query($query);
	echo "[done]\n";
	if ($ret === false)
		echo "[failed]\n--------------------\n" . $dbh->getLastError() . "\n";
	else
		echo "[done]\n";

	// Copy deleted
	// ------------------------------------------------------
	echo "\tcopying deleted objects_email_message_del...\t\t";
	$query = "INSERT INTO objects_email_message_del(
				object_type_id ";
	foreach ($cols as $cname)
		$query .= ", " . $cname;
	$query .= "	) SELECT ";
	$query .= "	'$typeId' as object_type_id ";
	foreach ($cols as $cname)
		$query .= ", " . $cname;
	$query .= " FROM email_messages WHERE f_deleted is true AND message_date>'1/1/2012'";
	if ($ret !== false)
		$ret = $dbh->Query($query);
	if ($ret === false)
		echo "[failed]\n--------------------\n" . $dbh->getLastError() . "\n";
	else
		echo "[done]\n";
}
