<?php
	require_once("../lib/AntConfig.php");
	require_once("lib/Ant.php");
	require_once("lib/ant_error_handler.php");
	require_once("lib/CAntFs.awp");
	require_once("lib/AntUser.php");
	require_once("lib/CWorker.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("lib/CAntObject.php");
	require_once("lib/Email.php");
	require_once("customer/CCustomer.php");
	require_once("email/email_functions.awp");

	ini_set("max_execution_time", "0");	
	ini_set("max_input_time", "0");	
	ini_set('memory_limit', -1);

	$worker = new CWorker();
	if ($settings_version)
		$worker->setAntVersion($settings_version);
	while ($worker->work()) 
	{
		if (WORKER_SUCCESS != $worker->returnCode()) 
		{
			echo "Worker failed: " . $worker->error() . "\n";
		}
	}
?>
