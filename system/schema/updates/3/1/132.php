<?php
/**
 * Make sure custom objects have all required system properties
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
$loader = $sl->get("EntityDefinitionLoader");
$dm = $sl->get("EntityDefinition_DataMapper");

$results = $dbh->Query("SELECT name FROM app_object_types;");
for ($i = 0; $i < $dbh->GetNumberRows($results); $i++)
{
	$row = $dbh->GetRow($results, $i);

	$def = $loader->get($row['name']);
	$dm->save($def);
	echo "\tSaved {$row['name']}\n";
}