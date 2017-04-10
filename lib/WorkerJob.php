<?php
/**
 * This class represents each worker job
 * 
 * It will eventually be an abstraction to GearmanJob class which will power the backend
 * so every effort should be made to make the function calls mirror the document found in
 * php.net/manual/en/class.gearmanjob.php
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright (c) 2011, Aereus All Rights Reserved
 */

class WorkerJob
{
	/**
	 * The unique id of this job
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * Handle to ant application database
	 *
	 * @var CDatabase
	 */
	public $dbh = null;

	/**
	 * The data being passed with this job
	 *
	 * @var array
	 */
	public $workload = null;

	/**
	 * The database oid of the file imported
	 *
	 * Used only if output is stored in a file
	 * 
	 * @var int
	 */
	public $result_oid = null;

	/**
	 * ANT user object
	 *
	 * @var AntUser
	 */
	public $user = null;

	/**
	 * ANT account
	 * 
	 * @var Ant
	 */
	public $ant = null;
    
    /**
     * Number of seconds to defer this job.
     * 
     * The worker will check this flag on return and decide whether to clear the job or re-insert
     * it for later processing again.
     * 
     * @var int (seconds)
     */
    public $defer = 0;

	/**
	 * Class constructor
	 *
	 * @var CDatabase $dbh Handle to account database
	 * @var int $jobid Job id to load
	 */
	public function __construct($dbh, $jobid=null)
	{
		$this->dbh = $dbh;
		$this->id = $jobid;
		$this->user = new AntUser($dbh, USER_SYSTEM);
	}

	/**
	 * Get the workload for this job
	 */
	public function workload()
	{
		return $this->workload;
	}

	/**
	 * Get the workload size in bytes for this job
	 *
	 * @return int Size of the workload in bytes
	 */
	public function workloadSize()
	{
		return sizeof($this->workload);
	}

	/**
	 * Update the status of a job
	 *
	 * @param int $numerator Current num done of denominator
	 * @param int $denominator Total num to be done
	 */
	public function sendStatus($numerator, $denominator)
	{
		if ($this->id)
		{
			$this->dbh->Query("UPDATE worker_jobs set status_numerator=".$dbh->EscapeNumber($numerator).", 
						 	   status_denominator=".$dbh->EscapeNumber($denominator)." where job_id='".$this->id."'");
		}
	}

	/**************************************************************************
	* Function: 	saveResultFile
	*
	* Purpose:		Store return value of a function if this is a background job
	*
	* Params:		(mixed) $rval = return value
	**************************************************************************/
	function createResultFile($rval)
	{
		
	}

	/**************************************************************************
	* Function: 	storeRetval
	*
	* Purpose:		Store return value of a function if this is a background job
	*
	* Params:		(mixed) $rval = return value
	**************************************************************************/
	function storeRetval($rval)
	{
		$dbh = $this->dbh;

		if ($this->id && $rval)
		{
			$ret = $dbh->Query("UPDATE worker_jobs set retval='".$dbh->EscapeBytea($rval)."' where job_id='".$this->id."'");
		}
	}

	/**************************************************************************
	* Function: 	sendComplete
	*
	* Purpose:		If this is a background job, then set it as completed
	**************************************************************************/
	function sendComplete()
	{
		$dbh = $this->dbh;

		if ($this->id)
		{
			$dbh->Query("UPDATE worker_jobs set status_numerator=status_denominator, ts_completed='now' where job_id='".$this->id."'");
		}
	}
    
    /**
     * Defer this job for x number of seconds
     * 
     * It will allow the worker to return and continue working on other jobs
     * until x seconds have passed and a worker should pick back up where this one
     * left off.
     * 
     * @param int $seconds The number of seconds to pause before job is run again
     */
    public function defer($seconds)
    {
        $this->defer = $seconds;
    }
    
    /**
     * Get defer number of seconds
     * 
     * @return int
     */
    public function getDefer()
    {
        return $this->defer;
    }
}