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
	die("Update failed because \$ant is not defined");

$dbh = $ant->dbh;

// Create index for dacl_id if it does not exist
if (!$dbh->indexExists("email_messages_uid_idx"))
{
	$dbh->Query("CREATE INDEX email_messages_uid_idx
   				ON email_messages (message_uid ASC NULLS LAST);");
}
