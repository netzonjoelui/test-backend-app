<?php
/**
 * Fix bug wih  object definition revisions updating past the local revision on every save
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/Dacl.php");
require_once("lib/ServiceLocator.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;
$sl = ServiceLocator::getInstance($ant);

$results = $dbh->Query("SELECT name FROM app_object_types WHERE f_system='t';");
for ($i = 0; $i < $dbh->GetNumberRows($results); $i++)
{
	$row = $dbh->GetRow($results, $i);

	$loader = $sl->get("EntityDefinitionLoader");
	$loader->forceSystemReset($row['name']);
}