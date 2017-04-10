<?php
/*======================================================================================
	
	Module:		Worker	

	Purpose:	Class used for handling distributed worker processes. Each node/machine
				should be it's own worker. Workers may handle multiple jobs.

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2011 Aereus Corporation. All rights reserved.
	
	Usage:		

======================================================================================*/
require_once("lib/Ant.php");
require_once("lib/AntProfiler.php");
require_once("workers/inc_workerfunctions.php");
require_once("lib/WorkerJob.php");

define("WORKER_SUCCESS", 1);
define("WORKER_ERROR", 2);
define("WORKER_DEFERRED", 3);

class Worker
{
	var $dbh = null;			// Handle to ant application database
	var $sysDbh = null;			// Handle to ant system database
	var $ant = null;			// Handle to instance of ant account
	var $returnCode = WORKER_SUCCESS;
	var $result_oid = null;		// The database oid of the file imported
	var $result_name = null;	// (optional) name for the result
	var $result_ctype = null;	// (optional) content-type of stored result
	var $functions = array();	// Array of functions (fname=>cb)
	var $debug = false;
	var $testMode = false; 		// Used for unit testing mostly
	var $limitAntVersion;		// Only process jobs for accounts of a specific version

	/**
	 * We only need to set the output escape type once so use a flag to indicate if that has been done
	 *
	 * @var bool
	 */
	private $fByteaOuputSet = false;

	/**
	 * Enable profiler
	 *
	 * @var bool
	 */
	public $enableProfile = false;

	/**
	 * Pass counter - number of queries run
	 *
	 * @var int
	 */
	public $numPasses = 0;

	/**
	 * Id of last job run
	 *
	 * @var int
	 */
	public $lastJobId = false;

