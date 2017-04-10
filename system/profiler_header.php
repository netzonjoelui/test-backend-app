<?php
/**
 * If the xhprof extension is installed then this can be included in the .htaccess
 */

/*
if (extension_loaded('xhprof')) 
{
	$base_path = ini_get("xhprof.output_dir");
	include_once $base_path.'/xhprof_lib/utils/xhprof_lib.php';
	include_once $base_path.'/xhprof_lib/utils/xhprof_runs.php';
	xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
}
*/

$sysDir = dirname(__FILE__);
include_once($sysDir . "/../lib/AntProfiler.php");
AntProfiler::startProfile();
