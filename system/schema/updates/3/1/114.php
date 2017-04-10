<?php
/**
 * Set default theme
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

/* Update had a bad query! 
$result = $dbh->Query("SELECT id FROM users WHERE id>'0';");
$num = $dbh->GetNumberRows($result);
for ($i = 0; $i < $num; $i++)
{
	$uid = $dbh->GetValue($result, 0, "id");
	$user = new AntUser($dbh, $uid);
	$user->setValue("theme", '');
	$user->save(false);
}
 */
