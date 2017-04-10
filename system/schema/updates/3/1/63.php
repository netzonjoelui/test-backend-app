<?php
/**
 * Make sure that field_id exists in object_grouping_mem table - might have been missed with new accounts
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

// Make sure DACL col exists
if (!$dbh->ColumnExists("object_grouping_mem", "field_id"))
{
	$dbh->Query("ALTER TABLE object_grouping_mem ADD COLUMN field_id integer");

	$dbh->Query("CREATE INDEX object_grouping_mem_fld_fkey
   				ON object_grouping_mem (field_id);");
}
