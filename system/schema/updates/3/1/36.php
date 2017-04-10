<?php
/**
 * This file is responsible for moving dynamic object tables to name based from id based
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/AntFs.php");

if (!$ant)
	die("This file must be called from the AntSystem class");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);
$ok = true; // flag used to fail if something goes wrong

// Set user id of all files with a null user id
// -------------------------------------------------------
$query = "SELECT id, name FROM app_object_types WHERE object_table is null or object_table=''";
$result = $dbh->Query($query);
$num = $dbh->GetNumberRows($result);
for ($i = 0; $i < $num; $i++)
{
	$row = $dbh->GetRow($result, $i);

	// Rename base
	if ($dbh->TableExists("objects_" . $row['id']))
		$dbh->Query("ALTER TABLE objects_" . $row['id']." RENAME TO objects_" . $row['name'].";");

	// Rename active
	if ($dbh->TableExists("objects_" . $row['id'] . "_act"))
		$dbh->Query("ALTER TABLE objects_" . $row['id']."_act RENAME TO objects_" . $row['name']."_act;");

	// Rename deleted
	if ($dbh->TableExists("objects_" . $row['id'] . "_del"))
		$dbh->Query("ALTER TABLE objects_" . $row['id']."_del RENAME TO objects_" . $row['name']."_del;");

	// Delete old indexes but exclude default system indexes
	$res2 = $dbh->Query("select name from app_object_type_fields where type_id='".$row['id']."'
						 and name!='id' and name!='uname';");
	$num2 = $dbh->GetNumberRows($res2);
	for ($j = 0; $j < $num2; $j++)
	{
		$fname = $dbh->GetValue($res2, $j, "name");

		echo "\tDropping index: objects_" . $row['id'] . "_*_" . $fname . "_idx\n";

		$idxname = "objects_" . $row['id'] . "_act_" . $fname . "_idx";
		if ($dbh->indexExists($idxname))
			$dbh->Query("DROP INDEX $idxname;");

		$idxname = "objects_" . $row['id'] . "_del_" . $fname . "_idx";
		if ($dbh->indexExists($idxname))
			$dbh->Query("DROP INDEX $idxname;");
	}
}
