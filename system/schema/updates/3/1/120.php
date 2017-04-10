<?php
/**
 * Set default theme - second try after first update failed
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

/**
 * Next update corrects bug below where only pulling user 0
$result = $dbh->Query("SELECT id FROM users WHERE id>'0';");
$num = $dbh->GetNumberRows($result);
for ($i = 0; $i < $num; $i++)
{
	$uid = $dbh->GetValue($result, 0, "id");
	$user = new AntUser($dbh, $uid);
	$user->setValue("theme", '');
	$user->save(false);

    $cache = CCache::getInstance();
	$cache->remove($dbh->dbname."/users/".$uid."/theme");
}
*/