	function __construct($dbh=null)
	{
		global $g_workerFunctions, $settings_version;

		$this->dbh = $dbh;
		$this->sysDbh = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

		// Only run workers for current version
		if (AntConfig::getInstance()->version)
			$this->setAntVersion(AntConfig::getInstance()->version);

		// Bootstrap functions
		if (is_array($g_workerFunctions) && count($g_workerFunctions))
		{
			foreach ($g_workerFunctions as $fname=>$callback)
			{
				$this->addFunction($fname, $callback);
			}
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

	function addFunction($fname, $callback)
	{
		$this->functions[$fname] = $callback;
	}
	
	/**************************************************************************
	* Function: 	functionExists
	*
	* Purpose:		Find out if the function name is in the list of availble
	*				functions for this worker.
	**************************************************************************/
	function functionExists($fname)
	{
		return isset($this->functions[$fname]);
	}

    /**
     * Execute or start a job
     * 
     * @param string The name of the function to execture
     * @param mixed $data Workload to send to the job
     * @param int $jobid Unique id of this job instance
     * @return boolean
     */
	public function run($fname=null, $data=null, $jobid=null)
	{
		if (!$fname)
			return false;

		$ret = false;

		$funct = $this->functions[$fname];

		if ($funct && function_exists($funct))
		{
			if ($this->enableProfile)
				$this->startProfile();

			if ($this->debug) echo "Running $funct";

			$job = new WorkerJob($this->dbh, $jobid);
			$job->workload = $data;
			$job->ant = $this->ant;
			$ret = call_user_func($funct, $job);
			$job->storeRetval($ret);	// If this is a background job, then store the return value
            if ($job->getDefer()>0)
            {
                $this->deferJob($job, $job->getDefer());
            }
            else
            {
                $job->sendComplete(); 		// Mark the job as finished
                
                // Clear from system job queue
                if ($jobid)
                    $this->sysDbh->Query("DELETE FROM worker_job_queue WHERE id='" . $jobid . "'");
            }
			
			if ($this->debug) echo "\t[done]\n";

			if ($this->enableProfile)
				$this->endProfile("/workers/" . $funct);
		}

		return $ret;
	}

	/**
	 * Poll worker pool server for queued background jobs
	 *
	 * @param int $jobid An optional param passed to limit what is pulled from the queue.
	 * 					 Used mostly for tests.
	 *
	 * @return bool return true on success
	 */
	public function work($jobid=null)
	{
		$sysDbh = $this->sysDbh;
		$this->returnCode = WORKER_SUCCESS;
		$this->numPasses++;

		// Set output encoding/escape type
		if (!$this->fByteaOuputSet)
		{
			$sysDbh->Query("SET bytea_output = 'escape';");
			$this->fByteaOuputSet = true;
		}

		$query = "SELECT worker_job_queue.id, worker_job_queue.function_name, worker_job_queue.workload, 
					worker_job_queue.account_id, accounts.version
					FROM worker_job_queue, accounts WHERE accounts.id=worker_job_queue.account_id 
					AND (ts_run IS NULL or ts_run<=now()) ";
		$query .= ($jobid) ? "AND worker_job_queue.id='$jobid' " : 'AND f_running is not true ';
		$query .= ($this->limitAntVersion) ? "AND version='".$this->limitAntVersion."' " : 'AND version is null ';
		$query .= "LIMIT 1";
		//echo "Running query " . $this->numPasses . "\n";
		$result = $sysDbh->Query($query);
		if ($sysDbh->GetNumberRows($result))
		{
			$row = $sysDbh->GetRow($result, 0);
			if ($this->functionExists($row['function_name']))
			{
				// Update status so no other workers try to handle this job
				$sysDbh->Query("UPDATE worker_job_queue SET f_running='t' WHERE id='".$row['id']."'");

				// Set current job vars
				$antAcct = new Ant($row['account_id']);
				$this->dbh = $antAcct->dbh;
				$this->ant = $antAcct;

				// insert into job log for account database
                $this->dbh->Query("DELETE FROM worker_jobs WHERE job_id='" . $row['id'] . "'");
				$ret = $this->dbh->Query("INSERT INTO worker_jobs(job_id, function_name, ts_started, status_numerator)
								   			VALUES('".$row['id']."', '".$row['function_name']."', 'now', '-1')");
				if ($ret !== false)
				{
					// Run process
					// With SET bytea_output above, data will automatically be unescaped
					//$ret = $this->run($row['function_name'], $sysDbh->UnEscapeBytea($row['workload']), $row['id']);
					$ret = $this->run($row['function_name'], $row['workload'], $row['id']);
				}
				else
				{
					// Failed to start job, reset f_running so another worker can start the job
					$sysDbh->Query("UPDATE worker_job_queue SET f_running='f' WHERE id='".$row['id']."'");
				}

				$this->lastJobId = $row['id'];
				
			}
			else if ($this->debug)
			{
				echo "ERROR: cannot find the function requested - " . $row['function_name'] . "\n";
				$this->lastJobId = false;
			}

		}
		else if ($jobid) // passed job not found
		{
			$this->returnCode = WORKER_ERROR;
			$this->lastJobId = false;
		}
		else
		{
			$this->lastJobId = false;
		}

		// php will eat up your cpu if you don't have this
		//if (!$jobid)
			//sleep(1);

		return true;
	}

	/**************************************************************************
	* Function: 	returnCode
	*
	* Purpose:		Get the return code for this worker
	**************************************************************************/
	function returnCode()
	{
		return $this->returnCode;
	}

	/**************************************************************************
	* Function: 	error	
	*
	* Purpose:		Get the last error
	**************************************************************************/
	function error()
	{
		return "";
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

	/**************************************************************************
	* Function: 	setAntVersion
	*
	* Purpose:		Limit certain background processes to a specific ANT version
	**************************************************************************/
	function setAntVersion($version)
	{
		$this->limitAntVersion = $version;
	}
    
    /**
     * Defer a job for x number of seconds
     * 
     * @param WorkerJob $job
     * @param int $seconds
     */
    public function deferJob($job, $seconds)
    {
        if (!$job->id || !is_numeric($seconds))
            return false;
        
        $sql = "UPDATE worker_job_queue SET f_running='f', "
                . "ts_run='" . date("Y-m-d h:i:s A", strtotime("+ $seconds seconds")) . "' "
                . "WHERE id='" . $job->id . "'";
        $this->sysDbh->Query($sql);
        
        return true;
    }

	/**
	 * Begin profile run
	 */
	protected function startProfile()
	{
		AntProfiler::startProfile();
	}

	/**
	 * End profile
	 */
	protected function endProfile($worker)
	{
		AntProfiler::endProfile($worker);
	}
}
