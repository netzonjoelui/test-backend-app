<?php
/**
 * Move public schema to account schema if not alraedy set
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/Dacl.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;

if ($dbh->setSchema("acc_" . $ant->id) === false)
{
	$dbh->Query("ALTER SCHEMA public RENAME TO acc_" . $ant->id . ";");
	$dbh->setSchema("acc_" . $ant->id); // set for future updates
}
