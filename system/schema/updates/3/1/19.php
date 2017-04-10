<?php
/**
 * This update assures that all existing fields of type=fkey, fkey_multi, object and object_multi are denomalized in the object table
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	exit;
$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);

$query = "select app_object_type_fields.name, app_object_types.object_table, app_object_types.id
		FROM
			app_object_types, app_object_type_fields
		WHERE
			app_object_types.id=app_object_type_fields.type_id
			AND (app_object_type_fields.type='fkey_multi'
			OR app_object_type_fields.type='fkey'
			OR app_object_type_fields.type='object_multi'
			OR app_object_type_fields.type='object');";
$results = $dbh->Query($query);
if (!$results)
	$ret = false;

for ($i = 0; $i < $dbh->GetNumberRows($results); $i++)
{
	$row = $dbh->GetRow($results, $i);

	$tbl = $row['object_table'];

	if (!$tbl) // not a custom table
		$tbl = "objects_" . $row['id'];

	// Cleanup old array columns (bad code in CAntObjectFields)
	if ($dbh->getColumnType($tbl, $row['name']) == "ARRAY")
		$dbh->Query("ALTER TABLE ".$tbl." DROP COLUMN ".$row['name']);

	// Add column for storing data
	if (!$dbh->ColumnExists($tbl, $row['name']))
		$dbh->Query("ALTER TABLE ".$tbl." ADD COLUMN ".$row['name']." text");

	// Add column for storing data foreign values
	if (!$dbh->ColumnExists($tbl, $row['name']."_fval"))
		$dbh->Query("ALTER TABLE ".$tbl." ADD COLUMN ".$row['name']."_fval text");
}
