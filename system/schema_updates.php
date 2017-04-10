<?php
/**
 * Everything is relative to the application root now.
 */
chdir(dirname(__DIR__));


/************************************************************************
 * This contains a list of SQL schema updates for the datbase
 * **********************************************************************/
require_once("init_application.php");
require_once("lib/AntConfig.php");
require_once("lib/Ant.php");
require_once("settings/settings_functions.php");		
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/AntUser.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("display_errors", "On");	
ini_set("memory_limit", "2G");	

$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

//$ACCOUNT_DB = "ant_demo";
if ($_SERVER['argv'][1] && $_SERVER['argv'][1]!="all")
	$ACCOUNT_DB = $_SERVER['argv'][1];

// First update the antsystem database
// -------------------------------------------------------------------------------------------
$queries = array();
$routines = array();
$revision = 0;

if ($dbh_sys->TableExists("settings"))
{
	$result = $dbh_sys->Query("select value from settings where name='schema_revision'");
	if ($dbh_sys->GetNumberRows($result))
	{
		$row = $dbh_sys->GetNextRow($result, 0);
		$revision = $row['value'];
	}
}
if (!$revision)
	$revision = 1.0; // Set default, starting query is 1.1
		
// Get updates
include(AntConfig::getInstance()->application_path . "/system/schema/antsystem/updates.php");
		
if (count($queries))
{
	foreach ($queries as $query)
	{
		$dbh_sys->Query($query);
	}
}

// Get dataset updates
//include(AntConfig::getInstance()->application_path . "/system/schema/antsystem/data.php");

// Update last rev
$dbh_sys->Query("DELETE FROM settings where name='schema_revision';");
$dbh_sys->Query("INSERT INTO settings(name, value) values('schema_revision', '$thisrev');");

// Get database to use from account
// -------------------------------------------------------------------------------------------
if (AntConfig::getInstance()->version) // limit to current version
{
	$res_sys = $dbh_sys->Query("select id from accounts where version='".AntConfig::getInstance()->version."' 
									".(($ACCOUNT_DB)?" and name='$ACCOUNT_DB'":''));
}
else
{
	$res_sys = $dbh_sys->Query("select id from accounts where version is null ".(($ACCOUNT_DB)?" and name='$ACCOUNT_DB'":''));
}

$num_sys = $dbh_sys->GetNumberRows($res_sys);
for ($s = 0; $s < $num_sys; $s++)
{
	$aid = $dbh_sys->GetValue($res_sys, $s, 'id');
	$ant = new Ant($aid);
	
	if (!$HIDE_MESSAGES)
		echo "Updating ".$ant->accountName."\n";

	// Update schmea to latest version
	try {
		$ret = $ant->schemaUpdate();
	} catch (Exception $ex) {
		echo "There was an error updating " . $ant->accountName . ":" . $ex->getMessage() . "\n";
	}
}
