<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\WorkerMan\Queue;

use Netric\WorkerMan\WorkerInterface;
use Netric\WorkerMan\Job;

class Gearman implements QueueInterface
{
    /**
     * Gearman client
     *
     * var \GearmanClient
     */
    private $gmClient = null;

    /**
     * Gearman worker
     *
     * @var \GearmanWorker
     */
    private $gmWorker = null;

    /**
     * Array of listeners
     *
     * @var WorkerInterface[]
     */
    private $listeners = array();

    /**
     * The gearman server
     *
     * @var string
     */
    private $server = "localhost";

    /**
     * Initialize a Gearman job queue
     *
     * @param $server
     */
    public function __construct($server)
    {
        $this->server = $server;
    }

    /**
     * Add a job to the queue and wait for it to return (RPC)
     *
     * @param string $workerName The name of the worker to run
     * @param array $jobData Data to be passed to the job
     * @return mixed Whatever the result of the worker is
     */
    public function doWork($workerName, array $jobData)
    {
        return $this->getGmClient()->doNormal($workerName, json_encode($jobData));
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
        $job = $this->getGmClient()->doBackground($workerName, json_encode($jobData));

        if ($this->getGmClient()->returnCode() != GEARMAN_SUCCESS) {
            throw new \RuntimeException("Cannot run background job: " . $this->getGmClient()->error());
        }

        $this->lastJobId = $job;
        return $job;
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
        $gmWorker = $this->getGmWorker();
        // Unregister previous worker if set
        @$gmWorker->unregister($workerName);
        $this->listeners[$workerName] = $worker;
        $ret = $gmWorker->addFunction($workerName, array($this, "sendJobToWorker"));
        return $ret;
    }

    /**
     * Array of listeners with the key bing the WorkerName
     *
     * @return \Netric\WorkerMan\WorkerInterface[]
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
        if (!count($this->getWorkers())) {
            return false;
        }

        $gmWorker = $this->getGmWorker();

        if ($gmWorker->work()) {
            return true;
        } else {
            // If we need to wait, then pause for a second then try again
            if ($gmWorker->returnCode() == GEARMAN_IO_WAIT) {
                sleep(1);
                return $this->dispatchJobs();
            }
            
            $error = $gmWorker->error();
            if ($error) {
                throw new \RuntimeException("Job failed: " . $error);
            } else {
                // No jobs
                $returnCode = $gmWorker->returnCode();
                return false;
            }
        }
    }

    /**
     * Local listener called when gearman submits a job
     *
     * @param \GearmanJob $gmJob
     * @return mixed Results of job
     */
    public function sendJobToWorker(\GearmanJob $gmJob)
    {
        if (!isset($this->listeners[$gmJob->functionName()])) {
            throw new \RuntimeException("No listeners for job: " . $gmJob->functionName());
        }

        // Construct job wrapper
        $job = new Job();
        $job->setWorkload(json_decode($gmJob->workload(), true));
        // TODO: populate the job with whatever is needed here

        // Send job to the worker
        $worker = $this->listeners[$gmJob->functionName()];
        return $worker->work($job);
    }

    /**
     * Remove all jobs in a a worker queue
     *
     * @param string $workerName The name of the queue to clear
     * @return int number of jobs cleared
     */
    public function clearWorkerQueue($workerName)
    {
        $purged = 0;
        $gmWorker = $this->getGmWorker();

        // First unregister all listeners with gearman client
        @$gmWorker->unregisterAll();

        // Register a no-op function to run through the queue (our /dev/null)
        $gmWorker->addFunction($workerName, function($job) { $job->sendComplete("Done"); return true; });

        // Remove non blocking because it is causing problems with clearing
        //$gmWorker->removeOptions(GEARMAN_WORKER_NON_BLOCKING);

        // If there are no jobs work will return GEARMAN_NO_JOBS
        while($gmWorker->work() || $gmWorker->returnCode() == GEARMAN_IO_WAIT) {
            $purged++;
        }
        $returnCode = $this->getGmWorker()->returnCode();
        $gmWorker->unregister($workerName);

        // Put non blocking options back
        //$gmWorker->setOptions(GEARMAN_WORKER_NON_BLOCKING);

        // Re-register original listeners
        foreach ($this->listeners as $listenerName=>$worker) {
            $gmWorker->addFunction($workerName, array($this, "sendJobToWorker"));
        }

        return $purged;
    }

    /**
     * Get instance of a gearman client
     *
     * @return \GearmanClient|null
     */
    private function getGmClient()
    {
        // Create a client instance and add the server
        if (!$this->gmClient) {
            $this->gmClient = new \GearmanClient();
            $this->gmClient->addServer($this->server, 4730);
        }

        return $this->gmClient;
    }

    /**
     * Get instance of the gearman worker
     *
     * @return \GearmanWorker
     */
    private function getGmWorker()
    {
        if (!$this->gmWorker) {
            $this->gmWorker = new \GearmanWorker();
            $this->gmWorker->addServer($this->server, 4730);

            // Turn off blocking so that $this->gmWorker->work will return right away if no jobs
            $this->gmWorker->setOptions(GEARMAN_WORKER_NON_BLOCKING);
        }

        return $this->gmWorker;
    }
}
