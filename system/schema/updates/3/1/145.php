<?php
/**
 * Move all type IDs for workflow actions to names
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/WorkFlow.php");
require_once("lib/WorkFlow/Action.php");

if (!$ant)
    die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;

$sql = "select id, type from workflow_actions where type_name is null and type is not null";
$num = $dbh->GetNumberRows($results = $dbh->Query($sql));
for ($i = 0; $i < $num; $i++)
{
    $row = $dbh->GetRow($results, $i);
    $typeName = WorkFlow_Action::getTypeNameFromId($row['type']);
    $dbh->Query("UPDATE workflow_actions SET type_name='$typeName' WHERE id='" . $row['id'] . "'");
}