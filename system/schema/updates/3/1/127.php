<?php
/**
 * Queue spam messages for sa-learn
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/Dacl.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;

if (!$dbh->ColumnExists("object_sync_stats", "revision"))
    $dbh->Query("ALTER TABLE object_sync_stats ADD COLUMN revision integer;");