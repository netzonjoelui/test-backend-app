<?php
/**
 * Process delayed or intervals workflow actions
 *
 * Most workflow actions are fired immediately when their originating event is triggered.
 * However, workflows can be delayed/scheduled for a later time or run at a specific interval
 * such as daily. This service is responsible for processing these actions.
 *
 * @category	Ant
 * @package		WorkFlow
 * @subpackage	Action_Processor
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once(dirname(__FILE__)."/../../lib/AntConfig.php");
require_once("lib/Ant.php");
require_once("lib/AntService.php");
require_once("services/WorkFlowActionProcessor.php");

ini_set("memory_limit", "-1");	

$svc = new WorkFlowActionProcessor();
while($svc->run())
{
	sleep(120); // Run every 2 minutes
}

?>
