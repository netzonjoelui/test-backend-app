<?php
/**
 * Client code to send worker jobs to listening workers
 * 
 * This will eventually be ported to GEARMAN for better distributed operation. 
 * For that reason all the function calls should be as similar so when moving to 
 * GEARMAN this class will act as a simple abstraction.
 * 
 * @link http://gearman.org/ The eventual backend that will power this
 * @author joe, sky.stebnicki@aereus.com
 * @copyright (c) 2010, Aereus Corporation. All Rights Reserved
 */
require_once("lib/CDatabase.awp");
require_once("settings/settings_functions.php");		
require_once("lib/aereus.lib.php/CCache.php");
//require_once("lib/aereus.lib.php/CSessions.php");
require_once("lib/Worker.php");

class WorkerMan
{
	var $accountId;
	var $accountName;
	var $dbh = null;
	var $sysDbh = null;
	var $serverRoot = "";
	var $worker = null;
	var $testMode = false; 		// Used for unit testing mostly

	/**
	 * Disable background processing
	 *
	 * @var bool
	 */
	public $noBackground = false;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to active ANT account database
	 */
	public function __construct($dbh)
	{
		$this->dbh = $dbh;
		$this->sysDbh = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);
		$this->serverRoot = AntConfig::getInstance()->application_path;

		if ($dbh->accountId)
		{
			$this->accountId = $dbh->accountId;
		}
		else
		{
			throw new Exception("The database passed is not associated with an account!");
		}

		// Make sure background processes are enabled, otherwise run everything immediately
		if (!AntConfig::getInstance()->workers['background_enabled'])
		{
			$this->noBackground = true;
		}

