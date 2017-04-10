<?php
/**
 * Move column names for workflows to data, and actions with schedules to parent WaitCondition actions
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

/*
 * First copy old action property fields to 'data'
 */
$sql = "SELECT
          id,
          send_email_fid,
          update_field,
          update_to,
          create_object,
          start_wfid,
          stop_wfid
        FROM workflow_actions
        WHERE data is NULL";
$num = $dbh->GetNumberRows($results = $dbh->Query($sql));
for ($i = 0; $i < $num; $i++)
{
    $row = $dbh->GetRow($results, $i);

    $data = [];
    foreach ($row as $key=>$value)
    {
        // Only set if we have a value and if not the ID
        if ($value && $key!='id')
            $data[$key] = $value;
    }

    // Now copy all workflow_object_values
    $sql = "SELECT id, field, value FROM workflow_object_values WHERE
            (f_array='f' OR (f_array='t' and parent_id is null) ) AND action_id=" . $row['id'];
    $res2 = $dbh->Query($sql);
    for ($j = 0; $j < $dbh->GetNumberRows($res2); $j++)
    {
        $row2 = $dbh->GetRow($res2, $j);

        if ($row2['f_array'] === 't')
        {
            if (!isset($data[$row2['field']]))
                $data[$row2['field']] = [];

            $sql = "SELECT value FROM workflow_object_values WHERE
                    parent_id='" . $row2['id'] . "' AND action_id=" . $row['id'];
            $res3 = $dbh->Query($sql);
            for ($y = 0; $y < $dbh->GetNumberRows($res3); $y++)
            {
                $row3 = $dbh->GetRow($res3, $y);
                $data[$row2['field']][] = $row3['value'];
            }
        }
        else
        {
            $data[$row2['field']] = $row2['value'];
        }
    }

    // Now set the data
    if (count($data))
    {
        $encData = json_encode($data);
        $sql = "UPDATE workflow_actions SET  data='" . $dbh->Escape($encData) . "' WHERE id=" . $row['id'];
        $dbh->Query($sql);
    }
}

/*
 * Second copy workflow_object_values
 */
$sql = "SELECT
          id,
          send_email_fid,
          update_field,
          update_to,
          create_object,
          start_wfid,
          stop_wfid
        FROM workflow_object_values
        WHERE data is NULL";
$num = $dbh->GetNumberRows($results = $dbh->Query($sql));
for ($i = 0; $i < $num; $i++)
{
    $row = $dbh->GetRow($results, $i);

    $data = [];
    foreach ($row as $key=>$value)
    {
        // Only set if we have a value and if not the ID
        if ($value && $key!='id')
            $data[$key] = $value;
    }

    if (count($data))
    {
        $encData = json_encode($data);
        $sql = "UPDATE workflow_actions SET  data='" . $dbh->Escape($encData) . "' WHERE id=" . $row['id'];
        $dbh->Query($sql);
    }
}

// Remove not null constraint of workflow_actions
$results = $dbh->Query("ALTER TABLE workflow_actions ALTER COLUMN type DROP NOT NULL");

// Make sure workflow exists as an object
if (!$dbh->GetNumberRows($dbh->Query("select * form app_object_types where name='workflow'"))) {
    $dbh->Query("INSERT INTO app_object_types(name, title, object_type, revision, system)
                 VALUES('workflow', 'Workflow', 'workflows', '0', 't'))");
}

// Make sure workflow_action exists as an object
if (!$dbh->GetNumberRows($dbh->Query("select * form app_object_types where name='workflow_action'"))) {
    $dbh->Query("INSERT INTO app_object_types(name, title, object_type, revision, system)
                 VALUES('workflow_action', 'Workflow Action', 'workflow_actions', '0', 't'))");
}


/*
 * Now move all when_* actions to child actions of a new wait condition action
 */
$workFlowDataMapper = $serviceManager->get("Netric/WorkFlow/DataMapper/DataMapper");
$sql = "SELECT
          id,
          when_interval,
          when_unit,
          workflow_id,
          parent_action_id,
          parent_action_event
        FROM workflow_actions WHERE when_interval>0";
$num = $dbh->GetNumberRows($results = $dbh->Query($sql));
for ($i = 0; $i < $num; $i++)
{
    $row = $dbh->GetRow($results, $i);

    // Open the workflow
    $workFlow = $workFlowDataMapper->getById($row['workflow_id']);

    // Create a new action id that is for waiting only
    $actionFactory = new Netric\WorkFlow\Action\ActionFactory($serviceManager);
    $waitAction = $actionFactory->create("wait_condition");
    $waitAction->setParam("wait_interval", $row['wait_interval']);
    $waitAction->setParam("when_unit", $row['when_unit']);
    $workFlow->addAction($waitAction);

    // Save the workflow
    $workFlowDataMapper->save($workFlow);


    // Update the old action to be a child of the new one
    $dbh->Query("UPDATE
                  workflow_actions SET
                  parent_action_id='" . $waitAction->getId() ."',
                  when_interval=0
                 WHERE id='" . $row['id'] . "'");

    echo "\tMoved {$row['id']} to be a child of " . $waitAction->getId() . "\n";
    echo "\tProcessed $i of $num\n";
}