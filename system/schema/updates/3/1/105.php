<?php
/**
 * Move vertical object_revisions data to horizontal serialized data field
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
 * Recurrsive function to reduce dataset size
 *
 * @param CDatabase $dbh
 */
function sysUpdate_105($dbh)
{
	$result = $dbh->Query("SELECT id
							FROM object_revisions
							WHERE data is NULL LIMIT 1000;");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$rid = $dbh->GetValue($result, $i, "id");

		$data = array();
		$res2 = $dbh->Query("select field_name, field_value FROM object_revision_data WHERE revision_id='".$rid."'");
		$num2 = $dbh->GetNumberRows($res2);
		for ($j = 0; $j < $num2; $j++)
		{
			$row2 = $dbh->GetRow($res2, $j);
			$data[$row2["field_name"]] = $row2["field_value"];
		}
		$dbh->FreeResults($res2);

		$dbh->Query("UPDATE object_revisions set data='" . $dbh->Escape(serialize($data)) . "' WHERE id='$rid'");
	}
	$dbh->FreeResults($result);

	return $num;
}

while (sysUpdate_105($dbh))
{
	echo "\tGetting next page\n";
}
