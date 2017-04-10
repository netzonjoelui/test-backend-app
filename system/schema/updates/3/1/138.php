<?php
/**
 * There was a bug in the EntitySync\Partner class that resulted in many duplicate collections being created.
 *
 * Ths script cleans up the duplicates and leaves the original.
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

// Select one duplicate at a time so we make sure we always leave at least one copy of the duplicate
$sql = "select * from (
  SELECT id,
  ROW_NUMBER() OVER(PARTITION BY partner_id, object_type, field_name, conditions ORDER BY id DESC) AS Row,
  partner_id,
  object_type,
  field_name,
  conditions
  FROM object_sync_partner_collections
) dups
where
dups.Row > 1 LIMIT 1";

while (($num = $dbh->GetNumberRows($results = $dbh->Query($sql)))>0)
{
    $row = $dbh->GetRow($results, 0);

    $sqlDelete = "DELETE FROM object_sync_partner_collections
                    WHERE
                        partner_id=" . $dbh->EscapeNumber($row['partner_id']) . "
                        AND object_type='" . $dbh->Escape($row['object_type']) . "'
                        AND field_name='" . $dbh->Escape($row['field_name']) . "'
                        AND id!='" . $row['id'] . "'
                        AND conditions='" . $dbh->Escape($row['conditions']) . "';";
    if (!$dbh->Query($sqlDelete))
        throw new \Exception("SQL ERROR: " . $dbh->getLastError());
}