<?php
/**
 * Delete an account
 */
require_once("../lib/AntConfig.php");
require_once("lib/CDatabase.awp");
require_once("lib/AntSystem.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE);

if (!$_SERVER['argv'][1])
	die("Account name is required as first param");

$accountName = $_SERVER['argv'][1];

// TODO: should we back this up?
$sys = new AntSystem();
$sys->deleteAccount($accountName);
