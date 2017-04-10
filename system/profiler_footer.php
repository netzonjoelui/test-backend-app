<?php
/*
if (extension_loaded('xhprof')) 
{
	$saveFile = true; // Check if we are skipping

	// Check if profile is enabled
	if (class_exists("AntConfig"))
	{
		if (!AntConfig::getInstance()->profile["enabled"])
			$saveFile = false;
	}

	if ($saveFile)
	{
		$profiler_namespace = 'ant';  // namespace for your application
		$xhprof_data = xhprof_disable();

		// Check if we pave passed the minimal wall time log - so we don't profile everything
		if (class_exists("AntConfig") && is_array($xhprof_data))
		{
			// Get the wall time of the last entry which is all inclusive
			$last = end($xhprof_data);
			$wt = $last['wt'];

			if (AntConfig::getInstance()->profile["min_wall"] != 0 && $wt <= AntConfig::getInstance()->profile["min_wall"])
				$xhprof_data = null; // don't save profile
		}

		// If data is set then save it
		if ($xhprof_data)
		{
			$xhprof_runs = new XHProfRuns_Default();
			$run_id = $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
		 
			$pageParts = $parts = explode("?",$_SERVER['REQUEST_URI']); // get path without query params
			$ent = array();
			$ent['page'] = $pageParts[0];
			$ent['ts_run'] = time();
			$ent['run_file'] = $run_id.".".$profiler_namespace;
			$base_path = ini_get("xhprof.output_dir");
			if (!file_exists("$base_path/queue"))
				mkdir("$base_path/queue");

			if (file_exists("$base_path/queue"))
				file_put_contents("$base_path/queue/$run_id", serialize($ent));
		}
	}
}
 */
$pageParts = explode("?",$_SERVER['REQUEST_URI']); // get path without query params
AntProfiler::endProfile($pageParts[0]);
