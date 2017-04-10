<?php
/**
 * This file is used to create default workflows
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	die("This script must be called from the system schema manager and ant mut be set");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);

// New Task Send Email Workflow
$wfObject = new WorkFlow($this->dbh, "uname:new-task-sendmail");
$rev = 2;
if (!$wfObject->id || $wfObject->revision < $rev)
{
    $wfObject->name = "New Task Sendmail";
    $wfObject->notes = "This will send a notification email to the task owner";
    $wfObject->object_type = "task";
    $wfObject->fActive = true;
    $wfObject->fOnCreate = true;
    $wfObject->fOnUpdate = false;
    $wfObject->fOnDelete = false;
    $wfObject->fOnDaily = false;
    $wfObject->fSingleton = false;
    $wfObject->fAllowManual = false;
	$wfObject->setUname("new-task-sendmail");
            
    $wfObject->revision = $rev-1;

	// EDIT: Add condition so workflow will only fire if someone other than the 
	// current user creates the task
	$cond = $wfObject->addCondition();
	$cond->blogic = "and";
	$cond->fieldName = "user_id";
	$cond->operator = "is_not_equal";
	$cond->condValue = USER_CURRENT;

    $wid = $wfObject->save(false);
    
    // Create Send Email Action
	$wfActObject = $wfObject->addAction("uname:task-owner-sendmail");
    //$wfActObject = new WorkFlow_Action($this->dbh); // EDIT: removed because this is a root level action, "uname:task-owner-sendmail"
    $wfActObject->type = WF_ATYPE_SENDEMAIL;
    $wfActObject->name = "Task Owner Sendmail";
    $wfActObject->when_interval = 0;
    $wfActObject->when_unit = 1;
    $wfActObject->setObjectValue("from", "no-reply");
    $wfActObject->setObjectValue("subject", "New Task");
    $wfActObject->setObjectValue("body", "You have been assigned a new task: <%object_link%>"); // EDIT: Added object_link which will provide a link to the end-user directly to the new task
    $wfActObject->setObjectMultiValue("to", "<%user_id%>");
    //$wfActObject->workflow_id = $wid; // EDIT: not needed, addAction above sets this
    $wfActObject->save();
}

// Assigned a case
$wfObject = new WorkFlow($this->dbh, "uname:case-assigned");
$rev = 4;
if (!$wfObject->id || $wfObject->revision < $rev)
{
    $wfObject->name = "Case Assigned Notification";
    $wfObject->notes = "This will send a notification email when a user is assigned to a case";
    $wfObject->object_type = "case";
    $wfObject->fActive = true;
    $wfObject->fOnCreate = true;
    $wfObject->fOnUpdate = true;
    $wfObject->fOnDelete = false;
    $wfObject->fOnDaily = false;
    $wfObject->fConditionUnmet = true; // only if not previously assigned
    $wfObject->fSingleton = false;
    $wfObject->fAllowManual = false;
	$wfObject->setUname("case-assigned");
            
    $wfObject->revision = $rev-1;

	// EDIT: Add condition so workflow will only fire if someone other than the 
	// current user creates the task
	$cond = $wfObject->addCondition();
	$cond->blogic = "and";
	$cond->fieldName = "owner_id";
	$cond->operator = "is_not_equal";
	$cond->condValue = USER_CURRENT;

    $wid = $wfObject->save(false);
    
    // Create Send Email Action
	$wfActObject = $wfObject->addAction("uname:case-assigned-email");
    //$wfActObject = new WorkFlow_Action($this->dbh); // EDIT: removed because this is a root level action, "uname:task-owner-sendmail"
    $wfActObject->type = WF_ATYPE_SENDEMAIL;
    $wfActObject->name = "Case Owner Sendmail";
    $wfActObject->when_interval = 0;
    $wfActObject->when_unit = 1;
    $wfActObject->setObjectValue("from", "no-reply");
    $wfActObject->setObjectValue("subject", "New Case");
    $wfActObject->setObjectValue("body", "You have been assigned a case: <%object_link%>"); 
    $wfActObject->setObjectMultiValue("to", "<%owner_id%>");
    //$wfActObject->workflow_id = $wid; // EDIT: not needed, addAction above sets this
    $wfActObject->save();
}
