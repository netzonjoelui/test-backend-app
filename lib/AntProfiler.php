<?php
/**
 * This class is reasponsible for handling all profiling
 */
class AntProfiler
{
	/**
	 * Begin profiling function usually called statically
	 */
	static public function startProfile()
	{
		if (extension_loaded('xhprof')) 
		{
			$libDir = dirname(__FILE__);
			include_once($libDir.'/xhprof/utils/xhprof_lib.php');
			include_once($libDir.'/xhprof/utils/xhprof_runs.php');
			xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
		}
	}

	/**
	 * Begin profiling function usually called statically
	 */
	static public function endProfile($module, $debug=false)
	{
		if (extension_loaded('xhprof')) 
		{
			if (!class_exists("AntConfig"))
				include_once(dirname(__FILE__).'/AntConfig.php');
			
			// Check if we are skipping
			$saveFile = true; 

			// Check if profile is enabled
			if (!AntConfig::getInstance()->profile["enabled"])
				$saveFile = false;

			if ($saveFile)
			{
				$basePath = AntProfiler::getProfilesDir();
				$runPath = $basePath . "/runs";
				$queuePath = $basePath . "/queue";

				$profiler_namespace = 'ant';  // namespace for your application
				$xhprof_data = xhprof_disable();

				// Check if we pave passed the minimal wall time log - so we don't profile everything
				if (is_array($xhprof_data))
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
                    if ($debug)
                        echo "Profiler: saving to $runPath\n";
                    
					$xhprof_runs = new XHProfRuns_Default($runPath);
					$run_id = $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
				 
					$ent = array();
					$ent['page'] = $module;
					$ent['ts_run'] = time();
					$ent['run_file'] = $run_id.".".$profiler_namespace;
					file_put_contents($queuePath . "/$run_id", serialize($ent));
				}
			}
		}
	}

	/**
	 * Get root path for profiles and make sure it exists
	 */
	static public function getProfilesDir()
	{
		$basePath = AntConfig::getInstance()->data_path . "/profiles";
		if (AntConfig::getInstance()->version)
			$basePath .= "/" . AntConfig::getInstance()->version;

		// Now make sure the runs and the queue paths exist
		if (!file_exists($basePath . "/runs"))
			mkdir($basePath . "/runs", 777, true);

		// Now make sure the runs and the queue paths exist
		if (!file_exists($basePath . "/queue"))
			mkdir($basePath . "/queue", 777, true);


		return $basePath;
	}
}
