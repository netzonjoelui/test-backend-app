<?php
	require_once('lib/Worker.php'); 
	require_once('lib/pdf/class.ezpdf.php'); 

	if (is_array($g_workerFunctions))
	{
		$g_workerFunctions["email/inject"] = "email_inject";
	}

	function email_inject($job)
	{
		$data = $job->workload();

		return strrev($data);
	}
?>
