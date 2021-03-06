<?php
/**
 * Add f_system to user_groups if it is missing for any reason (there was a bug for the aereus account where
 * this happend).
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

if (!$dbh->ColumnExists("user_groups", "f_system"))
    $dbh->Query("ALTER TABLE user_groups ADD COLUMN f_system bool;");