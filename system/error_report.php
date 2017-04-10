<?php
	require_once("../lib/AntConfig.php");
	require_once("userfiles/file_functions.awp");
	require_once("users/user_functions.php");
	require_once("lib/AntLog.php");
    require_once('../lib/aereus.lib.php/AnalogClient.php');
    // ANT LIB
	require_once("../lib/CDatabase.awp");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	ini_set("display_errors", "On");	
	ini_set("memory_limit", "2G");	
	ini_set("max_execution_time", "28800");	
	ini_set('default_socket_timeout', "28800"); 

	if (!AntConfig::getInstance()->analog)
		die("Analog settings must first be set");

	$antlogId = AntConfig::getInstance()->analog['logid'];
	
	// Craete a copy of the log for processing
    $log_file = AntConfig::getInstance()->data_path."/".AntConfig::getInstance()->log;

	if (!file_exists($log_file))
	{
		echo "The log file does not exist\n";
		exit;
	}

    $new_log_file = AntConfig::getInstance()->data_path."/".AntConfig::getInstance()->log.".sending.log";
	copy($log_file, $new_log_file) 
		or die("Unable to copy $log_file to $new_log_file.");
 
    // Analog Client
	$analog = new AnalogClient(php_uname('n'), AntConfig::getInstance()->data_path, AnalogClient::SERVER_PROD);

	// Autenticate
	$analog->setAuth(AntConfig::getInstance()->analog['appid'], AntConfig::getInstance()->analog['key']);
    
	echo "Processing:" . $new_log_file . "\n";
	if (file_exists($new_log_file))
	{
		$handle = fopen($new_log_file, "r");

		if ($handle === FALSE) 
			die ("Could not open the log file!");

		while (($buf = fgetcsv($handle, 1024, ",")) !== FALSE) 
		{
			$data = array(
				'level' => $buf[LOGDEF_LEVEL],
				'time' => $buf[LOGDEF_TIME],
				'source' => $buf[LOGDEF_SOURCE],
				'details' => $buf[LOGDEF_DETAILS],
			);

			$analog->sendLog($antlogId, $data);
		}

		fclose($handle);

		unlink($log_file);
		unlink($new_log_file);
	}
?>
