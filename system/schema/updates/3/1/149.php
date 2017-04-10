<?php
/**
 * Move conditions in workflows to be json encoded rather than a separate table
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
$serviceManager = $ant->getNetricAccount()->getServiceManager();
$entityLoader = $serviceManager->get("EntityLoader");

// Load up the workflow entity to make sure the definition is updated
$entityLoader->create("workflow");

/*
 * First get all workflow IDs
 */
$workflowIds = array();
$sql = "SELECT id FROM workflows WHERE conditions is NULL";
$num = $dbh->GetNumberRows($results = $dbh->Query($sql));
for ($i = 0; $i < $num; $i++)
{
    $row = $dbh->GetRow($results, $i);
    $workflowIds[] = $row['id'];
}
/*
 * First get all workflow IDs
 */
foreach ($workflowIds as $wfid)
{
    $conditions = array();
    $sql = "SELECT
              blogic, field_name, operator, cond_value
            FROM workflow_conditions
            WHERE workflow_id='$wfid'";
    $num = $dbh->GetNumberRows($results = $dbh->Query($sql));
    for ($i = 0; $i < $num; $i++)
    {
        $row = $dbh->GetRow($results, $i);
        $conditions[] = $row;
    }

    if (count($conditions))
    {
        $entity = $entityLoader->get("workflow", $wfid);
        $entity->setValue("conditions", json_encode($conditions));
        $entityLoader->save($entity);
    }
}
