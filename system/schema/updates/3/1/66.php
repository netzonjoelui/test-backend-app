<?php
/**
 * Update levels for activities
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/CAntObject.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);
$obj = new CAntObject($dbh, "activity", null, $user); // Load to update definition

// Set all defaults
$dbh->Query("UPDATE objects_activity_act SET level='3';"); 

// Downgrade system user activities (hide workflow stuff by default)
$dbh->Query("UPDATE objects_activity_act SET level='1' WHERE user_id<'0';"); 
