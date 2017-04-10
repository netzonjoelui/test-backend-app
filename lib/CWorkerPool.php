<?php
/*======================================================================================
	
	class:		CWorkerPool (DEPRICATED - USE WorkerMan)

	Purpose:	Manage ant tasks. This will eventually be ported to GEARMAN for
				better distributed operation. For that reason all the function calls
				should be as similar so when moving to GEARMAN this class will
				act as a simple abstraction.

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.

	Usage:		

	Depends:	settings.php - must be loaded in parent document

	Variables:	1.	$ALIB_USEGEARMAN (defaults to false)
				2. 	$ALIB_GEARMAN_SVR (defaults to localhost)

======================================================================================*/
require_once("lib/CDatabase.awp");
require_once("settings/settings_functions.php");		
require_once("lib/aereus.lib.php/CCache.php");
//require_once("lib/aereus.lib.php/CSessions.php");
require_once("lib/CWorker.php");

class CWorkerPool
{
	var $accountId;
	var $accountName;
	var $dbh;
	var $serverRoot;
	var $worker = null;

	function __construct($dbh=null)
	{
		$this->dbh = $dbh;
		$this->sysDbh = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);
		$this->serverRoot = AntConfig::getInstance()->application_path;
	}

	function run($function_name, $data)
	{
		$retval = false;

		if (null == $this->worker)
			$this->worker = new CWorker($this->dbh); 

		$retval = $this->worker->run($function_name, $data);

		/*
		// Return result
		$result = $dbh->Query("insert into workerpool(function_name, data, progress) 
								values('$function_name', '".$dbh->Escape(serialize($data))."', '-1');
							   select currval('workerpool_id_seq') as id;");
		if ($dbh->GetNumberRows($result))
		{
			$pid = $dbh->GetValue($result, 0, "id");
			chdir($this->serverRoot."/system");
			$command = "/usr/local/bin/php worker.php $pid";
			$retval = exec("$command");
		}
		*/

		return $retval;
	}

	function runBackground($function_name, $data)
	{
		// Return process id
		$dbh = $this->sysDbh;
		$retval = 0;

		// Return result
		$result = $dbh->Query("insert into workerpool(function_name, data, progress) 
									values('$function_name', '".$dbh->Escape(serialize($data))."', '-1');
							   select currval('workerpool_id_seq') as id;");
		if ($dbh->GetNumberRows($result))
		{
			$pid = $dbh->GetValue($result, 0, "id");
			$retval = $pid;
		}

		return $retval;
	}

	function getJobStatus($pid)
	{
		// Return [running, percent, retval]
		$dbh = $this->sysDbh;
		$retval = array();

		// Return result
		$result = $dbh->Query("select progress from workerpool where id='$pid'");
		if ($dbh->GetNumberRows($result))
		{
			$progress = $dbh->GetValue($result, 0, "progress");
			$retval = array($pid, $progress);
		}
		else
		{
			$retval = array(0, 0);
		}

		return $retval;
	}
}
?>
