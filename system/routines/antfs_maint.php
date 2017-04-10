<?php
/**
 * Cleanup AntFs with background processes
 *
 * @category	Ant
 * @package		AntFs
 * @subpackage	Maint
 * @copyright	Copyright (c) 2003-2014 Aereus Corporation (http://www.aereus.com)
 */
require_once(dirname(__FILE__)."/../../lib/AntConfig.php");
require_once("lib/Ant.php");
require_once("lib/AntService.php");
require_once("lib/AntRoutine.php");
require_once("services/AntFsMaint.php");

ini_set("memory_limit", "-1");	

$svc = new AntFsMaint();
$svc->run();
echo "Finished!\n";
