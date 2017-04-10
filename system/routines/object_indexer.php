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
require_once("services/ObjectIndexer.php");

ini_set("memory_limit", "-1");	
$ALIB_CACHE_DISABLE = true; // disable caching so we don't load seldom-used objects into cache

$svc = new ObjectIndexer();
$svc->run();
echo "Finished!\n";
