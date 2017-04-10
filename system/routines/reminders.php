<?php
/**
 * Cleanup email system
 *
 * @category	Ant
 * @package		Email
 * @subpackage	Queue_Process
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once(dirname(__FILE__)."/../../lib/AntConfig.php");
require_once("lib/Ant.php");
require_once("services/Reminders.php");

ini_set("memory_limit", "-1");	

$svc = new AntService_Reminders();
$svc->run();
