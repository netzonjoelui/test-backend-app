<?php
/**
 * Move all object type IDs to obj_type names
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

$sql = "ALTER TABLE workflow_instances ADD COLUMN object_type CHARACTER VARYING(128)";
$dbh->Query($sql);

// Now convert old IDs to object_type strings
$sql = "UPDATE workflow_instances SET
        object_type=(
            SELECT name FROM app_object_types WHERE app_object_types.id=workflow_instances.object_type_id
        ) WHERE object_type is NULL;";
$results = $dbh->Query($sql);