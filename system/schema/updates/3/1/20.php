<?php
/**
 * Deal with old message_id
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	exit;
$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);

// Cleanup old array columns in email_thread if it exists
if ($dbh->getColumnType("email_threads", "mailbox_id") == "integer")
{
	$dbh->Query("ALTER TABLE email_threads DROP COLUMN mailbox_id");
	$dbh->Query("ALTER TABLE email_threads ADD COLUMN mailbox_id text");
}
