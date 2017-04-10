<?php
/**
 * Make sure user_status exists for the calendar_events table
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

if (!$dbh->ColumnExists("calendar_events", "user_status"))
	$dbh->Query("ALTER TABLE calendar_events ADD COLUMN user_status integer;");
