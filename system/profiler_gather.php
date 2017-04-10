<?php
/**
 * Process for gathering up profile dumps and uploading to analog server for analysis
 */
require_once("../lib/AntConfig.php");
require_once("../lib/AntProfiler.php");
require_once("../settings/settings_functions.php");		
require_once("../userfiles/file_functions.awp");
require_once("../users/user_functions.php");
require_once("../lib/CDatabase.awp");
require_once("../lib/aereus.lib.php/CAdcClient.php");
require_once('../lib/aereus.lib.php/AnalogClient.php');
error_reporting(E_ERROR | E_WARNING | E_PARSE);

ini_set("max_execution_time", "28800");	
ini_set('default_socket_timeout', "28800"); 

// Open and lock process file to keep more than one copy of this from running
$pidFp = fopen(AntConfig::getInstance()->data_path."/tmp/pgather.txt", 'w+');
if(!flock($pidFp, LOCK_EX | LOCK_NB)) 
{
	echo 'Unable to obtain lock';
	exit(-1);
}

// Analog Client
$analog = new AnalogClient(php_uname('n'), AntConfig::getInstance()->data_path, AnalogClient::SERVER_PROD);

// Autenticate
$analog->setAuth(AntConfig::getInstance()->analog['appid'], AntConfig::getInstance()->analog['key']);

// Cleanup all older syncstates
$base_path = AntProfiler::getProfilesDir(); //ini_get("xhprof.output_dir");;
$queue_path = "$base_path/queue";
$dir = opendir($queue_path);
if ($dir)
{
	while($entry = readdir($dir)) 
	{
		if ($entry != '.' && $entry != '..')
		{
			$ent = array();
			$ent = unserialize(file_get_contents("$queue_path/$entry"));
			$filePath = $base_path."/runs/".$ent['run_file'];
			$xhprof_data = unserialize(file_get_contents($filePath));

			echo $ent['run_file'] . " - " . $ent['page'] . "\t";

			if (is_array($xhprof_data))
			{
				$ct = 0;
				$wt = 0;
				$cpu = 0;
				$mu = 0;
				$pmu = 0;
				$i = 0;
				foreach ($xhprof_data as $funct=>$data)
				{
					$ct += $data['ct'];
					$wt += $data['wt'];
					$cpu += $data['cpu'];
					$mu += $data['mu'];
					$pmu += $data['pmu'];

					$i++;
					if ($i == count($xhprof_data))
					{
						$ct = $data['ct'];
						$wt = $data['wt'];
						$cpu = $data['cpu'];
						$mu = $data['mu'];
						$pmu = $data['pmu'];
					}
				}	
				
				$profile = array(
					'page' => $ent['page'],
					'incl_wall' => $wt,
					'incl_cpu' => $cpu,
					'incl_mem' => $mu,
					'incl_pmem' => $pmu,
				);

				// SEND PROFILE TO ANALOG
				if (AntConfig::getInstance()->profile["min_wall"] == 0 || $wt >= AntConfig::getInstance()->profile["min_wall"])
				{
					$ret = $analog->sendProfile(AntConfig::getInstance()->analog['profileid'], $profile, $filePath);
					echo "[sent - $ret]\n";
				}
				else
				{
					echo "[skipped - wall below minimum]\n";
				}
			}
			else
			{
				echo "[skipped - no data]\n";
			}

			// Delete entry file
			@unlink("$queue_path/$entry");
			// Delete data
			@unlink("$filePath");
		}
	}
}

// clear the lock
fclose($pidFp);
