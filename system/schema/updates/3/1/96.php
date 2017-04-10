<?php
/**
 * Remove duplciate DACL entries
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

// Find duplicates
$result = $dbh->Query("SELECT name, COUNT(name) AS NumOccurrences
						FROM security_dacl
						GROUP BY name
						HAVING ( COUNT(name) > 1 );");
$num = $dbh->GetNumberRows($result);
for ($i = 0; $i < $num; $i++)
{
	$row = $dbh->GetRow($result, $i);

	$dacl = new Dacl($dbh, $row['name']);
	$dacl->clearCache();

	// Keep just the dacl that gets loaded
	if ($dacl->id)
	{
		$dbh->Query("DELETE FROM security_dacl WHERE name='" . $dbh->Escape($row['name']) . "' and id!='" . $dacl->id . "'");
	}
}
