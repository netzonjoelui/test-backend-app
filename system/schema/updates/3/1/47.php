<?php
/**
 * This updates checks for the existence of an index on the dacl_id of the security_acle table
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	die("Update 47 failed because \$ant is not defined");

$dbh = $ant->dbh;

// Create index for dacl_id if it does not exist
if (!$dbh->indexExists("security_acle_dacl_idx"))
{
	$dbh->Query("CREATE INDEX security_acle_dacl_idx
				  ON security_acle
				  USING btree
				  (dacl_id );");
}
