<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\WorkerMan\Queue;

use Netric\WorkerMan\WorkerInterface;
use Netric\WorkerMan\Job;

class InMemory implements QueueInterface
{
    /**
     * Queued jobs
     *
     * @var array
     */
    public $queuedJobs = array();

    /**
     * Workers that are listening for jobs by workerName
     *
     * @var WorkerInterface[]
     */
    private $listeners = array();

    /**
     * Add a job to the queue and run it
     *
     * @param string $workerName The name of the worker to run
     * @param array $jobData Data to be passed to the job
     * @return mixed Whatever the result of the worker is
     */
    public function doWork($workerName, array $jobData)
    {
        $this->queuedJobs[] = array($workerName, $jobData);
        return true;
    }

    /**
     * Add a job to the queue and run it
     *
     * @param string $workerName The name of the worker to run
     * @param array $jobData Data to be passed to the job
     * @return string A unique id/handle to the queued job
     */
    public function doWorkBackground($workerName, array $jobData)
    {
        $this->queuedJobs[] = array($workerName, $jobData);
        return (string) count($this->queuedJobs);
    }

    /**
     * Add an available worker to the queue
     *
     * @param string $workerName The name of the worker to run
     * @param WorkerInterface $worker Will call $worker::work and must match $workerName queue
     * @return bool true on success, false on failure
     */
    public function addWorker($workerName, WorkerInterface $worker)
    {
        $this->listeners[$workerName] = $worker;
    }

    /**
     * Get all workers that are listening for jobs
     *
     * @return WorkerInterface[]
     */
    public function getWorkers()
    {
        return $this->listeners;
    }

    /**
     * Loop through the work queue and dispatch each job to the appropriate worker (pop)
     *
     * @return bool true on success, false on failure
     */
    public function dispatchJobs()
    {
        while (true) {
            foreach ($this->queuedJobs as $aJob) {
                $workerName = $aJob[0];
                $jobData = $aJob[1];

                // Skip over jobs we are not listening to
                if (!isset($this->listeners[$workerName])) {
                    continue;
                }

                // Construct job wrapper
                $job = new Job();
                $job->setWorkload($jobData);

                // Send job to the worker
                $worker = $this->listeners[$workerName];
                $worker->work($job);
                return true;
            }
        }
    }

    /**
     * Remove all jobs in a a worker queue
     *
     * @param string $workerName The name of the queue to clear
     * @return int number of jobs cleared
     */
    public function clearWorkerQueue($workerName)
    {
        $toRemove = array();
        for ($i = 0; $i < count($this->queuedJobs); $i++)
        {
            if ($this->queuedJobs[$i][0] === $workerName) {
                $toRemove[] = $i;
            }
        }

        foreach ($toRemove as $idxToDelete) {
            array_splice($this->queuedJobs, $idxToDelete, 1);
        }

        return count($toRemove);
    }
}