		/** 
		 * For some reason autoloader is not referencing the constants
		 * - joe
		 */
		if (!defined("WORKER_SUCCESS"))
			define("WORKER_SUCCESS", 1);
		if (!defined("WORKER_ERROR"))
			define("WORKER_ERROR", 2);
		if (!defined("WORKER_DEFERRED"))
			define("WORKER_DEFERRED", 3);
	}

	/**
	 * Run a job immediately and get the return value
	 *
	 * @param string $function_name Uniue name of the function to run
	 * @param array $data Associateive array of data to use as a workload
	 * @return mixed Returns the return value of the function result
	 */
	public function run($function_name, $data)
	{
		$retval = false;

		if (null == $this->worker)
			$this->worker = new Worker($this->dbh); 

		$retval = $this->worker->run($function_name, $data);

		return $retval;
	}

	/**
	 * Queue a job to run in the background
	 *
	 * @param string $function_name Uniue name of the function to run
	 * @param array $data Associateive array of data to use as a workload
	 * @return int The unique id of this job for later reference
	 */
	public function runBackground($function_name, $data)
	{
		if (!$this->accountId)
			return false;

		// Return process id
		$dbhSys = $this->sysDbh;
		$retval = 0;

		$running = "f"; // Defaults to false which makes it open for workers
		if ($this->testMode || $this->noBackground)
			$running = 't'; // Limit this job so other workers will not grab it

		// Insert job into queue
		// Use $dbh->EscapeBytea and $dbh->UnEscapeBytea to encode/decode data
		//$result = $dbhSys->Query("SET bytea_output = 'escape';");
		$result = $dbhSys->Query("insert into worker_job_queue(function_name, workload, ts_entered, f_running, account_id) 
									values('$function_name', '".$dbhSys->EscapeBytea($data)."', 'now', '$running', '".$this->accountId."');
							   	  select currval('worker_job_queue_id_seq') as id;");
		if ($dbhSys->GetNumberRows($result))
		{
			$pid = $dbhSys->GetValue($result, 0, "id");
			$retval = $pid;
		}

		// Check if background processes have been disabled
		if ($this->noBackground && $pid)
		{
			$worker = new Worker($this->dbh); 
			$worker->work($pid);
		}

		return $retval;
	}

	/**
	 * Queue a job to be run in the background at a later time
	 *
	 * @param string $function_name Uniue name of the function to run
	 * @param array $data Associateive array of data to use as a workload
	 * @param string $tsStart Date and time when to start - may be any valid date format supported by php date
	 * @return int THe unique id of this job for later reference
	 */
	public function scheduleBackground($function_name, $data, $tsStart)
	{
		if (!$this->accountId)
			return false;

		if (false == @strtotime($tsStart))
			return false;

		// Return process id
		$dbhSys = $this->sysDbh;
		$retval = 0;

		// Insert job into queue
		$result = $dbhSys->Query("insert into worker_job_queue(function_name, workload, ts_entered, ts_run, f_running, account_id) 
									values('$function_name', '".$dbhSys->EscapeBytea($data)."', 'now', '$tsStart', 'f', '".$this->accountId."');
							   	  select currval('worker_job_queue_id_seq') as id;");
		if ($dbhSys->GetNumberRows($result))
		{
			$pid = $dbhSys->GetValue($result, 0, "id");
			$retval = $pid;
		}

		return $retval;
	}

	/**
	 * Get job by id
	 *
	 * @param $int $jobId The unique id of a queued job
	 */
	public function getJob($jobId)
	{
		$dbhSys = $this->sysDbh;
		$job = false;

		$dbhSys->Query("SET bytea_output = 'escape';");
		$sql = "SELECT id, function_name, workload, ts_run
				account_id FROM worker_job_queue WHERE id='$jobId'";
		$result = $dbhSys->Query($sql);
		if ($dbhSys->GetNumberRows($result))
		{
			$row = $dbhSys->GetRow($result, 0);
			
			$job = new WorkerJob($this->dbh, $jobId);
			$job->workload = $row['workload'];
			$job->tsRun = isset($row['ts_run']) ? $row['ts_run'] : null;
		}

		return $job;
	}

	/**************************************************************************
	* Function: 	getJobStatus
	*
	* Purpose:		Get the status of a job by id
	*
	* Params:		(string) $jobid = the unique id of this job
	*
	* Return:		Returns an associative array with the following values:
	*				'running '=> true if the process is still running
	*				'numerator' => the number finished of denominator
	*				'denominator' => the total number or size of workload
	*				'percent' => The calculated percentage complete
	**************************************************************************/
	function getJobStatus($jobid)
	{
		// Return [running, percent, retval]
		$dbh = $this->dbh;
		$retval = array();

		// Return result
		$result = $dbh->Query("select status_numerator, status_denominator from worker_jobs where job_id='$jobid'");
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetValue($result, 0);
			$running = ($row['status_numerator'] < $row['status_denominator']) ? true : false;
			$per = round((($row['status_numerator'] / $row['status_denominator']) * 100), 0);

			$retval = array("running"=>$running, "numerator"=>$row['status_numerator'], "denominator"=>$row['status_denominator'], "percent"=>$per);
		}
		else
		{
			$retval = array("running"=>false, "numerator"=>100, "denominator"=>100, "percent"=>100);
		}

		return $retval;
	}

	/**************************************************************************
	* Function: 	getRetval
	*
	* Purpose:		Get the return value of a job by id
	*
	* Params:		(string) $jobid = the unique id of this job
	*
	* Return:		Returns the return value of the job
	**************************************************************************/
	function getRetval($jobid)
	{
		$dbh = $this->dbh;

		// Return result
		$result = $dbh->Query("SET bytea_output = 'escape';select retval from worker_jobs where job_id='$jobid'");
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetRow($result, 0);

			// No longer need to escape with the "SET bytea_output='escape';" above
			//$retval = $dbh->UnEscapeBytea($row['retval']);
			$retval = $row['retval'];
		}
		else
		{
			$retval = false;
		}

		return $retval;
	}

	/**
	 * Cancel a queued or running job
	 *
	 * @param int $jobid The unique job id to cancel
	 */
	public function cancelJob($jobid)
	{
        if (!$jobid)
            return false;

		// Return result
		$result = $this->sysDbh->Query("DELETE FROM worker_job_queue WHERE id='$jobid'");

		return true;
	}

	/**************************************************************************
	* Function: 	clearJob
	*
	* Purpose:		Remove job from jobs log
	*
	* Params:		(string) $jobid = the unique id of this job
	**************************************************************************/
	public function clearJob($jobid)
	{
		// Return [running, percent, retval]
		$dbh = $this->dbh;

		// Return result
		$result = $dbh->Query("DELETE FROM worker_jobs WHERE job_id='$jobid'");

		return true;
	}

	/**************************************************************************
	* Function: 	setTestMode
	*
	* Purpose:		Place it test mode which will act like background but 
	*				it's actually just like run with db interface
	**************************************************************************/
	function setTestMode($istest)
	{
		$this->testMode = $istest;
	}
}
