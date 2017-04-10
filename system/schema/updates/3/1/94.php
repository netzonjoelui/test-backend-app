<?php
/**
 * This update removes duplicate folders
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/AntFs.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;

// Change groups to text (used to be fkey, is not fkey_multi)
$dbh->Query("ALTER TABLE users DROP COLUMN groups;");
$dbh->Query("ALTER TABLE users ADD COLUMN groups text;");

// Move all group membership into the user object
$result = $dbh->Query("SELECT user_id, group_id FROM user_group_mem ORDER by user_id");
$num = $dbh->GetNumberRows($result);
$lastUid = null;
$user = null;
for ($i = 0; $i < $num; $i++)
{
	$row = $dbh->GetRow($result, $i);

	if ($row['user_id'] != $lastUid || $user == null)
	{
		$user = CAntObject::factory($dbh, "user", $row['user_id']);
	}

	if ($user && $row['user_id'])
	{
		echo "\tAdded {$row['group_id']} to user {$row['user_id']}\n";
		$user->setMValue("groups", $row['group_id']);
		$user->save(false);
	}
}
