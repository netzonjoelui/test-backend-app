<?php
/**
 * Cleanup objects and performan periodic maintenance
 *
 * @category	Ant
 * @package		Email
 * @subpackage	Queue_Process
 * @copyright	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once(dirname(__FILE__)."/../../lib/AntConfig.php");
require_once("lib/Ant.php");
require_once("lib/AntService.php");
require_once("lib/AntRoutine.php");
require_once("services/SystemIncomingMail.php");

ini_set("memory_limit", "-1");	

$svc = new AntRoutine_SystemIncomingMail();
$svc->run();
