<?php
/**
 * Everything is relative to the application root now.
 */
chdir(dirname(__DIR__));

/**
 * Create AntConfig::default_account locally if it does not exist
 *
 * This is usually used for development to create a local development instance of ANT.
 */
require_once("init_application.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

//error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("memory_limit", "200M");

/**
 * Perform some basic enviroment validation
 */
if (!getenv('APPLICATION_ENV'))
	die("No APPLICATION_ENV variable has been set. Please add it to your system variables before running this command.");

if (!Netric\Config::getInstance()->default_account)
	die("The 'default_account' variable was not found in the current config. This variable is required. Please check your config and add it.");

/**
 * Make sure the data directory exists
 */
if (!file_exists(Netric\Config::getInstance()->data_path))
{
	if (!mkdir(Netric\Config::getInstance()->data_path, 0777))
		die(
			"Could not create data directory " .
			Netric\Config::getInstance()->data_path .
			"! Please create it manually, set permissions to 777, and re-run this script."
		);
}

/**
 * Make sure that the system database exists
 */
$dbh = new CDatabase(Netric\Config::getInstance()->db['syshost'], "template1");
$result = $dbh->Query("select * from pg_database where datname='".Netric\Config::getInstance()->db['sysdb']."'");
if (!$dbh->GetNumberRows($result))
{
	// create antsystem database
	$res = $dbh->Query("CREATE DATABASE ".Netric\Config::getInstance()->db['sysdb'].";");
    if ($res === false) {
        die("Could not create ansystem database: " . $dbh->getLastError());
    }
	
	$dbh = new CDatabase(Netric\Config::getInstance()->db['syshost'], Netric\Config::getInstance()->db['sysdb']);

	// Get queries from create script
	$queries = array();
	include("system/schema/antsystem/create.php");
	foreach ($queries as $query)
	{
		$ret = $dbh->Query($query);
		if ($ret === false)
		{
			echo "Could not initailize antsystem database. Error: ".$dbh->lastError."\n";
			exit;
		}
	}
}

/**
 * Make sure that the default account database exists
 */
$dbh = new CDatabase(Netric\Config::getInstance()->db['syshost'], "template1");
$result = $dbh->Query("select * from pg_database where datname='".Netric\Config::getInstance()->db['accdb']."'");
if (!$dbh->GetNumberRows($result)) {
	// create account database
	$res = $dbh->Query("CREATE DATABASE ".Netric\Config::getInstance()->db['accdb'].";");
	if ($res === false) {
		die("Could not create ansystem database: " . $dbh->getLastError());
	}
}

/**
 * Create local ant database using settings local host
 */
$antsys = new AntSystem();
$ret = $antsys->createAccount(Netric\Config::getInstance()->default_account);
if ($ret == false)
{
	echo "Create Account Error: ".$antsys->lastError."\n";
	exit;
}

/**
 * Add default user to the new account
 */
$ant = new Ant($ret['id']);
$user = $ant->createUser("test@myaereus.com", "test");
if (!$user)
	echo "Error creating user: ".$ant->lastError."\n\n";
$user->addToGroup(GROUP_ADMINISTRATORS);

echo "\nCreated initial user with username='test@myaereus.com' and password='test'\n";

/**
 * Inform the user that we are done
 */
echo "\n*** FINISHED CREATING DEFAULT ACCOUNT ($settings_default_account) ***\n";
