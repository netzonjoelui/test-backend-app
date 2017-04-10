<?php
/**
 * This update add the objects_* full text query column and creates indexes for existing objects
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	die("Update failed because \$ant is not defined");

$dbh = $ant->dbh;

// Add column to master table
$dbh->Query("ALTER TABLE objects ADD COLUMN tsv_fulltext tsvector;");


$query = "select name FROM app_object_types WHERE object_table is null OR object_table=''";
$results = $dbh->Query($query);
for ($i = 0; $i < $dbh->GetNumberRows($results); $i++)
{
	$oname = $dbh->GetValue($results, $i, "name");
	$obj_table = "objects_" . $oname;

	$dbh->Query("CREATE INDEX ".$obj_table."_act_tsv_fulltext_idx
							  ON ".$obj_table."_act
							  USING gin (tsv_fulltext)
							  where tsv_fulltext is not null;");

	$dbh->Query("CREATE INDEX ".$obj_table."_del_tsv_fulltext_idx
							  ON ".$obj_table."_del
							  USING gin (tsv_fulltext)
							  where tsv_fulltext is not null;");
}
