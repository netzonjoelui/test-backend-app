<?php
/**
 * Cleanup email system
 *
 * @category	Ant
 * @package		Email
 * @subpackage	Queue_Process
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("../../lib/AntConfig.php");
require_once("lib/Ant.php");
require_once("lib/AntService.php");
require_once("services/ObjectDynIdx.php");

ini_set("memory_limit", "-1");	

$svc = new ObjectDynIdx();
$svc->run();
echo "Finished!\n";
