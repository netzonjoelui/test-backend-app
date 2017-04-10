<?php
/**
 * Routine/cron version of workers because continual service appears to be having trouble
 *
 * @category	Ant
 * @package		Service
 * @subpackage	Workers
 * @copyright	Copyright (c) 2003-2014 Aereus Corporation (http://www.aereus.com)
 */
require_once(dirname(__FILE__)."/../../lib/AntConfig.php");
require_once("lib/Ant.php");
require_once("lib/WorkerMan.php");

ini_set("max_execution_time", "0");
ini_set("max_input_time", "0");
ini_set('memory_limit','4G');

// Get process id lock to assure we only run one instance at a time
$pidFp = fopen(AntConfig::getInstance()->data_path."/tmp/svc_workers_" . AntConfig::getInstance()->version, 'w+');
if(!flock($pidFp, LOCK_EX | LOCK_NB)) 
{
	echo 'Unable to obtain lock - process already running';
	return false;
}

$worker = new Worker();
$worker->debug = true;
$worker->enableProfile = true;

// Keep processing jobs until finished
while ($worker->work())
{
	if ($worker->returnCode() != WORKER_SUCCESS)
		AntLog::getInstance()->error($worker->returnCode());

	// Check if we did not find any jobs
	if (!$worker->lastJobId)
		break;
}

// Remove lock
fclose($pidFp);
