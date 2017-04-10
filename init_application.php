<?php
/** 
 * Root level application initialization
 * 
 * This is similar to a 'main' routine in that it MUST be included in all executed scripts
 * because it is responsible for setting up and initializing the netric application and account.
 * 
 *  @author joe <sky.stebnicki@aereus.com>
 *  @copyright 2014 Aereus
 */

// Setup autoloader
include(__DIR__ . "/init_autoloader.php");

// Initialize Netric Application and Account
// ------------------------------------------------
//$config = new Netric\Config\Config();
$configLoader = new Netric\Config\ConfigLoader();
$applicationEnvironment = (getenv('APPLICATION_ENV')) ? getenv('APPLICATION_ENV') : "production";

// Setup the new config
$config = $configLoader->fromFolder(__DIR__ . "/config", $applicationEnvironment);

// Initialize application
$application = new Netric\Application\Application($config);

// Initialize account
//$account = $application->getAccount();

// Initialize the current user (if set)
// if ($_SESSION['user'])
//      $user = new Netric\User($account);
